<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BPKS জাতীয় লটারি-২০২৬ | বিশ টাকায় জিতুন ১০ লক্ষ টাকা</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --teal: #0891b2; --teal-dark: #0e7490; --teal-deep: #0c4a6e;
      --green: #059948; --green-dark: #047a3a; --green-lite: #34d399;
      --gold: #fbbf24; --glass-bdr: rgba(255,255,255,0.2);
    }
    * { box-sizing: border-box; }
    body {
      font-family: 'Hind Siliguri', sans-serif; margin: 0;
      background: linear-gradient(135deg, #062e18 0%, #0c4a6e 35%, #0e7490 70%, #06b6d4 100%);
      min-height: 100vh;
    }

    /* ── HEADER ── */
    .site-header {
      background: linear-gradient(135deg, #062e18 0%, #047a3a 40%, #0e7490 100%);
      padding: 0.6rem 1rem;
      box-shadow: 0 4px 20px rgba(5,153,72,0.4);
    }
    .header-logo { height: 44px; }
    .org-name { color: #fff; font-weight: 700; font-size: 0.88rem; line-height: 1.3; }
    .org-name small { font-weight: 400; font-size: 0.72rem; opacity: 0.8; display: block; }
    .govt-badge {
      background: rgba(255,255,255,0.13); border: 1px solid rgba(255,255,255,0.28);
      border-radius: 2rem; padding: 0.22rem 0.7rem; color: #fff;
      font-size: 0.7rem; font-weight: 600; white-space: nowrap; text-decoration: none;
    }

    /* ── BUY CARD ── */
    .buy-card {
      border-radius: 1.5rem; overflow: hidden;
      background: linear-gradient(160deg, #ffffff 0%, #f0fdff 100%);
      border: 1px solid rgba(8,145,178,0.2);
      box-shadow: 0 16px 48px rgba(8,116,144,0.35);
    }
    .buy-card-header {
      background: linear-gradient(135deg, #082f49 0%, #0c4a6e 50%, #0e7490 100%);
      color: #fff; padding: 1.1rem 1.2rem; text-align: center;
      box-shadow: 0 4px 16px rgba(8,116,144,0.5);
    }
    .price-pill {
      background: rgba(255,255,255,0.15); border: 2px solid rgba(255,255,255,0.45);
      border-radius: 3rem; padding: 0.3rem 1.3rem; display: inline-block;
      font-size: 1.7rem; font-weight: 800; letter-spacing: 1px;
    }
    .buy-card-body { padding: 1.2rem 1.1rem; }

    .phone-group {
      border-radius: 0.85rem; overflow: hidden;
      box-shadow: 0 2px 12px rgba(8,145,178,0.15);
      border: 1.5px solid #a5f3fc;
    }
    .phone-prefix {
      background: #ecfeff; font-weight: 700; color: #164e63;
      border: none; border-right: 1.5px solid #a5f3fc;
      font-size: 1rem; padding: 0 0.85rem;
    }
    .phone-input {
      font-weight: 700; font-size: 1.1rem; border: none;
      background: #fff; color: #1e293b; padding: 0.72rem 0.7rem;
    }
    .phone-input::placeholder { color: #94a3b8; font-weight: 400; }
    .phone-input:focus { box-shadow: none; outline: none; background: #fff; }

    .operator-box {
      margin-top: 0.55rem; padding: 0.45rem 0.8rem;
      border-radius: 0.6rem; border: 1.5px solid #cbd5e1;
      background: #f8fafc; font-size: 0.82rem; color: #475569;
      transition: all 0.2s; min-height: 36px; display: flex; align-items: center;
    }
    .operator-badge { font-weight: 700; color: #4f46e5; }

    .buy-btn {
      background: linear-gradient(135deg, #047a3a 0%, #059948 50%, #0891b2 100%);
      border: none; color: #fff; font-weight: 800; font-size: 1.15rem;
      border-radius: 0.9rem; padding: 0.85rem; width: 100%;
      box-shadow: 0 8px 28px rgba(5,153,72,0.4);
      transition: all 0.2s; position: relative; overflow: hidden;
      -webkit-tap-highlight-color: transparent;
    }
    .buy-btn:active { transform: scale(0.97); }
    .buy-btn:disabled { opacity: 0.65; cursor: not-allowed; }

    .alert-danger { background: rgba(239,68,68,0.12); border-color: rgba(239,68,68,0.4); color: #dc2626; font-size: 0.88rem; border-radius: 0.7rem; }

    /* ── GLASS CARD ── */
    .glass-card {
      background: rgba(255,255,255,0.08); backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.18); border-radius: 1rem;
      box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    }

    /* ── COUNTDOWN ── */
    .countdown-label { color: rgba(255,255,255,0.65); font-size: 0.8rem; font-weight: 600; }
    #countdown { color: var(--gold); font-size: 1.15rem; font-weight: 800; letter-spacing: 1px; }
    .countdown-date { color: rgba(255,255,255,0.5); font-size: 0.8rem; }

    /* ── PAGE WRAP ── */
    .page-wrap { padding: 1rem 0 3rem; }

    /* ── SECTION TITLE ── */
    .section-title {
      font-size: 1.05rem; font-weight: 800; color: #fff;
      border-left: 4px solid var(--green-lite); padding-left: 0.65rem; margin-bottom: 1rem;
    }

    /* ── PRIZE BANNER ── */
    .prize-total-banner {
      background: linear-gradient(135deg, #047a3a 0%, #059948 40%, #0e7490 100%);
      color: #fff; border-radius: 1rem; padding: 0.9rem 1.1rem;
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 0.4rem; margin-bottom: 0.9rem;
      box-shadow: 0 6px 24px rgba(5,153,72,0.4);
    }
    .prize-total-banner .big-num { font-size: 1.5rem; font-weight: 800; }

    /* ── PRIZE GRID ── */
    .prize-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.6rem; }
    @media (min-width: 480px) { .prize-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 992px) { .prize-grid { grid-template-columns: repeat(3, 1fr); } }

    .prize-card {
      border-radius: 0.9rem; padding: 0.75rem 0.6rem; text-align: center;
      backdrop-filter: blur(12px); border: 1px solid var(--glass-bdr);
      box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }
    .prize-card.first  { background: linear-gradient(145deg,#3d2a00,#7a5200); border-color: rgba(251,191,36,0.6); }
    .prize-card.second { background: linear-gradient(145deg,#082f49,#0c4a6e); border-color: rgba(103,232,249,0.5); }
    .prize-card.third  { background: linear-gradient(145deg,#3b1f05,#6b3c10); border-color: rgba(205,124,47,0.5); }
    .prize-card.other  { background: linear-gradient(145deg,#052e1a,#0a4a2a); border-color: rgba(52,211,153,0.4); }
    .prize-rank   { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.85); margin-bottom: 0.25rem; }
    .prize-amount { font-size: 1.15rem; font-weight: 800; color: #fff; line-height: 1; }
    .prize-amount.mega { font-size: 1.5rem; color: #ffe066; text-shadow: 0 0 20px rgba(255,224,102,0.6); }
    .prize-count  { font-size: 0.7rem; color: rgba(255,255,255,0.65); margin-top: 0.25rem; }

    /* ── TERMS ── */
    .terms-item { padding: 0.45rem 0; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 0.84rem; color: rgba(255,255,255,0.78); }
    .terms-item:last-child { border-bottom: none; }
    .terms-item i { color: var(--green-lite); margin-right: 0.45rem; width: 14px; }

    /* ── ABOUT ── */
    .about-card {
      background: linear-gradient(135deg, rgba(5,153,72,0.18), rgba(52,211,153,0.08));
      border: 1px solid rgba(52,211,153,0.35); border-radius: 1rem; padding: 1rem 1.1rem;
    }
    .about-card p { color: rgba(255,255,255,0.85); margin-bottom: 0.5rem; font-size: 0.9rem; }

    /* ── FOOTER ── */
    .site-footer {
      background: rgba(0,0,0,0.4); backdrop-filter: blur(12px);
      border-top: 1px solid rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.5); padding: 1.2rem 1rem; font-size: 0.8rem;
    }
    .site-footer a { color: var(--gold); text-decoration: none; }

    /* ── DRAW BADGE ── */
    .draw-badge {
      background: linear-gradient(135deg, #047a3a, #0891b2);
      color: #fff; border-radius: 2rem; padding: 0.25rem 0.85rem;
      font-weight: 700; font-size: 0.82rem;
    }
    .lottery-title { color: #fff; font-size: 1.05rem; font-weight: 800; }

    /* ── MOBILE: sticky buy button bar ── */
    .sticky-buy-bar {
      display: none;
      position: fixed; bottom: 0; left: 0; right: 0; z-index: 999;
      background: linear-gradient(135deg, #062e18, #0c4a6e);
      border-top: 1px solid rgba(255,255,255,0.15);
      padding: 0.75rem 1rem; box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
    }
    @media (max-width: 991px) { .sticky-buy-bar { display: block; } }
    @media (min-width: 992px) { .sticky-col { position: sticky; top: 1rem; } }

    /* ── MODAL ── */
    .modal-content { border-radius: 1.3rem; overflow: hidden; border: none; }
    @media (max-width: 575px) {
      .modal-dialog { margin: 0.5rem; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
  <div class="container-lg">
    <div class="d-flex align-items-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ asset('logo.svg') }}" class="header-logo" alt="BPKS">
        <div class="org-name">
          বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি (বিপিকেএস)
          <small class="d-none d-sm-block">প্রতিবন্ধী ব্যাক্তিদের উন্নয়নে কর্মরত</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-1 flex-shrink-0">
        <span class="govt-badge d-none d-sm-inline"><i class="fas fa-certificate me-1"></i>সরকার অনুমোদিত</span>
        <a href="tel:09638222222" class="govt-badge"><i class="fas fa-phone me-1"></i>০৯৬৩৮-২২২২২২</a>
      </div>
    </div>
  </div>
</header>

<!-- MAIN -->
<div class="container-lg page-wrap px-3">
  <div class="row g-3 g-lg-4">

    <!-- LEFT: Buy Card (desktop sticky, mobile top) -->
    <div class="col-lg-5 order-lg-1 order-1">
      <div class="sticky-col">
        <!-- Buy Card -->
        <div class="buy-card">
          <div class="buy-card-header">
            <div class="fw-bold mb-1" style="font-size:1rem;">
              <i class="fas fa-ticket-alt me-1"></i> টিকেট কিনুন
            </div>
            <div class="price-pill">২০ টাকা</div>
            <div class="mt-1 opacity-75" style="font-size:0.82rem;">মোবাইল ব্যালেন্স থেকে কাটা হবে</div>
          </div>
          <div class="buy-card-body">

            @if($errors->any())
              <div class="alert alert-danger py-2 mb-3">
                <i class="fas fa-exclamation-circle me-1"></i> {{ $errors->first() }}
              </div>
            @endif

            <form method="POST" action="{{ route('buy.initiate') }}" id="buyForm">
              @csrf
              <div class="mb-3">
                <label class="form-label fw-bold small mb-1" style="color:#1e293b;">মোবাইল নম্বর</label>
                <div class="input-group phone-group">
                  <span class="input-group-text phone-prefix">88</span>
                  <input type="tel" name="phone" id="phone"
                         class="form-control phone-input"
                         placeholder="01XXXXXXXXX" inputmode="numeric"
                         maxlength="11" value="{{ old('phone') }}" autocomplete="tel">
                </div>
                <!-- Operator detect box -->
                <div class="operator-box" id="operatorBox" style="display:none;">
                  <span id="operatorText"></span>
                </div>
              </div>
              <!-- Desktop buy button -->
              <button type="button" class="buy-btn d-none d-lg-block" id="buyBtnDesktop">
                <i class="fas fa-shopping-cart me-2"></i>এখনই কিনুন
              </button>
              <!-- Mobile buy button (inline, above sticky bar) -->
              <button type="button" class="buy-btn d-lg-none" id="buyBtnMobile">
                <i class="fas fa-shopping-cart me-2"></i>এখনই কিনুন — ২০ টাকা
              </button>
            </form>
          </div>
        </div>

        <!-- Countdown -->
        <div class="glass-card mt-3 text-center px-3 py-2 d-none d-lg-block">
          <div class="countdown-label mb-1">ড্র পর্যন্ত বাকি</div>
          <div id="countdown">গণনা করা হচ্ছে...</div>
          <div class="countdown-date mt-1">১৯ জুলাই ২০২৬</div>
        </div>

        <!-- Helpline desktop -->
        <div class="glass-card mt-2 py-2 text-center d-none d-lg-block">
          <a href="tel:09638222222" class="text-decoration-none fw-bold" style="color:#34d399;">
            <i class="fas fa-headset me-2"></i>হেল্পলাইন: ০৯৬৩৮-২২২২২২
          </a>
        </div>
      </div>
    </div>

    <!-- RIGHT: Prize + Info -->
    <div class="col-lg-7 order-lg-2 order-2">

      <!-- Draw date + countdown (mobile) -->
      <div class="glass-card mb-3 px-3 py-2 d-lg-none d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <span class="lottery-title" style="font-size:0.95rem;"><i class="fas fa-star me-1" style="color:var(--gold);"></i>জাতীয় লটারি-২০২৬</span>
          <span class="draw-badge ms-2" style="font-size:0.75rem;"><i class="fas fa-calendar-alt me-1"></i>ড্র: ১৯ জুলাই ২০২৬</span>
        </div>
        <div class="text-center">
          <div class="countdown-label">বাকি:</div>
          <div id="countdown-mobile" style="color:var(--gold);font-size:0.9rem;font-weight:800;letter-spacing:1px;"></div>
        </div>
      </div>

      <!-- Draw date (desktop) -->
      <div class="d-lg-flex d-none align-items-center gap-2 mb-3">
        <span class="lottery-title"><i class="fas fa-star me-1" style="color:var(--gold);"></i>জাতীয় লটারি-২০২৬</span>
        <span class="draw-badge"><i class="fas fa-calendar-alt me-1"></i> ড্র: ১৯ জুলাই ২০২৬</span>
      </div>

      <!-- Prize Banner -->
      <div class="prize-total-banner">
        <div>
          <div class="opacity-75 small fw-semibold">মোট পুরস্কার</div>
          <div class="big-num">৫০ লক্ষ টাকা</div>
          <div class="opacity-75 small">৯৪৮টি পুরস্কার</div>
        </div>
        <div class="text-end">
          <div class="opacity-75 small fw-semibold">টিকেটের মূল্য</div>
          <div class="big-num">মাত্র ২০ টাকা</div>
          <div class="opacity-75 small">মোবাইল ব্যালেন্স থেকে</div>
        </div>
      </div>

      <!-- Prize Grid -->
      <div class="mb-3">
        <div class="section-title"><i class="fas fa-trophy me-2" style="color:var(--gold);"></i>পুরস্কার তালিকা</div>
        <div class="prize-grid">
          <div class="prize-card first">
            <div class="prize-rank">🥇 ১ম পুরস্কার</div>
            <div class="prize-amount mega">১০ লক্ষ</div>
            <div class="prize-count">১টি পুরস্কার</div>
          </div>
          <div class="prize-card second">
            <div class="prize-rank">🥈 ২য় পুরস্কার</div>
            <div class="prize-amount">৫ লক্ষ</div>
            <div class="prize-count">২টি পুরস্কার</div>
          </div>
          <div class="prize-card third">
            <div class="prize-rank">🥉 ৩য় পুরস্কার</div>
            <div class="prize-amount">২৫,০০০</div>
            <div class="prize-count">৫টি পুরস্কার</div>
          </div>
          <div class="prize-card other">
            <div class="prize-rank">৪র্থ পুরস্কার</div>
            <div class="prize-amount">১০,০০০</div>
            <div class="prize-count">৪০টি পুরস্কার</div>
          </div>
          <div class="prize-card other">
            <div class="prize-rank">৫ম পুরস্কার</div>
            <div class="prize-amount">২,০০০</div>
            <div class="prize-count">২০০টি পুরস্কার</div>
          </div>
          <div class="prize-card other">
            <div class="prize-rank">৬ষ্ঠ–৮ম</div>
            <div class="prize-amount">বিভিন্ন</div>
            <div class="prize-count">৭০০টি পুরস্কার</div>
          </div>
        </div>
      </div>

      <!-- Terms -->
      <div class="mb-3">
        <div class="section-title"><i class="fas fa-file-contract me-2" style="color:var(--green-lite);"></i>নিয়মাবলী</div>
        <div class="glass-card px-3 py-1">
          <div class="terms-item"><i class="fas fa-check-circle"></i> লটারি ক্রয়ের আগে মোবাইল ব্যালেন্স নিশ্চিত করুন। ২০ টাকা কাটা হবে।</div>
          <div class="terms-item"><i class="fas fa-check-circle"></i> টিকেট নম্বর অবশ্যই সংরক্ষণ করুন। এটিই পুরস্কার দাবির প্রমাণ।</div>
          <div class="terms-item"><i class="fas fa-check-circle"></i> একই নম্বর থেকে একাধিক টিকেট কেনা যাবে।</div>
          <div class="terms-item"><i class="fas fa-check-circle"></i> ড্র অনুষ্ঠান BPKS অফিসিয়াল Facebook পেজে সম্প্রচারিত হবে।</div>
          <div class="terms-item"><i class="fas fa-check-circle"></i> পুরস্কার বিজয়ীদের SMS-এ জানানো হবে।</div>
          <div class="terms-item"><i class="fas fa-check-circle"></i> পুরস্কার সংগ্রহে বৈধ জাতীয় পরিচয়পত্র আনতে হবে।</div>
          <div class="terms-item"><i class="fas fa-check-circle"></i> ড্র তারিখ: ১৯ জুলাই ২০২৬। এর পরে টিকেট বিক্রি বন্ধ।</div>
          <div class="terms-item"><i class="fas fa-check-circle"></i> এই লটারি বাংলাদেশ সরকার কর্তৃক অনুমোদিত।</div>
        </div>
      </div>

      <!-- About -->
      <div class="mb-5 mb-lg-3">
        <div class="section-title"><i class="fas fa-heart me-2" style="color:#f87171;"></i>আমাদের লক্ষ্য</div>
        <div class="about-card">
          <p><i class="fas fa-wheelchair me-2" style="color:var(--green-lite);"></i>
          আপনার প্রতিটি টিকেটের অর্থ সরাসরি ব্যবহৃত হবে <strong>বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি</strong>-এর প্রতিবন্ধী মানুষের উন্নয়নে।</p>
          <p class="mb-0" style="font-size:0.8rem;color:rgba(255,255,255,0.55);">
            <i class="fas fa-envelope me-1"></i> nibircorporation88@gmail.com &nbsp;|&nbsp;
            <i class="fas fa-globe me-1"></i> bpkslottery.com
          </p>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer text-center" style="margin-bottom:70px;" id="siteFooter">
  <div class="container">
    <div class="mb-1">বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি (বিপিকেএস) &nbsp;|&nbsp; জাতীয় লটারি-২০২৬</div>
    <div>Powered by <a href="#">B2M Technologies Ltd.</a> &nbsp;|&nbsp; <a href="tel:09638222222">০৯৬৩৮-২২২২২২</a></div>
  </div>
</footer>

<!-- CONFIRM MODAL -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:360px;">
    <div class="modal-content shadow-lg">
      <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
        <h6 class="modal-title fw-bold text-dark">
          <i class="fas fa-exclamation-triangle text-warning me-2"></i>নিশ্চিতকরণ
        </h6>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-3 text-center">
        <p id="confirmMsg" class="fs-6 fw-medium mb-0"></p>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center gap-2 pb-3">
        <button class="btn btn-outline-secondary px-3 rounded-pill" data-bs-dismiss="modal">বাতিল</button>
        <button class="btn btn-success px-4 rounded-pill fw-bold" id="confirmPayBtn">
          <i class="fas fa-check me-1"></i> হ্যাঁ, নিশ্চিত
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  const phoneInput   = document.getElementById('phone');
  const operatorBox  = document.getElementById('operatorBox');
  const operatorText = document.getElementById('operatorText');
  const confirmMsg   = document.getElementById('confirmMsg');
  const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

  const OPERATORS = {
    '013':'গ্রামীণফোন','017':'গ্রামীণফোন',
    '014':'বাংলালিংক', '019':'বাংলালিংক',
    '018':'রবি',       '016':'এয়ারটেল (রবি)',
    '015':'টেলিটক',
  };

  function detectOp(val) {
    const c = val.replace(/\D/g,'');
    let px = '';
    if (c.length >= 3 && c.startsWith('01')) px = c.slice(0,3);
    else if (c.length >= 2 && c[0]==='1')    px = '0'+c.slice(0,2);
    return OPERATORS[px] || null;
  }

  phoneInput.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g,'').slice(0,11);
    const op = detectOp(this.value);
    if (op) {
      operatorBox.style.display      = '';
      operatorBox.style.borderColor  = '#6366f1';
      operatorBox.style.background   = '#eef2ff';
      operatorText.innerHTML = `<span class="operator-badge"><i class="fas fa-sim-card me-1"></i>${op}</span> সনাক্ত হয়েছে ✓`;
    } else {
      operatorBox.style.display = 'none';
    }
  });

  if (phoneInput.value) phoneInput.dispatchEvent(new Event('input'));

  function triggerBuy() {
    const val = phoneInput.value.replace(/\D/g,'');
    if (!val) { phoneInput.focus(); return; }
    const op = detectOp(val);
    if (!op) { phoneInput.focus(); phoneInput.style.borderColor='#ef4444'; return; }
    phoneInput.style.borderColor='';
    const display = val.length===11 ? val : '0'+val;
    confirmMsg.innerHTML = `<strong>${op}</strong> নম্বর<br><strong class="text-primary fs-5">${display}</strong><br>থেকে <strong class="text-danger">২০ টাকা</strong> কাটা হবে।`;
    confirmModal.show();
  }

  document.getElementById('buyBtnDesktop').addEventListener('click', triggerBuy);
  document.getElementById('buyBtnMobile').addEventListener('click', triggerBuy);

  document.getElementById('confirmPayBtn').addEventListener('click', function () {
    confirmModal.hide();
    ['buyBtnDesktop','buyBtnMobile'].forEach(id => {
      const b = document.getElementById(id);
      if (b) { b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>প্রক্রিয়া হচ্ছে...'; }
    });
    document.getElementById('buyForm').submit();
  });

  // Countdown
  function updateCountdown() {
    const target = new Date('2026-07-19T00:00:00+06:00');
    const diff   = target - new Date();
    if (diff <= 0) { ['countdown','countdown-mobile'].forEach(id => { const el=document.getElementById(id); if(el) el.textContent='ড্র সম্পন্ন'; }); return; }
    const d = Math.floor(diff/86400000);
    const h = Math.floor((diff%86400000)/3600000);
    const m = Math.floor((diff%3600000)/60000);
    const s = Math.floor((diff%60000)/1000);
    const txt = `${d}d ${h}h ${m}m ${s}s`;
    ['countdown','countdown-mobile'].forEach(id => { const el=document.getElementById(id); if(el) el.textContent=txt; });
  }
  updateCountdown();
  setInterval(updateCountdown, 1000);

  // Hide sticky footer bar on desktop
  function adjustFooter() {
    const footer = document.getElementById('siteFooter');
    if (window.innerWidth >= 992) footer.style.marginBottom = '0';
    else footer.style.marginBottom = '70px';
  }
  adjustFooter();
  window.addEventListener('resize', adjustFooter);
})();
</script>
</body>
</html>
