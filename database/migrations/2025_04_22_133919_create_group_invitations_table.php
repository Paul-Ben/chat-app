<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('group_invitations', function (Blueprint $table) {
            $table->id(); 
            
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('invited_by');
            
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('token')->unique();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->foreign('chat_id')
                  ->references('id')
                  ->on('chats')
                  ->onDelete('cascade');
                  
            $table->foreign('invited_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_invitations');
    }
};
