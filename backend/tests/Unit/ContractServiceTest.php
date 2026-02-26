<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Services\ContractService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_if_contract_exceeds_3_years()
    {
        $service = new ContractService;
        $employee = Employee::factory()->create();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Contract duration cannot exceed 3 years');

        $service->createFixedTermContract($employee, '2024-01-01', '2027-01-02');
    }

    public function test_succeeds_if_contract_is_exactly_3_years()
    {
        // To prevent dompdf missing during purely unit testing the logic, we test via mocking or just expect it creates the DB entry but fails at PDF generation due to mock absence
        // In this case, we'll just test the DB creation.
        // PDF generation requires the facade/blade view, which works in a Feature test, but unit testing the logic here is faster

        $service = new ContractService;
        $employee = Employee::factory()->create();

        $contract = $service->createFixedTermContract($employee, '2024-01-01', '2027-01-01');

        $this->assertEquals('2024-01-01', $contract->start_date->format('Y-m-d'));
        $this->assertEquals('2027-01-01', $contract->end_date->format('Y-m-d'));
        $this->assertEquals($employee->id, $contract->employee_id);
    }
}
