<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
            $table->foreignId('manager_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('manager_id');
            $table->boolean('is_department_head')->default(false)->after('department_id');

            $table->index('is_active');
            $table->index('manager_id');
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropColumn(['is_active', 'manager_id', 'department_id', 'is_department_head']);
        });
    }
};
