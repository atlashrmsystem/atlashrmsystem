<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('employee_id')->constrained('stores')->nullOnDelete();
            $table->enum('workflow_status', [
                'pending_supervisor',
                'pending_manager',
                'pending_hr',
                'approved',
                'rejected',
            ])->default('pending_supervisor')->after('status');
            $table->timestamp('supervisor_approved_at')->nullable()->after('workflow_status');
            $table->timestamp('manager_approved_at')->nullable()->after('supervisor_approved_at');
            $table->timestamp('hr_approved_at')->nullable()->after('manager_approved_at');
            $table->timestamp('rejected_at')->nullable()->after('hr_approved_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');

            $table->index(['store_id', 'workflow_status']);
            $table->index(['employee_id', 'workflow_status']);
        });

        DB::table('leave_requests')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $workflowStatus = match ($row->status) {
                    'approved' => 'approved',
                    'rejected' => 'rejected',
                    default => 'pending_supervisor',
                };

                $storeId = DB::table('employees')->where('id', $row->employee_id)->value('store_id');

                DB::table('leave_requests')
                    ->where('id', $row->id)
                    ->update([
                        'workflow_status' => $workflowStatus,
                        'store_id' => $storeId,
                    ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'workflow_status']);
            $table->dropIndex(['employee_id', 'workflow_status']);
            $table->dropForeign(['store_id']);
            $table->dropColumn([
                'store_id',
                'workflow_status',
                'supervisor_approved_at',
                'manager_approved_at',
                'hr_approved_at',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
