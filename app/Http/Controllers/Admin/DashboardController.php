<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user     = Auth::user();
        $opFilter = $user->isOperator() ? $user->operator : null;

        $statsQuery = DB::table('tickets');
        if ($opFilter) $statsQuery->where('operator', $opFilter);

        $stats = $statsQuery->selectRaw('
            COUNT(*) as total,
            SUM(status = 0) as unsold,
            SUM(status = 1) as sold,
            SUM(CASE WHEN status = 1 THEN sell_price ELSE 0 END) as revenue
        ')->first();

        $recentSold = DB::table('tickets')
            ->where('status', 1)
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->orderByDesc('sold_at')
            ->limit(10)
            ->get();

        // Chart: last 60 days, by date + operator
        $chartRaw = DB::table('tickets')
            ->where('status', 1)
            ->where('sold_at', '>=', now()->subDays(60))
            ->when($opFilter, fn($q) => $q->where('operator', $opFilter))
            ->selectRaw('DATE(sold_at) as date, operator, COUNT(*) as qty, SUM(sell_price) as revenue')
            ->groupBy('date', 'operator')
            ->orderBy('date')
            ->get();

        $chartDates     = $chartRaw->pluck('date')->unique()->sort()->values()->all();
        $chartOperators = $chartRaw->pluck('operator')->unique()->sort()->values()->all();

        $chartDatasets = [];
        foreach ($chartOperators as $op) {
            $qtyRow = [];
            $revRow = [];
            foreach ($chartDates as $date) {
                $row     = $chartRaw->first(fn($r) => $r->date === $date && $r->operator === $op);
                $qtyRow[] = $row ? (int) $row->qty     : 0;
                $revRow[] = $row ? (float) $row->revenue : 0;
            }
            $chartDatasets[$op] = ['qty' => $qtyRow, 'revenue' => $revRow];
        }

        $stuckCount = $user->isAdmin()
            ? \App\Models\Ticket::where('status', 2)->where('updated_at', '<', now()->subHour())->count()
            : 0;

        return view('admin.dashboard', compact('stats', 'recentSold', 'chartDates', 'chartOperators', 'chartDatasets', 'stuckCount'));
    }
}
