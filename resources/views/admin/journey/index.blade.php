@extends('admin.layouts.app')

@section('title', 'Customer Journey')
@section('page-title', 'কাস্টমার জার্নি রিপোর্ট')

@push('styles')
<style>
/* ── table badges ─────────────────────────────── */
.sbadge { font-size:.72rem; padding:.28em .6em; border-radius:.4rem; font-weight:700; }
.s-success  { background:#d1fae5; color:#065f46; }
.s-pending  { background:#fef3c7; color:#92400e; }
.s-failed   { background:#fee2e2; color:#991b1b; }
.s-cancelled{ background:#f3f4f6; color:#374151; }

/* ── journey modal ────────────────────────────── */
.jmodal .modal-content { border:none; border-radius:1.25rem; overflow:hidden; }
.jmodal .modal-header  { background:linear-gradient(135deg,#1e3a8a,#2563eb); color:#fff; padding:1.2rem 1.5rem; }
.jmodal .modal-header .btn-close { filter:invert(1); }
.jmodal .modal-body    { padding:1.5rem; background:#f8fafc; }

/* ── hero row ─────────────────────────────────── */
.jm-hero { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
.jm-pill {
  background:#fff; border-radius:.75rem; padding:.55rem .9rem;
  box-shadow:0 1px 4px rgba(0,0,0,.08); font-size:.82rem; flex:1; min-width:120px;
}
.jm-pill .lbl { color:#94a3b8; font-size:.68rem; text-transform:uppercase; letter-spacing:.8px; display:block; margin-bottom:.15rem; }
.jm-pill .val { font-weight:700; color:#1e293b; }

/* ── section card ─────────────────────────────── */
.jm-card {
  background:#fff; border-radius:.9rem; padding:1rem 1.1rem;
  box-shadow:0 1px 4px rgba(0,0,0,.07); margin-bottom:.9rem;
}
.jm-card-title {
  font-size:.72rem; text-transform:uppercase; letter-spacing:1px;
  font-weight:700; color:#64748b; margin-bottom:.85rem;
  display:flex; align-items:center; gap:.4rem;
}

/* ── step timeline ────────────────────────────── */
.tl-wrap { position:relative; padding-left:1.8rem; }
.tl-wrap::before {
  content:''; position:absolute; left:.55rem; top:0; bottom:0;
  width:2px; background:#e2e8f0;
}
.tl-item { position:relative; margin-bottom:.85rem; }
.tl-item:last-child { margin-bottom:0; }
.tl-icon {
  position:absolute; left:-1.8rem; top:.1rem;
  width:22px; height:22px; border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  font-size:.58rem; font-weight:700; z-index:1; border:2px solid #fff;
}
.tl-ok   { background:#10b981; color:#fff; }
.tl-fail { background:#ef4444; color:#fff; }
.tl-info { background:#3b82f6; color:#fff; }
.tl-warn { background:#f59e0b; color:#fff; }
.tl-step-name { font-weight:700; font-size:.8rem; color:#1e293b; }
.tl-step-time { font-size:.7rem; color:#94a3b8; margin-left:.4rem; }
.tl-step-note { font-size:.75rem; color:#64748b; margin-top:.1rem; }

/* ── detail table ─────────────────────────────── */
.dt-table td { font-size:.78rem; padding:.2rem .3rem; vertical-align:top; }
.dt-table td:first-child { color:#94a3b8; white-space:nowrap; padding-right:.8rem; font-weight:500; }
.dt-table td:last-child  { color:#1e293b; font-weight:600; word-break:break-all; }

/* ── SMS pill ─────────────────────────────────── */
.sms-ok   { border-left:3px solid #10b981; }
.sms-fail { border-left:3px solid #ef4444; }
.sms-none { border-left:3px solid #e2e8f0; color:#94a3b8; }

/* ── row hover ────────────────────────────────── */
.journey-row { cursor:pointer; transition:background .12s; }
.journey-row:hover td { background:#f0f9ff !important; }
</style>
@endpush

@section('content')
{{-- Filters --}}
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small mb-1">ফোন নম্বর</label>
        <input type="text" name="phone" class="form-control form-control-sm"
               value="{{ request('phone') }}" placeholder="01XXXXXXXXX">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">স্ট্যাটাস</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">সব</option>
          <option value="pending"   @selected(request('status')=='pending')>Pending</option>
          <option value="success"   @selected(request('status')=='success')>Success</option>
          <option value="failed"    @selected(request('status')=='failed')>Failed</option>
          <option value="cancelled" @selected(request('status')=='cancelled')>Cancelled</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">তারিখ থেকে</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">তারিখ পর্যন্ত</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i>ফিল্টার</button>
        <a href="{{ route('admin.journey.index') }}" class="btn btn-outline-secondary btn-sm">রিসেট</a>
      </div>
    </form>
  </div>
</div>

{{-- Table --}}
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
    <span class="fw-semibold">মোট: {{ $transactions->total() }} টি জার্নি</span>
    <small class="text-muted">ক্লিক করুন বিস্তারিত দেখতে</small>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th class="ps-3">তারিখ / সময়</th>
          <th>ফোন</th>
          <th>TXN REF</th>
          <th>পরিমাণ</th>
          <th>স্ট্যাটাস</th>
          <th>টিকেট নং</th>
          <th class="pe-3">DCB TXN</th>
        </tr>
      </thead>
      <tbody>
        @forelse($transactions as $txn)
        @php
          $statusClass = ['success'=>'s-success','pending'=>'s-pending','failed'=>'s-failed','cancelled'=>'s-cancelled'][$txn->status] ?? 's-cancelled';

          $logs = $txn->consentLogs->map(fn($l) => [
            'step' => $l->step,
            'note' => $l->note,
            'time' => $l->created_at?->format('H:i:s') ?? '',
          ])->toArray();

          $ticketNos = $txn->resolved_ticket_nos ?? [];
          $journeyData = json_encode([
            'phone'       => $txn->phone,
            'operator'    => $txn->operator,
            'txn_ref'     => $txn->txn_ref,
            'amount'      => '৳'.number_format($txn->amount, 2),
            'status'      => $txn->status,
            'ticket_nos'  => $ticketNos,
            'qty'         => count($ticketNos) ?: ($txn->qty ?? 1),
            'dcb_txn'     => $txn->dcb_txn_id ?? null,
            'date'        => $txn->created_at->format('d M Y H:i:s'),
            'nonce'       => $txn->nonce ?? null,
            'initiated'   => $txn->consent_initiated_at?->format('H:i:s') ?? null,
            'confirmed'   => $txn->confirmed_at?->format('H:i:s') ?? null,
            'failure'     => $txn->failure_reason ?? null,
            'dcb_resp'    => $txn->dcb_response ?? null,
            'logs'        => $logs,
            'sms' => $txn->smsLog ? [
              'status'  => $txn->smsLog->status_message,
              'sent_at' => $txn->smsLog->sent_at?->format('H:i:s') ?? null,
              'message' => $txn->smsLog->message,
            ] : null,
          ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        @endphp
        <tr class="journey-row" data-journey='{!! $journeyData !!}'>
          <td class="ps-3 text-nowrap small">{{ $txn->created_at->format('d M Y H:i:s') }}</td>
          <td class="small fw-semibold">{{ $txn->phone }}</td>
          <td class="small font-monospace" style="font-size:.7rem;">{{ $txn->txn_ref }}</td>
          <td class="small">৳{{ number_format($txn->amount, 2) }}</td>
          <td><span class="sbadge {{ $statusClass }}">{{ ucfirst($txn->status) }}</span></td>
          <td class="small font-monospace fw-bold">
            @if(count($ticketNos))
              <span class="text-muted me-1" style="font-size:.68rem;">({{ count($ticketNos) }})</span>{{ implode(', ', $ticketNos) }}
            @else
              —
            @endif
          </td>
          <td class="pe-3 small font-monospace" style="font-size:.7rem;">{{ $txn->dcb_txn_id ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="7" class="text-center text-muted py-5">
          <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>কোনো ডেটা নেই
        </td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if($transactions->hasPages())
  <div class="card-footer bg-white py-2">
    {{ $transactions->links('pagination::bootstrap-5') }}
  </div>
  @endif
</div>

{{-- Journey Modal --}}
<div class="modal fade jmodal" id="journeyModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <div class="fw-bold fs-6 mb-1" id="jm-title">কাস্টমার জার্নি</div>
          <div class="d-flex gap-2 flex-wrap" id="jm-meta" style="font-size:.78rem;opacity:.85;"></div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        {{-- Hero pills --}}
        <div class="jm-hero" id="jm-hero"></div>

        <div class="row g-3">
          {{-- Timeline --}}
          <div class="col-md-5">
            <div class="jm-card">
              <div class="jm-card-title">
                <i class="fas fa-route" style="color:#6366f1;"></i> জার্নি স্টেপ
              </div>
              <div class="tl-wrap" id="jm-timeline"></div>
            </div>
          </div>

          {{-- Right column --}}
          <div class="col-md-7">
            {{-- Transaction details --}}
            <div class="jm-card">
              <div class="jm-card-title">
                <i class="fas fa-receipt" style="color:#0891b2;"></i> ট্রানজেকশন বিবরণ
              </div>
              <table class="dt-table w-100" id="jm-details"></table>
            </div>

            {{-- SMS --}}
            <div class="jm-card" id="jm-sms-card">
              <div class="jm-card-title">
                <i class="fas fa-sms" style="color:#059669;"></i> SMS লগ
              </div>
              <div id="jm-sms"></div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
const modal    = new bootstrap.Modal(document.getElementById('journeyModal'));
const jmTitle  = document.getElementById('jm-title');
const jmMeta   = document.getElementById('jm-meta');
const jmHero   = document.getElementById('jm-hero');
const jmTl     = document.getElementById('jm-timeline');
const jmDet    = document.getElementById('jm-details');
const jmSms    = document.getElementById('jm-sms');
const jmSmsCard= document.getElementById('jm-sms-card');

const STATUS_COLORS = {
  success:   ['#065f46','#d1fae5'],
  pending:   ['#92400e','#fef3c7'],
  failed:    ['#991b1b','#fee2e2'],
  cancelled: ['#374151','#f3f4f6'],
};

const STEP_ICON = {
  consent_generated: '<i class="fas fa-link"></i>',
  redirected:        '<i class="fas fa-arrow-right"></i>',
  callback_received: '<i class="fas fa-reply"></i>',
  ticket_assigned:   '<i class="fas fa-ticket-alt"></i>',
  sms_sent:          '<i class="fas fa-check"></i>',
  sms_failed:        '<i class="fas fa-times"></i>',
  failed:            '<i class="fas fa-times"></i>',
};

function stepClass(step) {
  if (step.includes('fail') || step.includes('error')) return 'tl-fail';
  if (step.includes('success') || step.includes('ticket') || step.includes('sms_sent')) return 'tl-ok';
  if (step.includes('redirect')) return 'tl-info';
  return 'tl-info';
}

function pill(label, value, color) {
  return `<div class="jm-pill">
    <span class="lbl">${label}</span>
    <span class="val" ${color ? `style="color:${color}"` : ''}>${value}</span>
  </div>`;
}

function detRow(label, value) {
  if (!value) return '';
  return `<tr><td>${label}</td><td>${value}</td></tr>`;
}

document.querySelectorAll('.journey-row').forEach(row => {
  row.addEventListener('click', function() {
    const d = JSON.parse(this.dataset.journey);

    // Header
    jmTitle.textContent = 'কাস্টমার জার্নি — ' + d.phone;
    jmMeta.innerHTML = `<span>${d.date}</span><span>·</span><span>${d.operator ?? ''}</span>`;

    // Hero pills
    const [fc, bc] = STATUS_COLORS[d.status] ?? ['#374151','#f3f4f6'];
    jmHero.innerHTML =
      pill('পরিমাণ', d.amount) +
      pill('স্ট্যাটাস', d.status.toUpperCase(), fc).replace('jm-pill"', `jm-pill" style="background:${bc}"`) +
      pill('টিকেট সংখ্যা', d.qty ?? '—', '#1d4ed8') +
      (d.dcb_txn ? pill('DCB TXN', `<span style="font-size:.7rem;word-break:break-all">${d.dcb_txn}</span>`) : '');

    // Timeline
    if (d.logs.length === 0) {
      jmTl.innerHTML = '<div class="text-muted small">কোনো স্টেপ লগ নেই</div>';
    } else {
      jmTl.innerHTML = d.logs.map(l => `
        <div class="tl-item">
          <div class="tl-icon ${stepClass(l.step)}">${STEP_ICON[l.step] ?? '<i class="fas fa-circle"></i>'}</div>
          <div>
            <span class="tl-step-name">${l.step}</span>
            <span class="tl-step-time">${l.time}</span>
            ${l.note ? `<div class="tl-step-note">${l.note}</div>` : ''}
          </div>
        </div>
      `).join('');
    }

    // Details
    const ticketNosHtml = d.ticket_nos && d.ticket_nos.length
      ? d.ticket_nos.map(n => `<span class="font-monospace fw-bold" style="color:#b91c1c;margin-right:.35rem">${n}</span>`).join('')
      : null;
    jmDet.innerHTML =
      detRow('TXN REF',    `<span class="font-monospace" style="font-size:.7rem">${d.txn_ref}</span>`) +
      detRow('টিকেট সংখ্যা', d.qty ?? '—') +
      (ticketNosHtml ? detRow('টিকেট নম্বর', ticketNosHtml) : '') +
      detRow('Nonce',     d.nonce ? `<span class="font-monospace" style="font-size:.7rem">${d.nonce}</span>` : null) +
      detRow('Initiated', d.initiated) +
      detRow('Confirmed', d.confirmed) +
      (d.failure ? detRow('Failure', `<span class="text-danger">${d.failure}</span>`) : '') +
      (d.dcb_resp ? detRow('DCB Response', `<span class="font-monospace" style="font-size:.68rem">${d.dcb_resp}</span>`) : '');

    // SMS
    if (d.sms) {
      const ok = d.sms.status && d.sms.status.toLowerCase().includes('success');
      jmSmsCard.style.borderLeft = '';
      jmSms.innerHTML = `
        <div class="jm-card mb-0 ${ok ? 'sms-ok' : 'sms-fail'}" style="padding:.6rem .8rem;box-shadow:none;background:#f8fafc;">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-bold small" style="color:${ok ? '#065f46' : '#991b1b'}">
              <i class="fas fa-${ok ? 'check' : 'times'}-circle me-1"></i>${d.sms.status ?? '—'}
            </span>
            <span class="text-muted small">${d.sms.sent_at ?? ''}</span>
          </div>
          <div class="text-muted" style="font-size:.75rem;">${d.sms.message ?? ''}</div>
        </div>`;
    } else {
      jmSms.innerHTML = '<div class="text-muted small"><i class="fas fa-ban me-1"></i>SMS পাঠানো হয়নি</div>';
    }

    modal.show();
  });
});
</script>
@endpush
