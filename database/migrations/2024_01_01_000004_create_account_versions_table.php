<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade');
            $table->integer('reason')->nullable();
            $table->decimal('balance', 32, 16)->default(0);
            $table->decimal('locked', 32, 16)->default(0);
            $table->decimal('fee', 32, 16)->default(0);
            $table->decimal('amount', 32, 16)->default(0);
            $table->unsignedBigInteger('modifiable_id')->nullable();
            $table->string('modifiable_type')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained('currencies')->onDelete('cascade');
            $table->integer('fun')->nullable();
            $table->timestamps();
            
            $table->index(['account_id', 'reason']);
            $table->index(['member_id', 'reason']);
            $table->index(['modifiable_id', 'modifiable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_versions');
    }
};
