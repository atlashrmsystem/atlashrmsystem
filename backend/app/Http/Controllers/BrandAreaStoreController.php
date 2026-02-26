<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\BrandArea;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandAreaStoreController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        if (! $request->user()->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Unauthorized');
        }
    }

    public function storeForBrand(Request $request, Brand $brand)
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stores', 'name')->where(fn ($q) => $q->where('brand_id', $brand->id)),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'brand_area_id' => ['nullable', 'integer', 'exists:brand_areas,id'],
        ]);

        $areaId = null;
        if ($this->supportsAreaManagers($brand)) {
            $areaId = (int) ($validated['brand_area_id'] ?? 0);
            if ($areaId <= 0) {
                abort(422, 'Area is required for Milestones Coffee.');
            }

            $area = BrandArea::query()->findOrFail($areaId);
            if ((int) $area->brand_id !== (int) $brand->id) {
                abort(422, 'Selected area does not belong to this brand.');
            }
        } else {
            $areaId = $this->defaultAreaIdForBrand($brand);
        }

        $store = Store::create([
            'brand_id' => $brand->id,
            'brand_area_id' => $areaId,
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'latitude' => 0,
            'longitude' => 0,
            'radius_meters' => 200,
            'is_active' => true,
        ]);

        return response()->json($store, 201);
    }

    public function store(Request $request, Brand $brand, BrandArea $area)
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
                Rule::unique('stores', 'name')->where(fn ($q) => $q->where('brand_id', $brand->id)),
            ],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'brand_id' => $brand->id,
            'brand_area_id' => $area->id,
            'name' => $validated['name'],
            'latitude' => 0,
            'longitude' => 0,
            'radius_meters' => 200,
            'is_active' => true,
            'address' => $validated['address'] ?? null,
        ];

        $store = Store::create($payload);

        return response()->json($store, 201);
    }

    public function update(Request $request, Brand $brand, BrandArea $area, Store $store)
    {
        $this->ensureAdmin($request);
        if ((int) $area->brand_id !== (int) $brand->id || (int) $store->brand_id !== (int) $brand->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('stores', 'name')
                    ->where(fn ($q) => $q->where('brand_id', $brand->id))
                    ->ignore($store->id),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'brand_area_id' => ['sometimes', 'integer', 'exists:brand_areas,id'],
        ]);

        $targetAreaId = (int) ($validated['brand_area_id'] ?? $area->id);
        $targetArea = BrandArea::query()->findOrFail($targetAreaId);
        if ((int) $targetArea->brand_id !== (int) $brand->id) {
            abort(422, 'Store can only be moved to an area within the same brand.');
        }

        $payload = [
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'brand_area_id' => $targetArea->id,
        ];

        $store->update($payload);

        return response()->json($store);
    }

    public function destroy(Request $request, Brand $brand, BrandArea $area, Store $store)
    {
        $this->ensureAdmin($request);
        if ((int) $area->brand_id !== (int) $brand->id || (int) $store->brand_id !== (int) $brand->id) {
            abort(404);
        }

        $store->delete();

        return response()->json(['message' => 'Branch deleted successfully.']);
    }

    private function supportsAreaManagers(Brand $brand): bool
    {
        $slug = strtolower((string) ($brand->slug ?? ''));
        if ($slug !== '') {
            return $slug === 'milestones-coffee';
        }

        return strtolower(trim((string) $brand->name)) === 'milestones coffee';
    }

    private function defaultAreaIdForBrand(Brand $brand): int
    {
        $existingDefault = $brand->areas()
            ->whereIn('name', ['Main', 'Unassigned'])
            ->orderBy('id')
            ->first();

        if ($existingDefault) {
            return (int) $existingDefault->id;
        }

        $firstArea = $brand->areas()->orderBy('id')->first();
        if ($firstArea) {
            return (int) $firstArea->id;
        }

        $area = BrandArea::create([
            'brand_id' => $brand->id,
            'name' => 'Main',
            'manager_user_id' => null,
        ]);

        return (int) $area->id;
    }
}
