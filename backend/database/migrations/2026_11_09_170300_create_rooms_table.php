<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->constrained()->cascadeOnDelete();
            $table->string('room_number', 20);
            $table->string('room_type', 30)->default('standard');
            $table->unsignedTinyInteger('capacity')->default(2);
            $table->unsignedTinyInteger('occupied_beds')->default(0);
            $table->string('status', 20)->default('available');
            $table->unsignedTinyInteger('floor')->default(0);
            $table->decimal('monthly_rent', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hostel_id', 'room_number']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
