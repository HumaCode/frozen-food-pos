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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['product', 'total'])->default('product');
            $table->enum('discount_type', ['percentage', 'nominal'])->default('percentage');
            $table->decimal('value', 15, 2)->default(0);
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('min_purchase', 15, 2)->nullable(); // untuk type=total
            $table->boolean('is_active')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
