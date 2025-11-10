<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name', 50);
            $table->string('symbol', 10)->nullable();
            $table->boolean('coin')->default(true);
            $table->integer('precision')->default(8);
            $table->decimal('quick_withdraw_limit', 32, 16)->default(0);
            $table->decimal('withdraw_fee', 32, 16)->default(0);
            $table->decimal('deposit_fee', 32, 16)->default(0);
            $table->decimal('min_deposit_amount', 32, 16)->default(0);
            $table->decimal('min_withdraw_amount', 32, 16)->default(0);
            $table->boolean('visible')->default(true);
            $table->boolean('deposit_enabled')->default(true);
            $table->boolean('withdraw_enabled')->default(true);
            $table->bigInteger('base_factor')->default(1);
            $table->timestamps();
            
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
