@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">ONE THING AT A TIME</p><h1>My Tasks<span class="heading-dot">.</span></h1><p class="page-intro">Make a little room for what matters today.</p></div>
        <span class="count-note">{{ $tasks->count() }} {{ $tasks->count() === 1 ? 'task' : 'tasks' }} on your list</span>
    </div>

    @if (session('success'))
        <div class="notice" role="status"><span aria-hidden="true">✓</span> {{ session('success') }}</div>
    @endif

    <div class="workspace">
    <section class="form-panel" aria-labelledby="new-task-heading">
    <div class="panel-heading"><span class="section-number">01</span><h2 id="new-task-heading">Add a task</h2></div>
    <p class="panel-intro">Off your mind. Onto the list.</p>
    <form action="{{ route('tasks.store') }}" method="POST">
        @csrf

        <div class="form-field">
        <label for="task_title">Task title <span class="required-mark" aria-hidden="true">*</span></label>
        <input type="text" id="task_title" name="task_title"
               value="{{ old('task_title') }}" placeholder="What needs to get done?" required maxlength="255"
               @error('task_title') aria-invalid="true" aria-describedby="title-error" @enderror>
        @error('task_title')
            <p class="field-error" id="title-error">{{ $message }}</p>
        @enderror

        </div>
        <div class="form-field">
        <label for="description">Description <span class="optional">optional</span></label>
        <textarea id="description" name="description" rows="4" placeholder="A few details, if you need them…"
            @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
        @error('description')
            <p class="field-error" id="description-error">{{ $message }}</p>
        @enderror

        </div>
        <button class="primary-button" type="submit"><span aria-hidden="true">+</span> Add Task</button>
    </form>

    <div class="small-note"><span aria-hidden="true">✦</span> Small steps still count.</div>
    </section>

    <section class="list-panel" aria-labelledby="saved-tasks-heading">
    <div class="list-heading"><div class="panel-heading"><span class="section-number">02</span><h2 id="saved-tasks-heading">Saved Tasks</h2></div><span class="task-count">{{ $tasks->count() }}</span></div>
    <div class="task-list">

    @forelse ($tasks as $task)
        <article class="task-card">
            <span class="task-indicator {{ $task->completed ? 'is-complete' : '' }}" aria-hidden="true">{{ $task->completed ? '✓' : '' }}</span>
            <div class="task-content">
            <h3>{{ $task->task_title }}</h3>
            <p class="task-description">{{ $task->description ?: 'No description provided.' }}</p>
            <div class="task-meta"><span class="status {{ $task->completed ? 'status-complete' : '' }}"><span class="sr-only">Status: </span>{{ $task->completed ? 'Completed' : 'Not completed' }}</span><span class="task-id">#{{ str_pad($task->id, 2, '0', STR_PAD_LEFT) }}</span></div>
            </div>
        </article>
    @empty
        <div class="empty-state"><span class="empty-icon" aria-hidden="true">☰</span><h3>A fresh start.</h3><p>No tasks have been added yet.</p><p>Add your first task and take it from there.</p></div>
    @endforelse
    </div>
    </section>
    </div>
@endsection
