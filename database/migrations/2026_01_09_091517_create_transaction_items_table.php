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
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // snapshot nama produk
            $table->decimal('price', 15, 2)->default(0); // harga saat transaksi
            $table->integer('qty')->default(1);
            $table->decimal('discount_per_item', 15, 2)->default(0);
            $table->boolean('is_wholesale')->default(false);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();

            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
