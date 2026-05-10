<?php

use App\Domains\PurchaseOrder\Enums\PurchaseOrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 64);
            $table->foreignId('department_id')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 32)->default(PurchaseOrderStatus::Draft->value);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('last_rejection_reason')->nullable();
            $table->unsignedInteger('submission_count')->default(0);
            $table->char('subject_hash', 64)->nullable();
            $table->timestamps();

            $table->index('requester_id');
            $table->index('status');
            $table->index('category');
            $table->index('department_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
