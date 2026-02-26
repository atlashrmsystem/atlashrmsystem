<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListSchedulesRequest;
use App\Http\Requests\Mobile\StoreScheduleRequest;
use App\Http\Requests\Mobile\UpdateScheduleRequest;
use App\Http\Resources\Mobile\ScheduleResource;
use App\Models\Employee;
use App\Models\Schedule;
use App\Models\ScheduleWeekPublication;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    public function weekStatus(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'week_start' => ['required', 'date'],
        ]);

        $storeId = (int) $data['store_id'];
        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($storeId, $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString();
        $publication = ScheduleWeekPublication::query()
            ->where('store_id', $storeId)
            ->whereDate('week_start', $weekStart)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'store_id' => $storeId,
                'week_start' => $weekStart,
                'status' => $publication?->status ?? ScheduleWeekPublication::STATUS_DRAFT,
                'published_at' => $publication?->published_at?->toDateTimeString(),
            ],
        ]);
    }

    public function weeks(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $storeId = (int) $data['store_id'];
        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($storeId, $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $scheduledWeekStarts = Schedule::query()
            ->where('store_id', $storeId)
            ->selectRaw('DATE_SUB(`date`, INTERVAL WEEKDAY(`date`) DAY) as week_start')
            ->distinct()
            ->orderBy('week_start')
            ->pluck('week_start')
            ->map(fn ($weekStart) => Carbon::parse($weekStart)->toDateString());

        $publicationRows = ScheduleWeekPublication::query()
            ->where('store_id', $storeId)
            ->orderBy('week_start')
            ->get(['week_start', 'status', 'published_at']);

        $publicationByWeek = $publicationRows->keyBy(
            fn (ScheduleWeekPublication $row) => $row->week_start->toDateString()
        );

        $allWeekStarts = $scheduledWeekStarts
            ->merge($publicationByWeek->keys())
            ->unique()
            ->sort()
            ->values();

        $weeks = $allWeekStarts->values()->map(function (string $weekStart, int $index) use ($publicationByWeek, $storeId) {
            /** @var ScheduleWeekPublication|null $publication */
            $publication = $publicationByWeek->get($weekStart);

            return [
                'store_id' => $storeId,
                'week_index' => $index + 1,
                'week_start' => $weekStart,
                'week_end' => Carbon::parse($weekStart)->addDays(6)->toDateString(),
                'status' => $publication?->status ?? ScheduleWeekPublication::STATUS_DRAFT,
                'published_at' => $publication?->published_at?->toDateTimeString(),
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => $weeks,
        ]);
    }

    public function index(ListSchedulesRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        $query = Schedule::query()->with(['store', 'shift', 'employee']);

        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            // Full visibility.
        } elseif ($user->hasAnyRole(['manager', 'supervisor', 'shift-supervisor'])) {
            $query->whereIn('store_id', $user->accessibleStoreIds());
        } else {
            if (! $user->employee_id) {
                return ScheduleResource::collection(collect());
            }

            $query->where('employee_id', $user->employee_id);
        }

        if (! empty($filters['store_id'])) {
            $query->where('store_id', $filters['store_id']);
        }
        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return ScheduleResource::collection(
            $query->orderBy('date')->paginate(30)
        );
    }

    public function store(StoreScheduleRequest $request)
    {
        $this->authorize('create', Schedule::class);

        $user = $request->user();
        $data = $request->validated();

        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($data['store_id'], $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $employee = Employee::findOrFail($data['employee_id']);
        if ((int) $employee->store_id !== (int) $data['store_id']) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee is not assigned to the selected store.',
            ]);
        }

        $shift = Shift::findOrFail($data['shift_id']);
        if ((int) $shift->store_id !== (int) $data['store_id']) {
            throw ValidationException::withMessages([
                'shift_id' => 'Shift does not belong to the selected store.',
            ]);
        }

        $alreadyScheduled = Schedule::query()
            ->where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])
            ->exists();

        if ($alreadyScheduled) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee already has a schedule for this date.',
            ]);
        }

        $schedule = DB::transaction(function () use ($data) {
            $schedule = Schedule::create($data);

            $this->normalizeClosingShiftForDay(
                storeId: (int) $data['store_id'],
                date: (string) $data['date'],
                preferredScheduleId: ($data['is_closing_shift'] ?? false) === true ? (int) $schedule->id : null,
            );
            $this->markWeekEditedAfterPublish(
                storeId: (int) $data['store_id'],
                date: (string) $data['date'],
            );

            return $schedule;
        });

        return new ScheduleResource($schedule->load(['store', 'shift', 'employee']));
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule)
    {
        $this->authorize('update', $schedule);

        $user = $request->user();
        $data = $request->validated();

        $targetStoreId = (int) ($data['store_id'] ?? $schedule->store_id);
        $targetEmployeeId = (int) ($data['employee_id'] ?? $schedule->employee_id);
        $targetShiftId = (int) ($data['shift_id'] ?? $schedule->shift_id);
        $targetDate = $data['date'] ?? $schedule->date->toDateString();

        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($targetStoreId, $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $employee = Employee::findOrFail($targetEmployeeId);
        if ((int) $employee->store_id !== $targetStoreId) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee is not assigned to the selected store.',
            ]);
        }

        $shift = Shift::findOrFail($targetShiftId);
        if ((int) $shift->store_id !== $targetStoreId) {
            throw ValidationException::withMessages([
                'shift_id' => 'Shift does not belong to the selected store.',
            ]);
        }

        $conflict = Schedule::query()
            ->where('employee_id', $targetEmployeeId)
            ->whereDate('date', $targetDate)
            ->where('id', '!=', $schedule->id)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'employee_id' => 'Employee already has a schedule for this date.',
            ]);
        }

        DB::transaction(function () use ($schedule, $data) {
            $previousStoreId = (int) $schedule->store_id;
            $previousDate = $schedule->date->toDateString();

            $schedule->update($data);

            $this->normalizeClosingShiftForDay(
                storeId: (int) $schedule->store_id,
                date: $schedule->date->toDateString(),
                preferredScheduleId: ($data['is_closing_shift'] ?? false) === true ? (int) $schedule->id : null,
            );
            $this->markWeekEditedAfterPublish(
                storeId: (int) $schedule->store_id,
                date: $schedule->date->toDateString(),
            );

            if (
                $previousStoreId !== (int) $schedule->store_id
                || $previousDate !== $schedule->date->toDateString()
            ) {
                $this->normalizeClosingShiftForDay(
                    storeId: $previousStoreId,
                    date: $previousDate,
                    preferredScheduleId: null,
                );
                $this->markWeekEditedAfterPublish(
                    storeId: $previousStoreId,
                    date: $previousDate,
                );
            }
        });

        return new ScheduleResource($schedule->fresh()->load(['store', 'shift', 'employee']));
    }

    public function destroy(Request $request, Schedule $schedule)
    {
        $this->authorize('delete', $schedule);

        DB::transaction(function () use ($schedule) {
            $storeId = (int) $schedule->store_id;
            $date = $schedule->date->toDateString();
            $schedule->delete();

            $this->normalizeClosingShiftForDay(
                storeId: $storeId,
                date: $date,
                preferredScheduleId: null,
            );
            $this->markWeekEditedAfterPublish(
                storeId: $storeId,
                date: $date,
            );
        });

        return response()->json([], 204);
    }

    public function publish(Request $request)
    {
        $this->authorize('create', Schedule::class);

        $user = $request->user();
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'week_start' => ['required', 'date'],
        ]);

        $storeId = (int) $data['store_id'];
        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($storeId, $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = (clone $weekStart)->addDays(6);

        $staffCount = Employee::query()
            ->where('store_id', $storeId)
            ->count();

        $scheduledRows = Schedule::query()
            ->where('store_id', $storeId)
            ->whereDate('date', '>=', $weekStart->toDateString())
            ->whereDate('date', '<=', $weekEnd->toDateString())
            ->get(['employee_id', 'date', 'is_closing_shift']);

        $assignedCount = $scheduledRows->count();
        $expectedSlots = $staffCount * 7;
        $unassignedSlots = max($expectedSlots - $assignedCount, 0);

        $closingByDay = $scheduledRows
            ->where('is_closing_shift', true)
            ->groupBy(fn ($row) => $row->date->toDateString())
            ->map(fn ($rows) => $rows->count());

        $daysWithoutClosing = 0;
        $daysWithMultipleClosing = 0;
        for ($i = 0; $i < 7; $i++) {
            $date = (clone $weekStart)->addDays($i)->toDateString();
            $count = (int) ($closingByDay[$date] ?? 0);
            if ($count == 0) {
                $daysWithoutClosing++;
            }
            if ($count > 1) {
                $daysWithMultipleClosing++;
            }
        }

        $ready = $unassignedSlots === 0 && $daysWithoutClosing === 0 && $daysWithMultipleClosing === 0;
        if ($ready) {
            ScheduleWeekPublication::query()->updateOrCreate(
                [
                    'store_id' => $storeId,
                    'week_start' => $weekStart->toDateString(),
                ],
                [
                    'status' => ScheduleWeekPublication::STATUS_PUBLISHED,
                    'published_by_user_id' => $user->id,
                    'published_at' => now(),
                ]
            );
        }

        $publication = ScheduleWeekPublication::query()
            ->where('store_id', $storeId)
            ->whereDate('week_start', $weekStart->toDateString())
            ->first();
        $status = $publication?->status ?? ScheduleWeekPublication::STATUS_DRAFT;

        return response()->json([
            'status' => 'success',
            'message' => $ready
                ? 'Week schedule published successfully.'
                : 'Week has validation issues. Review and publish again.',
            'data' => [
                'store_id' => $storeId,
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'staff_count' => $staffCount,
                'expected_slots' => $expectedSlots,
                'assigned_slots' => $assignedCount,
                'unassigned_slots' => $unassignedSlots,
                'days_without_closing_shift' => $daysWithoutClosing,
                'days_with_multiple_closing_shift' => $daysWithMultipleClosing,
                'ready_to_publish' => $ready,
                'published_at' => $publication?->published_at?->toDateTimeString(),
                'week_status' => $status,
            ],
        ]);
    }

    private function weekStartForDate(string $date): string
    {
        return Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    private function markWeekEditedAfterPublish(int $storeId, string $date): void
    {
        $weekStart = $this->weekStartForDate($date);
        ScheduleWeekPublication::query()
            ->where('store_id', $storeId)
            ->whereDate('week_start', $weekStart)
            ->where('status', ScheduleWeekPublication::STATUS_PUBLISHED)
            ->update(['status' => ScheduleWeekPublication::STATUS_EDITED_AFTER_PUBLISH]);
    }

    private function normalizeClosingShiftForDay(int $storeId, string $date, ?int $preferredScheduleId): void
    {
        $daySchedules = Schedule::query()
            ->where('store_id', $storeId)
            ->whereDate('date', $date)
            ->lockForUpdate()
            ->get(['id', 'is_closing_shift']);

        if ($daySchedules->isEmpty()) {
            return;
        }

        $chosenId = null;

        if ($preferredScheduleId !== null && $daySchedules->contains('id', $preferredScheduleId)) {
            $chosenId = $preferredScheduleId;
        } else {
            $existingClosing = $daySchedules->firstWhere('is_closing_shift', true);
            if ($existingClosing) {
                $chosenId = (int) $existingClosing->id;
            }
        }

        if ($chosenId === null) {
            $chosenId = (int) $daySchedules->first()->id;
        }

        Schedule::query()
            ->where('store_id', $storeId)
            ->whereDate('date', $date)
            ->where('id', '!=', $chosenId)
            ->update(['is_closing_shift' => false]);

        Schedule::query()
            ->whereKey($chosenId)
            ->update(['is_closing_shift' => true]);
    }
}
