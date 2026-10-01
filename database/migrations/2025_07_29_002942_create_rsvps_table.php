<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_guest_id')->unique()->constrained('event_guest')->onDelete('cascade');
            
            $table->enum('attendance', ['confirmed', 'declined', 'pending'])->default('pending')->index();
            
            $table->unsignedInteger('confirmed_passes')->default(0);
            
            $table->string('dietary_restrictions')->nullable();
            $table->text('comments')->nullable();
        
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsvps');
    }
};