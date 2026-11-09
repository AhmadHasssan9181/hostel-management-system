<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('registration_number', 40)->unique();
            $table->string('department', 100);
            $table->unsignedTinyInteger('semester')->default(1);
            $table->text('address')->nullable();
            $table->string('guardian_name', 120)->nullable();
            $table->string('guardian_phone', 30)->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('check_in_status', 20)->default('pending');
            $table->string('discipline_status', 20)->default('clear');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
