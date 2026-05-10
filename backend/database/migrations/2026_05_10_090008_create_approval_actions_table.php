<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_step_instance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('action', 16);
            $table->text('comment')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamp('created_at')->nullable();

            $table->index(['approval_step_instance_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
