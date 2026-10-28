<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\EnsureAdministrator;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/about', [PageController::class, 'about']);
Route::get('/tasks/{id}', [PageController::class, 'task']);
Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::patch('/tasks/{task}/completion', [TaskController::class, 'updateCompletion'])->name('tasks.completion');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', EnsureAdministrator::class])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('members', MemberController::class);
    Route::patch('/members/{member}/reactivate', [MemberController::class, 'reactivate'])->name('members.reactivate');
    Route::resource('categories', CategoryController::class)->except(['create', 'show']);
    Route::resource('levels', LevelController::class)->except(['create', 'show']);
    Route::resource('transactions', TransactionController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/transactions/{transaction}/approve', [TransactionController::class, 'approve'])->name('transactions.approve');
    Route::patch('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/transactions/{transaction}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('/transactions/{transaction}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
});
