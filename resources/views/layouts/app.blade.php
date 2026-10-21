<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#03045e">
    <title>@yield('title', 'My Tasks') · Daylist</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="{{ route('tasks.index') }}" aria-label="Daylist home"><span class="brand-mark" aria-hidden="true"><i></i><i></i><i></i></span>daylist<span class="brand-dot">.</span></a>
            <nav aria-label="Main navigation">
                <a href="{{ route('tasks.index') }}" @class(['nav-link', 'active' => request()->is('tasks*')]) @if(request()->is('tasks*')) aria-current="page" @endif>My tasks</a>
                <a href="{{ url('/about') }}" @class(['nav-link', 'active' => request()->is('about')]) @if(request()->is('about')) aria-current="page" @endif>About</a>
            </nav>
        </div>
    </header>
    <main id="main" class="page-shell">@yield('content')</main>
    <footer class="site-footer"><span>A little space to get things done.</span><span class="palette-dots" aria-hidden="true"><i></i><i></i><i></i></span></footer>
</body>
</html>
