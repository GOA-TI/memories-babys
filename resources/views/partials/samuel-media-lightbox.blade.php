<div
    id="samuelMediaLightbox"
    class="samuel-media-lightbox"
    hidden
    aria-modal="true"
    role="dialog"
    aria-label="Ver recuerdo"
>
    <div class="samuel-media-lightbox__backdrop" data-samuel-lightbox-close tabindex="-1" aria-hidden="true"></div>
    <div class="samuel-media-lightbox__panel">
        <header class="samuel-media-lightbox__toolbar">
            <span class="samuel-media-lightbox__count" data-samuel-lightbox-count hidden></span>
            <div class="samuel-media-lightbox__toolbar-actions">
                <a
                    class="samuel-media-lightbox__tool"
                    data-samuel-lightbox-download
                    href="#"
                    download
                    target="_blank"
                    rel="noopener"
                >
                    <i class="ri-download-2-line" aria-hidden="true"></i>
                    <span>Descargar</span>
                </a>
                <button type="button" class="samuel-media-lightbox__tool" data-samuel-lightbox-close aria-label="Cerrar">
                    <i class="ri-close-line" aria-hidden="true"></i>
                </button>
            </div>
        </header>
        <div class="samuel-media-lightbox__stage">
            <button type="button" class="samuel-media-lightbox__nav samuel-media-lightbox__nav--prev" data-samuel-lightbox-prev hidden aria-label="Anterior">
                <i class="ri-arrow-left-s-line" aria-hidden="true"></i>
            </button>
            <div class="samuel-media-lightbox__content" data-samuel-lightbox-content></div>
            <button type="button" class="samuel-media-lightbox__nav samuel-media-lightbox__nav--next" data-samuel-lightbox-next hidden aria-label="Siguiente">
                <i class="ri-arrow-right-s-line" aria-hidden="true"></i>
            </button>
        </div>
        <p class="samuel-media-lightbox__caption" data-samuel-lightbox-caption hidden></p>
    </div>
</div>
