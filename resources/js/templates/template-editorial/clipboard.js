/**
 * Copiar al portapapeles los datos marcados como copiables.
 *
 * Es de esta plantilla: no depende del JS del panel. El dato que se copia sale
 * del propio texto que se ve, así que el botón no necesita repetirlo en un
 * atributo ni se puede desincronizar de lo que está en pantalla.
 */
const SUCCESS_DELAY = 2000;

function copyGroup(group) {
    const value = group.querySelector('[data-copy-value]');
    const button = group.querySelector('[data-copy-button]');
    const icon = group.querySelector('[data-copy-icon]');
    const done = group.querySelector('[data-copy-done]');
    const message = group.querySelector('[data-copy-message]');

    if (!value || !button) {
        return;
    }

    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(value.textContent.trim());
        } catch (error) {
            // Navegadores sin permiso de portapapeles: se selecciona el texto
            // para que la persona lo copie a mano.
            const range = document.createRange();
            range.selectNodeContents(value);
            window.getSelection()?.removeAllRanges();
            window.getSelection()?.addRange(range);

            return;
        }

        icon?.style.setProperty('display', 'none');
        done?.style.setProperty('display', 'block');
        message?.classList.add('te-copy__done--visible');

        setTimeout(() => {
            icon?.style.removeProperty('display');
            done?.style.setProperty('display', 'none');
            message?.classList.remove('te-copy__done--visible');
        }, SUCCESS_DELAY);
    });
}

/** Conecta todos los datos copiables que haya en la página. */
export function initClipboard() {
    document.querySelectorAll('[data-copy-group]').forEach(copyGroup);
}
