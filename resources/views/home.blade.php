@extends('layouts.mobile', ['mobileNavActive' => 'home'])

@section('title', 'Inicio')

@section('content')
    <div class="samuel-home">
        @if(! $familyMember->hasRelationship())
            <section class="samuel-home-card samuel-home-card--hint">
                <p class="samuel-home-empty mb-0">Toca tu nombre arriba para elegir si eres mamá, papá, madrina…</p>
                <a href="{{ route('profile.edit') }}" class="samuel-home-cta">Mi perfil</a>
            </section>
        @endif

        @include('partials.samuel-home-card')

        <section class="samuel-home-card">
            <div class="samuel-home-card__head">
                <span class="samuel-home-card__title"><i class="ri-heart-3-fill"></i> Favoritos</span>
                <a href="{{ route('wall') }}" class="samuel-home-card__link">Ver muro</a>
            </div>
            <div class="samuel-chip-row samuel-chip-row--inset">
                @forelse($favorites as $post)
                    <a href="{{ route('wall') }}#post-{{ $post->id }}" class="samuel-fav-card">
                        @if($post->coverMedia())
                            @include('partials.media-thumb', ['item' => $post->coverMedia(), 'eager' => $loop->index < 3])
                        @endif
                    </a>
                @empty
                    <p class="samuel-home-empty">Aún no hay favoritos. Marca corazones en el muro.</p>
                @endforelse
            </div>
        </section>

        <section class="samuel-home-card">
            <div class="samuel-home-card__head">
                <span class="samuel-home-card__title"><i class="ri-sparkling-2-line"></i> Últimos recuerdos</span>
                <a href="{{ route('memories.index') }}" class="samuel-home-card__link">Ver los míos</a>
            </div>
            @if($latest->isEmpty())
                <p class="samuel-home-empty">Todavía no hay fotos. ¡Sé el primero en subir un recuerdo!</p>
                <a href="{{ route('upload.create') }}" class="samuel-home-cta">Subir recuerdo</a>
                <a href="{{ route('memories.index') }}" class="samuel-home-relationship-cta" style="margin-left:0.5rem;">Mis recuerdos</a>
            @else
                <div class="samuel-grid samuel-grid--inset">
                    @foreach($latest as $post)
                        <article class="samuel-grid__item" id="mem-{{ $post->id }}">
                            @if($post->coverMedia())
                                @include('partials.media-thumb', ['item' => $post->coverMedia(), 'eager' => $loop->index < 4])
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
