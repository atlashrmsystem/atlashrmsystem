<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\MobileAccessService;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function __construct(private readonly MobileAccessService $mobileAccess) {}

    public function index(Request $request)
    {
        $user = $request->user();

        if (! $this->mobileAccess->isSupervisor($user) && ! $this->mobileAccess->isManager($user) && ! $this->mobileAccess->isHr($user)) {
            abort(403, 'Forbidden');
        }

        $storeIds = $this->mobileAccess->accessibleStoreIds($user);

        $query = Employee::query()
            ->select(['id', 'employee_pin', 'full_name', 'email', 'phone', 'job_title', 'department', 'status', 'store_id'])
            ->with(['store:id,name,address'])
            ->whereIn('store_id', $storeIds)
            ->orderBy('full_name');

        if ($request->filled('store_id')) {
            $storeId = (int) $request->integer('store_id');
            if (! in_array($storeId, $storeIds, true)) {
                abort(403, 'Forbidden for this store.');
            }
            $query->where('store_id', $storeId);
        }

        if ($request->filled('status')) {
            $query->where('status', strtolower((string) $request->string('status')));
        }

        if ($request->filled('q')) {
            $search = (string) $request->string('q');
            $query->where(function ($inner) use ($search) {
                $inner->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_pin', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => $query->paginate(25),
        ]);
    }
}
