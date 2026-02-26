<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pay_slip_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->string('month', 7); // YYYY-MM
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('workflow_status', [
                'pending_supervisor',
                'pending_manager',
                'pending_hr',
                'approved',
                'rejected',
            ])->default('pending_supervisor');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->timestamp('manager_approved_at')->nullable();
            $table->timestamp('hr_approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'workflow_status']);
            $table->index(['store_id', 'workflow_status']);
            $table->index(['month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_slip_requests');
    }
};
