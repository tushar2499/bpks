@extends('admin.layouts.app')
@section('title', 'রিপ্লেসমেন্ট টিকেট')
@section('page-title', 'রিপ্লেসমেন্ট টিকেট ইস্যু')

@section('content')

{{-- Issue Form --}}
<div class="row mb-4">
  <div class="col-lg-5">
    <div class="card" style="border-radius:1rem;border:none;">
      <div class="card-header bg-white border-bottom py-3 px-4">
        <h6 class="fw-bold mb-0"><i class="fas fa-exchange-alt me-2 text-warning"></i>নতুন রিপ্লেসমেন্ট টিকেট ইস্যু</h6>
      </div>
      <div class="card-body p-4">
        <p class="text-muted small mb-4">
          <i class="fas fa-info-circle me-1 text-primary"></i>
          স্টেজিং সার্ভার থেকে কেনা টিকেটের বদলে প্রোডাকশনের বৈধ টিকেট ইস্যু করুন।
          অপারেটর স্বয়ংক্রিয়ভাবে MSISDN থেকে শনাক্ত হবে।
        </p>

        @if($errors->any())
          <div class="alert alert-danger py-2">
            <ul class="mb-0 ps-3">
              @foreach($errors->all() as $e)<li class="small">{{ $e }}</li>@endforeach
            </ul>
          </div>
        @endif

        <form method="POST" action="{{ route('admin.replacement-tickets.store') }}" id="replaceForm">
          @csrf

          <div class="mb-3">
            <label class="form-label fw-semibold">MSISDN (মোবাইল নম্বর) <span class="text-danger">*</span></label>
            <input type="text" name="msisdn" id="msisdnInput" class="form-control form-control-lg"
                   value="{{ old('msisdn') }}" placeholder="01XXXXXXXXX"
                   inputmode="tel" maxlength="15" required autofocus>
            <div class="form-text d-flex align-items-center gap-2 mt-1">
              <span>অপারেটর:</span>
              <span id="detectedOperator" class="badge bg-secondary">শনাক্ত হয়নি</span>
              <span class="text-muted" style="font-size:.7rem">(013/017→GP · 014/019→BL · 016/018→Robi · 015→TT)</span>
            </div>
          </div>

          <div class="mb-3 d-none" id="acrField">
            <label class="form-label fw-semibold">GP ACR (Customer Ref) <span class="text-danger">*</span></label>
            <input type="text" name="acr" id="acrInput" class="form-control form-control-lg"
                   value="{{ old('acr') }}" placeholder="ACR নম্বর দিন">
            <div class="form-text">Grameenphone গ্রাহকের ACR ছাড়া SMS পাঠানো সম্ভব নয়।</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">টিকেট সংখ্যা <span class="text-danger">*</span></label>
            <select name="qty" class="form-select form-select-lg" required>
              @for($i = 1; $i <= 10; $i++)
                <option value="{{ $i }}" {{ old('qty', 1) == $i ? 'selected' : '' }}>{{ $i }}টি</option>
              @endfor
            </select>
          </div>

          <button type="submit" class="btn btn-warning w-100 py-2 fw-bold text-dark" id="submitBtn"
                  onclick="return confirm('এই গ্রাহককে রিপ্লেসমেন্ট টিকেট ইস্যু করবেন?')">
            <i class="fas fa-ticket-alt me-2"></i> রিপ্লেসমেন্ট টিকেট ইস্যু করুন
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Report Table --}}
<div class="card" style="border-radius:1rem;border:none;">
  <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
    <h6 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>ইস্যু করা রিপ্লেসমেন্ট টিকেট</h6>
    <span class="badge bg-secondary">মোট: {{ $transactions->total() }}</span>
  </div>

  @if($transactions->isEmpty())
    <div class="card-body text-center text-muted py-5">
      <i class="fas fa-inbox fa-2x mb-2"></i>
      <p class="mb-0">এখনো কোনো রিপ্লেসমেন্ট টিকেট ইস্যু হয়নি।</p>
    </div>
  @else
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="px-3">তারিখ</th>
              <th>ফোন</th>
              <th>অপারেটর</th>
              <th>টিকেট নম্বর</th>
              <th>পরিমাণ</th>
              <th>TXN Ref</th>
              <th>SMS</th>
            </tr>
          </thead>
          <tbody>
            @foreach($transactions as $txn)
            <tr>
              <td class="px-3">
                <div class="small">{{ $txn->confirmed_at?->format('d M Y') ?? '—' }}</div>
                <div class="text-muted" style="font-size:.75rem">{{ $txn->confirmed_at?->format('h:i A') }}</div>
              </td>
              <td><code>{{ $txn->phone }}</code></td>
              <td>
                <span class="badge bg-{{ match($txn->operator) {
                  'Grameenphone' => 'success',
                  'Banglalink'   => 'danger',
                  'Robi'         => 'warning text-dark',
                  'Teletalk'     => 'info text-dark',
                  default        => 'secondary',
                } }}">{{ $txn->operator }}</span>
              </td>
              <td>
                @foreach($txn->resolved_ticket_nos ?? [] as $no)
                  <span class="badge bg-light text-dark border me-1" style="font-size:.75rem">{{ $no }}</span>
                @endforeach
              </td>
              <td>৳{{ number_format($txn->amount, 2) }}</td>
              <td><code style="font-size:.72rem">{{ $txn->txn_ref }}</code></td>
              <td>
                @if($txn->smsLog && strtolower($txn->smsLog->response ?? '') === 'sent')
                  <span class="badge bg-success"><i class="fas fa-check me-1"></i>পাঠানো হয়েছে</span>
                @else
                  <div class="d-flex flex-column gap-1">
                    @if($txn->smsLog)
                      <span class="badge bg-danger"><i class="fas fa-times me-1"></i>ব্যর্থ</span>
                    @else
                      <span class="badge bg-secondary">পাঠানো হয়নি</span>
                    @endif
                    <form method="POST" action="{{ route('admin.replacement-tickets.resend-sms', $txn->id) }}">
                      @csrf
                      @if($txn->operator === 'Grameenphone')
                        <input type="text" name="acr" class="form-control form-control-sm mb-1"
                               placeholder="GP ACR" value="{{ $txn->gp_customer_ref ?? '' }}"
                               style="font-size:.72rem;min-width:130px;">
                      @endif
                      <button type="submit" class="btn btn-sm btn-outline-primary" style="font-size:.72rem;">
                        <i class="fas fa-paper-plane me-1"></i>SMS পাঠান
                      </button>
                    </form>
                  </div>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer bg-white border-top py-3 px-4">
      {{ $transactions->links() }}
    </div>
    @endif
  @endif
