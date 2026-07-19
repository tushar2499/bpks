<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Http\Request;

class MyTicketController extends Controller
{
    public function show()
    {
        return view('buy.my-ticket');
    }

    public function find(Request $request)
    {
        $request->validate(['phone' => 'required|digits:11']);

        $phone = $request->phone;

        $transactions = Transaction::where('phone', $phone)
            ->where('status', 'success')
            ->orderByDesc('confirmed_at')
            ->get();

        if ($transactions->isEmpty()) {
            return back()->withInput()->with('error', 'এই নম্বরে কোনো টিকেট পাওয়া যায়নি।');
        }

        $allIds    = $transactions->flatMap(fn($t) => $t->ticket_ids ?? [$t->ticket_id])->filter()->unique()->values();
        $ticketMap = Ticket::whereIn('id', $allIds)->get()->keyBy('id');

        $ticketsByTxn = [];
        foreach ($transactions as $txn) {
            $ids = $txn->ticket_ids ?? [$txn->ticket_id];
            $ticketsByTxn[$txn->id] = collect(array_filter($ids))
                ->map(fn($id) => $ticketMap->get($id))
                ->filter()
                ->values();
        }

        return view('buy.my-ticket', compact('transactions', 'phone', 'ticketsByTxn'));
    }

}
