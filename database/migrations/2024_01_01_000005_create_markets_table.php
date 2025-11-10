<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ask_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->foreignId('bid_currency_id')->constrained('currencies')->onDelete('cascade');
            $table->string('ask_unit', 10);
            $table->string('bid_unit', 10);
            $table->decimal('ask_fee', 17, 4)->default(0);
            $table->decimal('bid_fee', 17, 4)->default(0);
            $table->decimal('min_ask_price', 32, 16)->default(0);
            $table->decimal('max_bid_price', 32, 16)->default(0);
            $table->decimal('min_ask_amount', 32, 16)->default(0);
            $table->decimal('min_bid_amount', 32, 16)->default(0);
            $table->integer('ask_precision')->default(4);
            $table->integer('bid_precision')->default(4);
            $table->boolean('enabled')->default(true);
            $table->boolean('visible')->default(true);
            $table->timestamps();
            
            $table->unique(['ask_currency_id', 'bid_currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('markets');
    }
};
