<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = DB::table('tickets')->selectRaw('
            COUNT(*) as total,
            SUM(status = 0) as unsold,
            SUM(status = 1) as sold,
            SUM(CASE WHEN status = 1 THEN sell_price ELSE 0 END) as revenue
        ')->first();

        $recentSold = DB::table('tickets')
            ->where('status', 1)
            ->orderByDesc('sold_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentSold'));
    }
}
