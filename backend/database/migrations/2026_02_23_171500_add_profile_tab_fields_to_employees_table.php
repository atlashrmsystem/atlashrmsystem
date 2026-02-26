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
            $table->string('employee_pin')->nullable()->unique()->after('id');
            $table->string('first_name')->nullable()->after('employee_pin');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('gender')->nullable()->after('last_name');
            $table->string('status')->default('active')->after('department');
            $table->date('date_of_birth')->nullable()->after('status');

            $table->text('permanent_address')->nullable()->after('manager_id');
            $table->string('permanent_city')->nullable()->after('permanent_address');
            $table->string('permanent_country')->nullable()->after('permanent_city');
            $table->text('present_address')->nullable()->after('permanent_country');
            $table->string('present_city')->nullable()->after('present_address');
            $table->string('present_country')->nullable()->after('present_city');

            $table->string('linkedin_url')->nullable()->after('present_country');
            $table->string('facebook_url')->nullable()->after('linkedin_url');
            $table->string('x_url')->nullable()->after('facebook_url');

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropUnique(['employee_pin']);
            $table->dropColumn([
                'employee_pin',
                'first_name',
                'last_name',
                'gender',
                'status',
                'date_of_birth',
                'permanent_address',
                'permanent_city',
                'permanent_country',
                'present_address',
                'present_city',
                'present_country',
                'linkedin_url',
                'facebook_url',
                'x_url',
            ]);
        });
    }
};
