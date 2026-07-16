<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Blink\BlinkService;
use App\Services\DCB\GpConsentService;
use App\Services\SMS\RobiSmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
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

        $ticketCombos = DB::table('tickets')
            ->whereNotNull('series')
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->selectRaw('series, operator, SUM(status=1) as sold, SUM(status=0) as unsold')
            ->groupBy('series', 'operator')
            ->orderBy('series')->orderBy('operator')
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
                    SUM(status = 2) as reserved,
                    MIN(ticket_no) as min_ticket,
                    MAX(ticket_no) as max_ticket')
                ->groupBy('operator', 'series', 'sale_tier')
                ->orderBy('operator')->orderBy('series')->orderBy('sale_tier')
                ->get()
                ->groupBy(fn($r) => $r->operator . '||' . $r->series);
        }

        return view('admin.reports.index', compact('stats', 'byOperator', 'daily', 'tierProgress', 'ticketCombos'));
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

            $gpCharge  = (int) $transaction->amount;
            $gpMessage = "আপনি সফল ভাবে BPKS লটারির টিকিট ক্রয় করেছেন। চার্জ {$gpCharge} টাকা।"
                       . " টিকেট নাম্বার: '{$ticketNos}' ,"
                       . " ডাউনলোড টিকিট: {$downloadUrl}"
                       . " | হেল্পলাইন: +8801725298711 (চার্জ প্রযোজ্য)";

            $sent = (new GpConsentService())->sendSms($acr, $transaction->phone, $gpMessage, $transaction->txn_ref);
        } elseif ($transaction->operator === 'Banglalink') {
            $amount  = number_format($transaction->amount, 2);
            $message = "আপনি সফল ভাবে BPKS ({$ticketNos}) টিকেট ক্রয় করেছেন। মূল্য: ৳{$amount} (ট্যাক্সসহ) | ট্রানজেকশন: {$transaction->txn_ref} | ডাউনলোড: {$downloadUrl} । হেল্পলাইন: 01920934747 (9:30 AM-5:30 PM)";

            $sent = (new BlinkService())->sendSms($transaction->phone, $message, $transaction->txn_ref);
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

    public function dailyReport(Request $request)
    {
        /** @var User $user */
        $user     = Auth::user();
        $opFilter = $user->isOperator() ? $user->operator : null;

        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $operator = $opFilter ?? $request->input('operator');

        $rows = DB::table('transactions')
            ->where('status', 'success')
            ->when($dateFrom, fn($q) => $q->whereDate('confirmed_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('confirmed_at', '<=', $dateTo))
            ->when($operator, fn($q) => $q->where('operator', $operator))
            ->selectRaw('DATE(confirmed_at) as date, operator,
                COUNT(*) as txn_count,
                SUM(qty) as ticket_count,
                SUM(amount) as total_amount')
            ->groupBy('date', 'operator')
            ->orderByDesc('date')
            ->orderBy('operator')
            ->get();

        $totals = [
            'txn_count'    => $rows->sum('txn_count'),
            'ticket_count' => $rows->sum('ticket_count'),
            'total_amount' => $rows->sum('total_amount'),
        ];

        $operators = $opFilter ? collect([$opFilter]) : collect(['Grameenphone', 'Robi', 'Airtel', 'Banglalink']);

        return view('admin.reports.daily', compact('rows', 'totals', 'operators', 'opFilter'));
    }

    public function dailyDetail(Request $request)
    {
        /** @var User $user */
        $user     = Auth::user();
        $opFilter = $user->isOperator() ? $user->operator : null;

        $date     = $request->input('date');
        $operator = $opFilter ?? $request->input('operator');

        $transactions = Transaction::with(['smsLog'])
            ->where('status', 'success')
            ->whereDate('confirmed_at', $date)
            ->when($operator, fn($q) => $q->where('operator', $operator))
            ->orderBy('confirmed_at')
            ->get();

        // Batch-load ticket numbers
        $allIds      = $transactions->flatMap(fn($t) => $t->ticket_ids ?? array_filter([$t->ticket_id]))->unique()->filter();
        $ticketsById = Ticket::whereIn('id', $allIds)->pluck('ticket_no', 'id');
        foreach ($transactions as $txn) {
            $ids = $txn->ticket_ids ?? array_filter([$txn->ticket_id]);
            $txn->resolved_ticket_nos = collect($ids)->map(fn($id) => $ticketsById[$id] ?? null)->filter()->values()->all();
        }

        return response()->json($transactions->map(fn($t) => [
            'txn_ref'    => $t->txn_ref,
            'phone'      => $t->phone,
            'operator'   => $t->operator,
            'qty'        => $t->qty,
            'amount'     => number_format($t->amount, 2),
            'ticket_nos' => $t->resolved_ticket_nos,
            'confirmed'  => $t->confirmed_at?->format('H:i:s'),
            'sms'        => $t->smsLog?->status_message ?? '—',
        ]));
    }

    public function exportSummaryXlsx(string $operator)
    {
        $opMap = [
            'Grameenphone' => 'GP',
            'Banglalink'   => 'BL',
            'Robi'         => 'Robi',
            'Teletalk'     => 'TT',
        ];

        if (!array_key_exists($operator, $opMap)) {
            abort(404);
        }

        // All series with total + available + sold-by-this-operator
        $rows = DB::table('tickets')
            ->selectRaw("
                series,
                COUNT(*) as total,
                SUM(status = 0) as remain,
                SUM(CASE WHEN status = 1 AND operator = ? THEN 1 ELSE 0 END) as sold
            ", [$operator])
            ->whereNotNull('series')
            ->groupBy('series')
            ->orderBy('series')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle($opMap[$operator]);

        // Row 1 — title
        $title = 'BPKS Lottery 2026 — ' . $operator;
        $lastCol = 'D';
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Row 2 — headers
        $headers = ['Details', 'TOTAL', 'Remain Ticket', 'Sale Ticket'];
        foreach ($headers as $i => $h) {
            $col = chr(ord('A') + $i);
            $sheet->setCellValue("{$col}2", $h);
        }
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4DD0E1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data rows
        $totals = ['total' => 0, 'remain' => 0, 'sold' => 0];
        $rowNum = 3;
        foreach ($rows as $r) {
            $label = rtrim($r->series, '-');
            $sheet->setCellValue("A{$rowNum}", $label);
            $sheet->setCellValue("B{$rowNum}", (int)$r->total);
            $sheet->setCellValue("C{$rowNum}", (int)$r->remain);
            $sheet->setCellValue("D{$rowNum}", (int)$r->sold);

            $totals['total']  += $r->total;
            $totals['remain'] += $r->remain;
            $totals['sold']   += $r->sold;

            if ($rowNum % 2 === 0) {
                $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
                ]);
            }
            $rowNum++;
        }

        // Summary row
        $sheet->setCellValue("A{$rowNum}", 'Summary');
        $sheet->setCellValue("B{$rowNum}", $totals['total']);
        $sheet->setCellValue("C{$rowNum}", $totals['remain']);
        $sheet->setCellValue("D{$rowNum}", $totals['sold']);
        $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
        ]);

        // Column widths + center numbers
        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getStyle("B3:D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Border around all data
        $sheet->getStyle("A2:D{$rowNum}")->getBorders()->getAllBorders()->setBorderStyle(
            \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
        );

        $filename = 'BPKS-Summary-' . $opMap[$operator] . '-' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportTicketsXlsx(string $operator, string $series, string $type)
    {
        $opMap = [
            'Grameenphone' => 'GP',
            'Banglalink'   => 'BL',
            'Robi'         => 'Robi',
            'Teletalk'     => 'TT',
        ];

        if (!array_key_exists($operator, $opMap) || !in_array($type, ['sold', 'unsold'])) {
            abort(404);
        }

        // series stored with trailing dash, URL param without
        $seriesDb = rtrim($series, '-') . '-';

        $query = DB::table('tickets')
            ->where('operator', $operator)
            ->where('series', $seriesDb)
            ->where('status', $type === 'sold' ? 1 : 0)
            ->orderBy('ticket_no');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $label       = $opMap[$operator] . '-' . rtrim($series, '-');
        $sheet->setTitle($label);

        // Title row
        $lastCol  = $type === 'sold' ? 'C' : 'B';
        $sheetTitle = 'BPKS Lottery 2026 — ' . $operator . ' — ' . rtrim($series, '-') . ' — ' . ($type === 'sold' ? 'Sold' : 'Unsold');
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $sheetTitle);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1565C0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Headers
        $headers = $type === 'sold' ? ['#', 'Ticket No', 'Msisdn'] : ['#', 'Ticket No'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(ord('A') + $i) . '2', $h);
        }
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font'      => ['bold' => true],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4DD0E1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Data — chunk to avoid memory spike on large datasets
        $rowNum = 3;
        $seq    = 1;
        $query->chunk(2000, function ($rows) use ($sheet, &$rowNum, &$seq, $type) {
            foreach ($rows as $r) {
                $ticketDisplay = str_replace('-', ' ', $r->ticket_no);
                $sheet->setCellValue("A{$rowNum}", $seq++);
                $sheet->setCellValue("B{$rowNum}", $ticketDisplay);
                if ($type === 'sold') {
                    $sheet->setCellValueExplicit(
                        "C{$rowNum}",
                        $r->phone ?? '',
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                }
                if ($rowNum % 2 === 0) {
                    $endCol = $type === 'sold' ? 'C' : 'B';
                    $sheet->getStyle("A{$rowNum}:{$endCol}{$rowNum}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
                    ]);
                }
                $rowNum++;
            }
        });

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(16);
        if ($type === 'sold') {
            $sheet->getColumnDimension('C')->setWidth(18);
        }

        // Border
        $endRow = $rowNum - 1;
        if ($endRow >= 2) {
            $sheet->getStyle("A2:{$lastCol}{$endRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        }

        $filename = 'BPKS-' . $label . '-' . ucfirst($type) . '-' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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
