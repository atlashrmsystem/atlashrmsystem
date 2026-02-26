<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandArea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandAreaController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Unauthorized');
        }
    }

    public function store(Request $request, Brand $brand)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('brand_areas', 'name')->where('brand_id', $brand->id)],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $managerUserId = $validated['manager_user_id'] ?? null;
        if ($managerUserId !== null && ! $this->supportsAreaManagers($brand)) {
            abort(422, 'Area manager assignment is only allowed for Milestones Coffee.');
        }
        $this->assertValidManager($managerUserId);

        $area = BrandArea::create([
            'brand_id' => $brand->id,
            'name' => $validated['name'],
            'manager_user_id' => $managerUserId,
        ]);

        return response()->json($area->load('manager:id,name,email'), 201);
    }

    public function update(Request $request, Brand $brand, BrandArea $area)
    {
        $this->ensureAdmin($request);
        if ((int) $area->brand_id !== (int) $brand->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('brand_areas', 'name')->where('brand_id', $brand->id)->ignore($area->id),
            ],
            'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $managerUserId = $validated['manager_user_id'] ?? null;
        if ($managerUserId !== null && ! $this->supportsAreaManagers($brand)) {
            abort(422, 'Area manager assignment is only allowed for Milestones Coffee.');
        }
        $this->assertValidManager($managerUserId);

        $area->update([
            'name' => $validated['name'],
            'manager_user_id' => $managerUserId,
        ]);

        return response()->json($area->load(['manager:id,name,email', 'stores:id,name,brand_id,brand_area_id']));
    }

    public function destroy(Request $request, Brand $brand, BrandArea $area)
    {
        $this->ensureAdmin($request);
        if ((int) $area->brand_id !== (int) $brand->id) {
            abort(404);
        }

        if ($area->stores()->exists()) {
            abort(422, 'Area has stores assigned. Reassign stores before deleting the area.');
        }

        $area->delete();

        return response()->json(['message' => 'Area deleted successfully.']);
    }

    private function assertValidManager(?int $managerUserId): void
    {
        if ($managerUserId === null) {
            return;
        }

        $user = User::query()->find($managerUserId);
        if (! $user || ! $user->hasRole('manager')) {
            abort(422, 'Selected manager must have the manager role.');
        }
    }

    private function supportsAreaManagers(Brand $brand): bool
    {
        $slug = strtolower((string) ($brand->slug ?? ''));
        if ($slug !== '') {
            return $slug === 'milestones-coffee';
        }

        return strtolower(trim((string) $brand->name)) === 'milestones coffee';
    }
}
