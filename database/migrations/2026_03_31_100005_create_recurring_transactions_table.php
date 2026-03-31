<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10);
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->cascadeOnUpdate()->restrictOnDelete();
            $table->text('description')->nullable();
            $table->string('merchant_name', 255)->nullable();
            $table->string('frequency', 20);
            $table->date('next_due_date');
            $table->date('last_processed')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_create')->default(true);
            $table->timestamps();

            $table->index('user_id');
        });

        DB::statement("ALTER TABLE recurring_transactions ADD CONSTRAINT recurring_transactions_type_check CHECK (type IN ('income', 'expense'))");
        DB::statement("ALTER TABLE recurring_transactions ADD CONSTRAINT recurring_transactions_frequency_check CHECK (frequency IN ('daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'))");
        DB::statement('CREATE INDEX recurring_transactions_next_due_date_active_index ON recurring_transactions (next_due_date) WHERE is_active = true');
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};
