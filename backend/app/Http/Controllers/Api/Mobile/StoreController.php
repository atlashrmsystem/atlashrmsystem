<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\StoreResource;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $storeIds = $request->user()->accessibleStoreIds();

        $stores = Store::query()
            ->when(! empty($storeIds), fn ($q) => $q->whereIn('id', $storeIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->where('is_active', true)
            ->whereNotNull('brand_id')
            ->whereNotNull('brand_area_id')
            ->with(['brand:id,name', 'brandArea:id,name'])
            ->orderBy('name')
            ->get();

        return StoreResource::collection($stores);
    }
}
