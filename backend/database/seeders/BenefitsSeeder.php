<?php

namespace Database\Seeders;

use App\Models\BenefitType;
use Illuminate\Database\Seeder;

class BenefitsSeeder extends Seeder
{
    public function run(): void
    {
        BenefitType::updateOrCreate(
            ['name' => 'Enhanced Insurance'],
            [
                'description' => 'Enhanced health insurance coverage for employees.',
                'type' => 'health_insurance',
                'eligibility_rules' => [],
                'is_active' => true,
            ]
        );

        BenefitType::updateOrCreate(
            ['name' => 'Plane Ticket'],
            [
                'description' => 'Annual return plane ticket benefit.',
                'type' => 'flight_ticket',
                'eligibility_rules' => [],
                'is_active' => true,
            ]
        );
    }
}
