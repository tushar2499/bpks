<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Transaction;
use Illuminate\Http\Request;

class MyTicketController extends Controller
{
    public function show()
    {
        $winners = $this->getWinners();
        return view('buy.my-ticket', compact('winners'));
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

        $winners = $this->getWinners();

        // Collect all user's ticket_nos (normalized: no dashes, uppercase)
        $userTicketNos = $ticketMap->pluck('ticket_no')
            ->map(fn($n) => strtoupper(str_replace('-', '', $n)))
            ->toArray();

        // Find winning ticket_nos that belong to this user
        $wonTickets = [];
        foreach ($winners as $group) {
            foreach ($group['winners'] as $w) {
                $normalized = strtoupper(str_replace('-', '', $w['ticket_no']));
                if (in_array($normalized, $userTicketNos)) {
                    $wonTickets[] = array_merge($w, ['prize' => $group['title']]);
                }
            }
        }

        return view('buy.my-ticket', compact('transactions', 'phone', 'ticketsByTxn', 'winners', 'wonTickets'));
    }

    private function getWinners(): array
    {
        $path = public_path('winner list.csv');
        if (!file_exists($path)) return [];

        $handle = fopen($path, 'r');
        fgetcsv($handle); // skip header
        $groups = [];

        while (($row = fgetcsv($handle)) !== false) {
            [$award_id, $title, $ticket_no, $ticket_type, $customer_name, , $customer_district, $merchant_name] = array_pad($row, 8, null);

            if (!isset($groups[$award_id])) {
                $groups[$award_id] = ['title' => trim($title), 'winners' => []];
            }
            $groups[$award_id]['winners'][] = [
                'ticket_no' => trim($ticket_no),
                'type'      => trim($ticket_type),
                'name'      => ($customer_name && $customer_name !== 'NULL') ? trim($customer_name) : null,
                'district'  => ($customer_district && $customer_district !== 'NULL') ? ucfirst(trim($customer_district)) : null,
                'merchant'  => trim($merchant_name),
            ];
        }
        fclose($handle);
        ksort($groups);
        return $groups;
    }
}
