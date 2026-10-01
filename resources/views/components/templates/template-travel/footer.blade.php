{{-- .boat-animation-move y #btn-scroll-top los usa animations.js. --}}
<footer class="tv-footer">
    <div class="tv-footer__boat">
        <img class="boat-animation-move" src="{{ asset('images/assets-travel/barco.png') }}" alt="Barco">
    </div>

    <div class="tv-container">
        <div class="tv-footer__row">
            <div class="tv-footer__logo">
                <img src="{{ asset('images/assets-travel/logo-footer.png') }}" alt="Logo">
            </div>

            <nav class="tv-footer__nav">
                <a href="#historia" data-block-go="#historia" class="link-footer">Historia</a>
                <a href="#time-canvas-weeding" data-block-go="#time-canvas-weeding" class="link-footer">Itinerario</a>
                <a href="#confirm" data-block-go="#confirm" class="link-footer">Confirmación asistencia</a>
                <a href="#galeria" data-block-go="#galeria" class="link-footer">Galería</a>
            </nav>
        </div>

        <h2 class="tv-footer__message">
            Cada historia de amor es única, y la nuestra brilla más con personas como tú.
        </h2>

        <div class="tv-footer__top">
            <button id="btn-scroll-top" type="button" aria-label="Volver arriba">
                <x-icons.arrow-up />
            </button>
        </div>
    </div>
</footer>
