<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipo de bloque con el que se pinta una sección del sistema (banner,
 * itinerario, formulario de contacto...). Con él se busca la vista dentro de la
 * carpeta de cada plantilla; si la plantilla no lo trae, se usa el compartido.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_sections', function (Blueprint $table) {
            $table->string('block_type', 40)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('system_sections', function (Blueprint $table) {
            $table->dropColumn('block_type');
        });
    }
};
