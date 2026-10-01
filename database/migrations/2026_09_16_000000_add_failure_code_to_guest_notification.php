<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Guarda el tipo de falla del envío. El mensaje del proveedor sigue en
 * error_message para soporte; al organizador se le muestra un texto derivado de
 * este código (ver App\Notifications\DeliveryIssue).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guest_notification', function (Blueprint $table) {
            $table->string('failure_code', 40)->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('guest_notification', function (Blueprint $table) {
            $table->dropColumn('failure_code');
        });
    }
};
