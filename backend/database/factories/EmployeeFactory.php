<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'employee_pin' => fake()->unique()->bothify('EMP######'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => "{$firstName} {$lastName}",
            'email' => fake()->unique()->safeEmail(),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive']),
            'date_of_birth' => fake()->dateTimeBetween('-55 years', '-20 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'passport_number' => strtoupper(fake()->bothify('??#######')),
            'passport_expiry' => fake()->dateTimeBetween('now', '+10 years')->format('Y-m-d'),
            'visa_status' => 'Active',
            'visa_issue_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'visa_expiry' => fake()->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
            'emirates_id' => fake()->numerify('784-####-#######-#'),
            'job_title' => fake()->jobTitle(),
            'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Operations']),
            'basic_salary' => fake()->numberBetween(5000, 50000),
            'joining_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'permanent_address' => fake()->streetAddress(),
            'permanent_city' => fake()->city(),
            'permanent_country' => fake()->country(),
            'present_address' => fake()->streetAddress(),
            'present_city' => fake()->city(),
            'present_country' => fake()->country(),
            'linkedin_url' => fake()->boolean(30) ? fake()->url() : null,
            'facebook_url' => fake()->boolean(20) ? fake()->url() : null,
            'x_url' => fake()->boolean(20) ? fake()->url() : null,
        ];
    }
}
