<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingSetting;
use App\Models\Accounting\Contact;
use App\Models\Accounting\Document;
use App\Models\Accounting\Payment;
use App\Services\Accounting\ReportBuilder;
use App\Services\Accounting\Reports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Batch 43: printable pages (invoice, receipt, statement, reports) and CSV
 * downloads for the Finance workspace. The route group already requires
 * manage_accounts; everything is scoped to the user's own agency.
 */
class AccountsPrintController extends Controller
{
    private function agencyId(): string
    {
        abort_unless(Auth::user()?->agency_id, 403);

        return Auth::user()->agency_id;
    }

    public function document(string $documentId)
    {
        $doc = Document::where('agency_id', $this->agencyId())->with('lines', 'contact', 'bankAccount', 'allocations.payment')->findOrFail($documentId);

        return view('accounting.print.document', ['doc' => $doc, 'cur' => AccountingSetting::forAgency($doc->agency_id), 'agency' => Auth::user()->agency]);
    }

    public function payment(string $paymentId)
    {
        $p = Payment::where('agency_id', $this->agencyId())->with('contact', 'bankAccount', 'allocations.document')->findOrFail($paymentId);

        return view('accounting.print.payment', ['p' => $p, 'cur' => AccountingSetting::forAgency($p->agency_id), 'agency' => Auth::user()->agency]);
    }

    public function statement(Request $request, string $contactId)
    {
        $c = Contact::where('agency_id', $this->agencyId())->findOrFail($contactId);
        $cur = AccountingSetting::forAgency($c->agency_id);
        $from = $request->date('from')?->toDateString() ?? today()->subMonths(3)->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? today()->toDateString();

        return view('accounting.print.statement', [
            'c' => $c, 'cur' => $cur, 'agency' => Auth::user()->agency,
            'statement' => (new Reports($cur))->statement($c, $from, $to), 'from' => $from, 'to' => $to,
            'open' => Document::where('contact_id', $c->id)->whereIn('status', ['posted', 'partial'])->orderBy('due_date')->get(),
        ]);
    }

    public function report(Request $request, string $report)
    {
        $cur = AccountingSetting::forAgency($this->agencyId());
        $rep = (new ReportBuilder($cur))->build($report, $request->only(['year', 'from', 'to', 'as_at', 'account', 'compare', 'budget']));

        return view('accounting.reports.print', ['rep' => $rep, 'cur' => $cur]);
    }

    /** Batch 44c: a VAT return (filed, or the draft for ?period=) to print. */
    public function vat(Request $request)
    {
        $cur = AccountingSetting::forAgency($this->agencyId());
        $vat = new \App\Services\Accounting\VatReturns($cur);
        $ret = $request->filled('return') ? \App\Models\Accounting\VatReturn::where('agency_id', $cur->agency_id)->findOrFail($request->input('return')) : null;
        [$start, $end] = $ret ? [$ret->period_start, $ret->period_end] : ($request->filled('period') ? $vat->periodFor($request->date('period')) : $vat->nextPeriod());
        $ret ??= \App\Models\Accounting\VatReturn::where('agency_id', $cur->agency_id)->where('period_start', $start->toDateString())->first();

        return view('accounting.print.vat', [
            'cur' => $cur, 'agency' => Auth::user()->agency, 'ret' => $ret,
            'calc' => $vat->compute($start, $end, $ret), 'boxes' => \App\Services\Accounting\VatReturns::BOXES,
        ]);
    }

    public function csv(Request $request, string $report)
    {
        $cur = AccountingSetting::forAgency($this->agencyId());
        $rep = (new ReportBuilder($cur))->build($report, $request->only(['year', 'from', 'to', 'as_at', 'account', 'compare', 'budget']));
        $name = str($rep['title'].' '.($rep['params']['as_at'] ?? ''))->slug().'.csv';

        return response()->streamDownload(function () use ($rep, $cur) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads £ / accents correctly
            fputcsv($out, [$cur->legal_name]);
            fputcsv($out, [$rep['title'], $rep['subtitle'], $cur->currency_code]);
            fputcsv($out, []);
            foreach ($rep['csv'] as $row) {
                fputcsv($out, array_map(fn ($v) => is_float($v) || is_int($v) ? round($v, 2) : $v, $row));
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Batch 44b: the year's full set of accounts in one printable document —
     * cover, approval page, the four statements with comparatives, the
     * notes (written and from the ledger), the trial balance and, if asked,
     * every account's ledger. Print or "Save as PDF" from the browser.
     */
    public function auditPack(Request $request, string $yearId)
    {
        $cur = AccountingSetting::forAgency($this->agencyId());
        $year = \App\Models\Accounting\FinancialYear::where('agency_id', $cur->agency_id)->findOrFail($yearId);
        $b = new ReportBuilder($cur);
        $in = ['year' => $year->id, 'from' => $year->starts_on->toDateString(), 'to' => $year->ends_on->toDateString(), 'as_at' => $year->ends_on->toDateString(), 'compare' => true];
        $statements = collect(['income-statement', 'balance-sheet', 'changes-in-equity', 'cash-flow'])->mapWithKeys(fn ($r) => [$r => $b->build($r, $in)]);
        $ledgers = [];
        if ($request->boolean('ledgers')) {
            $reports = new Reports($cur);
            foreach (\App\Models\Accounting\Account::where('agency_id', $cur->agency_id)->orderBy('code')->get() as $a) {
                $l = $reports->ledger($a, $in['from'], $in['to']);
                if ($l['lines']->isNotEmpty() || abs($l['opening']) >= 0.005) {
                    $ledgers[] = $l;
                }
            }
        }

        return view('accounting.audit-pack', [
            'cur' => $cur, 'year' => $year, 'agency' => Auth::user()->agency,
            'statements' => $statements,
            'tb' => $b->build('trial-balance', $in),
            'notes' => \App\Services\Accounting\Notes::forYear($year),
            'figures' => \App\Services\Accounting\Notes::figures($year),
            'ledgers' => $ledgers, 'withLedgers' => $request->boolean('ledgers'),
            'closed' => $year->isClosed(),
        ]);
    }
}
