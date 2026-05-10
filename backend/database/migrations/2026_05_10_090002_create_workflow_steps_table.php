<?php

use App\Workflow\Enums\ApprovalMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order');
            $table->string('name', 120);
            $table->string('approval_mode', 32)->default(ApprovalMode::Single->value);
            $table->unsignedSmallInteger('required_approvals')->default(1);
            $table->timestamps();

            $table->unique(['workflow_version_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_steps');
    }
};
