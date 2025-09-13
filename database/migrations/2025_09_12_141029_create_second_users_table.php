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
        Schema::create('second_users', function (Blueprint $table) {
            $table->uuid('id')->unique();
            $table->string('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('nim')->nullable();
            $table->string('status')->nullable();
            $table->string('gender')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('waktu_terdaftar')->nullable();
            $table->string('profile_picture')->nullable();
            $table->string('faculty')->nullable();
            $table->string('department')->nullable();
            $table->string('otoritas')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('second_users');
    }
};
