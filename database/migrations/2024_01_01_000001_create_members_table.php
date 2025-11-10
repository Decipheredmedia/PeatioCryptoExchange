<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('sn')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('display_name')->nullable();
            $table->string('country_code')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('nickname')->nullable();
            $table->boolean('activated')->default(false);
            $table->boolean('disabled')->default(false);
            $table->boolean('api_disabled')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('sn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
