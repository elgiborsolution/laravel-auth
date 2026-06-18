<?php

namespace ElgiborSolution\Authentication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ElgiborSolution\Authentication\Traits\ApiResponse;
use ElgiborSolution\Authentication\Models\Permission;
use Illuminate\Support\Facades\Cache;

class PermissionController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the permissions.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15);
        $page = $request->query('page');

        // Cache key based on search and pagination
        $cacheKey = "permissions_list_{$search}_{$perPage}_page_" . ($page ?? 'all');

        $permissions = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $search, $perPage) {
            $query = Permission::query();

            if ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            }

            if ($request->has('page') && ! empty($request->query('page'))) {
                return $query->paginate($perPage);
            }

            return $query->get();
        });

        return $this->successResponse('Permissions retrieved successfully', $permissions);
    }

    /**
     * Toggle the status of the permission.
     */
    public function toggleStatus($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return $this->errorResponse('Permission not found', 404);
        }

        $permission->status = $permission->status == 1 ? 9 : 1;
        $permission->save();

        Cache::flush(); // Flush permissions cache

        return $this->successResponse('Permission status toggled successfully', $permission);
    }
}
