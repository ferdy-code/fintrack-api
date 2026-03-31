<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
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
            $table->timestampTz('transaction_date');
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('recurring_id')->nullable()->constrained('recurring_transactions')->nullOnDelete();
            $table->boolean('ai_categorized')->default(false);
            $table->decimal('ai_confidence', 3, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('attachment_url')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date'], 'transactions_user_date_desc_index');
            $table->index('wallet_id');
            $table->index('category_id');
            $table->index('type');
            $table->index('recurring_id');
        });

        DB::statement("ALTER TABLE transactions ADD CONSTRAINT transactions_type_check CHECK (type IN ('income', 'expense', 'transfer'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
