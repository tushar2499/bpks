<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { background: #fff; }
  .page {
    width: 100%;
    page-break-after: always;
    text-align: center;
  }
  .page:last-child { page-break-after: avoid; }
  .ticket-img {
    width: 100%;
    height: auto;
    display: block;
  }
</style>
</head>
<body>
@foreach($images as $img)
<div class="page">
  <img class="ticket-img" src="data:image/jpeg;base64,{{ $img['b64'] }}">
</div>
@endforeach
</body>
</html>
