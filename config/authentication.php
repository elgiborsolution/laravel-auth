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

];
