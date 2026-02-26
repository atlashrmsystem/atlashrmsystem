<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure we have some departments and a user
        if (Department::count() === 0) {
            $departments = ['HR', 'Engineering', 'Sales', 'Marketing'];
            foreach ($departments as $name) {
                Department::create(['name' => $name, 'description' => $name.' Department']);
            }
        }

        if (User::count() === 0) {
            User::factory()->create(['email' => 'admin@atlas.com']);
        }

        // Create 10 Job Postings
        JobPosting::factory()->count(10)->create();

        // Create 30 Candidates
        Candidate::factory()->count(30)->create();

        // Create Applications for Candidates
        Candidate::all()->each(function ($candidate) {
            Application::factory()->create([
                'candidate_id' => $candidate->id,
                // Assign to a random job posting we just created
                'job_posting_id' => JobPosting::inRandomOrder()->first()->id,
            ]);
        });
    }
}
