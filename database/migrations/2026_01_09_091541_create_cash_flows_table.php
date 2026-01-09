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
        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['in', 'out'])->default('in');
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('description');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flows');
    }
};
