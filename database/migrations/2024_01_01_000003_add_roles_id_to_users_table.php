<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Because user tables might vary, we add roles_id nullable by default
            if (!Schema::hasColumn('users', 'roles_id')) {
                $table->integer('roles_id')->nullable()->after('id');
                // Note: we can add a foreign key if desired, but since users table might use UUIDs 
                // in some systems and standard increments in others, we leave the foreign constraint optional.
                // $table->foreign('roles_id')->references('id')->on('roles')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'roles_id')) {
                // $table->dropForeign(['roles_id']);
                $table->dropColumn('roles_id');
            }
        });
    }
};
