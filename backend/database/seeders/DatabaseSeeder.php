<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create once with a known default password; do not overwrite on future seeds.
        User::firstOrCreate(
            ['email' => 'superadmin@atlas.org'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('SuperAdmin@123'),
            ]
        );

        // Remove legacy seeded admin account if it exists.
        User::where('email', 'test@example.com')->delete();

        $this->call([
            EmployeeSeeder::class,
            PerformanceSeeder::class,
            RecruitmentSeeder::class,
            BenefitsSeeder::class,
            LeaveBalanceSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
