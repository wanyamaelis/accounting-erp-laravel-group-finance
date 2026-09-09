<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Installer - {{ config('app.name') }}</title>
    <style>
        body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:2rem}
        .card{max-width:800px;margin:1rem auto;padding:1rem;border:1px solid #eee;border-radius:6px}
        .actions{margin-top:1rem}
        label{display:block;margin-top:.5rem}
        input,select{width:100%;padding:.5rem;margin-top:.25rem}
        .error{color:#c00}
    </style>
</head>
<body>
    <div class="card">
        <h1>Installer</h1>
        <div>
            @yield('content')
        </div>
    </div>
</body>
</html>
