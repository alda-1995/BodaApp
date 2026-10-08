<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El texto con el que se presenta una plantilla antes de comprarla.
 *
 * Hasta ahora la landing y el resumen de compra describían todas las plantillas
 * con el mismo párrafo escrito en el Blade, comprara quien comprara lo que
 * comprara.
 *
 * Su imagen no vive aquí: va como AppFile, igual que las del wizard, para que
 * el borrado y el reemplazo los lleve el mismo servicio de archivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->text('description')->nullable()->after('view_path');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
