@extends('admin.layouts.app')

@section('title', 'কাস্টমার কেয়ার')
@section('page-title', 'কাস্টমার কেয়ার')

@push('styles')
<style>
  .ticket-badge {
    font-family: monospace; font-size: 0.8rem;
    background: #eff6ff; color: #1d4ed8;
    border: 1px solid #bfdbfe; border-radius: 6px;
    padding: 2px 8px; display: inline-block; margin: 1px;
  }
  .search-card { border-radius: 1rem; border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
  .summary-card { border-left: 4px solid #3b82f6; border-radius: 0 1rem 1rem 0; }
  .txn-row-success { border-left: 3px solid #10b981; }
  .txn-row-failed  { border-left: 3px solid #ef4444; }
  .txn-row-pending { border-left: 3px solid #f59e0b; }
  .empty-state { text-align: center; padding: 3rem; color: #94a3b8; }
</style>
@endpush

@section('content')

{{-- Search Form --}}
<div class="card search-card mb-4">
  <div class="card-body p-4">
    <h5 class="fw-bold mb-3"><i class="fas fa-search-location me-2 text-primary"></i>কাস্টমার অনুসন্ধান</h5>
    <form method="GET" action="{{ route('admin.customer-care.index') }}">
      <label class="form-label fw-semibold">কাস্টমার মোবাইল নম্বর</label>
      <div class="d-flex gap-2 align-items-center">
        <input type="text" id="phoneInput" name="phone" class="form-control form-control-lg"
               style="max-width:320px"
               placeholder="01XXXXXXXXX" value="{{ $phone ?? '' }}"
               required>
        <button type="submit" class="btn btn-primary btn-lg px-4">
          <i class="fas fa-search me-2"></i>অনুসন্ধান
        </button>
        @if($phone)
        <a href="{{ route('admin.customer-care.index') }}" class="btn btn-outline-secondary btn-lg px-4">
          <i class="fas fa-times me-2"></i>পরিষ্কার
        </a>
        @endif
      </div>
      <div class="form-text mt-1">উদাহরণ: 01712345678</div>
    </form>
  </div>
</div>

@if($phone)

  @if($transactions->isEmpty())
    <div class="card search-card">
      <div class="empty-state">
        <i class="fas fa-user-slash fa-3x mb-3 d-block"></i>
        <h5>কোনো তথ্য পাওয়া যায়নি</h5>
        <p class="mb-0">{{ $phone }} নম্বরে কোনো লেনদেন নেই।</p>
      </div>
    </div>
  @else

    {{-- Customer Summary --}}
    <div class="card summary-card mb-4">
      <div class="card-body p-4">
        <div class="row align-items-center">
          <div class="col-auto">
            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                 style="width:60px;height:60px">
              <i class="fas fa-user fa-2x text-primary"></i>
            </div>
          </div>
          <div class="col">
            <h5 class="fw-bold mb-0">{{ $phone }}</h5>
            <div class="text-muted small mt-1">
              @foreach($summary['operators'] as $op)
                <span class="badge bg-secondary me-1">{{ $op }}</span>
              @endforeach
            </div>
          </div>
          <div class="col-auto d-flex gap-2">
            @if($summary['successful'] > 0)
            <a href="{{ route('ticket.download-all-pdf', ['phone' => $phone]) }}"
               class="btn btn-success" target="_blank">
              <i class="fas fa-file-pdf me-2"></i>সব টিকেট PDF ডাউনলোড
            </a>
            @endif
          </div>
        </div>

        <hr class="my-3">

        <div class="row text-center g-3">
          <div class="col-6 col-md-3">
            <div class="fw-bold fs-4 text-primary">{{ $summary['total_transactions'] }}</div>
            <div class="text-muted small">মোট লেনদেন</div>
          </div>
          <div class="col-6 col-md-3">
            <div class="fw-bold fs-4 text-success">{{ $summary['successful'] }}</div>
            <div class="text-muted small">সফল লেনদেন</div>
          </div>
          <div class="col-6 col-md-3">
            <div class="fw-bold fs-4 text-info">{{ $summary['total_tickets'] }}</div>
            <div class="text-muted small">মোট টিকেট</div>
          </div>
          <div class="col-6 col-md-3">
            <div class="fw-bold fs-4 text-dark">৳{{ number_format($summary['total_spent'], 2) }}</div>
            <div class="text-muted small">মোট খরচ</div>
          </div>
        </div>

        @if($summary['last_purchase'])
        <div class="text-muted small mt-2">
          <i class="fas fa-clock me-1"></i>সর্বশেষ ক্রয়:
          {{ \Carbon\Carbon::parse($summary['last_purchase'])->format('d M Y, h:i A') }}
        </div>
        @endif
      </div>
    </div>

    {{-- Transactions Table --}}
    <div class="card search-card">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0"><i class="fas fa-list me-2 text-muted"></i>লেনদেনের ইতিহাস</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th class="px-4">তারিখ</th>
                <th>রেফারেন্স</th>
                <th>অপারেটর</th>
                <th>পরিমাণ</th>
                <th>স্ট্যাটাস</th>
                <th>টিকেট নম্বর</th>
                <th>SMS</th>
                <th>SMS পাঠান</th>
                <th class="text-end px-4">ডাউনলোড</th>
              </tr>
            </thead>
            <tbody>
              @foreach($transactions as $txn)
              <tr class="txn-row-{{ $txn->status }}">
                <td class="px-4">
                  <div class="small">{{ \Carbon\Carbon::parse($txn->created_at)->format('d M Y') }}</div>
                  <div class="text-muted" style="font-size:.75rem">{{ \Carbon\Carbon::parse($txn->created_at)->format('h:i A') }}</div>
                </td>
                <td>
                  <code class="small">{{ $txn->txn_ref }}</code>
                </td>
                <td>
                  <span class="badge
                    @if($txn->operator === 'Grameenphone') bg-info text-dark
                    @elseif($txn->operator === 'Robi') bg-warning text-dark
                    @elseif($txn->operator === 'Banglalink') bg-danger
                    @else bg-secondary
                    @endif">
                    {{ $txn->operator }}
                  </span>
                </td>
                <td>৳{{ number_format($txn->amount, 2) }}</td>
                <td>
                  @if($txn->status === 'success')
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>সফল</span>
                  @elseif($txn->status === 'failed')
                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i>ব্যর্থ</span>
                  @elseif($txn->status === 'pending')
                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>অপেক্ষমান</span>
                  @else
                    <span class="badge bg-secondary">{{ $txn->status }}</span>
                  @endif
                </td>
                <td>
                  @if(!empty($txn->resolved_ticket_nos))
                    @foreach($txn->resolved_ticket_nos as $tno)
                      <span class="ticket-badge">{{ $tno }}</span>
                    @endforeach
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>
                <td>
                  @php
                    $smsSentViaLog = !$txn->smsLog && $txn->consentLogs->contains('step', 'sms_sent');
                    $smsFailedViaLog = !$txn->smsLog && $txn->consentLogs->contains('step', 'sms_failed');
                  @endphp
                  @if($txn->smsLog)
                    @php $smsOk = !in_array($txn->smsLog->status_message, ['Failed', 'Unknown', '']) && $txn->smsLog->status_message; @endphp
                    @if($smsOk)
                      <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="fas fa-check me-1"></i>পাঠানো হয়েছে
                      </span>
                    @else
                      <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                        <i class="fas fa-times me-1"></i>ব্যর্থ
                      </span>
                    @endif
                  @elseif($smsSentViaLog)
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                      <i class="fas fa-check me-1"></i>পাঠানো হয়েছে
                    </span>
                  @elseif($smsFailedViaLog)
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                      <i class="fas fa-times me-1"></i>ব্যর্থ
                    </span>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>
                <td>
                  @if($txn->status === 'success')
                    <form method="POST" action="{{ route('admin.reports.sms.retry', $txn) }}">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2"
                              onclick="return confirm('{{ $txn->phone }} নম্বরে SMS পাঠাবেন?')"
                              title="SMS পাঠান">
                        <i class="fas fa-paper-plane me-1"></i>SMS
                      </button>
                    </form>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>
                <td class="text-end px-4">
                  @if($txn->status === 'success' && !empty($txn->resolved_ticket_nos))
                    <a href="{{ route('ticket.download-pdf', ['ref' => $txn->txn_ref]) }}"
                       class="btn btn-sm btn-outline-primary" target="_blank"
                       title="এই লেনদেনের টিকেট ডাউনলোড করুন">
                      <i class="fas fa-download me-1"></i>PDF
                    </a>
                  @else
                    <span class="text-muted small">—</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- GP Recharge Log --}}
    @php
      $rechargeSteps = ['recharge_initiated', 'recharge_failed', 'recharge_callback_received', 'recharge_charge_failed'];
      $rechargeTransactions = $transactions->filter(
        fn($t) => $t->operator === 'Grameenphone' &&
        $t->consentLogs->whereIn('step', $rechargeSteps)->isNotEmpty()
      );
    @endphp

    @if($rechargeTransactions->isNotEmpty())
    <div class="card search-card mt-4">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0"><i class="fas fa-network-wired me-2 text-warning"></i>GP রিচার্জ লগ</h6>
      </div>
      <div class="card-body p-0">
        @foreach($rechargeTransactions as $txn)
        <div class="border-bottom p-4">
          <div class="fw-semibold mb-3">
            <code>{{ $txn->txn_ref }}</code>
            <span class="badge bg-info text-dark ms-2">Grameenphone</span>
            <span class="text-muted small ms-2">{{ \Carbon\Carbon::parse($txn->created_at)->format('d M Y, h:i A') }}</span>
          </div>

          {{-- Mirrors: Log::info('GP recharge prepare request', [...]) --}}
          <div class="mb-3">
            <div class="text-muted small fw-semibold mb-1 text-uppercase" style="letter-spacing:.05em">GP recharge prepare request</div>
            <table class="table table-sm table-bordered mb-0" style="width:auto;font-size:.8rem">
              <tr><th class="bg-light">original</th><td><code>{{ $txn->txn_ref }}</code></td></tr>
              <tr><th class="bg-light">recharge</th><td><code>{{ $txn->gp_recharge_ref ?? '—' }}</code></td></tr>
              <tr><th class="bg-light">acr</th><td><code>{{ $txn->gp_customer_ref ?? '—' }}</code></td></tr>
            </table>
          </div>

          {{-- Mirrors: Log::info('GP recharge prepare response', [...]) + subsequent steps --}}
          @foreach($txn->consentLogs->whereIn('step', $rechargeSteps)->sortBy('created_at') as $log)
          <div class="mb-3">
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge bg-secondary">{{ $log->step }}</span>
              <span class="text-muted small">{{ \Carbon\Carbon::parse($log->created_at)->format('h:i:s A') }}</span>
              @if($log->note)
                <span class="text-danger small">{{ $log->note }}</span>
              @endif
            </div>
            @if($log->data)
            <pre class="bg-light border rounded p-2 mb-0" style="font-size:.75rem;max-height:220px;overflow:auto">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
          </div>
          @endforeach

        </div>
        @endforeach
      </div>
    </div>
    @endif

  @endif

  {{-- Blink Transaction Status — always show when phone searched --}}
  @if($blinkStatus && $blinkStatus['success'])
  @php $bd = $blinkStatus['data']; @endphp
  <div class="card search-card mt-4">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
      <h6 class="fw-bold mb-0"><i class="fas fa-mobile-alt me-2 text-danger"></i>Blink (Banglalink) ট্রানজেকশন স্ট্যাটাস</h6>
      <div class="small text-muted">
        মোট: <strong>{{ $bd['totalRows'] ?? 0 }}</strong> &nbsp;|&nbsp;
        সর্বশেষ: <strong>{{ $bd['latestDate'] ?? '—' }}</strong> &nbsp;|&nbsp;
        স্ট্যাটাস: <span class="badge bg-{{ ($bd['latestStatus'] ?? '') === 'A' ? 'success' : 'secondary' }}">{{ $bd['latestStatus'] ?? '—' }}</span>
        &nbsp;|&nbsp; Action: <span class="badge bg-secondary">{{ $bd['latestAction'] ?? '—' }}</span>
      </div>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="px-3">তারিখ / সময়</th>
              <th>Action</th>
              <th>Command</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Reason</th>
              <th>Transaction ID</th>
            </tr>
          </thead>
          <tbody>
            @foreach($bd['records'] ?? [] as $rec)
            <tr>
              <td class="px-3">
                <div class="small">{{ $rec['date'] ?? '—' }}</div>
                <div class="text-muted" style="font-size:.75rem">{{ $rec['time'] ?? '' }}</div>
              </td>
              <td>
                <span class="badge bg-{{ ($rec['action'] ?? '') === 'Ondemand' ? 'primary' : 'secondary' }}">
                  {{ $rec['action'] ?? '—' }}
                </span>
              </td>
              <td><span class="text-muted small">{{ $rec['command'] ?? '—' }}</span></td>
              <td>
                <span class="badge bg-{{ ($rec['type'] ?? '') === 'PAYMENT' ? 'warning text-dark' : 'light text-dark border' }}">
                  {{ $rec['type'] ?? '—' }}
                </span>
              </td>
              <td>{{ ($rec['chargeAmount'] ?? 0) > 0 ? '৳' . $rec['chargeAmount'] : '—' }}</td>
              <td>
                @php $reason = $rec['reason'] ?? ''; @endphp
                <span class="small {{ stripos($reason, 'success') !== false ? 'text-success' : (strtolower($reason) === 'low balance' ? 'text-danger' : 'text-muted') }}">
                  {{ $reason ?: '—' }}
                </span>
              </td>
              <td><code style="font-size:.72rem">{{ $rec['transactionId'] ?? '—' }}</code></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @elseif($blinkStatus && !$blinkStatus['success'])
  <div class="card search-card mt-4">
    <div class="card-body text-muted small p-3">
      <i class="fas fa-info-circle me-1"></i>Blink API থেকে কোনো ডেটা পাওয়া যায়নি।
    </div>
  </div>
  @endif

@else
  {{-- Idle state --}}
  <div class="card search-card">
    <div class="empty-state">
      <i class="fas fa-headset fa-3x mb-3 d-block text-primary opacity-50"></i>
      <h5 class="text-muted">কাস্টমারের মোবাইল নম্বর দিন</h5>
      <p class="mb-0 text-muted small">উপরের ফর্মে নম্বর লিখে অনুসন্ধান বাটন চাপুন।</p>
    </div>
  </div>
@endif

@push('scripts')
<script>
function normalizePhone(raw) {
    let d = raw.replace(/\D/g, '');
    if (d.startsWith('880') && d.length === 13) d = '0' + d.slice(3);
    else if (d.startsWith('88') && d.length === 13)  d = '0' + d.slice(2);
    return d;
}

const phoneInput = document.getElementById('phoneInput');

phoneInput.addEventListener('input', function () {
    const normalized = normalizePhone(this.value);
    if (normalized !== this.value) this.value = normalized;
});

phoneInput.closest('form').addEventListener('submit', function () {
    phoneInput.value = normalizePhone(phoneInput.value);
});
</script>
@endpush

@endsection
