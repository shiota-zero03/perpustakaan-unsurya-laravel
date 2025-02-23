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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('userId')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('profilePicture')->nullable();
            $table->enum('gender', ['L', 'P', 'N'])->nullable();
            $table->string('phoneNumber')->nullable();
            $table->foreignId('facultyId')->nullable()->constrained('faculties')->onDelete('set null');
            $table->foreignId('studyProgramId')->nullable()->constrained('study_programs')->onDelete('set null');
            $table->datetime('validUntil')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
