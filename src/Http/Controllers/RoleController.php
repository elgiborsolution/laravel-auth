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
        $search = $request->query('search');
        $perPage = $request->query('per_page', 15);

        // Cache key based on search and pagination
        $cacheKey = "roles_list_{$search}_{$perPage}_page_".$request->query('page', 1);

        $roles = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($search, $perPage) {
            $query = Role::query();

            if ($search) {
                $query->where('role_name', 'like', "%{$search}%")
                    ->orWhere('role_description', 'like', "%{$search}%");
            }

            return $query->paginate($perPage);
        });

        return $this->successResponse($roles, 'Roles retrieved successfully');
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

        return $this->successResponse($role->load('permissions'), 'Role created successfully', 201);
    }

    /**
     * Display the specified role.
     */
    public function show($id)
    {
        $cacheKey = "role_detail_{$id}";

        $role = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($id) {
            return Role::with('permissions')->find($id);
        });

        if (! $role) {
            return $this->errorResponse('Role not found', 404);
        }

        return $this->successResponse($role, 'Role retrieved successfully');
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

        return $this->successResponse($role->load('permissions'), 'Role updated successfully');
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

        return $this->successResponse(null, 'Role deleted successfully');
    }
}
