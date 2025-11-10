<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('amount', 32, 16)->default(0);
            $table->decimal('fee', 32, 16)->default(0);
            $table->decimal('sum', 32, 16)->default(0);
            $table->string('fund_uid')->nullable();
            $table->string('fund_extra')->nullable();
            $table->string('txid')->nullable();
            $table->string('state', 30)->default('submitted');
            $table->string('aasm_state', 30)->default('submitted');
            $table->string('type')->nullable();
            $table->string('tid')->nullable();
            $table->string('rid')->nullable();
            $table->integer('confirmations')->default(0);
            $table->timestamps();
            
            $table->index('member_id');
            $table->index('currency_id');
            $table->index('txid');
            $table->index('state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
