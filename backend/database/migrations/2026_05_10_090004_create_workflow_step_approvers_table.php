<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_step_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_step_id')->constrained()->cascadeOnDelete();
            $table->string('resolver_type', 64);
            $table->json('config');
            $table->timestamps();

            $table->index('workflow_step_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_approvers');
    }
};
