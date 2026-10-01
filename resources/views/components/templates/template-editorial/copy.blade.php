@props(['value', 'label' => ''])

{{--
    Un dato que se copia.

    Se usa cuando el superadmin marcó ese dato como copiable en "Tipos de mesa
    de regalo". Entonces el valor NO es una liga: se muestra y se copia, aunque
    sea una URL, porque lo que la pareja quiere es que lo peguen en su banco o
    en su navegador.

    Los data-* los conecta clipboard.js de esta plantilla.
--}}
<span class="te-copy" data-copy-group>
    <span class="te-copy__value" data-copy-value>{{ $value }}</span>

    <button class="te-copy__button" type="button" data-copy-button
        aria-label="Copiar {{ $label ?: $value }}">
        <svg data-copy-icon xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
            fill="none" aria-hidden="true">
            <path fill="currentColor"
                d="M9 18C8.45 18 7.97917 17.8042 7.5875 17.4125C7.19583 17.0208 7 16.55 7 16V4C7 3.45 7.19583 2.97917 7.5875 2.5875C7.97917 2.19583 8.45 2 9 2H18C18.55 2 19.0208 2.19583 19.4125 2.5875C19.8042 2.97917 20 3.45 20 4V16C20 16.55 19.8042 17.0208 19.4125 17.4125C19.0208 17.8042 18.55 18 18 18H9ZM9 16H18V4H9V16ZM5 22C4.45 22 3.97917 21.8042 3.5875 21.4125C3.19583 21.0208 3 20.55 3 20V6H5V20H16V22H5Z" />
        </svg>

        <svg data-copy-done xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" style="display: none;" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
    </button>

    <span class="te-copy__done" data-copy-message aria-live="polite">¡Copiado!</span>
</span>
