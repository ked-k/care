<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\Document;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\Reconciliation;
use App\Models\Accounting\StatementLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Batch 44a: bank reconciliation.
 *
 * A reconciliation agrees the books to one bank statement: you give the
 * statement's date and closing balance, then tick every ledger entry that
 * appears on it. When the ticked entries (plus everything reconciled
 * before) add up to the statement balance, the difference is zero and it
 * can be completed — after which those entries are locked as reconciled.
 *
 * Importing the bank's CSV is optional: its lines are matched to ledger
 * entries automatically (same amount, date within a week), and anything
 * left over can be posted straight from the statement line (bank charges,
 * interest, a direct debit nobody entered).
 */
class Reconciler
{
    public static function start(Account $account, string $date, float $balance, ?int $userId): Reconciliation
    {
        if (! $account->isMoney()) {
            throw new LedgerException(__('Only bank, cash and mobile-money accounts can be reconciled.'));
        }
        if (Reconciliation::where('account_id', $account->id)->where('status', 'in_progress')->exists()) {
            throw new LedgerException(__('There’s already a reconciliation in progress for this account — finish or delete it first.'));
        }
        $last = self::lastCompleted($account);
        if ($last && Carbon::parse($date)->lte($last->statement_date)) {
            throw new LedgerException(__('This account is already reconciled to :d. Choose a later statement date.', ['d' => $last->statement_date->format('d M Y')]));
        }

        return Reconciliation::create([
            'agency_id' => $account->agency_id, 'account_id' => $account->id, 'statement_date' => $date,
            'statement_balance' => round($balance, 2), 'status' => 'in_progress', 'created_by' => $userId,
        ]);
    }

    public static function lastCompleted(Account $account): ?Reconciliation
    {
        return Reconciliation::where('account_id', $account->id)->where('status', 'completed')->orderByDesc('statement_date')->first();
    }

    /** Ledger entries on the account that can still be ticked for this reconciliation. */
    public static function candidates(Reconciliation $rec, bool $includeLater = false)
    {
        return JournalLine::where('account_id', $rec->account_id)
            ->where(fn ($q) => $q->whereNull('reconciliation_id')->orWhere('reconciliation_id', $rec->id))
            ->when(! $includeLater, fn ($q) => $q->where('date', '<=', $rec->statement_date->toDateString()))
            ->with('journal:id,number,type,reference,memo,source_type,source_id', 'contact:id,name')
            ->orderBy('date')->orderBy('created_at');
    }

    /** Balance of everything reconciled before plus what's ticked in this one. */
    public static function clearedBalance(Reconciliation $rec): float
    {
        $done = Reconciliation::where('account_id', $rec->account_id)->where('status', 'completed')->pluck('id')->push($rec->id);

        return round((float) JournalLine::where('account_id', $rec->account_id)->whereIn('reconciliation_id', $done)
            ->sum(DB::raw('debit - credit')), 2);
    }

    public static function difference(Reconciliation $rec): float
    {
        return round((float) $rec->statement_balance - self::clearedBalance($rec), 2);
    }

    public static function tick(Reconciliation $rec, string $lineId, bool $on): void
    {
        self::assertOpen($rec);
        $line = JournalLine::where('account_id', $rec->account_id)->findOrFail($lineId);
        if ($line->reconciliation_id && $line->reconciliation_id !== $rec->id) {
            throw new LedgerException(__('That entry was reconciled on an earlier statement.'));
        }
        $line->update(['reconciliation_id' => $on ? $rec->id : null]);
        if (! $on) {
            StatementLine::where('reconciliation_id', $rec->id)->where('journal_line_id', $line->id)
                ->update(['journal_line_id' => null, 'status' => 'unmatched']);
        }
    }

    // -- Statement import ----------------------------------------------------

    /**
     * Read a bank CSV. Understands the usual layouts: Date / Description /
     * Amount, or separate Paid in / Paid out (Money in / Money out, Credit /
     * Debit) columns. UK dates (31/01/2026) and ISO dates both work.
     *
     * @return array<int, array{date: string, description: string, reference: ?string, amount: float}>
     */
    public static function parseCsv(string $path): array
    {
        $fh = fopen($path, 'r');
        if (! $fh) {
            throw new LedgerException(__('Couldn’t read that file.'));
        }
        $rows = [];
        while (($r = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            if (count(array_filter($r, fn ($v) => trim((string) $v) !== '')) > 0) {
                $rows[] = array_map(fn ($v) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $v)), $r);
            }
        }
        fclose($fh);

