<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::create('membership_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('membership_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('fee', 10, 2);
            $table->unsignedInteger('validity_months');
            $table->text('benefits')->nullable();
            $table->timestamps();
        });
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('membership_number', 40)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->foreignId('membership_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_level_id')->constrained()->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['last_name', 'first_name']);
        });
        Schema::create('membership_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 50)->unique();
            $table->foreignId('member_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_level_id')->constrained()->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 10, 2);
            $table->unsignedInteger('validity_months');
            $table->string('status', 20)->default('pending')->index();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('membership_transaction_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 30);
            $table->string('external_reference')->nullable();
            $table->date('paid_at');
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('membership_transactions');
        Schema::dropIfExists('members');
        Schema::dropIfExists('membership_levels');
        Schema::dropIfExists('membership_categories');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
