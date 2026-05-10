<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        Schema::create('test_approvables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->double('amount')->nullable();
            $table->string('category')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->foreignId('submitter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('test_approvables');
    }
};
