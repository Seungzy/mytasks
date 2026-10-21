<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_displays_controller_data(): void
    {
        $this->get('/about')->assertOk()
            ->assertSee('Web System Technologies')
            ->assertSee('Laravel Foundations');
    }

    public function test_task_example_displays_the_requested_id(): void
    {
        $this->get('/tasks/15')->assertOk()
            ->assertSee('Buy groceries')->assertSee('Task ID: 15');
        $this->get('/tasks/42')->assertOk()->assertSee('Task ID: 42');
    }

    public function test_task_list_displays_empty_and_saved_states(): void
    {
        $this->get('/tasks')->assertOk()->assertSee('No tasks have been added yet.');

        Task::create(['task_title' => 'Buy groceries']);

        $this->get('/tasks')->assertOk()->assertSee('Buy groceries')
            ->assertSee('No description provided.')->assertSee('Not completed');
    }

    public function test_valid_form_data_is_saved_with_default_status(): void
    {
        $this->post('/tasks', [
            'task_title' => 'Buy groceries',
            'description' => 'Milk and bread',
            'completed' => true,
            'id' => 999,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Task added successfully.');

        $this->assertDatabaseHas('tasks', [
            'task_title' => 'Buy groceries',
            'description' => 'Milk and bread',
            'completed' => false,
        ]);
        $this->assertDatabaseMissing('tasks', ['id' => 999]);
    }

    public function test_task_completion_can_be_set_and_reversed_without_changing_other_tasks(): void
    {
        $task = Task::create(['task_title' => 'Finish activity']);
        $other = Task::create(['task_title' => 'Other task']);

        $this->get('/tasks')->assertOk()->assertSee('Mark as complete');

        foreach ([1, 1, 0] as $completed) {
            $this->patch(route('tasks.completion', $task), [
                'completed' => $completed,
                'task_title' => 'Unwanted change',
            ])->assertRedirect(route('tasks.index'))->assertSessionHas('success');

            $this->assertDatabaseHas('tasks', [
                'id' => $task->id, 'completed' => $completed, 'task_title' => 'Finish activity',
            ]);
            $this->assertDatabaseHas('tasks', ['id' => $other->id, 'completed' => false]);

            $this->get('/tasks')->assertOk()
                ->assertSee($completed ? 'Mark as incomplete' : 'Mark as complete');
        }
    }

    public function test_completion_rejects_invalid_status_and_missing_tasks(): void
    {
        $task = Task::create(['task_title' => 'Finish activity']);
        foreach ([[], ['completed' => 'invalid']] as $input) {
            $this->patch(route('tasks.completion', $task), $input)
                ->assertSessionHasErrors('completed');
        }
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'completed' => false]);
        $this->patch('/tasks/999999/completion', ['completed' => 1])->assertNotFound();
    }

    public function test_invalid_title_is_rejected_and_input_is_preserved(): void
    {
        $this->from('/tasks')->post('/tasks', [
            'task_title' => '',
            'description' => 'Keep this description',
        ])->assertRedirect('/tasks')->assertSessionHasErrors('task_title')
            ->assertSessionHasInput('description', 'Keep this description');

        $this->post('/tasks', ['task_title' => str_repeat('a', 256)])
            ->assertSessionHasErrors('task_title');

        $this->assertDatabaseCount('tasks', 0);
    }
}
