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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('sender_id');
            
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_type')->nullable();
            
            $table->timestamp('read_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            $table->boolean('is_system_message')->default(false);
            $table->string('system_message_type')->nullable();
            $table->json('system_message_metadata')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('chat_id')
                  ->references('id')
                  ->on('chats')
                  ->onDelete('cascade');
                  
            $table->foreign('sender_id')
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
        Schema::dropIfExists('messages');
    }
};
