<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_processes', function (Blueprint $table) {
            $table->foreign('current_step_instance_id')
                ->references('id')
                ->on('approval_step_instances')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_processes', function (Blueprint $table) {
            $table->dropForeign(['current_step_instance_id']);
        });
    }
};
