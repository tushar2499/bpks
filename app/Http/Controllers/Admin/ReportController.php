<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\SMS\RobiSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $stats = $this->getStats();

        $byOperator = DB::table('tickets')
            ->where('status', 1)
            ->selectRaw('operator, COUNT(*) as count, SUM(sell_price) as revenue')
            ->groupBy('operator')
            ->orderByDesc('count')
            ->get();

        $daily = DB::table('tickets')
            ->where('status', 1)
            ->selectRaw('DATE(sold_at) as date, COUNT(*) as count, SUM(sell_price) as revenue')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->get();

        return view('admin.reports.index', compact('stats', 'byOperator', 'daily'));
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
        $stats      = $this->getStats();
        $byOperator = DB::table('tickets')
            ->where('status', 1)
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
        // Success transactions with no SMS log OR SMS log with non-success status
        $query = Transaction::with(['ticket', 'smsLog'])
            ->where('status', 'success')
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

        $totalSuccess = Transaction::where('status', 'success')->count();
        $totalNoSms   = Transaction::where('status', 'success')->doesntHave('smsLog')->count();
        $totalFailed  = Transaction::where('status', 'success')
            ->whereHas('smsLog', $failScope)->count();

        return view('admin.reports.sms', compact('transactions', 'totalSuccess', 'totalNoSms', 'totalFailed'));
    }

    public function retrySms(Transaction $transaction)
    {
        if ($transaction->status !== 'success' || !$transaction->ticket) {
            return back()->with('error', 'Invalid transaction.');
        }

        $ticket  = $transaction->ticket;
        $message = "প্রিয় গ্রাহক, আপনার BPKS লটারি টিকেট কেনা সফল হয়েছে।\n"
                 . "টিকেট নম্বর: {$ticket->ticket_no}\n"
                 . "মূল্য: ৳{$transaction->amount}\n"
                 . "লেনদেন: {$transaction->txn_ref}";

        $sms  = new RobiSmsService();
        $sent = $sms->send($transaction->phone, $message, $transaction->txn_ref);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? "{$transaction->phone} — SMS পাঠানো হয়েছে।" : "{$transaction->phone} — SMS পাঠাতে ব্যর্থ।"
        );
    }

    private function getStats(): object
    {
        return DB::table('tickets')->selectRaw('
            COUNT(*) as total,
            SUM(status = 0) as unsold,
            SUM(status = 1) as sold,
            SUM(CASE WHEN status = 1 THEN sell_price ELSE 0 END) as revenue
        ')->first();
    }
}
