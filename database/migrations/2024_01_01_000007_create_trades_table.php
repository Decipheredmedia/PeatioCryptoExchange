<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->decimal('price', 32, 16);
            $table->decimal('volume', 32, 16);
            $table->decimal('funds', 32, 16);
            $table->foreignId('market_id')->constrained('markets')->onDelete('cascade');
            $table->foreignId('maker_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('maker_order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('taker_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('taker_order_id')->constrained('orders')->onDelete('cascade');
            $table->integer('trend')->nullable();
            $table->timestamps();
            
            $table->index('market_id');
            $table->index('maker_id');
            $table->index('taker_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
