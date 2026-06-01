<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Services\DCB\GpConsentService;
use App\Services\SMS\RobiSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user     = Auth::user();
        $opFilter = $user->isOperator() ? $user->operator : null;

        $stats = $this->getStats($opFilter);

        $byOperator = DB::table('tickets')
            ->where('status', 1)
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->selectRaw('operator, COUNT(*) as count, SUM(sell_price) as revenue')
            ->groupBy('operator')
            ->orderByDesc('count')
            ->get();

        $daily = DB::table('tickets')
            ->where('status', 1)
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as count, SUM(sell_price) as revenue')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        $tierProgress = collect();
        if ($user->isAdmin()) {
            $tierProgress = DB::table('tickets')
                ->whereNotNull('series')
                ->selectRaw('operator, series, sale_tier,
                    COUNT(*) as total,
                    SUM(status = 1) as sold,
                    SUM(status = 0) as unsold,
                    SUM(status = 2) as reserved')
                ->groupBy('operator', 'series', 'sale_tier')
                ->orderBy('operator')->orderBy('series')->orderBy('sale_tier')
                ->get()
                ->groupBy(fn($r) => $r->operator . '||' . $r->series);
        }

        return view('admin.reports.index', compact('stats', 'byOperator', 'daily', 'tierProgress'));
    }

    public function exportCsv()
    {
        $filename = 'bpks-tickets-' . date('Y-m-d') . '.csv';

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM so Excel opens Bengali text correctly
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Ticket No', 'Status', 'Phone', 'Operator', 'Price (BDT)', 'Sold At', 'Created At']);

            Ticket::orderBy('ticket_no')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->ticket_no,
                        $row->status ? 'Sold' : 'Unsold',
                        $row->phone ?? '-',
                        $row->operator ?? '-',
                        $row->sell_price,
                        $row->sold_at ?? '-',
                        $row->created_at,
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function exportPdf()
    {
        $user       = Auth::user();
        $opFilter   = $user->isOperator() ? $user->operator : null;
        $stats      = $this->getStats($opFilter);
        $byOperator = DB::table('tickets')
            ->where('status', 1)
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->selectRaw('operator, COUNT(*) as count, SUM(sell_price) as revenue')
            ->groupBy('operator')
            ->orderByDesc('count')
            ->get();

        $pdf = Pdf::loadView('admin.reports.pdf', compact('stats', 'byOperator'));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('bpks-report-' . date('Y-m-d') . '.pdf');
    }

    public function smsReport(Request $request)
    {
        /** @var User $user */
        $user     = Auth::user();
        $opFilter = $user->isOperator() ? $user->operator : null;

        // Success transactions with no SMS log OR SMS log with non-success status
        $query = Transaction::with(['ticket', 'smsLog'])
            ->where('status', 'success')
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->orderByDesc('confirmed_at');

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('confirmed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('confirmed_at', '<=', $request->date_to);
        }
        // Failure = no log, or status is Failed/Unknown/empty
        $failScope = fn($q) => $q->whereIn('status_message', ['Failed', 'Unknown', ''])->orWhereNull('status_message');

        if ($request->filled('sms_status')) {
            if ($request->sms_status === 'not_sent') {
                $query->doesntHave('smsLog');
            } elseif ($request->sms_status === 'failed') {
                $query->whereHas('smsLog', $failScope);
            } elseif ($request->sms_status === 'sent') {
                $query->whereHas('smsLog', fn($q) =>
                    $q->whereNotIn('status_message', ['Failed', 'Unknown', ''])->whereNotNull('status_message')
                );
            }
        } else {
            $query->where(function ($q) use ($failScope) {
                $q->doesntHave('smsLog')->orWhereHas('smsLog', $failScope);
            });
        }

        $transactions = $query->paginate(30)->withQueryString();

        // Batch-load all ticket numbers (handles both single and multi-ticket)
        $allIds = $transactions->flatMap(fn($t) => $t->ticket_ids ?? array_filter([$t->ticket_id]))->unique()->filter();
        $ticketsById = Ticket::whereIn('id', $allIds)->pluck('ticket_no', 'id');
        foreach ($transactions as $txn) {
            $ids = $txn->ticket_ids ?? array_filter([$txn->ticket_id]);
            $txn->resolved_ticket_nos = collect($ids)->map(fn($id) => $ticketsById[$id] ?? null)->filter()->values()->all();
        }

        $totalSuccess = Transaction::where('status', 'success')->when($opFilter, fn($q) => $q->where('operator', $opFilter))->count();
        $totalNoSms   = Transaction::where('status', 'success')->when($opFilter, fn($q) => $q->where('operator', $opFilter))->doesntHave('smsLog')->count();
        $totalFailed  = Transaction::where('status', 'success')->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->whereHas('smsLog', $failScope)->count();

        return view('admin.reports.sms', compact('transactions', 'totalSuccess', 'totalNoSms', 'totalFailed'));
    }

    public function retrySms(Transaction $transaction)
    {
        if ($transaction->status !== 'success') {
            return back()->with('error', 'Invalid transaction.');
        }

        $ids     = $transaction->ticket_ids ?? array_filter([$transaction->ticket_id]);
        $tickets = Ticket::whereIn('id', $ids)->get();

        if ($tickets->isEmpty()) {
            return back()->with('error', 'No tickets found for this transaction.');
        }

        $ticketNos   = $tickets->pluck('ticket_no')->implode(', ');
        $downloadUrl = route('ticket.download-all-pdf', ['phone' => $transaction->phone]);

        $sent = false;

        if ($transaction->operator === 'Grameenphone') {
            $acr = $transaction->gp_customer_ref;
            if (!$acr) {
                return back()->with('error', 'GP customer reference (ACR) not found — cannot send GP SMS.');
            }

            $gpMessage = "আপনি সফল ভাবে BPKS লটারির টিকিট ক্রয় করেছেন। চার্জ ২০ টাকা।"
                       . " টিকেট নাম্বার: '{$ticketNos}' ,"
                       . " ডাউনলোড টিকিট: '{$downloadUrl}'"
                       . " | হেল্পলাইন: +8801725298711 (চার্জ প্রযোজ্য)";

            $sent = (new GpConsentService())->sendSms($acr, $transaction->phone, $gpMessage, $transaction->txn_ref);
        } else {
            $message = "প্রিয় গ্রাহক, আপনার BPKS লটারি টিকেট কেনা সফল হয়েছে।\n"
                     . "টিকেট নম্বর: {$ticketNos}\n"
                     . "মূল্য: ৳{$transaction->amount}\n"
                     . "লেনদেন: {$transaction->txn_ref}";

            $sent = (new RobiSmsService())->send($transaction->phone, $message, $transaction->txn_ref);
        }

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? "{$transaction->phone} — SMS পাঠানো হয়েছে।" : "{$transaction->phone} — SMS পাঠাতে ব্যর্থ।"
        );
    }

    private function getStats(?string $opFilter = null): object
    {
        return DB::table('tickets')
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->selectRaw('
                COUNT(*) as total,
                SUM(status = 0) as unsold,
                SUM(status = 1) as sold,
                SUM(CASE WHEN status = 1 THEN sell_price ELSE 0 END) as revenue
            ')->first();
    }
}
