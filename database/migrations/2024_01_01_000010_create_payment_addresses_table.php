<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->string('address')->unique();
            $table->string('secret')->nullable();
            $table->timestamps();
            
            $table->index(['member_id', 'currency_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_addresses');
    }
};
