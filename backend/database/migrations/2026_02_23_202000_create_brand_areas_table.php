<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['brand_id', 'name']);
        });

        $milestonesId = DB::table('brands')->where('name', 'Milestones Coffee')->value('id');
        if ($milestonesId) {
            $now = now();
            DB::table('brand_areas')->insert([
                ['brand_id' => $milestonesId, 'name' => 'Area 1', 'manager_user_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['brand_id' => $milestonesId, 'name' => 'Area 2', 'manager_user_id' => null, 'created_at' => $now, 'updated_at' => $now],
                ['brand_id' => $milestonesId, 'name' => 'Area 3', 'manager_user_id' => null, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_areas');
    }
};
