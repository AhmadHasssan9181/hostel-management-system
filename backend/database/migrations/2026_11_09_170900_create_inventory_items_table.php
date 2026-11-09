<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('category', 40)->default('furniture');
            $table->unsignedInteger('quantity')->default(0);
            $table->string('unit', 20)->default('pcs');
            $table->unsignedInteger('threshold')->default(5);
            $table->string('condition', 20)->default('good')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['hostel_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
