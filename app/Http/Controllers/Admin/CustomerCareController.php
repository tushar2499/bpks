<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Http\Request;

class CustomerCareController extends Controller
{
    public function index(Request $request)
    {
        $phone        = null;
        $transactions = collect();
        $summary      = null;

        if ($request->filled('phone')) {
            $phone = trim($request->phone);

            $transactions = Transaction::with(['smsLog'])
                ->where('phone', $phone)
                ->orderByDesc('created_at')
                ->get();

            // Batch-load all tickets
            $allIds = $transactions->flatMap(fn($t) => $t->ticket_ids ?? array_filter([$t->ticket_id]))
                ->unique()->filter();
            $ticketsById = Ticket::whereIn('id', $allIds)->pluck('ticket_no', 'id');

            foreach ($transactions as $txn) {
                $ids = $txn->ticket_ids ?? array_filter([$txn->ticket_id]);
                $txn->resolved_ticket_nos = collect($ids)
                    ->map(fn($id) => $ticketsById[$id] ?? null)
                    ->filter()->values()->all();
            }

            $successful = $transactions->where('status', 'success');

            $summary = [
                'total_transactions' => $transactions->count(),
                'successful'         => $successful->count(),
                'total_tickets'      => $successful->sum('qty'),
                'total_spent'        => $successful->sum('amount'),
                'operators'          => $transactions->pluck('operator')->unique()->filter()->values(),
                'last_purchase'      => $successful->sortByDesc('confirmed_at')->first()?->confirmed_at,
            ];
        }

        return view('admin.customer-care.index', compact('phone', 'transactions', 'summary'));
    }
}
