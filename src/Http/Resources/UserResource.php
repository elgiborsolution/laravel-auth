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

        // 1. Flatten permissions, remove them from the nested 'role' relation, and update role context dynamically
        if (isset($data['role']) && is_array($data['role'])) {
            $token = $request->user()?->token();
            $isTenantToken = false;
            $tenantId = null;

            if ($token) {
                $tokenName = (string) ($token->name ?? '');
                if (str_starts_with($tokenName, 'tenant-access:')) {
                    $isTenantToken = true;
                    $tenantId = str_replace('tenant-access:', '', $tokenName);
                } elseif ($request->user()?->tokenCan('tenant') ?? false) {
                    $isTenantToken = true;
                }
            }

            $isTenantActive = function_exists('tenant') && tenant() !== null;

            if ($isTenantToken || $isTenantActive) {
                $tenant = function_exists('tenant') ? tenant() : null;

                if (! $tenant && ! empty($tenantId) && class_exists('\App\Models\Tenant')) {
                    $tenant = \App\Models\Tenant::find($tenantId);
                }

                $isHoTenant = false;
                if ($tenant) {
                    if (method_exists($tenant, 'isHeadOffice')) {
                        $isHoTenant = $tenant->isHeadOffice();
                    } else {
                        $code = strtoupper((string) ($tenant->code ?? ''));
                        $name = strtoupper((string) ($tenant->name ?? ''));
                        $isHoTenant = ((bool) ($tenant->is_ho ?? false)) || $code === 'HO' || str_contains($name, 'HEAD OFFICE');
                    }
                }

                if (! $isHoTenant) {
                    $data['role']['context'] = 'tenant';
                } else {
                    $data['role']['context'] = 'global';
                }
            }

            if (isset($data['role']['permissions'])) {
                // Extract only the permission names
                $data['permissions'] = collect($data['role']['permissions'])->pluck('name')->toArray();

                // Remove the nested permissions array from the role object
                unset($data['role']['permissions']);
            }
        }

        // 2. Include tenant data only when enabled in config
        $includeTenantInResponse = config('authentication.two_step_login.include_tenant_in_response', true);
        $tenantRelation = config('authentication.two_step_login.tenant_relation', 'tenants');

        if ($includeTenantInResponse) {
            // Include active tenant data (if stancl/tenancy is used and initialized)
            if (function_exists('tenant') && tenant()) {
                $data['tenant'] = tenant()->toArray();
            }

            // Include only the logged-in tenant when two-step login is enabled
            if (config('authentication.two_step_login.enabled', false)) {
                $tenantId = null;
                if (function_exists('tenant') && tenant()) {
                    $tenantId = tenant('id');
                }

                if (! $tenantId) {
                    $token = $request->user()?->token();
                    if ($token !== null && isset($token->name) && str_starts_with((string) $token->name, 'tenant-access:')) {
                        $tenantId = str_replace('tenant-access:', '', (string) $token->name);
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
            }
        } else {
            unset($data['tenant']);
        }

        unset($data[$tenantRelation]);

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
