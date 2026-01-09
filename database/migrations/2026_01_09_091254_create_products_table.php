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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('barcode', 100)->unique()->nullable();
            $table->string('name');
            $table->string('image')->nullable();
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('unit', 50)->default('pcs');
            $table->integer('min_stock')->default(10);
            $table->date('expired_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index('barcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
