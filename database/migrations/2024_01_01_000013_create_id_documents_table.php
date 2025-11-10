<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('id_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('id_document_type')->nullable();
            $table->string('name')->nullable();
            $table->string('id_document_number')->nullable();
            $table->string('id_bill_type')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('aasm_state')->default('unverified');
            $table->boolean('verified')->default(false);
            $table->timestamps();
            
            $table->index('member_id');
            $table->index('aasm_state');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('id_documents');
    }
};
