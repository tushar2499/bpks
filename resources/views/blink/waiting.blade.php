<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>পেমেন্ট প্রক্রিয়াধীন | BPKS লটারি</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:      #1e40af;
      --blue-mid:  #2563eb;
      --blue-lite: #eff6ff;
      --blue-bdr:  #bfdbfe;
      --green:     #059669;
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
    .wait-card { background: #fff; border-radius: var(--radius); border: 1px solid var(--blue-bdr); box-shadow: 0 8px 30px rgba(30,64,175,.12); overflow: hidden; width: 100%; max-width: 400px; }
    .wait-card-header { background: linear-gradient(135deg, var(--blue), var(--blue-mid)); color: #fff; padding: 1.4rem; text-align: center; }
    .wait-card-body { padding: 1.4rem; text-align: center; }
    .spinner-ring { width: 64px; height: 64px; border: 5px solid var(--blue-bdr); border-top-color: var(--blue-mid); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .status-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #f59e0b; animation: pulse 1.5s ease-in-out infinite; margin-right: .4rem; }
    @keyframes pulse { 0%,100%{opacity:1}50%{opacity:.3} }
    .info-box { background: var(--blue-lite); border: 1px solid var(--blue-bdr); border-radius: .7rem; padding: .7rem .9rem; font-size: .82rem; color: var(--blue); }
    .error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: .7rem; padding: .7rem .9rem; font-size: .85rem; color: #dc2626; display: none; }
    .success-box { background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: .7rem; padding: .7rem .9rem; font-size: .85rem; color: #065f46; display: none; }
    .low-balance-box { background: #fffbeb; border: 1px solid #fcd34d; border-radius: .7rem; padding: .9rem; font-size: .88rem; color: #92400e; display: none; }
    .home-btn { display: inline-block; margin-top: .75rem; background: linear-gradient(135deg,var(--blue),var(--blue-mid)); color:#fff; font-weight:700; font-size:.9rem; border-radius:.6rem; padding:.55rem 1.4rem; text-decoration:none; }
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
  <div class="wait-card">
    <div class="wait-card-header">
      <div class="fw-bold" style="font-size:1.05rem;">পেমেন্ট প্রক্রিয়াধীন</div>
      <div style="font-size:.82rem;opacity:.85;margin-top:.3rem;">
        <strong>{{ $maskedPhone }}</strong>
      </div>
    </div>

    <div class="wait-card-body">
      <div id="pendingBlock">
        <div class="spinner-ring"></div>
        <p class="fw-bold mb-1" style="color:var(--blue);">
          <span class="status-dot"></span>অপেক্ষা করুন...
        </p>
        <p style="font-size:.88rem;color:var(--muted);" class="mb-3">
          আপনার পেমেন্ট যাচাই হচ্ছে। এই পেজটি বন্ধ করবেন না।
        </p>
        <div class="info-box mb-3">
          <i class="fas fa-info-circle me-1"></i>
          Banglalink পেমেন্ট নিশ্চিত হলে স্বয়ংক্রিয়ভাবে টিকেট তৈরি হবে।
        </div>
        <p style="font-size:.78rem;color:var(--muted);" id="timeoutMsg"></p>
      </div>

      <div class="success-box mb-3" id="successBox">
        <i class="fas fa-check-circle me-1"></i>পেমেন্ট সফল! টিকেট পেজে যাচ্ছি...
      </div>

      <div class="error-box mb-3" id="errorBox">
        <i class="fas fa-times-circle me-1"></i><span id="errorMsg">পেমেন্ট ব্যর্থ হয়েছে।</span>
        <br><a href="{{ route('buy.index') }}" class="home-btn mt-2"><i class="fas fa-home me-1"></i>হোমে ফিরুন</a>
      </div>

      <div class="low-balance-box mb-3" id="lowBalanceBox">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>পেমেন্ট ব্যর্থ।</strong><br>
        <span id="blinkStatusMsg" style="font-size:.85rem;"></span>
        <br><a href="{{ route('buy.index') }}" class="home-btn"><i class="fas fa-home me-1"></i>হোমে ফিরুন</a>
      </div>

    </div>
  </div>
</div>

<script>
(function () {
  const txnRef    = @json($transaction->txn_ref);
  const phone     = @json($transaction->phone);
  const statusUrl = @json(route('blink.status', $transaction->txn_ref)) + '?phone=' + encodeURIComponent(phone);
  const buyUrl    = @json(route('buy.index'));

  const pendingBlock   = document.getElementById('pendingBlock');
  const successBox     = document.getElementById('successBox');
  const errorBox       = document.getElementById('errorBox');
  const errorMsg       = document.getElementById('errorMsg');
  const lowBalanceBox  = document.getElementById('lowBalanceBox');
  const blinkStatusMsg = document.getElementById('blinkStatusMsg');
  const timeoutMsg     = document.getElementById('timeoutMsg');

  const POLL_INTERVAL = 3000;   // 3 seconds
  const TIMEOUT_MS    = 15 * 60 * 1000; // 15 minutes
  const startTime     = Date.now();

  let pollTimer = null;

  function showError(msg) {
    pendingBlock.style.display = 'none';
    errorBox.style.display = 'block';
    errorMsg.textContent = msg || 'পেমেন্ট ব্যর্থ হয়েছে।';
  }

  function poll() {
    if (Date.now() - startTime > TIMEOUT_MS) {
      showError('সময়সীমা পার হয়ে গেছে। পেমেন্ট নিশ্চিত হয়নি। আবার চেষ্টা করুন।');
      return;
    }

    const elapsed = Math.floor((Date.now() - startTime) / 1000);
    const remaining = Math.max(0, Math.ceil((TIMEOUT_MS - (Date.now() - startTime)) / 60000));
    timeoutMsg.textContent = `যাচাই চলছে... আর প্রায় ${remaining} মিনিট অপেক্ষা করুন।`;

    fetch(statusUrl)
      .then(r => r.json())
      .then(data => {
        if (data.status === 'success') {
          clearInterval(pollTimer);
          pendingBlock.style.display = 'none';
          successBox.style.display = 'block';
          setTimeout(() => { window.location.href = data.redirect; }, 1500);
        } else if (data.status === 'failed') {
          clearInterval(pollTimer);
          showError(data.message || 'পেমেন্ট ব্যর্থ হয়েছে।');
        } else if (data.status === 'expired') {
          clearInterval(pollTimer);
          showError('সময়সীমা পার হয়ে গেছে। পেমেন্ট নিশ্চিত হয়নি।');
        } else if (data.status === 'low_balance') {
          clearInterval(pollTimer);
          pendingBlock.style.display = 'none';
          blinkStatusMsg.textContent = data.blink_status || '';
          lowBalanceBox.style.display = 'block';
        }
        // 'pending' → keep polling
      })
      .catch(() => {
        // network error — keep polling silently
      });
  }

  poll();
  pollTimer = setInterval(poll, POLL_INTERVAL);
})();
</script>

</body>
</html>