        // The header is the first row that names a date column.
        $headerAt = null;
        foreach (array_slice($rows, 0, 15, true) as $i => $r) {
            if (collect($r)->contains(fn ($h) => preg_match('/\bdate\b/i', $h))) {
                $headerAt = $i;
                break;
            }
        }
        if ($headerAt === null) {
            throw new LedgerException(__('No “Date” column found. The file needs a header row with Date, Description and Amount (or Paid in / Paid out).'));
        }
        $head = array_map('strtolower', $rows[$headerAt]);
        $find = function (array $patterns) use ($head) {
            foreach ($patterns as $p) {
                foreach ($head as $i => $h) {
                    if (preg_match($p, $h)) {
                        return $i;
                    }
                }
            }

            return null;
        };
        $dateCol = $find(['/^(transaction |posting |value )?date$/', '/date/']);
        $descCol = $find(['/description|details|narrative|memo|payee|particulars|transaction/']);
        $refCol = $find(['/reference|ref\b|cheque/']);
        $inCol = $find(['/paid in|money in|credit|deposit|receipt/']);
        $outCol = $find(['/paid out|money out|debit|withdrawal|payment/']);
        $amtCol = $find(['/^amount|^value$|amount \(/']);
        if ($amtCol === null && ($inCol === null || $outCol === null)) {
            throw new LedgerException(__('No amount column found — the file needs an “Amount” column, or “Paid in” and “Paid out”.'));
        }

        $out = [];
        foreach (array_slice($rows, $headerAt + 1) as $r) {
            $date = self::parseDate($r[$dateCol] ?? '');
            if (! $date) {
                continue; // totals, balance rows, blank lines
            }
            $amount = $amtCol !== null
                ? self::parseMoney($r[$amtCol] ?? '')
                : self::parseMoney($r[$inCol] ?? '') - abs(self::parseMoney($r[$outCol] ?? ''));
            if ($amount === 0.0) {
                continue;
            }
            $out[] = [
                'date' => $date,
                'description' => mb_substr($descCol !== null ? ($r[$descCol] ?? '') : '', 0, 255),
                'reference' => $refCol !== null && $refCol !== $descCol ? mb_substr($r[$refCol] ?? '', 0, 255) : null,
                'amount' => round($amount, 2),
            ];
        }
        if (! $out) {
            throw new LedgerException(__('No transactions found in that file.'));
        }

