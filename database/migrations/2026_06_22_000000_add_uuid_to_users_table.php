<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'uuid')) {
                // If PostgreSQL is used, we can auto-generate the UUID at database level.
                // For other drivers (e.g., SQLite for testing), we make it nullable and unique to avoid errors.
                $driverName = Schema::getConnection()->getDriverName();
                if ($driverName === 'pgsql') {
                    $table->uuid('uuid')->unique()->default(DB::raw('gen_random_uuid()'))->after('id');
                } else {
                    $table->uuid('uuid')->nullable()->unique()->after('id');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
