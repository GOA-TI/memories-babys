@php
    $pwaName = config('samuel.pwa_name');
    $giftLine = config('samuel.welcome.gift_line');
@endphp
<div class="samuel-modal-backdrop" id="samuelWelcomeBackdrop" aria-hidden="true"></div>
<div class="samuel-welcome-confetti" id="samuelWelcomeConfetti" aria-hidden="true"></div>
<div class="samuel-welcome-sheet" id="samuelWelcomeSheet" role="dialog" aria-labelledby="samuelWelcomeTitle" aria-hidden="true" data-welcome-seen-url="{{ route('welcome.seen') }}">
    <div class="samuel-welcome-sheet__glow" aria-hidden="true"></div>
    <div class="samuel-welcome-sheet__sparkles" aria-hidden="true">
        <span>✨</span><span>🎉</span><span>💗</span><span>👶</span><span>✨</span>
    </div>
    <button type="button" class="samuel-welcome-sheet__close" id="samuelWelcomeClose" aria-label="Cerrar">
        <i class="ri-close-line"></i>
    </button>
    <div class="samuel-welcome-sheet__hero">
        <img src="{{ asset('images/pwa/apple-touch-icon.png') }}" alt="" class="samuel-welcome-sheet__photo" width="88" height="88">
        <div class="samuel-welcome-sheet__user-avatar">
            @include('partials.member-avatar', ['member' => $familyMember, 'size' => 'md'])
        </div>
    </div>
    <h2 class="samuel-welcome-sheet__title" id="samuelWelcomeTitle">
        ¡Qué alegría tenerte aquí, {{ $familyMember->name }}!
    </h2>
    @if($familyMember->relationshipLine())
        <p class="samuel-welcome-sheet__relation">{{ $familyMember->relationshipLine() }}</p>
    @endif
    <div class="samuel-welcome-sheet__body">
        <p class="samuel-welcome-sheet__lead">
            <strong>{{ $pwaName }}</strong> es el rinconcito de la familia para guardar fotos y videos de Samuel con cariño.
        </p>
        <ul class="samuel-welcome-sheet__list">
            <li><i class="ri-camera-lens-line"></i> Sube recuerdos con respeto y buena energía.</li>
            <li><i class="ri-heart-3-line"></i> Reacciona y celebra los momentos de los demás.</li>
            <li><i class="ri-shield-heart-line"></i> Solo entra quien la familia invita; cuidemos la privacidad del bebé.</li>
        </ul>
        <p class="samuel-welcome-sheet__gift">
            <i class="ri-gift-2-line" aria-hidden="true"></i>
            {{ $giftLine }}
        </p>
    </div>
    <button type="button" class="samuel-welcome-sheet__cta" id="samuelWelcomeEnter">
        Entrar al álbum
    </button>
</div>
