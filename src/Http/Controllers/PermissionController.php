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
        $context = $request->query('context');
        $accessType = $request->query('access_type');
        $perPage = $request->query('per_page');
        $page = $request->query('page');

        // Cache key based on search, context, access_type and pagination
        $cacheKey = "permissions_list_{$search}_{$context}_{$accessType}_{$perPage}_page_" . ($page ?? 'all');

        $permissions = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $search, $context, $accessType, $perPage) {
            $query = Permission::query();

            if ($context) {
                $query->where('context', $context);
            }

            if ($accessType) {
                $query->where('access_type', $accessType);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
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
