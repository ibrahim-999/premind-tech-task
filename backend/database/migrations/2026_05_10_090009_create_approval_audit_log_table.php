<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_audit_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_process_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 64);
            $table->json('payload')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->index();

            $table->index(['approval_process_id', 'occurred_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_audit_log');
    }
};
