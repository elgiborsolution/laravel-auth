<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Package Route Prefix
    |--------------------------------------------------------------------------
    |
    | This value determines the prefix for the authentication API routes.
    | By default, it is set to 'api/auth', which means your routes will
    | be accessible at /api/auth/login, /api/auth/logout, etc.
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
