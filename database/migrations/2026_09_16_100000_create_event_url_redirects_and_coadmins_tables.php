<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - event_url_redirects: URLs amigables que tuvo un evento. Si la pareja cambia sus
 *   nombres, los enlaces ya enviados siguen llevando a su invitación.
 * - event_coadmins: personas invitadas a administrar el evento junto al dueño.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_url_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // Única en toda la tabla: una URL vieja sólo puede llevar a un evento.
            $table->string('custom_url')->unique();
            $table->timestamps();
        });

        Schema::create('event_coadmins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            // Se llena al aceptar la invitación.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_coadmins');
        Schema::dropIfExists('event_url_redirects');
    }
};
