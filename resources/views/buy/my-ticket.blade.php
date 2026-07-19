<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>টিকেট খুঁজুন | BPKS লটারি</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;600;700;800&display=swap" rel="stylesheet">
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-28X43HSNFH"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','G-28X43HSNFH');</script>
  <style>
    *{box-sizing:border-box;}
    body {
      font-family: 'Noto Sans Bengali', sans-serif;
      background: linear-gradient(160deg, #0f2460 0%, #1e40af 55%, #3b82f6 100%);
      min-height: 100vh;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* ── Search card ── */
    .search-card {
      background: #fff;
      border-radius: 1.5rem;
      width: 100%;
      max-width: 480px;
      padding: 1.5rem 1.5rem 1.25rem;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      animation: slideUp .4s ease;
      margin-bottom: 1rem;
    }
    @keyframes slideUp {
      from{opacity:0;transform:translateY(20px);}
      to{opacity:1;transform:translateY(0);}
    }
    .phone-input {
      border: 2px solid #e2e8f0;
      border-radius: .75rem;
      padding: .65rem 1rem;
      font-size: 1.05rem;
      font-family: 'Noto Sans Bengali', sans-serif;
      width: 100%;
      transition: border-color .2s;
    }
    .phone-input:focus {
      outline: none; border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }
    .btn-find {
      background: linear-gradient(135deg, #1e3a8a, #2563eb);
      color: #fff; border: none; border-radius: 2rem;
      padding: .65rem 1.5rem; font-weight: 700;
      font-size: .95rem; width: 100%;
      font-family: 'Noto Sans Bengali', sans-serif;
      box-shadow: 0 4px 14px rgba(30,58,138,.3);
      transition: transform .15s;
    }
    .btn-find:hover{transform:translateY(-1px);color:#fff;}
    .ticket-no-big {
      font-size: 1.15rem; font-weight: 800;
      color: #b91c1c; letter-spacing: 1px;
      font-family: monospace;
    }
    .btn-dl {
      background: linear-gradient(135deg, #059669, #10b981);
      color: #fff; border: none; border-radius: 1.5rem;
      padding: .4rem .9rem; font-size: .8rem; font-weight: 700;
      white-space: nowrap; text-decoration: none;
      font-family: 'Noto Sans Bengali', sans-serif;
    }
    .btn-dl:hover{color:#fff;}
    .btn-back {
      background: linear-gradient(135deg, #64748b, #475569);
      color: #fff; border: none; border-radius: 2rem;
      padding: .6rem 1.5rem; font-weight: 700;
      font-size: .9rem; width: 100%;
      font-family: 'Noto Sans Bengali', sans-serif;
      text-decoration: none; display: block; text-align: center;
    }
    .btn-back:hover{color:#fff;}

    /* ── Winner table card ── */
    .winner-card {
      background: #fff;
      border-radius: 1.5rem;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 20px 60px rgba(0,0,0,.3);
      overflow: hidden;
      animation: slideUp .5s ease .1s both;
      margin-bottom: 1.5rem;
    }
    .winner-header {
      background: linear-gradient(135deg, #1e3a8a, #1d4ed8);
      padding: 1rem 1.25rem .9rem;
      display: flex; align-items: center; justify-content: space-between;
      gap: .75rem;
    }
    .winner-header h2 {
      color: #fff; font-size: 1rem; font-weight: 800; margin: 0;
      letter-spacing: .3px;
    }
    .filter-wrap {
      padding: .75rem 1rem;
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
    }
    .filter-input {
      width: 100%;
      border: 1.5px solid #cbd5e1;
      border-radius: .6rem;
      padding: .45rem .75rem;
      font-size: .85rem;
      font-family: 'Noto Sans Bengali', sans-serif;
      background: #fff;
      transition: border-color .2s;
    }
    .filter-input:focus{outline:none;border-color:#2563eb;box-shadow:0 0 0 2px rgba(37,99,235,.1);}
    .winner-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .82rem;
    }
    .winner-table thead th {
      background: #1e3a8a;
      color: #fff;
      padding: .5rem .75rem;
      font-weight: 700;
      position: sticky; top: 0;
      white-space: nowrap;
    }
    .winner-table thead th:first-child{width:42%;}
    .winner-table tbody tr{border-bottom:1px solid #f1f5f9;transition:background .1s;}
    .winner-table tbody tr:hover{background:#eff6ff;}
    .winner-table tbody tr.hidden-row{display:none;}
    .winner-table td{padding:.45rem .75rem;vertical-align:middle;}
    .prize-badge {
      display: inline-block;
      border-radius: .4rem;
      padding: .15rem .5rem;
      font-size: .72rem;
      font-weight: 700;
      white-space: nowrap;
    }
    .ticket-chip {
      font-family: monospace;
      font-weight: 800;
      font-size: .85rem;
      color: #b91c1c;
      letter-spacing: .5px;
    }
    .ticket-chip.highlight {
      background: #fef9c3;
      border-radius: .3rem;
      padding: .05rem .3rem;
    }
    .table-scroll {
      max-height: 460px;
      overflow-y: auto;
    }
    .no-result {
      text-align: center; padding: 1.5rem;
      color: #94a3b8; font-size: .85rem;
      display: none;
    }
    .footer-note{font-size:.72rem;color:rgba(255,255,255,.55);text-align:center;margin-bottom:1rem;}

    /* Download overlay */
    .dl-overlay {
      display: none; position: fixed; inset: 0; z-index: 9999;
      background: rgba(15,23,42,.72); backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
      flex-direction: column; gap: 1rem;
    }
    .dl-overlay.show{display:flex;}
    .dl-spinner {
      width: 52px; height: 52px;
      border: 5px solid rgba(255,255,255,.2);
      border-top-color: #fff;
      border-radius: 50%; animation: spin .8s linear infinite;
    }
    @keyframes spin{to{transform:rotate(360deg);}}
    .dl-text{color:#fff;font-size:1rem;font-weight:700;}
    .dl-dots::after{content:'';animation:dots 1.5s steps(4,end) infinite;}
    @keyframes dots{0%{content:'';}25%{content:'.';}50%{content:'..';}75%{content:'...';}100%{content:'';}}
    a[download].dl-loading{opacity:.55;pointer-events:none;cursor:not-allowed;}
  </style>
</head>
<body>

{{-- ── Search Card ── --}}
<div class="search-card">
  <div class="text-center mb-3">
    <img src="{{ asset('logo.svg') }}" alt="BPKS" style="height:60px;width:auto;">
  </div>

  @if(\Carbon\Carbon::now('Asia/Dhaka')->gte(\Carbon\Carbon::parse('2026-07-17 23:45:00', 'Asia/Dhaka')))
  <div class="mb-3 p-3 text-center" style="background:#fff3cd;border:1px solid #ffc107;border-radius:.75rem;">
    <p class="mb-1" style="color:#856404;font-size:.82rem;font-weight:600;">
      <i class="fas fa-info-circle me-1"></i>লটারির টিকিট বিক্রয় বন্ধ হয়েছে।
    </p>
    <p class="mb-0" style="color:#856404;font-size:.78rem;">
      আপডেটের জন্য ভিজিট করুনঃ
      <a href="https://www.facebook.com/bpksbd1985" target="_blank" rel="noopener"
         style="color:#0d6efd;font-weight:700;">facebook.com/bpksbd1985</a>
    </p>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:.75rem;font-size:.83rem;">
    <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
  </div>
  @endif

  {{-- Congratulations --}}
  @if(isset($wonTickets) && count($wonTickets) > 0)
  <div class="mb-3 p-3 text-center" style="background:#d1fae5;border:2px solid #10b981;border-radius:.75rem;">
    <div style="font-size:1.4rem;">🎉</div>
    <p class="mb-2 fw-bold" style="color:#065f46;font-size:.92rem;">অভিনন্দন! আপনার টিকেট পুরস্কার জিতেছে!</p>
    @foreach($wonTickets as $w)
    <div class="mb-1 p-2" style="background:#a7f3d0;border-radius:.5rem;">
      <div class="fw-bold" style="color:#064e3b;font-family:monospace;font-size:.95rem;">{{ $w['ticket_no'] }}</div>
      <div style="color:#065f46;font-size:.78rem;">{{ $w['prize'] }}</div>
    </div>
    @endforeach
    <p class="mb-0 mt-2" style="color:#065f46;font-size:.75rem;">পুরস্কার সংগ্রহের জন্য BPKS-এর সাথে যোগাযোগ করুন।</p>
  </div>
  @endif

  @if(!isset($transactions))
  {{-- Search form --}}
  <form method="POST" action="{{ route('my-ticket.find') }}">
    @csrf
    <p class="text-muted text-center mb-2" style="font-size:.82rem;">আপনার ফোন নম্বর দিন</p>
    <div class="mb-2">
      <input type="tel" name="phone" class="phone-input @error('phone') border-danger @enderror"
             placeholder="01XXXXXXXXX" inputmode="numeric" maxlength="11"
             value="{{ old('phone') }}" autocomplete="tel" required>
      @error('phone')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <button type="submit" class="btn-find">
      <i class="fas fa-search me-2"></i>টিকেট খুঁজুন
    </button>
  </form>

  @else
  {{-- Results --}}
  @php $totalTickets = collect($ticketsByTxn)->sum(fn($t) => $t->count()); @endphp
  <p class="mb-2 text-muted" style="font-size:.8rem;">
    <i class="fas fa-mobile-alt me-1"></i>{{ $phone }} — {{ $totalTickets }}টি টিকেট
  </p>

  <a href="{{ route('ticket.download-all-pdf', ['phone' => $phone]) }}"
     class="btn-dl d-block text-center mb-3 py-2" download
     data-filename="BPKS-Tickets-{{ $phone }}.pdf"
     style="background:linear-gradient(135deg,#1e40af,#2563eb);border-radius:.75rem;font-size:.88rem;">
    <i class="fas fa-file-pdf me-1"></i>সব টিকেট PDF ডাউনলোড ({{ $totalTickets }}টি)
  </a>

  @foreach($transactions as $txn)
    @foreach($ticketsByTxn[$txn->id] as $t)
    <div style="background:#f8fafc;border-radius:.65rem;padding:.6rem .9rem;margin-bottom:.5rem;display:flex;align-items:center;justify-content:space-between;">
      <div>
        <div class="ticket-no-big">{{ $t->ticket_no }}</div>
        <div style="font-size:.7rem;color:#94a3b8;">{{ $txn->confirmed_at?->format('d M Y') ?? $txn->created_at->format('d M Y') }}</div>
      </div>
    </div>
    @endforeach
  @endforeach

  <div class="mt-3">
    <a href="{{ route('my-ticket.show') }}" class="btn-back mb-2">
      <i class="fas fa-search me-1"></i> আবার খুঁজুন
    </a>
  </div>
  @endif

  <div class="footer-note mt-3">Powered by B2M Technologies Ltd.</div>
</div>

{{-- ── Winner Table Card ── --}}
@if(!empty($winners))
@php
  $prizeColors = [
    1 => ['bg'=>'#fef3c7','color'=>'#92400e','label'=>'১ম পুরস্কার'],
    2 => ['bg'=>'#e2e8f0','color'=>'#374151','label'=>'২য় পুরস্কার'],
    3 => ['bg'=>'#fde8d0','color'=>'#7c2d12','label'=>'৩য় পুরস্কার'],
    4 => ['bg'=>'#ede9fe','color'=>'#5b21b6','label'=>'৪র্থ পুরস্কার'],
    5 => ['bg'=>'#cffafe','color'=>'#155e75','label'=>'৫ম পুরস্কার'],
    6 => ['bg'=>'#dcfce7','color'=>'#166534','label'=>'৬ষ্ঠ পুরস্কার'],
    7 => ['bg'=>'#fce7f3','color'=>'#9d174d','label'=>'৭ম পুরস্কার'],
    8 => ['bg'=>'#f0fdf4','color'=>'#14532d','label'=>'৮ম পুরস্কার'],
  ];
@endphp
<div class="winner-card">
  <div class="winner-header">
    <h2><i class="fas fa-trophy me-2" style="color:#fbbf24;"></i>বিজয়ী তালিকা</h2>
    <span style="color:rgba(255,255,255,.7);font-size:.78rem;">
      @php $total = array_sum(array_map(fn($g)=>count($g['winners']),$winners)); @endphp
      {{ $total }} বিজয়ী
    </span>
  </div>

  <div class="filter-wrap">
    <input type="text" id="ticketFilter" class="filter-input"
           placeholder="🔍  টিকেট নম্বর দিয়ে খুঁজুন…" autocomplete="off">
  </div>

  <div class="table-scroll">
    <table class="winner-table">
      <thead>
        <tr>
          <th>পুরস্কার</th>
          <th>টিকেট নম্বর</th>
        </tr>
      </thead>
      <tbody id="winnerTbody">
        @foreach($winners as $awardId => $group)
          @php $pc = $prizeColors[$awardId] ?? ['bg'=>'#f1f5f9','color'=>'#374151','label'=>$awardId.'তম']; @endphp
          @foreach($group['winners'] as $w)
          <tr class="winner-row" data-ticket="{{ strtolower($w['ticket_no']) }}">
            <td>
              <span class="prize-badge" style="background:{{ $pc['bg'] }};color:{{ $pc['color'] }};">
                {{ $pc['label'] }}
              </span>
            </td>
            <td><span class="ticket-chip">{{ $w['ticket_no'] }}</span></td>
          </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>
    <div class="no-result" id="noResult">কোনো টিকেট পাওয়া যায়নি।</div>
  </div>
</div>
@endif

<!-- Download overlay -->
<div class="dl-overlay" id="dlOverlay">
  <div class="dl-spinner"></div>
  <div class="dl-text">ডাউনলোড হচ্ছে<span class="dl-dots"></span></div>
</div>

<script>
(function(){
  // Download overlay
  var overlay = document.getElementById('dlOverlay');
  document.querySelectorAll('a[download]').forEach(function(link){
    link.addEventListener('click',function(e){
      e.preventDefault();
      var url=this.href, filename=this.dataset.filename||'ticket';
      overlay.classList.add('show');
      this.classList.add('dl-loading');
      fetch(url).then(function(r){return r.blob();}).then(function(blob){
        var a=document.createElement('a');
        a.href=URL.createObjectURL(blob);a.download=filename;
        document.body.appendChild(a);a.click();document.body.removeChild(a);
        setTimeout(function(){URL.revokeObjectURL(a.href);},1000);
        overlay.classList.remove('show');
      }).catch(function(){overlay.classList.remove('show');});
    });
  });

  // Winner filter
  var filterInput = document.getElementById('ticketFilter');
  if(!filterInput) return;
  var rows = document.querySelectorAll('.winner-row');
  var noResult = document.getElementById('noResult');

  filterInput.addEventListener('input', function(){
    var q = this.value.trim().toLowerCase().replace(/-/g,'');
    var visible = 0;
    rows.forEach(function(row){
      var ticket = row.dataset.ticket.replace(/-/g,'');
      if(!q || ticket.includes(q)){
        row.classList.remove('hidden-row');
        visible++;
        // highlight match
        var chip = row.querySelector('.ticket-chip');
        chip.classList.toggle('highlight', q.length > 0);
      } else {
        row.classList.add('hidden-row');
      }
    });
    noResult.style.display = (visible===0 && q) ? 'block' : 'none';
  });
})();
</script>
</body>
</html>
