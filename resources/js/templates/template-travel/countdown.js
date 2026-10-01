/**
 * Cuenta regresiva del banner.
 *
 * Es de esta plantilla: no depende del JS del panel.
 */
export function startCountdown(targetDateString) {
    const targetDate = new Date(targetDateString).getTime();

    const $days = document.getElementById('days');
    const $hours = document.getElementById('hours');
    const $minutes = document.getElementById('minutes');
    const $seconds = document.getElementById('seconds');

    if (!$days || !$hours || !$minutes || !$seconds) {
        return;
    }

    const pad = (value) => String(value).padStart(2, '0');

    const updateCountdown = () => {
        const distance = targetDate - new Date().getTime();

        // Ya pasó la fecha: se deja el contador en cero.
        if (distance < 0) {
            $days.textContent = '00';
            $hours.textContent = '00';
            $minutes.textContent = '00';
            $seconds.textContent = '00';

            return clearInterval(interval);
        }

        const day = 1000 * 60 * 60 * 24;
        const hour = 1000 * 60 * 60;
        const minute = 1000 * 60;

        $days.textContent = pad(Math.floor(distance / day));
        $hours.textContent = pad(Math.floor((distance % day) / hour));
        $minutes.textContent = pad(Math.floor((distance % hour) / minute));
        $seconds.textContent = pad(Math.floor((distance % minute) / 1000));
    };

    updateCountdown();
    const interval = setInterval(updateCountdown, 1000);
}

/** Arranca la cuenta regresiva con la fecha que trae el bloque en el HTML. */
export function initCountdown() {
    const element = document.querySelector('[data-countdown]');

    if (element) {
        startCountdown(element.dataset.countdown);
    }
}
