<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('access_key', 50)->unique();
            $table->string('secret_key', 50)->unique();
            $table->text('trusted_ip_list')->nullable();
            $table->string('label')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->string('scopes')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
