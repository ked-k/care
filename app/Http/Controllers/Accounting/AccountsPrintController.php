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
        $rep = (new ReportBuilder($cur))->build($report, $request->only(['year', 'from', 'to', 'as_at', 'account', 'compare']));

        return view('accounting.reports.print', ['rep' => $rep, 'cur' => $cur]);
    }

    public function csv(Request $request, string $report)
    {
        $cur = AccountingSetting::forAgency($this->agencyId());
        $rep = (new ReportBuilder($cur))->build($report, $request->only(['year', 'from', 'to', 'as_at', 'account', 'compare']));
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
}
