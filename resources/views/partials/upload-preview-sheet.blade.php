<div class="samuel-upload-preview-backdrop" id="samuelUploadPreviewBackdrop" aria-hidden="true"></div>
<section class="samuel-upload-preview" id="samuelUploadPreview" aria-hidden="true" aria-labelledby="samuelUploadPreviewTitle">
    <div class="samuel-upload-preview__sheet">
        <header class="samuel-upload-preview__head">
            <h2 id="samuelUploadPreviewTitle">Vista previa</h2>
            <button type="button" class="samuel-upload-preview__close" id="samuelUploadPreviewClose" aria-label="Cerrar">
                <i class="ri-close-line"></i>
            </button>
        </header>
        <div class="samuel-upload-preview__body">
            <article class="samuel-wall-card samuel-wall-card--compact samuel-upload-preview__card">
                <div class="samuel-wall-card__head">
                    @include('partials.member-avatar', ['member' => $familyMember, 'size' => 'sm'])
                    <div class="samuel-wall-card__meta">
                        <div class="samuel-wall-card__line1">
                            <strong class="samuel-wall-card__name">{{ $familyMember->name }}</strong>
                            @if($familyMember->relationshipLabel())
                                <span class="samuel-wall-card__dot" aria-hidden="true">·</span>
                                <span class="samuel-wall-card__relation">{{ $familyMember->relationshipLabel() }}</span>
                            @endif
                        </div>
                        <div class="samuel-wall-card__time" id="samuelPreviewPubLine"></div>
                        <div class="samuel-wall-card__age-line">
                            <span class="samuel-age-at" id="samuelPreviewAgeLine" hidden></span>
                        </div>
                    </div>
                </div>
                <div class="samuel-wall-card__media" id="samuelPreviewMedia"></div>
                <p class="samuel-wall-caption" id="samuelPreviewCaption" hidden></p>
            </article>
        </div>
        <div class="samuel-upload-preview__actions">
            <button type="button" class="samuel-btn samuel-btn--ghost" id="samuelUploadPreviewBack">Seguir editando</button>
            <button type="button" class="samuel-btn" id="samuelUploadPreviewPublish">Publicar recuerdo</button>
        </div>
    </div>
</section>
