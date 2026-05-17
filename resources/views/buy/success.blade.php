<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>টিকেট সফল | BPKS লটারি</title>
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

    .success-card {
      background: #fff;
      border-radius: 1.5rem;
      max-width: 420px;
      width: 100%;
      padding: 2rem 1.5rem;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0,0,0,0.35);
      animation: slideUp .4s ease;
    }

    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .check-ring {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; color: #fff;
      margin: 0 auto 1rem;
      box-shadow: 0 8px 20px rgba(16,185,129,.4);
      animation: pop .5s .2s both cubic-bezier(.175,.885,.32,1.275);
    }

    @keyframes pop {
      from { transform: scale(.4); opacity: 0; }
      to   { transform: scale(1);  opacity: 1; }
    }

    .ticket-box {
      background: linear-gradient(135deg, #1e3a8a, #2563eb);
      border-radius: 1rem;
      padding: .9rem 1rem;
      margin: 1rem 0;
      position: relative;
      overflow: hidden;
    }
    .ticket-box::before {
      content: '';
      position: absolute; top: -30px; right: -30px;
      width: 90px; height: 90px;
      background: rgba(255,255,255,.06);
      border-radius: 50%;
    }

    .ticket-label {
      color: rgba(255,255,255,.75);
      font-size: .75rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: .2rem;
    }

    .ticket-number {
      font-size: clamp(1.6rem, 8vw, 2.2rem);
      font-weight: 800;
      color: #fbbf24;
      letter-spacing: 3px;
      line-height: 1.1;
    }

    .info-row {
      background: #f8fafc;
      border-radius: .75rem;
      padding: .65rem 1rem;
      margin-bottom: .5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: .85rem;
    }
    .info-row .label { color: #64748b; }
    .info-row .value { font-weight: 600; color: #1e293b; }

    .btn-buy-more {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      color: #fff;
      border: none;
      border-radius: 2rem;
      padding: .7rem 2rem;
      font-weight: 700;
      font-size: 1rem;
      width: 100%;
      box-shadow: 0 4px 14px rgba(245,158,11,.4);
      transition: transform .15s, box-shadow .15s;
    }
    .btn-buy-more:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 18px rgba(245,158,11,.5);
      color: #fff;
    }

    .sms-note {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      border-radius: .75rem;
      padding: .6rem .9rem;
      font-size: .8rem;
      color: #166534;
      margin-bottom: 1rem;
    }

    .footer-note {
      font-size: .72rem;
      color: #94a3b8;
      margin-top: 1rem;
    }

    @media (max-width: 360px) {
      .success-card { padding: 1.5rem 1rem; border-radius: 1.25rem; }
      .check-ring   { width: 60px; height: 60px; font-size: 1.6rem; }
    }
  </style>
</head>
<body>
<div class="success-card">

  <div class="check-ring"><i class="fas fa-check"></i></div>

  <h2 class="fw-bold mb-1" style="font-size:1.4rem;">অভিনন্দন!</h2>
  <p class="text-muted mb-0" style="font-size:.88rem;">আপনার লটারি টিকেট নম্বর</p>

  <!-- Ticket number display -->
  <div class="ticket-box">
    <div class="ticket-label">টিকেট নম্বর</div>
    <div class="ticket-number">{{ $transaction->ticket->ticket_no }}</div>
  </div>

  <!-- SMS confirmation note -->
  <div class="sms-note">
    <i class="fas fa-sms me-1"></i>
    টিকেট নম্বর SMS-এ পাঠানো হয়েছে — <strong>{{ $transaction->phone }}</strong>
  </div>

  <!-- Info rows -->
  <div class="info-row">
    <span class="label"><i class="fas fa-mobile-alt me-1 text-primary"></i> ফোন</span>
    <span class="value">{{ $transaction->phone }}</span>
  </div>
  <div class="info-row">
    <span class="label"><i class="fas fa-wifi me-1 text-primary"></i> অপারেটর</span>
    <span class="value">{{ $transaction->operator }}</span>
  </div>
  <div class="info-row">
    <span class="label"><i class="fas fa-receipt me-1 text-primary"></i> রেফারেন্স</span>
    <span class="value" style="font-size:.75rem;word-break:break-all;">{{ $transaction->txn_ref }}</span>
  </div>

  <p class="text-muted mb-3" style="font-size:.82rem;">
    শুভকামনা! ড্র এর ফলাফল SMS-এ জানানো হবে।
  </p>

  <a href="{{ route('ticket.download', ['ref' => $transaction->txn_ref]) }}"
     class="btn btn-buy-more mb-2" download>
    <i class="fas fa-download me-1"></i> টিকেট ডাউনলোড করুন
  </a>
  <a href="{{ route('buy.index') }}" class="btn btn-buy-more"
     style="background:linear-gradient(135deg,#64748b,#475569);">
    <i class="fas fa-plus me-1"></i> আরও টিকেট কিনুন
  </a>

  <div class="footer-note">
    হেল্পলাইন: ০৯৬৩৮-২২২২২২ &nbsp;·&nbsp; Powered by B2M Technologies Ltd.
  </div>

</div>
</body>
</html>
