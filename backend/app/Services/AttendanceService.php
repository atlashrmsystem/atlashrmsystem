<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\Store;
use Illuminate\Support\Carbon;

class AttendanceService
{
    // Geofencing constants removed in favor of Database stores.
    // private const STORE_LAT = ...
    // private const STORE_LNG = ...
    // private const ALLOWED_RADIUS_METERS = ...

    // Geofencing constants removed in favor of Database stores.
    // private const STORE_LAT = ...
    // private const STORE_LNG = ...
    // private const ALLOWED_RADIUS_METERS = ...
    /**
     * Get today's attendance record for an employee.
     */
    public function getTodayRecord(int $employeeId): ?AttendanceRecord
    {
        return AttendanceRecord::where('employee_id', $employeeId)
            ->whereDate('date', Carbon::today())
            ->first();
    }

    /**
     * Calculate distance between two points in meters using Haversine formula.
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // in meters

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function isLocationTrackingRequired(): bool
    {
        return (bool) config('attendance.require_location', false);
    }

    /**
     * Verify location within geofence of ANY active store.
     * Returns the store the employee is near.
     */
    private function verifyGeofence(?array $location): ?Store
    {
        if (! $this->isLocationTrackingRequired()) {
            return null;
        }

        if (! $location || ! isset($location['latitude']) || ! isset($location['longitude'])) {
            throw new \Exception('Location data is required for clock-in/out.');
        }

        $stores = Store::where('is_active', true)->get();
        if ($stores->isEmpty()) {
            return null;
        }

        $nearestStore = null;

        $isNearAnyStore = false;
        $minDistance = PHP_INT_MAX;

        foreach ($stores as $store) {
            $distance = $this->calculateDistance(
                (float) $store->latitude,
                (float) $store->longitude,
                (float) $location['latitude'],
                (float) $location['longitude']
            );

            if ($distance <= $store->radius_meters) {
                return $store;
            }

            if ($distance < $minDistance) {
                $minDistance = $distance;
            }
        }

        throw new \Exception('You are outside the required radius of all stores (nearest is '.round($minDistance).'m away). Please go to a store location.');
    }

    /**
     * Clock in an employee.
     */
    public function clockIn(int $employeeId, array $location = []): AttendanceRecord
    {
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // Define work start time (e.g., 09:00 AM)
        $workStartTime = Carbon::today()->setHour(9)->setMinute(0)->setSecond(0);

        // Check if already clocked in today
        $record = $this->getTodayRecord($employeeId);

        if ($record) {
            throw new \Exception('Already clocked in today.');
        }

        $status = 'present';
        if ($now->greaterThan($workStartTime->copy()->addMinutes(15))) {
            $status = 'late';
        }

        // Verify Geofence and get store
        $store = $this->verifyGeofence($location);

        return AttendanceRecord::create([
            'employee_id' => $employeeId,
            'date' => $today,
            'clock_in_time' => $now,
            'check_in_location' => $location,
            'status' => $status,
            'store_id' => $store?->id,
        ]);
    }

    /**
     * Clock out an employee.
     */
    public function clockOut(int $employeeId, array $location = []): AttendanceRecord
    {
        $record = $this->getTodayRecord($employeeId);

        if (! $record) {
            throw new \Exception('No clock-in record found for today.');
        }

        if ($record->clock_out_time) {
            throw new \Exception('Already clocked out today.');
        }

        // Verify Geofence
        $this->verifyGeofence($location);

        $record->clock_out_time = Carbon::now();
        $record->check_out_location = $location;
        $record->save();

        return $record;
    }
}
