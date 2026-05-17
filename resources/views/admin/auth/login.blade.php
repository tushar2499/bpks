<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | BPKS লটারি</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Hind Siliguri', sans-serif; background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    .login-card { background: #fff; border-radius: 1.5rem; box-shadow: 0 20px 50px rgba(0,0,0,0.3); padding: 2.5rem; width: 100%; max-width: 420px; }
    .login-logo { background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #fff; width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.2rem; }
  </style>
</head>
<body>
  <div class="login-card">
    <div class="login-logo"><i class="fas fa-wheelchair"></i></div>
    <h4 class="text-center fw-bold mb-1">BPKS Admin</h4>
    <p class="text-center text-muted mb-4 small">প্রতিবন্ধী কল্যাণ লটারি — ব্যবস্থাপনা প্যানেল</p>

    @if($errors->any())
      <div class="alert alert-danger small py-2">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}">
      @csrf
      <div class="mb-3">
        <label class="form-label fw-semibold">ইমেইল</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">পাসওয়ার্ড</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-4 form-check">
        <input type="checkbox" name="remember" class="form-check-input" id="remember">
        <label for="remember" class="form-check-label">মনে রাখুন</label>
      </div>
      <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
        <i class="fas fa-sign-in-alt me-1"></i> লগইন
      </button>
    </form>
  </div>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</body>
</html>
