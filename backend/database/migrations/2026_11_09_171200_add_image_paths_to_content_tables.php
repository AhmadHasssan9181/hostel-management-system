<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostels', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('room_type');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('hostels', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
