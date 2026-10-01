@include($event->template->view_path, [
    'event' => $event,
    'guestData' => $guestData ?? null
])