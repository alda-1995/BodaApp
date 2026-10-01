<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La fecha de la boda se captura en el wizard, no al comprar: hasta entonces el
 * evento no tiene fecha (ni vigencia) y así se muestra en el panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('event_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dateTime('event_date')->nullable(false)->change();
        });
    }
};
