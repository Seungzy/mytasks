@extends('layouts.app')
@section('title', 'About')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">BEHIND THE LIST</p><h1>About<span class="heading-dot">.</span></h1><p class="page-intro">A simple place for your everyday tasks.</p></div></div>
    <section class="detail-panel"><span class="section-number">THE PROJECT</span><h2>{{ $course }}</h2><p>{{ $module }}</p><p class="detail-copy">Built with Laravel. Add a task, keep the details together, and see your list in one place.</p><a class="text-link" href="{{ route('tasks.index') }}">Back to my tasks <span aria-hidden="true">→</span></a></section>
@endsection
