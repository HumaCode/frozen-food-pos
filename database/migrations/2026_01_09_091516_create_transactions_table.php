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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->foreignId('discount_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('payment_method', 50)->default('cash');
            $table->text('notes')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['shift_id', 'created_at']);
            $table->index('invoice_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
