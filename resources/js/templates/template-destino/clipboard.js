/**
 * Copiar al portapapeles los datos marcados como copiables.
 *
 * Es de esta plantilla: no depende del JS del panel. El dato que se copia sale
 * del propio texto que se ve, así que el botón no necesita repetirlo en un
 * atributo ni se puede desincronizar de lo que está en pantalla.
 */
const AVISO_MS = 2000;

export function initClipboard() {
    document.querySelectorAll('[data-copy-group]').forEach(conectar);
}

function conectar(grupo) {
    const valor = grupo.querySelector('[data-copy-value]');
    const boton = grupo.querySelector('[data-copy-button]');
    const icono = grupo.querySelector('[data-copy-icon]');
    const listo = grupo.querySelector('[data-copy-done]');
    const aviso = grupo.querySelector('[data-copy-message]');

    if (!valor || !boton) {
        return;
    }

    boton.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(valor.textContent.trim());
        } catch (error) {
            // Navegadores sin permiso de portapapeles: se selecciona el texto
            // para que la persona lo copie a mano.
            const rango = document.createRange();
            rango.selectNodeContents(valor);
            window.getSelection()?.removeAllRanges();
            window.getSelection()?.addRange(rango);

            return;
        }

        icono?.style.setProperty('display', 'none');
        listo?.style.setProperty('display', 'block');
        aviso?.classList.add('is-visible');

        setTimeout(() => {
            icono?.style.removeProperty('display');
            listo?.style.setProperty('display', 'none');
            aviso?.classList.remove('is-visible');
        }, AVISO_MS);
    });
}
