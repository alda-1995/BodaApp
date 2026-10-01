<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Las preguntas personalizadas del formulario las define cada organizador,
     * así que sus respuestas se guardan juntas como pregunta => respuesta.
     */
    public function up(): void
    {
        Schema::table('rsvps', function (Blueprint $table) {
            $table->json('answers')->nullable()->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('rsvps', function (Blueprint $table) {
            $table->dropColumn('answers');
        });
    }
};
