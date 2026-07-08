<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Package Route Prefix
    |--------------------------------------------------------------------------
    |
    | This value determines the prefix for the authentication API routes.
    | By default, it is set to 'api/auth', which means your protected routes
    | will be accessible at /api/auth/me, /api/auth/logout, etc.
    | Public routes (like login) will automatically strip the '/auth' part,
    | e.g. /api/login.
    |
    */

    'prefix' => 'api/auth',

    /*
    |--------------------------------------------------------------------------
    | Package Route Middleware
    |--------------------------------------------------------------------------
    |
    | This value determines the middleware applied to the authentication routes.
    | If you are using stancl/tenancy, you can add your tenant middleware
    | here (e.g., ['api', 'tenant']) so the database context switches properly.
    |
    */

    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Login Identifier Fields
    |--------------------------------------------------------------------------
    |
    | Define which fields can be used as the login identifier.
    | The user can send any one of these fields in the login payload.
    | Supported values: 'email', 'username', or both.
    |
    | If ['email'] => payload must contain 'email'
    | If ['username'] => payload must contain 'username'
    | If ['email', 'username'] => payload can contain either 'email' or 'username'
    |
    */

    'login_fields' => ['email'],

    /*
    |--------------------------------------------------------------------------
    | Login Extra Fields (Single-Database Multi-Tenancy Support)
    |--------------------------------------------------------------------------
    |
    | Define any additional fields required during the login process.
    | This is useful for multi-tenancy environments where a user might
    | belong to a 'tenant_id'.
    |
    | Example: ['tenant_id']
    |
    */

    'login_extra_fields' => [],

    /*
    |--------------------------------------------------------------------------
    | Include Eager Load Relations
    |--------------------------------------------------------------------------
    |
    | You can specify an array of Eloquent relationship names on the User model
    | that you want to be eagerly loaded and included in the /me response.
    | This is useful for fetching tenant data, profiles, or agencies dynamically.
    |
    | Example: ['role.permissions', 'hospital']
    |
    */

    'load_relations' => ['role.permissions'],

    /*
    |--------------------------------------------------------------------------
    | Two-Step Login (Central → Tenant)
    |--------------------------------------------------------------------------
    |
    | Enable two-step login for multi-tenant applications.
    |
    | When enabled:
    |   - Step 1: POST /api/login → returns { token (central), data: { user, tenants[] } }
    |   - Step 2: POST /api/tenant-login → returns { token (tenant-scoped), data: { tenant } }
    |
    | Options:
    |   enabled                   : bool   — toggle the feature (false = single-step only)
    |   tenant_login_path          : string — URI path for step 2
    |   include_tenants_on_login   : bool   — include tenant list in step 1 response
    |   include_tenant_in_response : bool   - include tenant relation/object in /me response
    |   tenant_relation            : string — relation method name on User model
    |
    | Note: Requires the User model to have a `tenants()` relation when enabled.
    |
    */

    'two_step_login' => [
        'enabled' => false,
        'tenant_login_path' => 'api/tenant-login',
        'include_tenants_on_login' => true,
        'include_tenant_in_response' => true,
        'tenant_relation' => 'tenants',
    ],

];
