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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('passport_number'); // encrypted
            $table->date('passport_expiry')->nullable();
            $table->string('visa_status')->nullable();
            $table->date('visa_expiry')->nullable();
            $table->string('emirates_id')->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->text('basic_salary'); // encrypted
            $table->json('allowances')->nullable();
            $table->date('joining_date')->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
