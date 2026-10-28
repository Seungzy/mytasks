<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#142c44"><title>@yield('title', 'Membership') · Daylist</title><link rel="stylesheet" href="{{ asset('css/membership.css') }}"></head>
<body><a class="skip" href="#main">Skip to content</a>
<aside class="sidebar no-print"><a class="brand" href="{{ route('dashboard') }}"><span class="brand-icon">d.</span> daylist<span class="brand-dot">.</span></a><p class="nav-caption">MEMBERSHIP WORKSPACE</p>
<nav aria-label="Main navigation">
@foreach (['dashboard'=>['Dashboard','01'], 'members.index'=>['Members','02'], 'categories.index'=>['Categories','03'], 'levels.index'=>['Membership levels','04'], 'transactions.index'=>['Transactions','05'], 'payments.index'=>['Payments','06']] as $route => [$label,$number])
<a href="{{ route($route) }}" @class(['nav-item','selected'=>request()->routeIs(explode('.', $route)[0].'*')]) @if(request()->routeIs(explode('.', $route)[0].'*')) aria-current="page" @endif><span>{{ $number }}</span>{{ $label }}</a>
@endforeach
</nav><div class="sidebar-bottom"><a href="{{ route('tasks.index') }}">↗ Original tasks activity</a><p>Members. Memberships. Momentum.</p></div></aside>
<div class="app"><header class="topbar no-print"><span>Membership management <span class="topbar-divider">/</span> @yield('title')</span><div class="account"><span class="avatar">{{ substr(auth()->user()->name,0,1) }}</span><span>{{ auth()->user()->name }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">Sign out</button></form></div></header>
<main id="main" class="main"><div class="page-heading"><div><p class="eyebrow">DAYLIST / MEMBERSHIP</p><h1>@yield('title')</h1><p class="intro">@yield('subtitle')</p></div><div class="actions no-print">@yield('actions')</div></div>
@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert error" role="alert"><strong>Please check the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
<footer class="footer no-print"><span>Daylist · Membership Management & Transaction Processing</span><span>All amounts in PHP</span></footer></main></div><script src="{{ asset('js/membership.js') }}" defer></script></body></html>
