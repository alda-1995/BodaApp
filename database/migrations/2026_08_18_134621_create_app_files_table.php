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
        Schema::create('app_files', function (Blueprint $table) {
            $table->id();
            
            // Relación polimórfica (crea fileable_type y fileable_id)
            $table->nullableMorphs('fileable');

            // Contexto del archivo dentro del módulo o formulario
            $table->string('section')->nullable()->comment('Ej: dress_code, avatar, gallery, invoices');
            $table->string('field_name')->nullable()->comment('Ej: reference_image, document_pdf');

            // Información del archivo
            $table->string('original_name');
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type');
            $table->string('file_type', 20)->comment('image, pdf, video, audio, document');
            $table->unsignedBigInteger('file_size')->comment('En bytes');

            // Ordenamiento y Metadatos adicionales
            $table->integer('sort_order')->default(0);
            $table->json('meta_data')->nullable();

            $table->timestamps();

            // Índice para agrupar por sección rápidamente
            $table->index('section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_files');
    }
};
