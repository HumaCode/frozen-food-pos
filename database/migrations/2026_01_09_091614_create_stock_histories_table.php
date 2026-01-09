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
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out', 'adjustment', 'sale'])->default('in');
            $table->integer('qty')->default(0);
            $table->integer('stock_before')->default(0);
            $table->integer('stock_after')->default(0);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
