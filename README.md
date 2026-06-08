# Elgibor Solution Laravel Authentication

Enterprise-grade, multi-tenant ready authentication and authorization engine for Laravel.

Build secure, scalable API authentication processes with built-in role & permission management, seamless `stancl/tenancy` integration, and automated setup — right out of the box.

---

## Table of Contents
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Multi-Tenancy](#multi-tenancy)
- [Quick Start](#quick-start)
  - [1. Update Your User Model](#1-update-your-user-model)
  - [2. Authenticate](#2-authenticate)
  - [3. Fetch User Profile](#3-fetch-user-profile)
  - [4. Manage Roles & Permissions](#4-manage-roles--permissions)
- [API Reference](#api-reference)
  - [Public Authentication Routes](#public-authentication-routes)
  - [Authorization Admin Routes](#authorization-admin-routes)

---

## Features

| Category | Capability |
| :--- | :--- |
| **Authentication Engine** | Robust API token generation and validation powered by Laravel Passport. |
| **Role & Permission** | Built-in custom roles and permissions management (No need for Spatie). |
| **Multi-Tenant** | Shared-database or Database-per-tenant architectures natively supported via `stancl/tenancy`. |
| **Automated Setup** | 1-click installation via Artisan command to scaffold migrations, keys, and configs. |
| **Standardized API** | Consistent HTTP status codes (200, 422, 401) wrapped in standard JSON formats. |
| **Extensible Relations** | Eager-load dynamic relationships (e.g., profiles, agency data) automatically upon fetching `/me`. |

---

## Requirements

- PHP ≥ 8.3
- Laravel 11.x or 12.x
- Database MySQL 8+ or PostgreSQL 14+

---

## Installation

### 1. Add the Package
Run the following command in your main project terminal to download the package:

```bash
composer require elgibor-solution/laravel-authentication
```

### 2. Automated Setup (Highly Recommended)
Instead of configuring migrations and settings manually, run this automation command:

```bash
php artisan elgibor-auth:install
```

**The wizard will automatically:**
1. Publish all migration files (`roles`, `permissions` tables, etc.).
2. Ask if you are using `stancl/tenancy`. (If yes, it smartly moves migrations to the `tenant/` directory and generates keys securely).
3. Install Passport encryption keys (`php artisan passport:install` or `passport:keys`).
4. Update your `config/auth.php` file by injecting the `api` guard.
5. Automatically append the necessary traits into your project's `app/Models/User.php` model.

### 3. Publish Configuration (Optional)
To customize the flexibility of this package, publish the configuration file to your application's root directory:

```bash
php artisan vendor:publish --tag=authentication-config
```
This publishes `config/authentication.php` where you can customize all settings.

---

## Configuration

The full configuration lives in `config/authentication.php`. Below are the most important sections:

```php
// config/authentication.php
return [
    // Base URL prefix for all authentication endpoints
    'prefix' => 'api/auth',
    
    // Core middleware required for the package to function
    'middleware' => ['api', 'tenant'],

    // Require extra fields during login (e.g., 'tenant_id' for single-db tenancy)
    'login_extra_fields' => [],
    
    // Automatically eager-load relationships when calling the `/me` endpoint
    'load_relations' => ['profile', 'agency'], 
];
```

---

## Multi-Tenancy

This package is designed to work seamlessly with `stancl/tenancy` for database-per-tenant isolation. 

### Automated Integration
If you run `php artisan elgibor-auth:install` and select **"Yes"** for `stancl/tenancy`:
- The package will automatically move `oauth_*`, `roles`, and `permissions` migrations into `database/migrations/tenant/`.
- It will generate central Passport keys without forcing client creation on the central DB.

### 1. Register Tenant Middleware
In Laravel 11, you must ensure the tenant *middleware* is registered in your application. Open your project's `bootstrap/app.php` file and add the alias:
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'tenant' => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    ]);
})
```

### 2. Configure Package Middleware
Ensure the `tenant` middleware is injected into the package's configuration:
```php
// config/authentication.php
'middleware' => ['api', 'tenant'],
```

### 3. Tenant Client Generation
Since the database is isolated, you must create a Personal Access Client inside each newly created tenant:
```bash
php artisan tenants:run passport:client --personal
```

---

## Quick Start

### 1. Update Your User Model
Ensure the `User` Model in your project uses the traits provided by the package (this is done automatically if you used the install command):

```php
use ElgiborSolution\Authentication\Traits\HasCustomRole;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable {
    use HasApiTokens, HasCustomRole;
}
```

### 2. Authenticate
Submit credentials to retrieve your access token:

```bash
curl -X POST http://your-app/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password123"
  }'
```

### 3. Fetch User Profile
Retrieve the authenticated user's profile, including their flattened permissions array and active tenant object:

```bash
curl http://your-app/api/auth/me \
  -H "Authorization: Bearer <your-access-token>"
```

### 4. Manage Roles & Permissions
Create a new role with assigned permissions:

```bash
curl -X POST http://your-app/api/auth/roles \
  -H "Authorization: Bearer <your-access-token>" \
  -H "Content-Type: application/json" \
  -d '{
    "role_name": "Manager",
    "role_description": "Store manager",
    "permissions": [1, 2, 5]
  }'
```

---

## API Reference

### Public Authentication Routes
All routes are prefixed with `/api/auth` (configurable) and use configured middleware.

| Method | Endpoint | Description | Request Body |
| :--- | :--- | :--- | :--- |
| **POST** | `/login` | Authenticate user and issue token | `email`, `password`, + `login_extra_fields` |
| **GET** | `/me` | Get current user profile (with roles/tenant) | — *(Requires Authorization Header)* |
| **POST** | `/logout` | Revoke the current access token | — *(Requires Authorization Header)* |

### Authorization Admin Routes
All routes require the `auth:api` middleware.

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| **GET** | `/roles` | List all roles (paginated and cached) |
| **POST** | `/roles` | Create a new role with specific permissions |
| **GET** | `/roles/{id}` | Show specific role details |
| **PUT** | `/roles/{id}` | Update an existing role |
| **DELETE** | `/roles/{id}` | Delete a role (if not protected) |
| **GET** | `/permissions` | List all available permissions |
| **PATCH** | `/permissions/{id}/toggle-status` | Toggle permission status (Active 1 / Inactive 9) |
