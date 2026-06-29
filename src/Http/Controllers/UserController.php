<?php

namespace ElgiborSolution\Authentication\Http\Controllers;

use ElgiborSolution\Authentication\Models\User;
use ElgiborSolution\Authentication\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Check whether a user's password has been changed from the default.
     *
     * @param  string  $uuid
     * @return JsonResponse
     */
    public function passwordStatus(string $uuid): JsonResponse
    {
        $userModel = config('auth.providers.users.model', User::class);
        $user = $userModel::where('uuid', $uuid)->first();

        if (! $user) {
            return $this->errorResponse(['user' => ['User not found']], 404, 'User Not Found');
        }

        $passwordChanged = ! is_null($user->password_changed_at);

        return $this->successResponse('Password status retrieved successfully', [
            'password_changed' => $passwordChanged,
            'password_changed_at' => $user->password_changed_at,
        ]);
    }
}
