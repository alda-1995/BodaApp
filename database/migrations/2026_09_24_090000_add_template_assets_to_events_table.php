<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Imágenes de la plantilla que el superadmin reemplaza para un evento
     * concreto (por ejemplo el monograma de una pareja).
     *
     * Van en su propia columna y no en features a propósito: features es del
     * organizador y lo escribe el wizard. Separando el almacén, ninguno de los
     * dos puede pisar lo del otro.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('template_assets')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('template_assets');
        });
    }
};
