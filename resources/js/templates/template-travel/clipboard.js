/**
 * Copiar al portapapeles los datos de la mesa de regalos.
 *
 * Es de esta plantilla: no depende del JS del panel.
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
        message?.style.setProperty('opacity', '1');

        setTimeout(() => {
            icon?.style.removeProperty('display');
            done?.style.setProperty('display', 'none');
            message?.style.setProperty('opacity', '0');
        }, SUCCESS_DELAY);
    });
}

/** Conecta todos los datos copiables que haya en la página. */
export function initClipboard() {
    document.querySelectorAll('[data-copy-group]').forEach(copyGroup);
}
