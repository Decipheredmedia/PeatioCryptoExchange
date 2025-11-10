<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('market_id')->constrained('markets')->onDelete('cascade');
            $table->decimal('price', 32, 16)->nullable();
            $table->decimal('volume', 32, 16)->default(0);
            $table->decimal('origin_volume', 32, 16)->default(0);
            $table->decimal('locked', 32, 16)->default(0);
            $table->decimal('origin_locked', 32, 16)->default(0);
            $table->integer('trades_count')->default(0);
            $table->string('state', 20)->default('wait');
            $table->string('type', 20)->default('limit'); // limit, market
            $table->string('side', 10); // buy, sell
            $table->timestamps();
            
            $table->index(['member_id', 'state']);
            $table->index(['market_id', 'state']);
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
