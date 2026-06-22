<?php

namespace ElgiborSolution\Authentication\Http\Controllers;

use ElgiborSolution\Authentication\Events\UserAuthenticated;
use ElgiborSolution\Authentication\Http\Resources\UserResource;
use ElgiborSolution\Authentication\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Handle user login and token generation.
     *
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        $loginFields = config('authentication.login_fields', ['email']);

        // Determine which login field the user is sending
        $loginField = null;
        foreach ($loginFields as $field) {
            if ($request->has($field)) {
                $loginField = $field;
                break;
            }
        }

        // Build validation rules
        $rules = [
            'password' => 'required|string',
        ];

        if (count($loginFields) > 1) {
            // Multiple login fields configured: require at least one
            if ($loginField === null) {
                return $this->errorResponse(
                    [implode('/', $loginFields) => ['One of '.implode(', ', $loginFields).' is required.']],
                    422,
                    'Validation Error'
                );
            }

            // Apply field-specific validation
            if ($loginField === 'email') {
                $rules[$loginField] = 'required|email';
            } else {
                $rules[$loginField] = 'required|string';
            }
        } else {
            // Single login field configured
            $loginField = $loginFields[0];
            if ($loginField === 'email') {
                $rules[$loginField] = 'required|email';
            } else {
                $rules[$loginField] = 'required|string';
            }
        }

        // Merge extra login fields from config (e.g., for multi-tenancy)
        $extraFields = config('authentication.login_extra_fields', []);
        foreach ($extraFields as $field) {
            $rules[$field] = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422, 'Validation Error');
        }

        $credentials = $request->only(array_merge([$loginField, 'password'], $extraFields));

        if (! Auth::attempt($credentials)) {
            return $this->errorResponse(['auth' => ['Invalid credentials']], 401, 'Unauthorized');
        }

        $user = Auth::user();

        // Dispatch an event to allow applications to hook into the authentication process.
        // Listeners can return false, a string error message, or an array with error details to interrupt the login.
        $responses = event(new UserAuthenticated($user, $request));

        foreach ($responses as $response) {
            if ($response === false) {
                Auth::logout();

                return $this->errorResponse(['auth' => ['Login interrupted by custom checks.']], 401, 'Unauthorized');
            } elseif (is_string($response)) {
                Auth::logout();

                return $this->errorResponse(['auth' => [$response]], 401, 'Unauthorized');
            } elseif (is_array($response)) {
                Auth::logout();

                return $this->errorResponse($response, 401, 'Unauthorized');
            }
        }

        try {
            $user->forceFill(['last_login_at' => now()])->save();
        } catch (\Throwable $e) {
            // Ignore if column does not exist yet or update fails
        }

        $isTwoStep = config('authentication.two_step_login.enabled', false);

        // Use 'central' scope when two-step is enabled so tenantLogin() can verify token origin
        $tokenResult = $isTwoStep
            ? $user->createToken('central-access', ['central'])
            : $user->createToken('Personal Access Token');

        $tokenResult->token->save();

        $userData = $user->toArray();
        unset($userData['id']);

        $responseData = ['user' => $userData];

        // Include accessible tenants in response when two-step is enabled
        if ($isTwoStep && config('authentication.two_step_login.include_tenants_on_login', true)) {
            $tenantRelation = config('authentication.two_step_login.tenant_relation', 'tenants');
            if (method_exists($user, $tenantRelation)) {
                // Qualify columns with table name to avoid ambiguous column error on PostgreSQL
                // (JOIN between tenants and pivot table causes ambiguity on bare 'id'/'name')
                $relatedTable = $user->{$tenantRelation}()->getRelated()->getTable();
                $responseData['tenants'] = $user->{$tenantRelation}()
                    ->get(["{$relatedTable}.id", "{$relatedTable}.name"])
                    ->toArray();
            }
        }

        return response()->json([
            'token' => $tokenResult->accessToken,
            'data' => $responseData,
        ], 200);
    }

    /**
     * Handle user logout and revoke token.
     *
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return $this->successResponse('Successfully logged out');
    }

    /**
     * Get authenticated user profile.
     *
     * @return JsonResponse
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

        // Auto-load tenant relation when two-step login is enabled
        if (config('authentication.two_step_login.enabled', false)) {
            $tenantRelation = config('authentication.two_step_login.tenant_relation', 'tenants');
            if (method_exists($user, $tenantRelation) && ! in_array($tenantRelation, $loadRelations)) {
                $loadRelations[] = $tenantRelation;
            }
        }

        if (! empty($loadRelations)) {
            $user->load($loadRelations);
        }

        return $this->successResponse(
            'User information retrieved successfully',
            new UserResource($user)
        );
    }

    /**
     * Handle second-step tenant login (Two-Step Login flow).
     *
     * Requires a valid central token (scope: central) obtained from POST /api/login.
     * Validates the user has access to the requested tenant, then issues a
     * tenant-scoped token.
     *
     * Request body:
     *   - tenant_id (string, UUID): the tenant the user wants to switch into
     *
     * Response:
     *   {
     *     "token": "<tenant-scoped-token>",
     *     "data": { "tenant": { ... } }
     *   }
     */
    public function tenantLogin(Request $request): JsonResponse
    {
        // Gate: only central tokens (scope: central) may reach step 2
        if (! $request->user()->tokenCan('central')) {
            return $this->errorResponse(
                ['token' => ['A central token is required. Please complete step 1 login first.']],
                403,
                'Forbidden'
            );
        }

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'required|string|uuid',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422, 'Validation Error');
        }

        $user = $request->user();
        $tenantId = $request->input('tenant_id');
        $tenantRelation = config('authentication.two_step_login.tenant_relation', 'tenants');

        // Guard: ensure the relation exists on the User model
        if (! method_exists($user, $tenantRelation)) {
            return $this->errorResponse(
                ['configuration' => ["Relation '{$tenantRelation}' not found on User model. Check 'two_step_login.tenant_relation' config."]],
                500,
                'Configuration Error'
            );
        }

        // Check user actually has access to the requested tenant
        // Qualify 'id' with table name to avoid ambiguous column error on PostgreSQL (JOIN with pivot)
        $relatedTable = $user->{$tenantRelation}()->getRelated()->getTable();
        $tenant = $user->{$tenantRelation}()
            ->where("{$relatedTable}.id", $tenantId)
            ->first();

        if (! $tenant) {
            return $this->errorResponse(
                ['tenant_id' => ['You do not have access to this tenant.']],
                403,
                'Forbidden'
            );
        }

        // Create tenant-scoped token
        // Token name includes tenant_id for traceability/auditing.
        // Scope is generic 'tenant' — Passport 12+ requires scopes pre-registered,
        // so dynamic tenant:{id} scopes are not possible.
        // Actual tenant binding security is already enforced by the pivot check above.
        $tokenResult = $user->createToken(
            "tenant-access:{$tenantId}",
            ['tenant']
        );
        $tokenResult->token->save();

        return response()->json([
            'token' => $tokenResult->accessToken,
            'data' => [
                'tenant' => $tenant->toArray(),
            ],
        ], 200);
    }

    /**
     * Reset the authenticated user's password.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422, $validator->errors()->first());
        }

        $user = $request->user();
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return $this->successResponse('Password reset successfully');
    }

    /**
     * Reset another user's password using their UUID.
     *
     * @param  Request  $request
     * @param  string  $uuid
     * @return JsonResponse
     */
    public function resetOtherUserPassword(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422, $validator->errors()->first());
        }

        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $user = $userModel::where('uuid', $uuid)->first();

        if (! $user) {
            return $this->errorResponse(['user' => ['User not found']], 404, 'User Not Found');
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        return $this->successResponse('Password reset successfully');
    }
}
