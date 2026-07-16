<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsentLog;
use App\Models\RechargeImport;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Services\Blink\BlinkService;
use App\Services\DCB\DCBFactory;
use App\Services\DCB\GpConsentService;
use App\Services\SMS\RobiSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RechargeImportController extends Controller
{
    public function index()
    {
        // Auto-mark rows where customer already has a matching successful transaction
        DB::statement("
            UPDATE recharge_imports ri
            INNER JOIN transactions t
                ON  t.phone  = ri.msisdn
                AND t.status = 'success'
                AND t.qty    = ri.ticket_count
                AND t.confirmed_at BETWEEN DATE_SUB(ri.trx_time, INTERVAL 10 MINUTE)
                                       AND DATE_ADD(ri.trx_time, INTERVAL 10 MINUTE)
            SET ri.ticket_status = 2
            WHERE ri.ticket_status = 0
              AND ri.trx_time IS NOT NULL
        ");

        $imports = RechargeImport::orderByDesc('created_at')->paginate(50);
        return view('admin.recharge-imports.index', compact('imports'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $filename = $request->file('file')->getClientOriginalName();
        $handle   = fopen($request->file('file')->getRealPath(), 'r');

        $inserted = 0;
        $skipped  = 0;
        $header   = true;
        $seen     = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($header) { $header = false; continue; }
            if (count($row) < 10) continue;

            [$trx_time, $msisdn, $invoice_no, $dob_msisdn, $dob_amount,
             $sof_status, $ers_status, $dob_status, $remarks, $ticket_count] = $row;

            $msisdn     = $this->normalizePhone(trim($msisdn));
            $invoice_no = trim($invoice_no);

            $key = $msisdn . '|' . $invoice_no;
            if (isset($seen[$key])) { $skipped++; continue; }
            $seen[$key] = true;

            $affected = DB::table('recharge_imports')->insertOrIgnore([
                'source_file'  => $filename,
                'trx_time'     => $trx_time ?: null,
                'msisdn'       => $msisdn,
                'invoice_no'   => $invoice_no,
                'dob_msisdn'   => trim($dob_msisdn) ?: null,
                'dob_amount'   => (float) $dob_amount,
                'sof_status'   => trim($sof_status) ?: null,
                'ers_status'   => trim($ers_status) ?: null,
                'dob_status'   => trim($dob_status) ?: null,
                'remarks'      => trim($remarks) ?: null,
                'ticket_count' => (int) ($ticket_count ?: 1),
                'ticket_status'=> 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $affected ? $inserted++ : $skipped++;
        }

        fclose($handle);

        return back()->with('success', "{$inserted} রো যোগ হয়েছে, {$skipped} ডুপ্লিকেট এড়িয়ে গেছে।");
    }

    public function bulkGenerate(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']]);

        $imports = RechargeImport::whereIn('id', $request->ids)
            ->where('ticket_status', 0)
            ->get();

        if ($imports->isEmpty()) {
            return back()->with('error', 'নির্বাচিত সব রো ইতোমধ্যে তৈরি হয়েছে বা পাওয়া যায়নি।');
        }

        $generated = 0;
        $failed    = [];

        foreach ($imports as $import) {
            $phone    = $import->msisdn;
            $qty      = $import->ticket_count;
            $operator = DCBFactory::detectOperator($phone);

            if (!$operator) {
                $failed[] = $phone . ' (অপারেটর শনাক্ত হয়নি)';
                continue;
            }

            if ($this->alreadyHasTicket($import)) {
                $import->update(['ticket_status' => 2]);
                $failed[] = $phone . ' (ইতোমধ্যে টিকেট ছিল)';
                continue;
            }

            try {
                $result = DB::transaction(function () use ($phone, $qty, $operator) {
                    $tier = DB::table('tickets')
                        ->where('operator', $operator)
                        ->where('status', 0)
                        ->whereNotNull('series')
                        ->min('sale_tier');

                    if ($tier === null) return null;

                    $candidateIds = Ticket::where('operator', $operator)
                        ->where('status', 0)
                        ->where('sale_tier', $tier)
                        ->inRandomOrder()
                        ->limit($qty * 3)
                        ->pluck('id');

                    if ($candidateIds->isEmpty()) return null;

                    $tickets = Ticket::whereIn('id', $candidateIds)
                        ->where('status', 0)
                        ->orderBy('id')
                        ->limit($qty)
                        ->lockForUpdate()
                        ->get();

                    if ($tickets->count() < $qty) return null;

                    $ticketIds = $tickets->pluck('id')->toArray();
                    $txnRef    = 'RCHG' . strtoupper(Str::random(12));

                    $txn = Transaction::create(array_filter([
                        'txn_ref'      => $txnRef,
                        'ticket_id'    => $tickets->first()->id,
                        'ticket_ids'   => $ticketIds,
                        'phone'        => $phone,
                        'operator'     => $operator,
                        'amount'       => $qty * 20,
                        'qty'          => $qty,
                        'status'       => 'success',
                        'confirmed_at' => now(),
                    ]));

                    Ticket::whereIn('id', $ticketIds)->update([
                        'status'   => 1,
                        'phone'    => $phone,
                        'operator' => $operator,
                        'sold_at'  => now(),
                    ]);

                    return ['txn' => $txn, 'tickets' => $tickets];
                });
            } catch (\Throwable $e) {
                Log::error('Bulk recharge import ticket failed', ['phone' => $phone, 'err' => $e->getMessage()]);
                $failed[] = $phone . ' (ত্রুটি)';
                continue;
            }

            if (!$result) {
                $failed[] = $phone . ' (পর্যাপ্ত ' . $operator . ' টিকেট নেই)';
                continue;
            }

            $txn     = $result['txn'];
            $tickets = $result['tickets'];

            $import->update(['ticket_status' => 1, 'txn_ref' => $txn->txn_ref]);

            /** @var \App\Models\User $admin */
            $admin = auth()->user();
            ConsentLog::record($txn->txn_ref, $phone, 'recharge_import_ticket_issued', [
                'import_id'  => $import->id,
                'invoice_no' => $import->invoice_no,
                'ticket_ids' => $txn->ticket_ids,
                'operator'   => $operator,
                'by'         => $admin?->name,
            ]);

            $ticketNos = $tickets->pluck('ticket_no')->implode(', ');
            $this->sendSms($txn, $ticketNos);
            $generated++;
        }

        $page = (int) $request->input('page', 1);
        $msg  = "{$generated}টি টিকেট সফলভাবে তৈরি হয়েছে।";

        if ($failed) {
            $msg .= ' ব্যর্থ: ' . implode('; ', $failed);
            return redirect()->route('admin.recharge-imports.index', ['page' => $page])->with('error', $msg);
        }

        return redirect()->route('admin.recharge-imports.index', ['page' => $page])->with('success', $msg);
    }

    public function generateTicket(Request $request, RechargeImport $import)
    {
        $page = (int) $request->input('page', 1);

        if ($import->ticket_status === 1) {
            return redirect()->route('admin.recharge-imports.index', ['page' => $page])
                ->with('error', 'ইতোমধ্যে টিকেট তৈরি হয়েছে।');
        }

        if ($this->alreadyHasTicket($import)) {
            $import->update(['ticket_status' => 2]);
            return redirect()->route('admin.recharge-imports.index', ['page' => $page])
                ->with('error', $import->msisdn . ' — এই গ্রাহক ইতোমধ্যে টিকেট পেয়েছেন (DB-তে ম্যাচিং সফল লেনদেন পাওয়া গেছে)।');
        }

        $phone    = $import->msisdn;
        $qty      = $import->ticket_count;
        $operator = DCBFactory::detectOperator($phone);

        if (!$operator) {
            return redirect()->route('admin.recharge-imports.index', ['page' => $page])
                ->with('error', 'অপারেটর শনাক্ত করা যায়নি: ' . $phone);
        }

        try {
            $result = DB::transaction(function () use ($phone, $qty, $operator) {
                $tier = DB::table('tickets')
                    ->where('operator', $operator)
                    ->where('status', 0)
                    ->whereNotNull('series')
                    ->min('sale_tier');

                if ($tier === null) return null;

                $candidateIds = Ticket::where('operator', $operator)
                    ->where('status', 0)
                    ->where('sale_tier', $tier)
                    ->inRandomOrder()
                    ->limit($qty * 3)
                    ->pluck('id');

                if ($candidateIds->isEmpty()) return null;

                $tickets = Ticket::whereIn('id', $candidateIds)
                    ->where('status', 0)
                    ->orderBy('id')
                    ->limit($qty)
                    ->lockForUpdate()
                    ->get();

                if ($tickets->count() < $qty) return null;

                $ticketIds = $tickets->pluck('id')->toArray();
                $txnRef    = 'RCHG' . strtoupper(Str::random(12));

                $txn = Transaction::create(array_filter([
                    'txn_ref'      => $txnRef,
                    'ticket_id'    => $tickets->first()->id,
                    'ticket_ids'   => $ticketIds,
                    'phone'        => $phone,
                    'operator'     => $operator,
                    'amount'       => $qty * 20,
                    'qty'          => $qty,
                    'status'       => 'success',
                    'confirmed_at' => now(),
                ]));

                Ticket::whereIn('id', $ticketIds)->update([
                    'status'   => 1,
                    'phone'    => $phone,
                    'operator' => $operator,
                    'sold_at'  => now(),
                ]);

                return ['txn' => $txn, 'tickets' => $tickets];
            });
        } catch (\Throwable $e) {
            Log::error('Recharge import ticket failed', ['phone' => $phone, 'err' => $e->getMessage()]);
            return redirect()->route('admin.recharge-imports.index', ['page' => $page])
                ->with('error', 'সিস্টেম ত্রুটি: ' . $e->getMessage());
        }

        if (!$result) {
            return redirect()->route('admin.recharge-imports.index', ['page' => $page])
                ->with('error', 'পর্যাপ্ত ' . $operator . ' টিকেট নেই।');
        }

        $txn     = $result['txn'];
        $tickets = $result['tickets'];

        $import->update(['ticket_status' => 1, 'txn_ref' => $txn->txn_ref]);

        /** @var \App\Models\User $admin */
        $admin = auth()->user();
        ConsentLog::record($txn->txn_ref, $phone, 'recharge_import_ticket_issued', [
            'import_id'  => $import->id,
            'invoice_no' => $import->invoice_no,
            'ticket_ids' => $txn->ticket_ids,
            'operator'   => $operator,
            'by'         => $admin?->name,
        ]);

        $ticketNos = $tickets->pluck('ticket_no')->implode(', ');
        $this->sendSms($txn, $ticketNos);

        $page = (int) $request->input('page', 1);

        return redirect()->route('admin.recharge-imports.index', ['page' => $page])
            ->with('success', "টিকেট তৈরি হয়েছে: {$ticketNos} | রেফারেন্স: {$txn->txn_ref}");
    }

    private function alreadyHasTicket(RechargeImport $import): bool
    {
        if (!$import->trx_time) return false;

        return Transaction::where('phone', $import->msisdn)
            ->where('status', 'success')
            ->where('qty', $import->ticket_count)
            ->whereBetween('confirmed_at', [
                $import->trx_time->copy()->subMinutes(10),
                $import->trx_time->copy()->addMinutes(10),
            ])
            ->exists();
    }

    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);
        if (strlen($clean) === 13 && str_starts_with($clean, '880')) return '0' . substr($clean, 3);
        if (strlen($clean) === 10 && $clean[0] === '1') return '0' . $clean;
        return $clean;
    }

    private function sendSms(Transaction $transaction, string $ticketNos): void
    {
        $phone       = $transaction->phone;
        $downloadUrl = route('ticket.download-all-pdf', ['phone' => $phone]);
        $message     = "প্রিয় গ্রাহক, আপনার নতুন বৈধ টিকিট নম্বর: {$ticketNos}। অনুগ্রহ করে এই নম্বরটিই আপনার অফিসিয়াল টিকিট হিসেবে ব্যবহার করুন। আপনার সহযোগিতার জন্য ধন্যবাদ। – BPKS\n\nDownload: {$downloadUrl}";

        $sent = false;

        try {
            if ($transaction->operator === 'Grameenphone') {
                $acr = Transaction::where('phone', $phone)
                    ->where('operator', 'Grameenphone')
                    ->whereNotNull('gp_customer_ref')
                    ->orderByDesc('id')
                    ->value('gp_customer_ref');

                $sent = $acr
                    ? (new GpConsentService())->sendSms($acr, $phone, $message, $transaction->txn_ref)
                    : false;
                $note = $sent ? null : ($acr ? 'GP SMS failed' : 'GP SMS skipped — no ACR found');
            } else {
                $sent = match ($transaction->operator) {
                    'Banglalink' => (new BlinkService())->sendSms($phone, $message, $transaction->txn_ref),
                    default      => (new RobiSmsService())->send($phone, $message, $transaction->txn_ref),
                };
                $note = $sent ? null : 'SMS service returned false';
            }

            $step = $sent ? 'sms_sent' : 'sms_failed';
        } catch (\Throwable $e) {
            Log::error('Recharge import SMS error', ['txn' => $transaction->txn_ref, 'err' => $e->getMessage()]);
            $step = 'sms_failed';
            $note = $e->getMessage();
        }

        ConsentLog::record($transaction->txn_ref, $phone, $step, ['ticket_nos' => $ticketNos], $note ?? null);
    }
}
