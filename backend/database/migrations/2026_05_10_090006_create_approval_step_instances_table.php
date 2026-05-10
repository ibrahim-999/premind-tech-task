<?php

use App\Domains\Workflow\Enums\StepInstanceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_step_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ad_hoc_name', 120)->nullable();
            $table->string('ad_hoc_resolver_type', 64)->nullable();
            $table->json('ad_hoc_resolver_config')->nullable();
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('ad_hoc_reason')->nullable();
            $table->string('status', 32)->default(StepInstanceStatus::Pending->value);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['approval_process_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_step_instances');
    }
};
