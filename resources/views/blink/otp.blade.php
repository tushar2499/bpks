<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>OTP যাচাই | BPKS লটারি</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:      #1e40af;
      --blue-mid:  #2563eb;
      --blue-lite: #eff6ff;
      --blue-bdr:  #bfdbfe;
      --text:      #1e293b;
      --muted:     #64748b;
      --bg:        #f1f5f9;
      --radius:    .9rem;
    }
    * { box-sizing: border-box; }
    body { font-family: 'Noto Sans Bengali', sans-serif; background: var(--bg); color: var(--text); margin: 0; min-height: 100vh; display: flex; flex-direction: column; }
    .navbar-top { background: #fff; border-bottom: 1px solid #e2e8f0; padding: .65rem 1rem; box-shadow: 0 1px 8px rgba(30,64,175,.08); }
    .header-logo { height: 40px; }
    .org-name { font-weight: 700; font-size: .88rem; color: var(--blue); line-height: 1.3; }
    .page-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
    .otp-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--blue-bdr); box-shadow: 0 8px 30px rgba(30,64,175,.12); overflow: hidden; width: 100%; max-width: 400px; }
    .otp-card-header { background: linear-gradient(135deg, var(--blue), var(--blue-mid)); color: #fff; padding: 1.2rem 1.4rem; text-align: center; }
    .otp-card-body { padding: 1.4rem; }
    .otp-input { font-size: 2rem; font-weight: 800; letter-spacing: .4rem; text-align: center; border: 2px solid var(--blue-bdr); border-radius: .75rem; padding: .6rem; width: 100%; color: var(--blue); }
    .otp-input:focus { outline: none; border-color: var(--blue-mid); box-shadow: 0 0 0 3px rgba(37,99,235,.15); }
    .submit-btn { background: linear-gradient(135deg, var(--blue), var(--blue-mid)); border: none; color: #fff; font-weight: 800; font-size: 1.05rem; border-radius: .75rem; padding: .8rem; width: 100%; box-shadow: 0 6px 20px rgba(37,99,235,.3); transition: transform .15s; }
    .submit-btn:hover { transform: translateY(-1px); }
    .submit-btn:disabled { opacity: .65; cursor: not-allowed; transform: none; }
    .resend-btn { background: none; border: 1.5px solid var(--blue-bdr); color: var(--blue-mid); font-weight: 600; font-size: .88rem; border-radius: .6rem; padding: .45rem .9rem; cursor: pointer; transition: all .15s; }
    .resend-btn:hover:not(:disabled) { background: var(--blue-lite); }
    .resend-btn:disabled { opacity: .5; cursor: not-allowed; }
    .info-box { background: var(--blue-lite); border: 1px solid var(--blue-bdr); border-radius: .7rem; padding: .7rem .9rem; font-size: .85rem; color: var(--blue); }
    .alert-danger { background: #fef2f2; border-color: #fecaca; color: #dc2626; font-size: .88rem; border-radius: .7rem; }
    .alert-success { background: #f0fdf4; border-color: #a7f3d0; color: #065f46; font-size: .88rem; border-radius: .7rem; }
  </style>
</head>
<body>

<nav class="navbar-top">
  <div class="container-lg">
    <div class="d-flex align-items-center gap-2">
      <img src="{{ asset('logo.svg') }}" class="header-logo" alt="BPKS">
      <div class="org-name">বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি (বিপিকেএস)</div>
    </div>
  </div>
</nav>

<div class="page-wrap">
  <div class="otp-card">
    <div class="otp-card-header">
      <div style="font-size:2.5rem;margin-bottom:.3rem;">📱</div>
      <div class="fw-bold" style="font-size:1.1rem;">OTP যাচাই করুন</div>
      <div style="font-size:.82rem;opacity:.85;margin-top:.3rem;">
        <strong>{{ $maskedPhone }}</strong> নম্বরে একটি ৫ সংখ্যার কোড পাঠানো হয়েছে
      </div>
    </div>

    <div class="otp-card-body">

      @if($errors->any())
        <div class="alert alert-danger py-2 mb-3">
          <i class="fas fa-exclamation-circle me-1"></i>{{ $errors->first() }}
        </div>
      @endif

      @if(session('success'))
        <div class="alert alert-success py-2 mb-3">
          <i class="fas fa-check-circle me-1"></i>{{ session('success') }}
        </div>
      @endif

      <div class="info-box mb-3">
        <i class="fas fa-info-circle me-1"></i>
        আপনার মোবাইলে আসা OTP কোডটি নিচে দিন। কোডটি <strong>৫ মিনিট</strong> পর্যন্ত বৈধ।
      </div>

      <form method="POST" action="{{ route('blink.otp.submit', $transaction->txn_ref) }}" id="otpForm">
        @csrf
        <div class="mb-3">
          <input type="text" name="otp" id="otpInput"
                 class="otp-input"
                 placeholder="_____"
                 inputmode="numeric"
                 pattern="\d{5}"
                 maxlength="5"
                 autocomplete="one-time-code"
                 autofocus>
        </div>

        <button type="submit" class="submit-btn mb-3" id="submitBtn">
          <i class="fas fa-check me-2"></i>যাচাই করুন
        </button>
      </form>

      <div class="d-flex align-items-center justify-content-between">
        <span style="font-size:.82rem;color:var(--muted);">OTP পাননি?</span>
        <form method="POST" action="{{ route('blink.resend', $transaction->txn_ref) }}">
          @csrf
          <button type="submit" class="resend-btn" id="resendBtn" {{ $resendAvailableAt->isFuture() ? 'disabled' : '' }}>
            <i class="fas fa-redo me-1"></i>
            পুনরায় পাঠান
            @if($resendAvailableAt->isFuture())
              (<span id="resendCountdown"></span>)
            @endif
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  const otpInput  = document.getElementById('otpInput');
  const submitBtn = document.getElementById('submitBtn');
  const resendBtn = document.getElementById('resendBtn');
  const countdown = document.getElementById('resendCountdown');
  const otpForm   = document.getElementById('otpForm');

  submitBtn.disabled = true;

  otpInput.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 5);
    submitBtn.disabled = this.value.length < 5;
    // Auto-submit when 5 digits filled (catches SMS autofill on mobile)
    if (this.value.length === 5) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>যাচাই হচ্ছে...';
      otpForm.submit();
    }
  });

  otpForm.addEventListener('submit', function () {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>যাচাই হচ্ছে...';
  });

  @if($resendAvailableAt->isFuture())
  const resendAt = new Date({{ $resendAvailableAt->valueOf() }});

  function tickResend() {
    const diff = Math.ceil((resendAt - Date.now()) / 1000);
    if (diff <= 0) {
      resendBtn.disabled = false;
      if (countdown) countdown.parentNode.removeChild(countdown);
      clearInterval(timer);
      return;
    }
    const m = Math.floor(diff / 60);
    const s = diff % 60;
    if (countdown) countdown.textContent = `${m}:${String(s).padStart(2,'0')}`;
  }
  tickResend();
  const timer = setInterval(tickResend, 1000);
  @endif
})();
</script>

</body>
</html>
