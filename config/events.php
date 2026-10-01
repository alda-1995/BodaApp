<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vigencia de la invitación digital
    |--------------------------------------------------------------------------
    |
    | Días que la invitación sigue activa después de la fecha del evento cuando
    | la plantilla no define su propia duración (templates.duration_days).
    | Después se deshabilita y el organizador puede comprar otra.
    |
    */
    'default_duration_days' => (int) env('EVENT_DEFAULT_DURATION_DAYS', 21),
];