        return $out;
    }

    public static function parseDate(string $v): ?string
    {
        $v = trim($v);
        if ($v === '') {
            return null;
        }
        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'd M Y', 'd-M-Y', 'd F Y', 'j/n/Y', 'Y/m/d', 'd M y'] as $f) {
            try {
                $d = Carbon::createFromFormat('!'.$f, $v);
                if ($d && $d->year > 1990 && $d->year < 2100) {
                    return $d->toDateString();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    public static function parseMoney(string $v): float
    {
        $v = trim($v);
        if ($v === '') {
            return 0.0;
        }
        $neg = str_starts_with($v, '(') || str_starts_with($v, '-') || preg_match('/\bdr\b/i', $v);
        $n = (float) preg_replace('/[^0-9.]/', '', str_replace(',', '', $v));

        return $neg ? -$n : $n;
    }

    /** Add statement lines and match what can be matched. @return array{added: int, matched: int} */
    public static function import(Reconciliation $rec, array $rows, ?string $fileName = null): array
    {
        self::assertOpen($rec);

        return DB::transaction(function () use ($rec, $rows, $fileName) {
            $n = StatementLine::where('reconciliation_id', $rec->id)->max('sort_order') ?? 0;
            foreach ($rows as $r) {
                StatementLine::create($r + ['reconciliation_id' => $rec->id, 'status' => 'unmatched', 'sort_order' => ++$n]);
            }
            $fileName && $rec->update(['source_file' => $fileName]);

            return ['added' => count($rows), 'matched' => self::autoMatch($rec)];
        });
    }

    /** Same amount, date within 7 days, closest date first. Returns how many matched. */
    public static function autoMatch(Reconciliation $rec): int
    {
        $matched = 0;
        $taken = StatementLine::where('reconciliation_id', $rec->id)->whereNotNull('journal_line_id')->pluck('journal_line_id')->all();
        $candidates = self::candidates($rec, true)->get()->reject(fn ($l) => in_array($l->id, $taken, true));
        foreach (StatementLine::where('reconciliation_id', $rec->id)->where('status', 'unmatched')->orderBy('date')->get() as $sl) {
            $best = $candidates->filter(fn ($l) => abs(round((float) $l->debit - (float) $l->credit, 2) - (float) $sl->amount) < 0.005
                    && abs($l->date->diffInDays($sl->date)) <= 7)
                ->sortBy(fn ($l) => abs($l->date->diffInDays($sl->date)))->first();
            if ($best) {
                self::link($rec, $sl, $best);
                $candidates = $candidates->reject(fn ($l) => $l->id === $best->id);
                $matched++;
            }
        }

        return $matched;
    }

    public static function link(Reconciliation $rec, StatementLine $sl, JournalLine $line, string $status = 'matched'): void
    {
        $line->update(['reconciliation_id' => $rec->id]);
        $sl->update(['journal_line_id' => $line->id, 'status' => $status]);
    }

    /**
     * Post a statement line nobody entered yet (bank charges, interest, a
     * direct debit) as money in / out against the chosen account, and match it.
     */
    public static function createFromLine(Reconciliation $rec, StatementLine $sl, string $accountId, ?string $description, ?int $userId): Document
    {
        self::assertOpen($rec);

        return DB::transaction(function () use ($rec, $sl, $accountId, $description, $userId) {
            $in = (float) $sl->amount > 0;
            $doc = Documents::saveDraft(null, $rec->agency_id, $in ? 'money_in' : 'money_out', [
                'bank_account_id' => $rec->account_id,
                'issue_date' => $sl->date->toDateString(),
                'reference' => $sl->reference ?: null,
                'notes' => __('Created from the bank statement'),
            ], [[
                'account_id' => $accountId, 'description' => $description ?: $sl->description,
                'qty' => 1, 'unit_price' => abs((float) $sl->amount), 'vat_rate' => 0,
            ]], $userId);
            $doc = Documents::post($doc, $userId);
            $bankLine = JournalLine::where('journal_id', $doc->journal_id)->where('account_id', $rec->account_id)->firstOrFail();
            self::link($rec, $sl, $bankLine, 'created');

            return $doc;
        });
    }

    public static function complete(Reconciliation $rec, ?int $userId): void
    {
        self::assertOpen($rec);
        $diff = self::difference($rec);
        if (abs($diff) >= 0.005) {
            throw new LedgerException(__('There’s still a difference of :d. Tick the entries on the statement, or add the missing ones, until it’s zero.', [
                'd' => \App\Models\Accounting\AccountingSetting::forAgency($rec->agency_id)->money($diff),
            ]));
        }
        $rec->update(['status' => 'completed', 'completed_by' => $userId, 'completed_at' => now()]);
        activity('accounts')->performedOn($rec)->causedBy($userId)
            ->log(__(':a reconciled to :d', ['a' => $rec->account->name, 'd' => $rec->statement_date->format('d M Y')]));
    }

    /** Only the latest completed reconciliation of an account can be undone. */
    public static function reopen(Reconciliation $rec, ?int $userId): void
    {
        $latest = self::lastCompleted($rec->account);
        if (! $rec->isCompleted() || $latest?->id !== $rec->id) {
            throw new LedgerException(__('Only the most recent reconciliation can be re-opened.'));
        }
        if (Reconciliation::where('account_id', $rec->account_id)->where('status', 'in_progress')->exists()) {
            throw new LedgerException(__('Finish or delete the reconciliation in progress first.'));
        }
        $rec->update(['status' => 'in_progress', 'completed_by' => null, 'completed_at' => null]);
        activity('accounts')->performedOn($rec)->causedBy($userId)
            ->log(__(':a reconciliation to :d re-opened', ['a' => $rec->account->name, 'd' => $rec->statement_date->format('d M Y')]));
    }

    /** Throw away an in-progress reconciliation (ticks are cleared; entries created from it stay posted). */
    public static function discard(Reconciliation $rec): void
    {
        self::assertOpen($rec);
        DB::transaction(function () use ($rec) {
            JournalLine::where('reconciliation_id', $rec->id)->update(['reconciliation_id' => null]);
            $rec->delete();
        });
    }

    private static function assertOpen(Reconciliation $rec): void
    {
        if ($rec->isCompleted()) {
            throw new LedgerException(__('This reconciliation is completed. Re-open it to change it.'));
        }
    }
}
