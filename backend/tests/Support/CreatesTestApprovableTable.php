<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesTestApprovableTable
{
    protected function createTestApprovableTable(): void
    {
        if (Schema::hasTable('test_approvables')) {
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
}
