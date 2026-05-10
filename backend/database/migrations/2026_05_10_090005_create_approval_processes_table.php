<?php

use App\Workflow\Enums\ProcessStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_processes', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 255);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('workflow_version_id')->constrained()->restrictOnDelete();
            $table->string('status', 32)->default(ProcessStatus::Pending->value);
            $table->unsignedBigInteger('current_step_instance_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('status');
            $table->index('current_step_instance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_processes');
    }
};
