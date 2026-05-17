<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
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
