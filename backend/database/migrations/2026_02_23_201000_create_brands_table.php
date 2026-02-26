<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $brands = [
            'Milestones Coffee',
            'SandwichPlus',
            'Coffee Beans World',
            'Il Mio Cioccolato',
            'Venale',
            'AL Maryah Rostery',
            'Tomahawk',
            'Cuccina Lagera',
        ];

        $now = now();
        $rows = [];
        foreach ($brands as $name) {
            $rows[] = [
                'name' => $name,
                'slug' => Str::slug($name),
                'manager_user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('brands')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
