<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Employee;
use App\Models\EmployeeOnboarding;
use App\Models\Interview;
use App\Models\JobPosting;
use App\Models\OfferLetter;
use App\Models\OnboardingChecklist;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecruitmentService
{
    /**
     * Create a new job posting
     */
    public function createJobPosting(array $data, int $userId): JobPosting
    {
        $data['created_by'] = $userId;

        return JobPosting::create($data);
    }

    /**
     * Apply a candidate to a job posting
     */
    public function applyForJob(array $data): Application
    {
        return DB::transaction(function () use ($data) {
            // Find or create candidate
            $candidate = Candidate::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'] ?? null,
                    'current_company' => $data['current_company'] ?? null,
                    'current_position' => $data['current_position'] ?? null,
                    'resume_path' => $data['resume_path'] ?? null,
                    'source' => $data['source'] ?? 'website',
                    'status' => 'new',
                ]
            );

            // Create application
            return Application::create([
                'job_posting_id' => $data['job_posting_id'],
                'candidate_id' => $candidate->id,
                'cover_letter' => $data['cover_letter'] ?? null,
                'status' => 'under_review',
            ]);
        });
    }

    /**
     * Advance candidate application status
     */
    public function updateApplicationStatus(int $applicationId, string $status): Application
    {
        $application = Application::findOrFail($applicationId);
        $application->status = $status;
        $application->save();

        if ($status === 'moved_to_interview') {
            $application->candidate->update(['status' => 'interviewed']);
        } elseif ($status === 'rejected') {
            $application->candidate->update(['status' => 'rejected']);
        }

        return $application;
    }

    /**
     * Schedule an interview
     */
    public function scheduleInterview(array $data): Interview
    {
        // Update application status automatically if scheduling
        $this->updateApplicationStatus($data['application_id'], 'moved_to_interview');

        return Interview::create($data);
    }

    /**
     * Create offer letter
     */
    public function createOfferLetter(array $data): OfferLetter
    {
        $application = Application::findOrFail($data['application_id']);
        $application->candidate->update(['status' => 'offered']);

        return OfferLetter::create([
            'application_id' => $data['application_id'],
            'offer_date' => $data['offer_date'] ?? Carbon::today(),
            'salary_offered' => $data['salary_offered'],
            'joining_date' => $data['joining_date'],
            'status' => 'draft',
            'pdf_path' => $data['pdf_path'] ?? null,
        ]);
    }

    /**
     * Accept offer letter and convert candidate to employee
     */
    public function acceptOfferLetter(int $offerLetterId): Employee
    {
        return DB::transaction(function () use ($offerLetterId) {
            $offer = OfferLetter::with('application.candidate')->findOrFail($offerLetterId);

            if ($offer->status === 'accepted') {
                throw new Exception('Offer already accepted.');
            }

            $offer->status = 'accepted';
            $offer->save();

            $candidate = $offer->application->candidate;
            $candidate->status = 'hired';
            $candidate->save();

            // Create new employee
            $employee = Employee::create([
                'full_name' => $candidate->first_name.' '.$candidate->last_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'department' => $offer->application->jobPosting->department->name ?? 'Unassigned',
                'job_title' => $offer->application->jobPosting->title,
                'basic_salary' => $offer->salary_offered,
                'joining_date' => $offer->joining_date,
            ]);

            // Assign onboarding tasks automatically
            $this->assignOnboardingChecklists($employee->id);

            return $employee;
        });
    }

    /**
     * Assign mandatory onboarding tasks to new employee
     */
    public function assignOnboardingChecklists(int $employeeId): void
    {
        $checklists = OnboardingChecklist::where('is_mandatory', true)->get();

        foreach ($checklists as $item) {
            EmployeeOnboarding::create([
                'employee_id' => $employeeId,
                'checklist_item_id' => $item->id,
            ]);
        }
    }
}