</div>

@endsection

@push('scripts')
<script>
const PREFIX_MAP = {
  '013': 'Grameenphone', '017': 'Grameenphone',
  '014': 'Banglalink',   '019': 'Banglalink',
  '016': 'Robi',         '018': 'Robi',
  '015': 'Teletalk',
};
const OP_COLOR = {
  'Grameenphone': 'success', 'Banglalink': 'danger',
  'Robi': 'warning text-dark', 'Teletalk': 'info text-dark',
};

function detectOperator(val) {
  const clean = val.replace(/\D/g, '');
  const norm  = (clean.length === 10 && clean[0] === '1') ? '0' + clean : clean;
  return PREFIX_MAP[norm.substring(0, 3)] || null;
}

const msisdnInput       = document.getElementById('msisdnInput');
const acrField          = document.getElementById('acrField');
const acrInput          = document.getElementById('acrInput');
const detectedOperator  = document.getElementById('detectedOperator');

const lookupAcrUrl = @json(route('admin.replacement-tickets.lookup-acr'));
let acrLookupTimer = null;

msisdnInput.addEventListener('input', function() {
  const op = detectOperator(this.value);
  if (op) {
    detectedOperator.className = 'badge bg-' + (OP_COLOR[op] || 'secondary');
    detectedOperator.textContent = op;
  } else {
    detectedOperator.className = 'badge bg-secondary';
    detectedOperator.textContent = 'শনাক্ত হয়নি';
  }
  const isGP = op === 'Grameenphone';
  acrField.classList.toggle('d-none', !isGP);
  acrInput.required = isGP;

  if (isGP && this.value.replace(/\D/g,'').length >= 11) {
    clearTimeout(acrLookupTimer);
    acrLookupTimer = setTimeout(() => {
      fetch(lookupAcrUrl + '?phone=' + encodeURIComponent(this.value))
        .then(r => r.json())
        .then(data => {
          if (data.acr) {
            acrInput.value = data.acr;
            acrInput.style.borderColor = '#198754';
          } else {
            acrInput.value = '';
            acrInput.style.borderColor = '';
          }
        })
        .catch(() => {});
    }, 400);
  } else {
    acrInput.value = '';
    acrInput.style.borderColor = '';
  }
});

// Restore on page load (validation error re-render)
if (msisdnInput.value) msisdnInput.dispatchEvent(new Event('input'));

document.getElementById('replaceForm').addEventListener('submit', function(e) {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> প্রক্রিয়াকরণ হচ্ছে...';
});
</script>
@endpush
