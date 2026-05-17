<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>টিকেট খুঁজুন | BPKS লটারি</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Hind Siliguri', sans-serif;
      background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 60%, #3b82f6 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }

    .main-card {
      background: #fff;
      border-radius: 1.5rem;
      max-width: 440px;
      width: 100%;
      padding: 2rem 1.5rem;
      box-shadow: 0 20px 60px rgba(0,0,0,.35);
      animation: slideUp .4s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .page-icon {
      width: 64px; height: 64px;
      background: linear-gradient(135deg, #1e3a8a, #2563eb);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem; color: #fff;
      margin: 0 auto 1rem;
    }

    .phone-input {
      border: 2px solid #e2e8f0;
      border-radius: .75rem;
      padding: .75rem 1rem;
      font-size: 1.1rem;
      font-family: 'Hind Siliguri', sans-serif;
      width: 100%;
      transition: border-color .2s;
    }
    .phone-input:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }

    .btn-find {
      background: linear-gradient(135deg, #1e3a8a, #2563eb);
      color: #fff; border: none; border-radius: 2rem;
      padding: .75rem 2rem; font-weight: 700;
      font-size: 1rem; width: 100%;
      font-family: 'Hind Siliguri', sans-serif;
      box-shadow: 0 4px 14px rgba(30,58,138,.35);
      transition: transform .15s, box-shadow .15s;
    }
    .btn-find:hover { transform: translateY(-1px); color: #fff; }

    .ticket-row {
      background: #f8fafc;
      border-radius: .75rem;
      padding: .75rem 1rem;
      margin-bottom: .6rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: .75rem;
    }
    .ticket-no {
      font-size: 1.1rem; font-weight: 800;
      color: #b91c1c; letter-spacing: 1px;
    }
    .ticket-date { font-size: .72rem; color: #94a3b8; }

    .btn-dl {
      background: linear-gradient(135deg, #059669, #10b981);
      color: #fff; border: none; border-radius: 1.5rem;
      padding: .4rem .9rem; font-size: .8rem; font-weight: 700;
      white-space: nowrap; text-decoration: none;
      font-family: 'Hind Siliguri', sans-serif;
    }
    .btn-dl:hover { color: #fff; }

    .btn-back {
      background: linear-gradient(135deg, #64748b, #475569);
      color: #fff; border: none; border-radius: 2rem;
      padding: .65rem 2rem; font-weight: 700;
      font-size: .95rem; width: 100%;
      font-family: 'Hind Siliguri', sans-serif;
      text-decoration: none; display: block; text-align: center;
    }
    .btn-back:hover { color: #fff; }

    .footer-note { font-size: .72rem; color: #94a3b8; margin-top: 1rem; text-align: center; }
  </style>
</head>
<body>
<div class="main-card">

  <div class="page-icon"><i class="fas fa-ticket-alt"></i></div>
  <h2 class="fw-bold text-center mb-1" style="font-size:1.3rem;">টিকেট খুঁজুন</h2>
  <p class="text-muted text-center mb-4" style="font-size:.85rem;">আপনার ফোন নম্বর দিন</p>

  @if(session('error'))
    <div class="alert alert-danger py-2 px-3 mb-3" style="border-radius:.75rem;font-size:.85rem;">
      <i class="fas fa-exclamation-circle me-1"></i>{{ session('error') }}
    </div>
  @endif

  @if(!isset($transactions))
  {{-- Form state --}}
  <form method="POST" action="{{ route('my-ticket.find') }}">
    @csrf
    <div class="mb-3">
      <input type="tel" name="phone" class="phone-input @error('phone') border-danger @enderror"
             placeholder="01XXXXXXXXX" inputmode="numeric" maxlength="11"
             value="{{ old('phone') }}" autocomplete="tel" required>
      @error('phone')
        <div class="text-danger small mt-1">{{ $message }}</div>
      @enderror
    </div>
    <button type="submit" class="btn-find">
      <i class="fas fa-search me-2"></i>টিকেট খুঁজুন
    </button>
  </form>

  @else
  {{-- Results state --}}
  <div class="mb-1" style="font-size:.82rem;color:#64748b;">
    <i class="fas fa-mobile-alt me-1"></i>{{ $phone }} — {{ count($transactions) }}টি টিকেট পাওয়া গেছে
  </div>
  <hr class="my-2">

  @foreach($transactions as $txn)
  <div class="ticket-row">
    <div>
      <div class="ticket-no">{{ $txn->ticket->ticket_no }}</div>
      <div class="ticket-date">
        {{ $txn->confirmed_at?->format('d M Y') ?? $txn->created_at->format('d M Y') }}
      </div>
    </div>
    <a href="{{ route('ticket.download', ['ref' => $txn->txn_ref]) }}"
       class="btn-dl" download>
      <i class="fas fa-download me-1"></i>ডাউনলোড
    </a>
  </div>
  @endforeach

  <div class="mt-3">
    <a href="{{ route('my-ticket.show') }}" class="btn-back mb-2">
      <i class="fas fa-search me-1"></i> আবার খুঁজুন
    </a>
  </div>
  @endif

  <div class="mt-3">
    <a href="{{ route('buy.index') }}" class="btn-back" style="background:linear-gradient(135deg,#475569,#334155);">
      <i class="fas fa-home me-1"></i> হোম
    </a>
  </div>

  <div class="footer-note">হেল্পলাইন: ০৯৬৩৮-২২২২২২ &nbsp;·&nbsp; Powered by B2M Technologies Ltd.</div>
</div>
</body>
</html>
