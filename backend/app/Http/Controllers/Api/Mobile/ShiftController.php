<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ShiftResource;
use App\Models\ScheduleWeekPublication;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $storeIds = $request->user()->accessibleStoreIds();

        $shifts = Shift::query()
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when(! empty($storeIds), fn ($q) => $q->whereIn('store_id', $storeIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->orderBy('store_id')
            ->orderBy('start_time')
            ->get();

        return ShiftResource::collection($shifts);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->can('manage schedules')) {
            abort(403, 'Forbidden');
        }

        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($data['store_id'], $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $shift = Shift::create($data);
        $this->markPublishedWeeksAsEdited((int) $shift->store_id);

        return new ShiftResource($shift);
    }

    public function update(Request $request, Shift $shift)
    {
        $user = $request->user();
        if (! $user->can('manage schedules')) {
            abort(403, 'Forbidden');
        }

        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($shift->store_id, $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
        ]);

        $shift->update($data);
        $this->markPublishedWeeksAsEdited((int) $shift->store_id);

        return new ShiftResource($shift);
    }

    public function destroy(Request $request, Shift $shift)
    {
        $user = $request->user();
        if (! $user->can('manage schedules')) {
            abort(403, 'Forbidden');
        }

        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($shift->store_id, $user->accessibleStoreIds(), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $shift->delete();
        $this->markPublishedWeeksAsEdited((int) $shift->store_id);

        return response()->json([], 204);
    }

    private function markPublishedWeeksAsEdited(int $storeId): void
    {
        ScheduleWeekPublication::query()
            ->where('store_id', $storeId)
            ->where('status', ScheduleWeekPublication::STATUS_PUBLISHED)
            ->update(['status' => ScheduleWeekPublication::STATUS_EDITED_AFTER_PUBLISH]);
    }
}
