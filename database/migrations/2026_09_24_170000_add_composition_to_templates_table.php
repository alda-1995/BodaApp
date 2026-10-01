<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Plantillas armadas desde el panel.
     *
     * 'composition' guarda de qué bloques se compone y en qué orden; las
     * plantillas escritas en código la dejan nula y siguen resolviéndose por su
     * view_path. 'status' permite dejarlas a medias sin ponerlas a la venta.
     */
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->json('composition')->nullable()->after('admin_fields');
            $table->string('status')->default('published')->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['composition', 'status']);
        });
    }
};
