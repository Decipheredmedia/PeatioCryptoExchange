<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('aasm_state')->default('open');
            $table->foreignId('author_id')->constrained('members')->onDelete('cascade');
            $table->timestamps();
            
            $table->index('author_id');
            $table->index('aasm_state');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('author_id')->constrained('members')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
            
            $table->index('ticket_id');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('tickets');
    }
};
