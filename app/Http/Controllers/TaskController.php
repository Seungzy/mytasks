<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::latest()->get();

        return view('tasks', compact('tasks'));
    }

    public function updateCompletion(Request $request, Task $task)
    {
        $validated = $request->validate([
            'completed' => 'required|boolean',
        ]);

        $task->update($validated);

        return redirect()->route('tasks.index')
            ->with('success', $task->completed ? 'Task marked as complete.' : 'Task marked as incomplete.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        Task::create($validated);

        return redirect()->route('tasks.index')
            ->with('success', 'Task added successfully.');
    }
}
