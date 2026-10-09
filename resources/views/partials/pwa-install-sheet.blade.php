@php $pwaName = config('samuel.pwa_name'); @endphp
<div class="samuel-pwa-backdrop" id="samuelPwaInstallBackdrop" aria-hidden="true"></div>
<div class="samuel-pwa-sheet" id="samuelPwaInstallSheet" role="dialog" aria-labelledby="samuelPwaInstallTitle" aria-hidden="true">
    <div class="samuel-pwa-sheet__head">
        <h2 class="samuel-pwa-sheet__title" id="samuelPwaInstallTitle">Instalar {{ $pwaName }}</h2>
        <button type="button" class="samuel-pwa-sheet__close" id="samuelPwaInstallClose" aria-label="Cerrar">
            <i class="ri-close-line"></i>
        </button>
    </div>
    <div class="samuel-pwa-sheet__body">
        <img src="{{ asset('images/pwa/apple-touch-icon.png') }}" alt="" class="samuel-pwa-sheet__icon" width="72" height="72">
        <p class="samuel-pwa-sheet__text">
            Añade el álbum a tu pantalla de inicio para abrirlo como app, con el icono de Samuel.
        </p>
        <p class="samuel-pwa-sheet__hint samuel-pwa-sheet__hint--ios">
            <strong>iPhone:</strong> Safari → Compartir → Añadir a pantalla de inicio.
        </p>
        <p class="samuel-pwa-sheet__hint samuel-pwa-sheet__hint--android">
            <strong>Android:</strong> Chrome → menú ⋮ → Instalar app.
        </p>
    </div>
    <div class="samuel-pwa-sheet__actions">
        <button type="button" class="samuel-pwa-sheet__btn samuel-pwa-sheet__btn--primary" id="samuelPwaInstallNativeBtn" hidden>
            <i class="ri-download-2-line"></i> Instalar ahora
        </button>
        <button type="button" class="samuel-pwa-sheet__btn samuel-pwa-sheet__btn--ghost" id="samuelPwaInstallCloseSecondary">
            Cerrar
        </button>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('samuelPwaInstallCloseSecondary');
    var close = document.getElementById('samuelPwaInstallClose');
    function shut() {
        var sheet = document.getElementById('samuelPwaInstallSheet');
        var backdrop = document.getElementById('samuelPwaInstallBackdrop');
        if (sheet) sheet.classList.remove('is-open');
        if (backdrop) backdrop.classList.remove('is-open');
    }
    if (btn) btn.addEventListener('click', shut);
    if (close) close.addEventListener('click', shut);
})();
</script>
@endpush
