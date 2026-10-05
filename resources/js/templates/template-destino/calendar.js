/**
 * "Añadir al calendario" del bloque de destino.
 *
 * Genera un .ics y lo descarga. Es a propósito un solo botón y no un menú de
 * Google/Outlook: el .ics lo abren todos los calendarios, incluidos ésos, y el
 * diseño tiene sitio para un botón, no para tres.
 *
 * La boda se da por terminada 6 horas después de la hora de inicio, que es lo
 * que ya asume la otra plantilla.
 */
const DURACION_HORAS = 6;

export function initCalendar() {
    document.querySelectorAll('[data-calendar]').forEach((boton) => {
        const inicio = new Date(boton.dataset.inicio);

        if (Number.isNaN(inicio.getTime())) {
            return;
        }

        boton.addEventListener('click', () => descargar({
            titulo: boton.dataset.titulo || 'Nuestra boda',
            lugar: boton.dataset.lugar || '',
            inicio,
            fin: new Date(inicio.getTime() + DURACION_HORAS * 60 * 60 * 1000),
        }));
    });
}

function descargar(evento) {
    const ics = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Tamira//Invitacion//ES',
        'BEGIN:VEVENT',
        `UID:${sello(evento.inicio)}-${Math.random().toString(36).slice(2)}@tamira`,
        `DTSTAMP:${sello(new Date())}`,
        `DTSTART:${sello(evento.inicio)}`,
        `DTEND:${sello(evento.fin)}`,
        `SUMMARY:${escapar(evento.titulo)}`,
        `LOCATION:${escapar(evento.lugar)}`,
        'END:VEVENT',
        'END:VCALENDAR',
    ].join('\r\n');

    const url = URL.createObjectURL(new Blob([ics], { type: 'text/calendar;charset=utf-8' }));
    const enlace = document.createElement('a');

    enlace.href = url;
    enlace.download = 'invitacion.ics';
    enlace.click();

    URL.revokeObjectURL(url);
}

/** Formato UTC que piden los calendarios: 20270110T213000Z. */
function sello(fecha) {
    return fecha.toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');
}

function escapar(texto) {
    return String(texto).replace(/([,;\\])/g, '\\$1').replace(/\n/g, '\\n');
}
