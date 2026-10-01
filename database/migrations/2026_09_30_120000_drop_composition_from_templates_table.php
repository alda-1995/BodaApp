<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Se retira el armador de plantillas del panel.
 *
 * Cada plantilla vuelve a tener su clase de estrategia, que es donde declara
 * sus secciones, así que ya no hay plantillas "compuestas" y estas dos columnas
 * se quedaron sin quién las lea:
 *
 *   composition  de qué bloques se componía y en qué orden.
 *   status       permitía dejarla a medias sin ponerla a la venta; para eso ya
 *                estaba is_active, que es la que usa el catálogo.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['composition', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->json('composition')->nullable()->after('admin_fields');
            $table->string('status')->default('published')->after('is_active');
        });
    }
};
