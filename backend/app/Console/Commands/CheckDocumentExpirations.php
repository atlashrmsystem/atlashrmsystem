<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDocumentExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hr:check-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for employee documents and contracts expiring in 30 or 60 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        // Check documents (passport, visa) expiring in exactly 30 days
        $targetDate30 = $today->copy()->addDays(30);
        $expiringPassports = Employee::where('passport_expiry', $targetDate30->format('Y-m-d'))->get();
        $expiringVisas = Employee::where('visa_expiry', $targetDate30->format('Y-m-d'))->get();

        foreach ($expiringPassports as $emp) {
            Log::info("ALERT: Employee {$emp->full_name} passport expires in 30 days.");
            // In a real application, emit an Event or Notification here
        }

        foreach ($expiringVisas as $emp) {
            Log::info("ALERT: Employee {$emp->full_name} visa expires in 30 days.");
        }

        // Check contracts expiring in exactly 60 days
        $targetDate60 = $today->copy()->addDays(60);
        $expiringContracts = Contract::where('end_date', $targetDate60->format('Y-m-d'))->with('employee')->get();

        foreach ($expiringContracts as $contract) {
            Log::info("ALERT: Contract for {$contract->employee->full_name} expires in 60 days.");
            // Notify HR logic goes here
        }

        $this->info('Expiration checks completed.');
    }
}
