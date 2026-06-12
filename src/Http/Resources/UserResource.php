<?php

namespace ElgiborSolution\Authentication\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);

        // 1. Flatten permissions and remove them from the nested 'role' relation
        if (isset($data['role']) && isset($data['role']['permissions'])) {
            // Extract only the permission names
            $data['permissions'] = collect($data['role']['permissions'])->pluck('name')->toArray();

            // Remove the nested permissions array from the role object
            unset($data['role']['permissions']);
        }

        // 2. Include active tenant data (if stancl/tenancy is used and initialized)
        if (function_exists('tenant') && tenant()) {
            $data['tenant'] = tenant()->toArray();
        }

        // 3. Include only the logged-in tenant when two-step login is enabled
        if (config('authentication.two_step_login.enabled', false)) {
            $tenantRelation = config('authentication.two_step_login.tenant_relation', 'tenants');

            $tenantId = null;
            if (function_exists('tenant') && tenant()) {
                $tenantId = tenant('id');
            }

            if (! $tenantId) {
                $token = $request->user()?->token();
                if ($token instanceof \Laravel\Passport\Token && $token->name !== null && str_starts_with($token->name, 'tenant-access:')) {
                    $tenantId = str_replace('tenant-access:', '', $token->name);
                }
            }

            if ($tenantId && $this->resource instanceof Model && $this->resource->relationLoaded($tenantRelation)) {
                $relationData = $this->{$tenantRelation};

                if ($relationData instanceof Model) {
                    if ((string) $relationData->getKey() === (string) $tenantId) {
                        $data['tenant'] = $relationData->toArray();
                    }
                } elseif ($relationData !== null) {
                    $tenant = collect($relationData)->first(function ($t) use ($tenantId) {
                        $id = $t instanceof Model ? $t->getKey() : ($t['id'] ?? null);

                        return (string) $id === (string) $tenantId;
                    });
                    if ($tenant) {
                        $data['tenant'] = $tenant instanceof Model
                            ? $tenant->toArray()
                            : (array) $tenant;
                    }
                }
            }

            // Remove the full tenants relation list from the response
            unset($data[$tenantRelation]);
        }

        // 4. Include any other dynamically configured relations
        $relations = config('authentication.load_relations', []);
        foreach ($relations as $relation) {
            // Skip role.permissions since we already handled it
            if ($relation === 'role.permissions') {
                continue;
            }

            if ($this->resource instanceof Model && $this->resource->relationLoaded($relation)) {
                $data[$relation] = $this->whenLoaded($relation);
            }
        }

        return $data;
    }
}
