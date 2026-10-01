<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nombres cortos con los que se arma la URL amigable. Son aparte de los del paso
 * "general" del wizard, que son los que ve el invitado en la plantilla y suelen
 * llevar apellidos (harían una URL larguísima).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('url_partner_1', 100)->nullable()->after('custom_url');
            $table->string('url_partner_2', 100)->nullable()->after('url_partner_1');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['url_partner_1', 'url_partner_2']);
        });
    }
};
