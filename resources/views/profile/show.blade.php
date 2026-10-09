@extends('layouts.mobile', ['mobileNavActive' => 'wall'])

@section('title', $member->name)

@section('content')
    <div class="samuel-home samuel-member-public">
        <div class="samuel-section-title samuel-section-title--compact" style="margin-top:0;">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('wall') }}" class="samuel-back-link" aria-label="Volver">
                <i class="ri-arrow-left-line"></i>
            </a>
            <span><i class="ri-user-heart-line"></i> Perfil</span>
        </div>

        <section class="samuel-form-card samuel-member-public__card">
            <div class="samuel-member-public__hero">
                @include('partials.member-avatar', ['member' => $member, 'size' => 'lg'])
                <h1 class="samuel-member-public__name">{{ $member->name }}</h1>
                @if($member->relationshipLine())
                    <p class="samuel-member-public__relation">{{ $member->relationshipLine() }}</p>
                @elseif($member->relationshipLabel())
                    <p class="samuel-member-public__relation">{{ $member->relationshipLabel() }}</p>
                @else
                    <p class="samuel-member-public__relation samuel-member-public__relation--muted">Aún no ha elegido parentesco</p>
                @endif
            </div>

            @if($isSelf)
                <a href="{{ route('profile.edit') }}" class="samuel-home-cta samuel-member-public__edit">
                    <i class="ri-edit-line" aria-hidden="true"></i> Editar mi perfil
                </a>
            @endif
        </section>

        @if($recentPosts->isNotEmpty())
            <section class="samuel-home-card">
                <div class="samuel-home-card__head">
                    <span class="samuel-home-card__title"><i class="ri-gallery-line"></i> En el muro</span>
                    <a href="{{ route('wall') }}" class="samuel-home-card__link">Ver muro</a>
                </div>
                <div class="samuel-chip-row samuel-chip-row--inset">
                    @foreach($recentPosts as $post)
                        <a href="{{ route('wall') }}#post-{{ $post->id }}" class="samuel-fav-card" aria-label="Recuerdo del {{ $post->memoryMoment()->locale('es')->isoFormat('D MMM YYYY') }}">
                            @if($post->coverMedia())
                                @include('partials.media-thumb', ['item' => $post->coverMedia(), 'eager' => $loop->index < 4])
                            @endif
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
