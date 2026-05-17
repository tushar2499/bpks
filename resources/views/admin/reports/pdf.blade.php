<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>BPKS Lottery Report</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; margin: 0; padding: 20px; }
    h1 { font-size: 20px; color: #1e3a8a; margin-bottom: 4px; }
    .subtitle { color: #64748b; font-size: 11px; margin-bottom: 20px; }
    .stat-box { display: inline-block; width: 22%; margin-right: 2%; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; text-align: center; vertical-align: top; }
    .stat-box .label { font-size: 10px; color: #64748b; margin-bottom: 4px; }
    .stat-box .value { font-size: 22px; font-weight: bold; color: #1e3a8a; }
    .stat-row { margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th { background: #1e3a8a; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; }
    td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .footer { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 10px; color: #94a3b8; font-size: 10px; text-align: center; }
    .highlight { color: #059669; font-weight: bold; }
  </style>
</head>
<body>
  <h1>BPKS Lottery Ticket Report</h1>
  <div class="subtitle">Generated: {{ now()->format('d F Y, h:i A') }} &nbsp;|&nbsp; Powered by B2M Technologies Ltd.</div>

  <div class="stat-row">
    <div class="stat-box">
      <div class="label">Total Tickets</div>
      <div class="value">{{ number_format($stats->total) }}</div>
    </div>
    <div class="stat-box" style="border-color:#d1fae5;">
      <div class="label">Sold</div>
      <div class="value" style="color:#059669;">{{ number_format($stats->sold) }}</div>
    </div>
    <div class="stat-box" style="border-color:#fef3c7;">
      <div class="label">Unsold</div>
      <div class="value" style="color:#d97706;">{{ number_format($stats->unsold) }}</div>
    </div>
    <div class="stat-box" style="border-color:#ede9fe;">
      <div class="label">Total Revenue</div>
      <div class="value" style="color:#7c3aed;">BDT {{ number_format($stats->revenue, 0) }}</div>
    </div>
  </div>

  <h3 style="font-size:14px;color:#1e3a8a;margin-bottom:4px;">Sales by Operator</h3>
  @if($byOperator->isEmpty())
    <p style="color:#94a3b8;">No sales data available.</p>
  @else
  <table>
    <thead>
      <tr><th>Operator</th><th>Tickets Sold</th><th>Revenue (BDT)</th><th>Avg per Ticket</th></tr>
    </thead>
    <tbody>
      @foreach($byOperator as $op)
      <tr>
        <td>{{ $op->operator ?: 'Unknown' }}</td>
        <td class="highlight">{{ number_format($op->count) }}</td>
        <td class="highlight">{{ number_format($op->revenue, 0) }}</td>
        <td>{{ $op->count > 0 ? number_format($op->revenue / $op->count, 2) : '-' }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif

  <div class="footer">
    BPKS — Disability Welfare Lottery &nbsp;|&nbsp; Helpline: 09638-222222 &nbsp;|&nbsp; B2M Technologies Ltd.
  </div>
</body>
</html>
