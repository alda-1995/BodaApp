<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Las notificaciones se vuelven por evento y el medio deja de ser un enum:
 * agregar un medio nuevo no debe requerir una migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->dropColumn('sent_via');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->string('channel', 30)->default('email')->after('message')->index();
        });

        Schema::table('guest_notification', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('guest_notification', function (Blueprint $table) {
            // queued, sent, failed, skipped
            $table->string('status', 20)->default('queued')->after('notification_id')->index();
            $table->string('provider_message_id')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('guest_notification', function (Blueprint $table) {
            $table->dropColumn(['status', 'provider_message_id']);
        });

        Schema::table('guest_notification', function (Blueprint $table) {
            $table->enum('status', ['sent', 'failed'])->default('sent')->after('notification_id');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
            $table->dropColumn('channel');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('sent_via', ['email', 'sms', 'whatsapp', 'push'])->default('email')->after('message');
        });
    }
};
