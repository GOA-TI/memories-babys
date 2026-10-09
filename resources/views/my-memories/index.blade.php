@extends('layouts.mobile', ['mobileNavActive' => 'memories'])

@section('title', 'Mis recuerdos')

@section('content')
    <div class="samuel-section-title samuel-section-title--toolbar" style="margin-top:0.85rem;">
        <span><i class="ri-gallery-upload-line"></i> Mis recuerdos</span>
        <a href="{{ route('upload.create') }}" class="samuel-section-title__action">
            <i class="ri-add-line"></i> Subir
        </a>
    </div>

    <div class="samuel-grid-toggle" role="group" aria-label="Vista de cuadrícula">
        @foreach(['1' => 'Lista', '2' => 'Cuadros', '3' => 'Mini'] as $cols => $label)
            <a href="{{ route('memories.index', ['grid' => $cols]) }}"
               class="samuel-grid-toggle__btn {{ $grid === $cols ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>

    @if($posts->isEmpty())
        <section class="samuel-home-card" style="margin:0.75rem 1rem;">
            <p class="samuel-home-empty">Aún no has subido recuerdos.</p>
            <a href="{{ route('upload.create') }}" class="samuel-home-cta">Subir el primero</a>
        </section>
    @else
        <div class="samuel-my-grid samuel-my-grid--cols-{{ $grid }}">
            @foreach($posts as $post)
                @include('partials.my-memory-item', ['post' => $post])
            @endforeach
        </div>
        <div class="samuel-pagination-wrap">{{ $posts->links() }}</div>
    @endif

    <div class="samuel-fab-spacer" aria-hidden="true"></div>
    <a href="{{ route('upload.create') }}" class="samuel-fab-upload" aria-label="Subir recuerdo">
        <i class="ri-camera-lens-line"></i>
    </a>
@endsection
