/**
 * "Agregar a mi calendario" del bloque de confirmación.
 *
 * Con la fecha del evento arma la liga de Google o de Outlook, o genera un .ics
 * para el resto de los calendarios. La boda se da por terminada 6 horas después.
 */
const DURATION_HOURS = 6;

export function initCalendarButtons() {
    document.querySelectorAll('[data-calendar]').forEach((group) => {
        const event = readEvent(group);

        if (!event) {
            return;
        }

        group.querySelectorAll('[data-calendar-type]').forEach((button) => {
            button.addEventListener('click', () => open(button.dataset.calendarType, event));
        });
    });
}

function readEvent(group) {
    const start = new Date(group.dataset.start);

    if (Number.isNaN(start.getTime())) {
        return null;
    }

    const end = new Date(start.getTime() + DURATION_HOURS * 60 * 60 * 1000);

    return {
        title: group.dataset.title || 'Nuestra boda',
        description: group.dataset.description || '',
        location: group.dataset.location || '',
        start,
        end,
    };
}

function open(type, event) {
    if (type === 'google') {
        const params = new URLSearchParams({
            action: 'TEMPLATE',
            text: event.title,
            details: event.description,
            location: event.location,
            dates: `${stamp(event.start)}/${stamp(event.end)}`,
        });

        window.open(`https://calendar.google.com/calendar/render?${params}`, '_blank', 'noopener');
        return;
    }

    if (type === 'outlook') {
        const params = new URLSearchParams({
            path: '/calendar/action/compose',
            rru: 'addevent',
            subject: event.title,
            body: event.description,
            location: event.location,
            startdt: event.start.toISOString(),
            enddt: event.end.toISOString(),
        });

        window.open(`https://outlook.live.com/calendar/0/deeplink/compose?${params}`, '_blank', 'noopener');
        return;
    }

    download(event);
}

function download(event) {
    const ics = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Tamira//Invitacion//ES',
        'BEGIN:VEVENT',
        `UID:${stamp(event.start)}-${Math.random().toString(36).slice(2)}@tamira`,
        `DTSTAMP:${stamp(new Date())}`,
        `DTSTART:${stamp(event.start)}`,
        `DTEND:${stamp(event.end)}`,
        `SUMMARY:${escape(event.title)}`,
        `DESCRIPTION:${escape(event.description)}`,
        `LOCATION:${escape(event.location)}`,
        'END:VEVENT',
        'END:VCALENDAR',
    ].join('\r\n');

    const url = URL.createObjectURL(new Blob([ics], { type: 'text/calendar;charset=utf-8' }));
    const link = document.createElement('a');

    link.href = url;
    link.download = 'invitacion.ics';
    link.click();

    URL.revokeObjectURL(url);
}

/** Formato UTC que piden los calendarios: 20270110T213000Z. */
function stamp(date) {
    return date.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
}

function escape(text) {
    return String(text).replace(/([,;\\])/g, '\\$1').replace(/\n/g, '\\n');
}
