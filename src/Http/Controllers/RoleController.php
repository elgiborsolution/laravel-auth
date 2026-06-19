<?php

namespace ElgiborSolution\Authentication\Http\Controllers;

use ElgiborSolution\Authentication\Models\Role;
use ElgiborSolution\Authentication\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the roles.
     */
    public function index(Request $request)
    {
        $keyword = $request->query('keyword');
        $context = $request->query('context');
        $isActive = $request->query('is_active');
        $perPage = $request->query('per_page');
        $page = $request->query('page');

        // Cache key based on search and pagination
        $cacheKey = "roles_list_{$keyword}_{$context}_{$isActive}_{$perPage}_page_".($page ?? 'all');

        $roles = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($request, $keyword, $context, $isActive, $perPage) {
            $query = Role::with('permissions:id,name,description,status');

            if ($context) {
                $query->where('context', $context);
            }

            if ($isActive) {
                $query->where('is_active', $isActive);
            }

            if ($keyword) {
                $query->where('role_name', 'like', "%{$keyword}%")
                    ->orWhere('role_description', 'like', "%{$keyword}%");
            }

            if ($request->has('page') && ! empty($request->query('page'))) {
                return $query->paginate($perPage);
            }

            return $query->get();
        });
        
        return $this->successResponse('Roles retrieved successfully', $roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_name' => 'required|string|max:150|unique:roles,role_name',
            'role_description' => 'nullable|string|max:150',
            'default' => 'boolean',
            'can_delete' => 'boolean',
            'login_destination' => 'string|max:150',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create($validated);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        Cache::flush(); // Flush roles cache

        return $this->successResponse('Role created successfully', $role->load('permissions:id,name,description,status'), 201);
    }

    /**
     * Display the specified role.
     */
    public function show($id)
    {
        $cacheKey = "role_detail_{$id}";

        $role = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($id) {
            return Role::with('permissions:id,name,description,status')->find($id);
        });

        if (! $role) {
            return $this->errorResponse('Role not found', 404);
        }

        return $this->successResponse('Role retrieved successfully', $role);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->errorResponse('Role not found', 404);
        }

        $validated = $request->validate([
            'role_name' => 'sometimes|required|string|max:150|unique:roles,role_name,'.$role->id,
            'role_description' => 'nullable|string|max:150',
            'default' => 'boolean',
            'can_delete' => 'boolean',
            'login_destination' => 'string|max:150',
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->update($validated);

        if (isset($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        Cache::flush(); // Flush roles cache

        return $this->successResponse('Role updated successfully', $role->load('permissions:id,name,description,status'));
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id)
    {
        $role = Role::find($id);

        if (! $role) {
            return $this->errorResponse('Role not found', 404);
        }

        if (! $role->can_delete) {
            return $this->errorResponse('This role is protected and cannot be deleted', 403);
        }

        $role->delete();

        Cache::flush(); // Flush roles cache

        return $this->successResponse('Role deleted successfully', null);
    }

    public function status(Request $request)
    {
        $role = Role::find($request->id);

        if (! $role) {
            return $this->errorResponse('Role not found', 404);
        }

        $role->is_active = $request->is_active;
        $role->save();

        Cache::flush(); // Flush roles cache

        return $this->successResponse('Role status updated successfully', $role);
    }
}
