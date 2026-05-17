<?php

namespace App\Http\Controllers;

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

        $transactions = Transaction::with('ticket')
            ->where('phone', $phone)
            ->where('status', 'success')
            ->orderByDesc('confirmed_at')
            ->get();

        if ($transactions->isEmpty()) {
            return back()->withInput()->with('error', 'এই নম্বরে কোনো টিকেট পাওয়া যায়নি।');
        }

        return view('buy.my-ticket', compact('transactions', 'phone'));
    }
}
