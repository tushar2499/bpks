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
      --blue:      #1e40af;
      --blue-mid:  #2563eb;
      --blue-lite: #eff6ff;
      --blue-bdr:  #bfdbfe;
      --green:     #059669;
      --gold:      #d97706;
      --text:      #1e293b;
      --muted:     #64748b;
      --bg:        #f1f5f9;
      --card-bg:   #ffffff;
      --radius:    .9rem;
    }
    * { box-sizing: border-box; }
    body {
      font-family: 'Hind Siliguri', sans-serif;
      background: var(--bg);
      color: var(--text);
      margin: 0;
    }

    /* ── NAVBAR ── */
    .navbar-top {
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      padding: .65rem 1rem;
      box-shadow: 0 1px 8px rgba(30,64,175,.08);
      position: sticky; top: 0; z-index: 100;
    }
    .header-logo { height: 40px; }
    .org-name { font-weight: 700; font-size: .88rem; color: var(--blue); line-height: 1.3; }
    .org-name small { font-weight: 400; font-size: .7rem; color: var(--muted); display: block; }
    .nav-badge {
      background: var(--blue-lite); color: var(--blue);
      border: 1px solid var(--blue-bdr);
      border-radius: 2rem; padding: .22rem .7rem;
      font-size: .72rem; font-weight: 600; white-space: nowrap;
      text-decoration: none;
    }
    .nav-badge-green {
      background: #f0fdf4; color: #065f46;
      border-color: #a7f3d0;
    }

    .price-tag {
      display: inline-block;
      background: rgba(255,255,255,.18);
      border: 2px solid rgba(255,255,255,.45);
      border-radius: 2rem; padding: .3rem 1.2rem;
      font-size: 1.4rem; font-weight: 800;
    }

    /* ── MAIN WRAP ── */
    .page-wrap { padding: 1.5rem 0 4rem; }

    /* ── BUY CARD ── */
    .buy-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      border: 1px solid var(--blue-bdr);
      box-shadow: 0 8px 30px rgba(30,64,175,.12);
      overflow: hidden;
    }
    .buy-card-header {
      background: linear-gradient(135deg, var(--blue), var(--blue-mid));
      color: #fff; padding: 1rem 1.2rem; text-align: center;
    }
    .buy-card-body { padding: 1.2rem; }

    .phone-group {
      border-radius: .75rem; overflow: hidden;
      border: 2px solid var(--blue-bdr);
      transition: border-color .2s;
    }
    .phone-group:focus-within { border-color: var(--blue-mid); }
    .phone-prefix {
      background: var(--blue-lite); color: var(--blue);
      font-weight: 700; border: none;
      border-right: 2px solid var(--blue-bdr);
      font-size: 1rem; padding: 0 .85rem;
    }
    .phone-input {
      font-weight: 700; font-size: 1.05rem;
      border: none; background: #fff;
      color: var(--text); padding: .72rem .7rem;
    }
    .phone-input::placeholder { color: #94a3b8; font-weight: 400; }
    .phone-input:focus { box-shadow: none; outline: none; }

    .operator-box {
      margin-top: .5rem; padding: .4rem .75rem;
      border-radius: .6rem; border: 1.5px solid var(--blue-bdr);
      background: var(--blue-lite); font-size: .82rem; color: var(--blue);
      display: flex; align-items: center; transition: all .2s;
    }
    .operator-badge { font-weight: 700; color: var(--blue); }

    .buy-btn {
      background: linear-gradient(135deg, var(--blue) 0%, var(--blue-mid) 100%);
      border: none; color: #fff; font-weight: 800;
      font-size: 1.1rem; border-radius: .75rem;
      padding: .85rem; width: 100%;
      box-shadow: 0 6px 20px rgba(37,99,235,.35);
      transition: transform .15s, box-shadow .15s;
      -webkit-tap-highlight-color: transparent;
    }
    .buy-btn:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(37,99,235,.45); }
    .buy-btn:active { transform: scale(.97); }
    .buy-btn:disabled { opacity: .65; cursor: not-allowed; }

    /* ── SECTION HEADING ── */
    .sec-title {
      font-size: 1rem; font-weight: 800; color: var(--blue);
      border-left: 4px solid var(--blue-mid);
      padding-left: .6rem; margin-bottom: .9rem;
    }

    /* ── PRIZE BANNER ── */
    .prize-banner {
      background: linear-gradient(135deg, var(--blue), var(--blue-mid));
      color: #fff; border-radius: var(--radius);
      padding: 1rem 1.2rem;
      display: flex; justify-content: space-between;
      align-items: center; flex-wrap: wrap; gap: .5rem;
      margin-bottom: .9rem;
      box-shadow: 0 4px 16px rgba(30,64,175,.2);
    }
    .prize-banner .num { font-size: 1.6rem; font-weight: 800; }
    .prize-banner .lbl { font-size: .75rem; opacity: .8; }

    /* ── PRIZE GRID ── */
    .prize-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: .6rem; }
    @media (min-width: 480px) { .prize-grid { grid-template-columns: repeat(3, 1fr); } }

    .prize-card {
      background: var(--card-bg);
      border-radius: var(--radius);
      padding: .8rem .6rem; text-align: center;
      border: 1px solid #e2e8f0;
      box-shadow: 0 2px 8px rgba(0,0,0,.06);
      transition: transform .15s, box-shadow .15s;
    }
    .prize-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(30,64,175,.12); }
    .prize-card.first  { border-top: 3px solid #f59e0b; }
    .prize-card.second { border-top: 3px solid #94a3b8; }
    .prize-card.third  { border-top: 3px solid #b45309; }
    .prize-card.other  { border-top: 3px solid var(--blue-mid); }
    .prize-rank   { font-size: .7rem; font-weight: 600; color: var(--muted); margin-bottom: .2rem; }
    .prize-amount { font-size: 1.1rem; font-weight: 800; color: var(--text); line-height: 1; }
    .prize-amount.mega { font-size: 1.4rem; color: #b45309; }
    .prize-count  { font-size: .68rem; color: var(--muted); margin-top: .2rem; }

    /* ── INFO CARD ── */
    .info-card {
      background: var(--card-bg);
      border: 1px solid #e2e8f0;
      border-radius: var(--radius);
      box-shadow: 0 2px 8px rgba(0,0,0,.05);
    }

    /* ── TERMS ── */
    .terms-item {
      padding: .5rem 0; border-bottom: 1px solid #f1f5f9;
      font-size: .82rem; color: var(--text);
      display: flex; gap: .5rem;
    }
    .terms-item:last-child { border-bottom: none; }
    .terms-item i { color: var(--blue-mid); flex-shrink: 0; margin-top: 2px; }
    .terms-item.highlight { color: var(--gold); font-weight: 700; }
    .terms-item.highlight i { color: var(--gold); }

    /* ── ABOUT ── */
    .about-card {
      background: var(--blue-lite);
      border: 1px solid var(--blue-bdr);
      border-radius: var(--radius);
      padding: 1rem 1.1rem;
    }
    .about-card p { color: var(--text); margin-bottom: .5rem; font-size: .88rem; }

    /* ── HELPLINE CARD ── */
    .helpline-card {
      background: #f0fdf4; border: 1px solid #a7f3d0;
      border-radius: var(--radius); padding: .75rem 1rem;
      text-align: center;
    }

    /* ── COUNTDOWN CARD ── */
    .countdown-card {
      background: var(--card-bg); border: 1px solid var(--blue-bdr);
      border-radius: var(--radius); padding: .75rem 1rem; text-align: center;
    }
    .countdown-card .lbl { font-size: .75rem; color: var(--muted); margin-bottom: .2rem; }
    .countdown-card #countdown { color: var(--blue); font-size: 1.1rem; font-weight: 800; }
    .countdown-card .date { font-size: .75rem; color: var(--muted); margin-top: .15rem; }

    /* ── FOOTER ── */
    .site-footer {
      background: var(--blue);
      color: rgba(255,255,255,.75);
      padding: 1.2rem 1rem; font-size: .8rem; text-align: center;
    }
    .site-footer a { color: #93c5fd; text-decoration: none; }

    /* ── STICKY BUY BAR (mobile) ── */
    .sticky-buy-bar {
      display: none;
      position: fixed; bottom: 0; left: 0; right: 0; z-index: 999;
      background: #fff;
      border-top: 1px solid #e2e8f0;
      padding: .75rem 1rem;
      box-shadow: 0 -4px 16px rgba(0,0,0,.1);
    }
    @media (max-width: 991px) { .sticky-buy-bar { display: block; } }
    @media (min-width: 992px) { .sticky-col { position: sticky; top: 5rem; } }

    /* ── QTY SELECTOR ── */
    .qty-box { margin-top: .5rem; }
    .qty-wrap {
      display: flex; align-items: center; justify-content: space-between;
      background: var(--blue-lite); border: 1.5px solid var(--blue-bdr);
      border-radius: .75rem; padding: .5rem .75rem;
    }
    .qty-label { font-size: .82rem; color: var(--blue); font-weight: 600; }
    .qty-controls { display: flex; align-items: center; gap: .6rem; }
    .qty-btn {
      width: 30px; height: 30px; border-radius: 50%;
      border: 2px solid var(--blue-mid); background: #fff; color: var(--blue-mid);
      font-size: 1.15rem; font-weight: 700; line-height: 1;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all .15s; -webkit-tap-highlight-color: transparent;
      padding: 0;
    }
    .qty-btn:hover:not(:disabled) { background: var(--blue-mid); color: #fff; }
    .qty-btn:disabled { opacity: .35; cursor: not-allowed; }
    .qty-num { font-size: 1.25rem; font-weight: 800; color: var(--blue); min-width: 1.6rem; text-align: center; }
    .qty-total-row { font-size: .78rem; color: var(--muted); text-align: right; margin-top: .2rem; }

    /* ── MODAL ── */
    .modal-content { border-radius: 1.2rem; overflow: hidden; border: none; }

    .alert-danger { background: #fef2f2; border-color: #fecaca; color: #dc2626; font-size: .88rem; border-radius: .7rem; }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar-top">
  <div class="container-lg">
    <div class="d-flex align-items-center justify-content-between gap-2">
      <div class="d-flex align-items-center gap-2">
        <img src="{{ asset('logo.svg') }}" class="header-logo" alt="BPKS">
        <div class="org-name">
          বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি (বিপিকেএস)
          <small class="d-none d-sm-block">প্রতিবন্ধী ব্যাক্তিদের উন্নয়নে কর্মরত</small>
        </div>
      </div>
      <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <span class="nav-badge nav-badge-green d-none d-sm-inline">
          <i class="fas fa-certificate me-1"></i>সরকার অনুমোদিত
        </span>
        <a href="tel:09638222222" class="nav-badge">
          <i class="fas fa-phone me-1"></i>০৯৬৩৮-২২২২২২
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- MAIN -->
<div class="container-lg page-wrap px-3">
  <div class="row g-3 g-lg-4">

    <!-- LEFT: Buy Card -->
    <div class="col-lg-4 order-lg-1 order-1">
      <div class="sticky-col">

        <!-- Buy Card -->
        <div class="buy-card mb-3">
          <div class="buy-card-header">
            <div class="fw-bold mb-1" style="font-size:.95rem;">
              <i class="fas fa-ticket-alt me-1"></i> টিকেট কিনুন
            </div>
            <div class="price-tag" style="font-size:1.5rem;">২০ টাকা</div>
            <div class="mt-1" style="font-size:.78rem;opacity:.8;">মোবাইল ব্যালেন্স থেকে কাটা হবে</div>
          </div>
          <div class="buy-card-body">
            @if($errors->any())
              <div class="alert alert-danger py-2 mb-3">
                <i class="fas fa-exclamation-circle me-1"></i>{{ $errors->first() }}
              </div>
            @endif

            <form method="POST" action="{{ route('buy.initiate') }}" id="buyForm">
              @csrf
              <div class="mb-3">
                <label class="form-label fw-semibold small mb-1" style="color:var(--blue);">মোবাইল নম্বর</label>
                <div class="input-group phone-group">
                  <span class="input-group-text phone-prefix">88</span>
                  <input type="tel" name="phone" id="phone"
                         class="form-control phone-input"
                         placeholder="01XXXXXXXXX" inputmode="numeric"
                         maxlength="11" value="{{ old('phone') }}" autocomplete="tel">
                </div>
                <div class="operator-box" id="operatorBox" style="display:none;">
                  <span id="operatorText"></span>
                </div>
                <div class="qty-box" id="qtyBox" style="display:none;">
                  <div class="qty-wrap">
                    <div class="qty-label"><i class="fas fa-ticket-alt me-1"></i>টিকেট সংখ্যা</div>
                    <div class="qty-controls">
                      <button type="button" class="qty-btn" id="qtyMinus" disabled>−</button>
                      <span class="qty-num" id="qtyNum">১</span>
                      <button type="button" class="qty-btn" id="qtyPlus">+</button>
                    </div>
                  </div>
                  <div class="qty-total-row" id="qtyTotalRow">মোট: ২০ টাকা</div>
                  <input type="hidden" name="qty" id="qtyInput" value="1">
                </div>
              </div>
              <button type="button" class="buy-btn d-none d-lg-block" id="buyBtnDesktop">
                <i class="fas fa-shopping-cart me-2"></i>এখনই কিনুন
              </button>
              <button type="button" class="buy-btn d-lg-none" id="buyBtnMobile">
                <i class="fas fa-shopping-cart me-2"></i>এখনই কিনুন — ২০ টাকা
              </button>
            </form>
          </div>
        </div>

        <!-- Countdown -->
        <div class="countdown-card mb-3 d-none d-lg-block">
          <div class="lbl">ড্র পর্যন্ত বাকি</div>
          <div id="countdown-desktop" style="color:var(--blue);font-size:1.05rem;font-weight:800;"></div>
          <div class="date">১৯ জুলাই ২০২৬</div>
        </div>

        <!-- Helpline -->
        <div class="helpline-card d-none d-lg-block">
          <a href="tel:09638222222" class="text-decoration-none fw-bold" style="color:#065f46;">
            <i class="fas fa-headset me-2"></i>হেল্পলাইন: ০৯৬৩৮-২২২২২২
          </a>
        </div>

        <!-- Find ticket link -->
        <div class="text-center mt-2 d-none d-lg-block">
          <a href="{{ route('my-ticket.show') }}" style="font-size:.8rem;color:var(--blue-mid);">
            <i class="fas fa-search me-1"></i>আগের টিকেট খুঁজুন
          </a>
        </div>

      </div>
    </div>

    <!-- RIGHT: Content -->
    <div class="col-lg-8 order-lg-2 order-2">

      <!-- Prize Banner -->
      <div class="prize-banner">
        <div>
          <div class="lbl">মোট পুরস্কার</div>
          <div class="num">৫০ লক্ষ টাকা</div>
          <div class="lbl">৯৪৮টি পুরস্কার</div>
        </div>
        <div class="text-end">
          <div class="lbl">টিকেটের মূল্য</div>
          <div class="num">মাত্র ২০ টাকা</div>
          <div class="lbl">মোবাইল ব্যালেন্স থেকে</div>
        </div>
      </div>

      <!-- Prize Grid -->
      <div class="mb-3">
        <div class="sec-title"><i class="fas fa-trophy me-2" style="color:#d97706;"></i>পুরস্কার তালিকা</div>
        <div class="prize-grid">
          <div class="prize-card first">
            <div class="prize-rank">🥇 ১ম পুরস্কার</div>
            <div class="prize-amount mega">১০ লক্ষ</div>
            <div class="prize-count">১টি পুরস্কার</div>
          </div>
          <div class="prize-card second">
            <div class="prize-rank">🥈 ২য় পুরস্কার</div>
            <div class="prize-amount" style="color:#475569;">৫ লক্ষ</div>
            <div class="prize-count">২টি পুরস্কার</div>
          </div>
          <div class="prize-card third">
            <div class="prize-rank">🥉 ৩য় পুরস্কার</div>
            <div class="prize-amount" style="color:#92400e;">২৫,০০০</div>
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
        <div class="sec-title"><i class="fas fa-file-contract me-2" style="color:var(--blue-mid);"></i>নিয়মাবলী</div>
        <div class="info-card px-3 py-1">
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>অনুমোদিত ব্যাংক, প্রতিষ্ঠান ও এই পোর্টাল ব্যতীত অন্য কোনো মাধ্যম হতে টিকেট ক্রয় করলে ঐ টিকেটের জন্য বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি ও সংশ্লিষ্ট কর্তৃপক্ষ দায়ী থাকবে না।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>ক্রয়কৃত লটারীর টিকেট নম্বর ও কনফার্মেশন কেবলমাত্র SMS এর মাধ্যমে পাঠানো হবে।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>SMS না পেলে আপনার মোবাইলের SMS এপ এর SPAM সেকশনে যাচাই করুন। অন্যথায় হেল্পলাইনে (8801701677479 বা 8801732701937) অথবা cservice@b2m-tech.com এ যোগাযোগ করুন।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>নির্ধারিত তারিখে বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি কর্তৃপক্ষ ও বিশিষ্ট ব্যক্তিদের উপস্থিতিতে ঢাকায় ড্র অনুষ্ঠিত হবে।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>লটারীর ড্র এর নির্ধারিত তারিখ ১৯ জুলাই ২০২৬; বিজয়ীদের তালিকা সংবাদপত্রের মাধ্যমে প্রকাশ করা হবে এবং বর্তমান ওয়েবসাইটেও (bpkslottery.com) বিজয়ীদের তালিকা প্রকাশ হবে।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>ফলাফল প্রকাশের ৩০ দিনের মধ্যে বিজয়ীদের পুরষ্কারের জন্য নাম ঠিকানা, সত্যায়িত ছবি ও টিকেট প্রাপ্তির এসএমএস সহ লিখিত দাবী কর্তৃপক্ষের নিকট দাখিল করতে হবে।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>৬ষ্ঠ হতে ৮ম পুরস্কারের ক্ষেত্রে বিজয়ী নম্বর ক, খ, গ, ঘ, ঙ, চ, ছ, জ, ঝ, ঞ প্রত্যেক সিরিজের ক্ষেত্রে প্রযোজ্য হবে।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>এই মোবাইল লটারি ক্রয় প্রক্রিয়ায় অপারেটর শুধুমাত্র পেমেন্ট পার্টনার হিসেবে বিদ্যমান; লটারি সংক্রান্ত সকল কার্যক্রম সম্পূর্ণরূপে বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি কর্তৃক পরিচালিত হয়।</span></div>
          <div class="terms-item"><i class="fas fa-check-circle"></i><span>লটারি ড্র সম্পন্ন হওয়ার পর বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি (বিপিকেএস)-এর ওয়েবসাইটে ফলাফল প্রকাশ করা হবে এবং পরদিন ইত্তেফাক ও সমকাল পত্রিকায় তা প্রকাশিত হবে।</span></div>
          <div class="terms-item highlight"><i class="fas fa-exclamation-circle"></i><span>এই লটারী সংক্রান্ত যে কোন বিষয়ে বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি কর্তৃপক্ষের সিদ্ধান্তই চূড়ান্ত বলে বিবেচিত হবে।</span></div>
        </div>
      </div>

      <!-- About -->
      <div class="mb-5 mb-lg-3">
        <div class="sec-title"><i class="fas fa-heart me-2" style="color:#ef4444;"></i>আমাদের লক্ষ্য</div>
        <div class="about-card">
          <p><i class="fas fa-wheelchair me-2" style="color:var(--blue-mid);"></i>
          আপনার প্রতিটি টিকেটের অর্থ সরাসরি ব্যবহৃত হবে <strong>বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি</strong>-এর প্রতিবন্ধী মানুষের উন্নয়নে।</p>
          <p class="mb-0" style="font-size:.8rem;color:var(--muted);">
            <i class="fas fa-envelope me-1"></i> nibircorporation88@gmail.com &nbsp;|&nbsp;
            <i class="fas fa-globe me-1"></i> bpkslottery.com
          </p>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="site-footer" style="margin-bottom:64px;" id="siteFooter">
  <div class="container">
    <div class="mb-1">বাংলাদেশ প্রতিবন্ধী কল্যাণ সমিতি (বিপিকেএস) &nbsp;|&nbsp; জাতীয় লটারি-২০২৬</div>
    <div>Powered by <a href="#">B2M Technologies Ltd.</a> &nbsp;|&nbsp;
      <a href="tel:09638222222">০৯৬৩৮-২২২২২২</a> &nbsp;|&nbsp;
      <a href="{{ route('my-ticket.show') }}">টিকেট খুঁজুন</a>
    </div>
  </div>
</footer>

<!-- STICKY BUY BAR (mobile) -->
<div class="sticky-buy-bar d-lg-none">
  <div class="d-flex align-items-center gap-2">
    <a href="tel:09638222222" class="btn btn-outline-secondary btn-sm rounded-pill flex-shrink-0">
      <i class="fas fa-headset"></i>
    </a>
    <a href="{{ route('my-ticket.show') }}" class="btn btn-outline-primary btn-sm rounded-pill flex-shrink-0" style="font-size:.75rem;">
      <i class="fas fa-search me-1"></i>টিকেট
    </a>
    <div class="flex-fill text-center small fw-bold" style="color:var(--blue);font-size:.72rem;">
      <i class="fas fa-clock me-1"></i><span id="countdown-bar"></span>
    </div>
  </div>
</div>

<!-- CONFIRM MODAL -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered mx-auto" style="max-width:360px;">
    <div class="modal-content shadow-lg">
      <div class="modal-header border-0 pb-0" style="background:var(--blue-lite);">
        <h6 class="modal-title fw-bold" style="color:var(--blue);">
          <i class="fas fa-exclamation-triangle me-2" style="color:#f59e0b;"></i>নিশ্চিতকরণ
        </h6>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-3 text-center">
        <p id="confirmMsg" class="fs-6 fw-medium mb-0"></p>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center gap-2 pb-3">
        <button class="btn btn-outline-secondary px-3 rounded-pill" data-bs-dismiss="modal">বাতিল</button>
        <button class="btn btn-primary px-4 rounded-pill fw-bold" id="confirmPayBtn" style="background:var(--blue);border-color:var(--blue);">
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
  const qtyBox       = document.getElementById('qtyBox');
  const qtyMinus     = document.getElementById('qtyMinus');
  const qtyPlus      = document.getElementById('qtyPlus');
  const qtyNumEl     = document.getElementById('qtyNum');
  const qtyTotalRow  = document.getElementById('qtyTotalRow');
  const qtyInput     = document.getElementById('qtyInput');
  const confirmMsg   = document.getElementById('confirmMsg');
  const confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));

  const BANGLA_DIGITS = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
  function toBangla(n) { return String(n).split('').map(d => BANGLA_DIGITS[d] ?? d).join(''); }

  const OPERATORS = {
    '013':'গ্রামীণফোন','017':'গ্রামীণফোন',
    '014':'বাংলালিংক', '019':'বাংলালিংক',
    '018':'রবি',       '016':'এয়ারটেল (রবি)',
    '015':'টেলিটক',
  };
  const ROBI_AIRTEL_PREFIXES = new Set(['016','018']);

  let qty = 1;

  function updateQty(n) {
    qty = Math.max(1, Math.min(5, n));
    qtyNumEl.textContent   = toBangla(qty);
    qtyInput.value         = qty;
    qtyTotalRow.textContent = 'মোট: ' + toBangla(qty * 20) + ' টাকা';
    qtyMinus.disabled = qty <= 1;
    qtyPlus.disabled  = qty >= 5;
  }

  qtyMinus.addEventListener('click', () => updateQty(qty - 1));
  qtyPlus.addEventListener('click',  () => updateQty(qty + 1));

  function getPrefix(val) {
    const c = val.replace(/\D/g,'');
    if (c.length >= 3 && c.startsWith('01')) return c.slice(0,3);
    if (c.length >= 2 && c[0]==='1')         return '0'+c.slice(0,2);
    return '';
  }

  function detectOp(val) {
    return OPERATORS[getPrefix(val)] || null;
  }

  phoneInput.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g,'').slice(0,11);
    const px = getPrefix(this.value);
    const op = OPERATORS[px] || null;
    if (op) {
      operatorBox.style.display = '';
      operatorText.innerHTML = `<span class="operator-badge"><i class="fas fa-sim-card me-1"></i>${op}</span> সনাক্ত হয়েছে ✓`;
    } else {
      operatorBox.style.display = 'none';
    }
    if (op && ROBI_AIRTEL_PREFIXES.has(px)) {
      qtyBox.style.display = '';
    } else {
      qtyBox.style.display = 'none';
      updateQty(1);
    }
  });

  if (phoneInput.value) phoneInput.dispatchEvent(new Event('input'));

  function triggerBuy() {
    const val = phoneInput.value.replace(/\D/g,'');
    if (!val) { phoneInput.focus(); return; }
    const op = detectOp(val);
    if (!op) { phoneInput.focus(); phoneInput.style.outline='2px solid #ef4444'; return; }
    phoneInput.style.outline='';
    const display  = val.length===11 ? val : '0'+val;
    const total    = qty * 20;
    const qtyLine  = qty > 1 ? `<br><span style="font-size:.85rem;color:var(--muted);">${toBangla(qty)} টি টিকেট × ২০ টাকা</span>` : '';
    confirmMsg.innerHTML = `<strong>${op}</strong> নম্বর<br><strong class="text-primary fs-5">${display}</strong>${qtyLine}<br>থেকে <strong class="text-danger">${toBangla(total)} টাকা</strong> কাটা হবে।`;
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
    const done   = 'ড্র সম্পন্ন';
    if (diff <= 0) {
      ['countdown-desktop','countdown-bar'].forEach(id => {
        const el = document.getElementById(id); if (el) el.textContent = done;
      });
      return;
    }
    const d = Math.floor(diff/86400000);
    const h = Math.floor((diff%86400000)/3600000);
    const m = Math.floor((diff%3600000)/60000);
    const s = Math.floor((diff%60000)/1000);
    const txt = `${d}d ${h}h ${m}m ${s}s`;
    ['countdown','countdown-mobile','countdown-desktop','countdown-bar'].forEach(id => {
      const el = document.getElementById(id); if (el) el.textContent = txt;
    });
  }
  updateCountdown();
  setInterval(updateCountdown, 1000);

  // Footer margin
  function adjustFooter() {
    const f = document.getElementById('siteFooter');
    if (window.innerWidth >= 992) f.style.marginBottom = '0';
    else f.style.marginBottom = '64px';
  }
  adjustFooter();
  window.addEventListener('resize', adjustFooter);
})();
</script>
</body>
</html>
