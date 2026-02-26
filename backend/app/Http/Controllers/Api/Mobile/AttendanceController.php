<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ClockInRequest;
use App\Http\Requests\Mobile\ClockOutRequest;
use App\Http\Requests\Mobile\ListAttendancesRequest;
use App\Http\Resources\Mobile\AttendanceResource;
use App\Models\AttendanceRecord;
use App\Models\SalesEntry;
use App\Models\Schedule;
use App\Services\AttendanceService;
use App\Services\MobileAccessService;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly MobileAccessService $mobileAccess
    ) {}

    public function index(ListAttendancesRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        $query = AttendanceRecord::query()->with(['store', 'employee']);

        if ($this->mobileAccess->isHr($user)) {
            // Full visibility.
        } elseif ($this->mobileAccess->isManager($user)) {
            $query->whereIn('store_id', $this->mobileAccess->accessibleStoreIds($user));
        } elseif ($this->mobileAccess->isSupervisor($user)) {
            $query->whereIn('store_id', $this->mobileAccess->accessibleStoreIds($user));
        } else {
            if (! $user->employee_id) {
                return response()->json([
                    'status' => 'success',
                    'data' => [],
                ]);
            }
            $query->where('employee_id', $user->employee_id);
        }

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        $records = $query->orderBy('date', 'desc')->paginate(20);
        $records->through(fn (AttendanceRecord $record) => (new AttendanceResource($record))->resolve());

        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }

    public function today()
    {
        $user = request()->user();
        if (! $user->employee_id) {
            abort(404, 'Employee profile not found.');
        }

        $record = $this->attendanceService->getTodayRecord($user->employee_id);

        return response()->json([
            'status' => 'success',
            'data' => $record ? (new AttendanceResource($record->load('store')))->resolve() : null,
        ]);
    }

    public function clockIn(ClockInRequest $request)
    {
        $user = $request->user();
        if (! $user->employee_id) {
            abort(404, 'Employee profile not found.');
        }

        $data = $request->validated();

        try {
            $record = $this->attendanceService->clockIn(
                $user->employee_id,
                $data['location'] ?? []
            );
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'clock_in' => $e->getMessage(),
            ]);
        }

        $fallbackStoreId = $data['store_id'] ?? $user->employee?->store_id;
        if (! $record->store_id && $fallbackStoreId) {
            if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($fallbackStoreId, $user->accessibleStoreIds(), true)) {
                abort(403, 'You are not assigned to this store.');
            }
            $record->update(['store_id' => $fallbackStoreId]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Clocked in successfully',
            'data' => (new AttendanceResource($record->fresh()->load('store')))->resolve(),
        ]);
    }

    public function clockOut(ClockOutRequest $request)
    {
        $user = $request->user();
        if (! $user->employee_id) {
            abort(404, 'Employee profile not found.');
        }

        $data = $request->validated();
        $today = now()->toDateString();

        $todayRecord = AttendanceRecord::query()
            ->where('employee_id', $user->employee_id)
            ->whereDate('date', $today)
            ->first();

        if (! $todayRecord) {
            throw ValidationException::withMessages([
                'clock_out' => 'No clock-in record found for today.',
            ]);
        }

        $closingShift = Schedule::query()
            ->where('employee_id', $user->employee_id)
            ->whereDate('date', $today)
            ->where('is_closing_shift', true)
            ->first();

        if ($closingShift) {
            $salesDate = $data['sales_date'] ?? $today;
            $hasSalesReport = SalesEntry::query()
                ->where('store_id', $closingShift->store_id)
                ->whereDate('date', $salesDate)
                ->where('employee_id', $user->employee_id)
                ->exists();

            // Backward compatibility: allow providing amount directly at clock-out.
            if (! $hasSalesReport && array_key_exists('sales_amount', $data)) {
                SalesEntry::query()->updateOrCreate(
                    [
                        'store_id' => $closingShift->store_id,
                        'date' => $salesDate,
                    ],
                    [
                        'employee_id' => $user->employee_id,
                        'amount' => $data['sales_amount'],
                    ]
                );
                $hasSalesReport = true;
            }

            if (! $hasSalesReport) {
                throw ValidationException::withMessages([
                    'sales_report' => 'Submit today\'s sales report before clock-out for a closing shift.',
                ]);
            }
        }

        try {
            $record = $this->attendanceService->clockOut(
                $user->employee_id,
                $data['location'] ?? []
            );
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'clock_out' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Clocked out successfully',
            'data' => (new AttendanceResource($record->fresh()->load('store')))->resolve(),
        ]);
    }
}
