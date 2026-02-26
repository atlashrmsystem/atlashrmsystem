<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\UpdateProfileRequest;
use App\Http\Resources\Mobile\ProfileResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        return new ProfileResource($request->user()->load(['employee', 'stores']));
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            abort(404, 'Employee profile not found.');
        }

        $validated = $request->validated();

        DB::transaction(function () use ($user, $employee, $validated): void {
            $userData = Arr::only($validated, ['name', 'email']);
            if (! empty($userData)) {
                $user->update($userData);
            }

            $employeeData = Arr::only($validated, [
                'phone',
                'email',
                'present_address',
                'present_city',
                'present_country',
                'permanent_address',
                'permanent_city',
                'permanent_country',
                'nationality',
            ]);

            if (array_key_exists('name', $validated)) {
                $employeeData['full_name'] = $validated['name'];
            }

            if (! empty($employeeData)) {
                $employee->update($employeeData);
            }
        });

        return new ProfileResource($user->fresh()->load(['employee', 'stores']));
    }
}
