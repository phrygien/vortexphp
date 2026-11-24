<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'Mecene'))</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            line-height: 1.6;
        }
        header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 24px;
        }
        .nav-inner {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .brand {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            text-decoration: none;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-links a {
            color: #475569;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .nav-links a:hover { color: #0f172a; }
        main {
            max-width: 900px;
            margin: 32px auto;
            padding: 0 24px;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 16px;
        }
        .btn {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 7px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
        }
        .btn-primary { background: #0f172a; color: #ffffff; }
        .btn-secondary { background: #ffffff; color: #334155; border-color: #cbd5e1; }
        .btn-danger { background: #dc2626; color: #ffffff; }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid;
        }
        .alert-success { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
        .alert-error { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 14px;
            font-family: inherit;
        }
        .error-text { color: #dc2626; font-size: 13px; margin-top: 5px; }
        .meta { color: #64748b; font-size: 13px; }
    </style>
</head>
<body>
    <header>
        <div class="nav-inner">
            <a href="{{ route('posts.index') }}" class="brand">{{ config('app.name') }}</a>
            <nav class="nav-links">
                <a href="{{ route('posts.index') }}">Articles</a>
                <a href="{{ route('api.posts') }}">API JSON</a>
                <a href="{{ route('posts.create') }}" class="btn btn-primary">Nouvel article</a>
            </nav>
        </div>
    </header>

    <main>
        @include('partials.flash')
        @yield('content')
    </main>
</body>
</html>
