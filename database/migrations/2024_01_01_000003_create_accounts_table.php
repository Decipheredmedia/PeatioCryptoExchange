<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('currency_id')->constrained('currencies')->onDelete('cascade');
            $table->decimal('balance', 32, 16)->default(0);
            $table->decimal('locked', 32, 16)->default(0);
            $table->decimal('in', 32, 16)->default(0);
            $table->decimal('out', 32, 16)->default(0);
            $table->timestamps();
            
            $table->unique(['member_id', 'currency_id']);
            $table->index('member_id');
            $table->index('currency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
