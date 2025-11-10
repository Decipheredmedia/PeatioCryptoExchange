<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('type', 20); // app, sms, email
            $table->string('otp_secret')->nullable();
            $table->boolean('activated')->default(false);
            $table->timestamp('refreshed_at')->nullable();
            $table->timestamp('last_verify_at')->nullable();
            $table->timestamps();
            
            $table->index('member_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factors');
    }
};
