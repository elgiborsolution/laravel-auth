<?php

namespace ElgiborSolution\Authentication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use ElgiborSolution\Authentication\Traits\ApiResponse;
use ElgiborSolution\Authentication\Http\Resources\UserResource;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle user login and token generation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string',
        ];

        // Merge extra login fields from config (e.g., for multi-tenancy)
        $extraFields = config('authentication.login_extra_fields', []);
        foreach ($extraFields as $field) {
            $rules[$field] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422, 'Validation Error');
        }

        $credentials = $request->only(array_merge(['email', 'password'], $extraFields));

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse(['auth' => ['Invalid credentials']], 401, 'Unauthorized');
        }

        $user = Auth::user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        $token->save();

        $userData = $user->toArray();
        
        unset($userData['id']);

        return $this->successResponse('Login successful', [
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'user' => $userData,
        ]);
    }

    /**
     * Handle user logout and revoke token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->successResponse('Successfully logged out');
    }

    /**
     * Get authenticated user profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $user = $request->user();

        // Prepare relations to be eager loaded
        $loadRelations = [];

        // Always include custom role and its permissions if they exist
        // Note: the trait provides `role()` relation which we want to eager load
        if (method_exists($user, 'role')) {
            $loadRelations[] = 'role.permissions';
        }

        // Eager load dynamically configured relations
        $loadRelationsFromConfig = config('authentication.load_relations', []);
        foreach ($loadRelationsFromConfig as $relation) {
            $loadRelations[] = $relation;
        }

        if (!empty($loadRelations)) {
            $user->load($loadRelations);
        }

        return $this->successResponse(
            'User information retrieved successfully',
            new UserResource($user)
        );
    }
}
