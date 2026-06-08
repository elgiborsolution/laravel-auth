<?php

namespace ElgiborSolution\Authentication\Traits;

use ElgiborSolution\Authentication\Models\Role;

trait HasCustomRole
{
    /**
     * Get the role associated with the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'roles_id');
    }

    /**
     * Determine if the user has a specific permission.
     *
     * @param  string  $permission
     * @return bool
     */
    public function hasPermissionTo(string $permission): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->permissions->contains('name', $permission);
    }

    /**
     * Check if user has the given role.
     *
     * @param  string  $roleName
     * @return bool
     */
    public function hasRole(string $roleName): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->role_name === $roleName;
    }
}
