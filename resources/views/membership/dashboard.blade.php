@extends('layouts.membership')
@section('title','Dashboard')
@section('subtitle','A clear view of your community, all in one place.')
@section('actions')<a class="btn" href="{{ route('members.create') }}">+ Register member</a>@endsection
@section('content')
<section class="stats" aria-label="Membership overview"><article class="stat"><span>Total members</span><strong>{{ number_format($total) }}</strong><small>Your registered community</small></article><article class="stat"><span>Active members</span><strong>{{ number_format($active) }}</strong><small>Enabled with a current approved membership</small></article><article class="stat"><span>Pending transactions</span><strong>{{ number_format($pending) }}</strong><small>Awaiting full payment</small></article><article class="stat stat-accent"><span>Collected payments</span><strong>₱{{ number_format($collected,2) }}</strong><small>Total recorded collections</small></article></section>
<section class="panel"><div class="panel-title"><div><p class="eyebrow">THE LATEST ACTIVITY</p><h2>Recent transactions</h2></div><a class="text-link" href="{{ route('transactions.index') }}">View all →</a></div>@include('membership.transactions.table',['transactions'=>$recent])</section>
<section class="quick-grid"><a class="quick-card" href="{{ route('members.index') }}"><span>01 / COMMUNITY</span><h2>Know your members.</h2><p>Profiles, membership details, and a complete history.</p><b>Explore members →</b></a><a class="quick-card warm" href="{{ route('transactions.create') }}"><span>02 / MEMBERSHIPS</span><h2>Keep things moving.</h2><p>Start an application or renew an existing membership.</p><b>Create transaction →</b></a></section>
@endsection
