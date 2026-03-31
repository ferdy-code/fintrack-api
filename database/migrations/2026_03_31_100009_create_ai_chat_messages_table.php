<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('ai_chat_sessions')->cascadeOnDelete();
            $table->string('role', 10);
            $table->text('content');
            $table->timestamp('created_at')->nullable();

            $table->index('session_id');
        });

        DB::statement("ALTER TABLE ai_chat_messages ADD CONSTRAINT ai_chat_messages_role_check CHECK (role IN ('user', 'model'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
};
