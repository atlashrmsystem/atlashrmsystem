<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('passport_issue_date')->nullable()->after('passport_number');
            $table->date('insurance_start_date')->nullable()->after('visa_expiry');
            $table->date('insurance_end_date')->nullable()->after('insurance_start_date');
            $table->date('emirates_id_issue_date')->nullable()->after('emirates_id');
            $table->date('emirates_id_expiry_date')->nullable()->after('emirates_id_issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'passport_issue_date',
                'insurance_start_date',
                'insurance_end_date',
                'emirates_id_issue_date',
                'emirates_id_expiry_date',
            ]);
        });
    }
};
