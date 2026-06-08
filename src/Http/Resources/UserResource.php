<?php

namespace ElgiborSolution\Authentication\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
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

        // 3. Include any other dynamically configured relations
        $relations = config('authentication.load_relations', []);
        foreach ($relations as $relation) {
            // Skip role.permissions since we already handled it
            if ($relation === 'role.permissions') continue;
            
            if ($this->relationLoaded($relation)) {
                $data[$relation] = $this->whenLoaded($relation);
            }
        }

        return $data;
    }
}
