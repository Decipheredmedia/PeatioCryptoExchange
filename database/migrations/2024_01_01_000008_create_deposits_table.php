<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('amount', 32, 16)->default(0);
            $table->decimal('fee', 32, 16)->default(0);
            $table->string('txid')->nullable();
            $table->integer('confirmations')->default(0);
            $table->string('state', 30)->default('submitted');
            $table->string('aasm_state', 30)->default('submitted');
            $table->string('type')->nullable();
            $table->string('tid')->nullable();
            $table->unsignedBigInteger('payment_transaction_id')->nullable();
            $table->timestamps();
            
            $table->index('member_id');
            $table->index('currency_id');
            $table->index('txid');
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
