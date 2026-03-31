<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('default_currency_code', 3)->default('IDR');
            $table->foreign('default_currency_code')
                ->references('code')
                ->on('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->text('avatar_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['default_currency_code']);
            $table->dropColumn(['default_currency_code', 'avatar_url']);
        });
    }
};
