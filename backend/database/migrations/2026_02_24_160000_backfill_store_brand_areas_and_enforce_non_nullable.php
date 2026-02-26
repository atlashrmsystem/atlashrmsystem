<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Backfill stores without brand into a fallback brand.
        $fallbackBrandId = DB::table('brands')->where('slug', 'unassigned-brand')->value('id');
        if (! $fallbackBrandId) {
            $fallbackBrandId = DB::table('brands')->insertGetId([
                'name' => 'Unassigned Brand',
                'slug' => 'unassigned-brand',
                'manager_user_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('stores')->whereNull('brand_id')->update(['brand_id' => $fallbackBrandId]);

        // Ensure every brand has at least one area.
        $brandIdsWithoutArea = DB::table('brands')
            ->whereNotExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('brand_areas')
                    ->whereColumn('brand_areas.brand_id', 'brands.id');
            })
            ->pluck('id');

        foreach ($brandIdsWithoutArea as $brandId) {
            $this->createDefaultArea((int) $brandId, $now);
        }

        // Backfill null brand_area_id by brand default area.
        $storesWithMissingArea = DB::table('stores')
            ->select(['id', 'brand_id'])
            ->whereNull('brand_area_id')
            ->get();

        foreach ($storesWithMissingArea as $store) {
            $defaultAreaId = $this->defaultAreaIdForBrand((int) $store->brand_id, $now);
            DB::table('stores')->where('id', $store->id)->update(['brand_area_id' => $defaultAreaId]);
        }

        // Repair stores pointing to an area from another brand.
        $storesWithMismatch = DB::table('stores')
            ->join('brand_areas', 'stores.brand_area_id', '=', 'brand_areas.id')
            ->whereColumn('stores.brand_id', '!=', 'brand_areas.brand_id')
            ->select(['stores.id', 'stores.brand_id'])
            ->get();

        foreach ($storesWithMismatch as $store) {
            $defaultAreaId = $this->defaultAreaIdForBrand((int) $store->brand_id, $now);
            DB::table('stores')->where('id', $store->id)->update(['brand_area_id' => $defaultAreaId]);
        }

        // Enforce non-nullable brand_area_id with strict FK.
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropForeign(['brand_area_id']);
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->unsignedBigInteger('brand_area_id')->nullable(false)->change();
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->foreign('brand_area_id')->references('id')->on('brand_areas')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropForeign(['brand_area_id']);
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->unsignedBigInteger('brand_area_id')->nullable()->change();
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->foreign('brand_area_id')->references('id')->on('brand_areas')->nullOnDelete();
        });
    }

    private function defaultAreaIdForBrand(int $brandId, \Illuminate\Support\Carbon $now): int
    {
        $areaId = DB::table('brand_areas')
            ->where('brand_id', $brandId)
            ->orderBy('id')
            ->value('id');

        if ($areaId) {
            return (int) $areaId;
        }

        return $this->createDefaultArea($brandId, $now);
    }

    private function createDefaultArea(int $brandId, \Illuminate\Support\Carbon $now): int
    {
        $name = 'Unassigned';
        $suffix = 1;

        while (
            DB::table('brand_areas')
                ->where('brand_id', $brandId)
                ->where('name', $name)
                ->exists()
        ) {
            $suffix++;
            $name = 'Unassigned '.$suffix;
        }

        return (int) DB::table('brand_areas')->insertGetId([
            'brand_id' => $brandId,
            'name' => $name,
            'manager_user_id' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
