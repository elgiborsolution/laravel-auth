<?php

namespace ElgiborSolution\Authentication\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'elgibor-auth:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the Elgibor Authentication package (setup Passport, publish migrations, config auth, and update User model)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Elgibor Authentication Setup...');

        // Ask for Tenancy
        $isTenancy = $this->confirm('Are you using stancl/tenancy for multi-tenancy?', false);

        // 1. Publish Migrations
        $this->info('1. Publishing Package Migrations...');
        Artisan::call('vendor:publish', ['--tag' => 'authentication-migrations'], $this->getOutput());

        if ($isTenancy) {
            $this->info('1b. Publishing Passport Migrations...');
            Artisan::call('vendor:publish', ['--tag' => 'passport-migrations'], $this->getOutput());

            $this->info('1c. Moving migrations to database/migrations/tenant...');
            $migrationPath = database_path('migrations');
            $tenantPath = database_path('migrations/tenant');
            
            if (!File::exists($tenantPath)) {
                File::makeDirectory($tenantPath, 0755, true);
            }
            
            $files = File::files($migrationPath);
            foreach ($files as $file) {
                $filename = $file->getFilename();
                // Move our package and passport migrations
                if (strpos($filename, 'create_roles_table') !== false ||
                    strpos($filename, 'create_permissions_table') !== false ||
                    strpos($filename, 'create_role_permissions_table') !== false ||
                    strpos($filename, 'create_oauth_') !== false) {
                    File::move($file->getPathname(), $tenantPath . '/' . $filename);
                }
            }

            $this->info('2. Generating Passport Keys (Tenancy Mode)...');
            Artisan::call('passport:keys', ['--force' => true], $this->getOutput());
            
        } else {
            $this->info('2. Installing Laravel Passport (Central Mode)...');
            Artisan::call('passport:install', [], $this->getOutput());
        }

        // 3. Update auth.php config
        $this->info('3. Configuring API Guard in config/auth.php...');
        $this->updateAuthConfig();

        // 4. Update User Model
        $this->info('4. Updating User Model with necessary Traits...');
        $this->updateUserModel();

        $this->info('Setup Completed Successfully!');
        
        if ($isTenancy) {
            $this->warn("\n[IMPORTANT] Since you are using stancl/tenancy, you MUST run:");
            $this->warn("php artisan tenants:run passport:client --personal");
            $this->warn("To generate personal access clients for your newly created tenants!\n");
        } else {
            $this->info('You are ready to go.');
        }
        
        return 0;
    }

    protected function updateAuthConfig()
    {
        $authConfigPath = config_path('auth.php');

        if (!File::exists($authConfigPath)) {
            $this->warn('config/auth.php not found. Skipping.');
            return;
        }

        $content = File::get($authConfigPath);

        // Check if api guard already exists
        if (strpos($content, "'api' => [") !== false) {
            $this->info('API guard already configured.');
            return;
        }

        // Inject the api guard block under the web guard
        $search = <<<EOT
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
EOT;

        $replace = <<<EOT
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
EOT;
        // Some systems might have a different default auth.php spacing
        if (strpos($content, $search) !== false) {
            $content = str_replace($search, $replace, $content);
            File::put($authConfigPath, $content);
            $this->info('API guard successfully added.');
        } else {
            // Alternative search if spacing differs
            $this->warn('Could not auto-inject api guard. Please manually add the api guard in config/auth.php.');
        }
    }

    protected function updateUserModel()
    {
        $userModelPath = app_path('Models/User.php');

        if (!File::exists($userModelPath)) {
            $this->warn('app/Models/User.php not found. Skipping.');
            return;
        }

        $content = File::get($userModelPath);
        $updated = false;

        // Inject HasApiTokens import
        if (strpos($content, 'Laravel\Passport\HasApiTokens') === false) {
            $content = str_replace(
                "use Illuminate\Foundation\Auth\User as Authenticatable;",
                "use Illuminate\Foundation\Auth\User as Authenticatable;\nuse Laravel\Passport\HasApiTokens;",
                $content
            );
            $updated = true;
        }

        // Inject HasCustomRole import
        if (strpos($content, 'ElgiborSolution\Authentication\Traits\HasCustomRole') === false) {
            // Replace the previous string if we just injected it, otherwise replace Authenticatable
            if (strpos($content, "use Laravel\Passport\HasApiTokens;") !== false) {
                $content = str_replace(
                    "use Laravel\Passport\HasApiTokens;",
                    "use Laravel\Passport\HasApiTokens;\nuse ElgiborSolution\Authentication\Traits\HasCustomRole;",
                    $content
                );
            } else {
                $content = str_replace(
                    "use Illuminate\Foundation\Auth\User as Authenticatable;",
                    "use Illuminate\Foundation\Auth\User as Authenticatable;\nuse ElgiborSolution\Authentication\Traits\HasCustomRole;",
                    $content
                );
            }
            $updated = true;
        }

        // Inject HasApiTokens and HasCustomRole traits into the class
        if (strpos($content, 'use HasApiTokens, HasCustomRole') === false && strpos($content, 'use HasFactory') !== false) {
            $content = preg_replace(
                '/use HasFactory(.*);/',
                'use HasApiTokens, HasCustomRole, HasFactory$1;',
                $content
            );
            $updated = true;
        }

        if ($updated) {
            File::put($userModelPath, $content);
            $this->info('User model updated with HasApiTokens and HasCustomRole.');
        } else {
            $this->info('User model already has the required traits.');
        }
    }
}
