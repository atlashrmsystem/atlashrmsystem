<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\ListSalesRequest;
use App\Http\Requests\Mobile\StoreSalesRequest;
use App\Http\Resources\Mobile\SalesEntryResource;
use App\Models\SalesEntry;
use App\Services\MobileAccessService;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function __construct(private readonly MobileAccessService $mobileAccess) {}

    public function index(ListSalesRequest $request)
    {
        $user = $request->user();
        $filters = $request->validated();

        $query = SalesEntry::query()->with(['store', 'employee']);

        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            // full access
        } elseif ($user->can('view store sales') || $user->can('view store reports')) {
            $query->whereIn('store_id', $this->mobileAccess->accessibleStoreIds($user));
        } else {
            if (! $user->employee_id) {
                return SalesEntryResource::collection(collect());
            }
            $query->where('employee_id', $user->employee_id);
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

        return SalesEntryResource::collection($query->orderBy('date', 'desc')->paginate(20));
    }

    public function store(StoreSalesRequest $request)
    {
        $this->authorize('create', SalesEntry::class);

        $user = $request->user();
        $data = $request->validated();

        if (! $user->hasAnyRole(['admin', 'super-admin']) && ! in_array($data['store_id'], $this->mobileAccess->accessibleStoreIds($user), true)) {
            abort(403, 'You are not assigned to this store.');
        }

        $sales = SalesEntry::query()->updateOrCreate(
            [
                'store_id' => $data['store_id'],
                'date' => $data['date'],
            ],
            [
                'employee_id' => $user->employee_id,
                'amount' => $data['amount'],
            ]
        );

        return new SalesEntryResource($sales->fresh());
    }

    public function report(ListSalesRequest $request)
    {
        $user = $request->user();
        if (! $user->can('view store sales') && ! $user->can('view store reports') && ! $user->hasAnyRole(['admin', 'super-admin'])) {
            abort(403, 'Forbidden');
        }

        $filters = $request->validated();
        $groupBy = $filters['group_by'] ?? 'day';

        $query = SalesEntry::query();

        if (! $user->hasAnyRole(['admin', 'super-admin'])) {
            $query->whereIn('store_id', $this->mobileAccess->accessibleStoreIds($user));
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

        $driver = DB::connection()->getDriverName();
        if ($groupBy === 'week') {
            $periodExpression = $driver === 'sqlite'
                ? "strftime('%Y-W%W', date)"
                : "DATE_FORMAT(date, '%x-W%v')";
        } elseif ($groupBy === 'month') {
            $periodExpression = $driver === 'sqlite'
                ? "strftime('%Y-%m', date)"
                : "DATE_FORMAT(date, '%Y-%m')";
        } else {
            $periodExpression = $driver === 'sqlite'
                ? "strftime('%Y-%m-%d', date)"
                : 'DATE(date)';
        }

        $rows = $query
            ->selectRaw($periodExpression.' as period')
            ->selectRaw('store_id')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(*) as entries_count')
            ->groupBy('period', 'store_id')
            ->orderBy('period')
            ->get();

        return response()->json([
            'group_by' => $groupBy,
            'data' => $rows,
            'summary' => [
                'stores_count' => $rows->pluck('store_id')->unique()->count(),
                'entries_count' => (int) $rows->sum('entries_count'),
                'total_amount' => (float) $rows->sum('total_amount'),
            ],
        ]);
    }
}
