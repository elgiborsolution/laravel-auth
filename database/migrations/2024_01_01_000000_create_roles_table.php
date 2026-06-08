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
        Schema::create('roles', function (Blueprint $table) {
            $table->integer('id')->autoIncrement();
            $table->string('role_name', 150);
            $table->string('role_description', 150)->nullable();
            $table->boolean('default')->default(false)->comment('1 = default role when create new user');
            $table->boolean('can_delete')->default(true)->comment('1 = not protected role');
            $table->string('login_destination', 150)->default('/');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
