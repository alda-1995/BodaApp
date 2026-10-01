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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->foreignId('template_id')->constrained()->onDelete('restrict');

            $table->string('stripe_session_id')->unique()->index();
            $table->string('stripe_payment_intent_id')->nullable()->unique()->index();

            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('MXN');

            $table->enum('status', ['completed', 'pending', 'paid', 'failed', 'refunded'])->default('pending')->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
