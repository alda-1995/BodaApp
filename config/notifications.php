<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Medios de envío
    |--------------------------------------------------------------------------
    |
    | Cada medio es una clase que implementa NotificationChannel. Para agregar
    | uno nuevo (SMS, push...) basta con crear su clase y registrarla aquí; para
    | quitarlo, borrar su línea. La pantalla de notificaciones se arma con esta
    | lista, así que el botón aparece o desaparece solo.
    |
    */
    'channels' => [
        'email' => App\Notifications\Channels\EmailChannel::class,
        'whatsapp' => App\Notifications\Channels\WhatsAppChannel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mensajes incluidos por mes
    |--------------------------------------------------------------------------
    |
    | Cupo mensual por evento, sumando todos los medios. Los envíos omitidos
    | (por ejemplo, un invitado sin correo) no consumen cupo.
    |
    */
    'monthly_quota' => (int) env('NOTIFICATIONS_MONTHLY_QUOTA', 400),

];
