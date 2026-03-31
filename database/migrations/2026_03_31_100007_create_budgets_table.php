<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3);
            $table->foreign('currency_code')->references('code')->on('currencies')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('period', 10);
            $table->decimal('alert_threshold', 3, 2)->default(0.80);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'period']);
            $table->index('user_id');
        });

        DB::statement("ALTER TABLE budgets ADD CONSTRAINT budgets_period_check CHECK (period IN ('weekly', 'monthly', 'yearly'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
