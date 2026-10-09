@extends('layouts.mobile', ['mobileNavActive' => 'home'])

@section('title', 'Usuarios')

@section('content')
    <div class="samuel-home samuel-members-page">
        <div class="samuel-members-page__head">
            <div class="samuel-section-title samuel-section-title--toolbar" style="margin:0;">
                <div class="samuel-section-title__left">
                    <a href="{{ route('admin.home') }}" class="samuel-back-link"><i class="ri-arrow-left-line"></i></a>
                    <span><i class="ri-team-line"></i> Usuarios</span>
                </div>
            </div>
            <p class="samuel-members-page__hint">
                <span id="samuelMembersCount" data-total="{{ $members->count() }}">{{ $members->count() }} {{ $members->count() === 1 ? 'persona' : 'personas' }}</span>
                ·
                <a href="{{ route('admin.members.create') }}" class="samuel-link-action">+ Crear usuario</a>
            </p>
            @if($members->isNotEmpty())
                <div class="samuel-members-search">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="samuelMembersSearch"
                        class="samuel-members-search__input"
                        placeholder="Buscar nombre, cédula o celular"
                        autocomplete="off"
                        enterkeyhint="search"
                        inputmode="search"
                    >
                    <button type="button" class="samuel-members-search__clear" id="samuelMembersSearchClear" hidden aria-label="Limpiar búsqueda">
                        <i class="ri-close-line" aria-hidden="true"></i>
                    </button>
                </div>
            @endif
        </div>

        @if($errors->any())
            <div class="samuel-flash samuel-flash--err samuel-flash--inline" role="alert">
                @foreach($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        @if(session('pin_reveal'))
            @php $pr = session('pin_reveal'); @endphp
            <div class="samuel-pin-reveal samuel-pin-reveal--flash samuel-pin-reveal--inline" role="status">
                <div class="samuel-pin-reveal__label">Clave para entrar</div>
                <div class="samuel-pin-reveal__who">{{ $pr['name'] }} · cédula {{ $pr['cedula'] }}</div>
                <div class="samuel-pin-reveal__code">{{ $pr['pin'] }}</div>
            </div>
        @endif

        @if($members->isEmpty())
            <div class="samuel-members-empty">
                <p>Aún no hay usuarios.</p>
                <a href="{{ route('admin.members.create') }}" class="samuel-link-action">+ Crear el primero</a>
            </div>
        @else
            <p class="samuel-members-search-empty" id="samuelMembersSearchEmpty" hidden>No hay usuarios que coincidan con tu búsqueda.</p>
            <div class="samuel-member-list samuel-member-list--page" id="samuelMembersList" role="list">
                @foreach($members as $member)
                    @php
                        $searchBlob = mb_strtolower(implode(' ', array_filter([
                            $member->name,
                            $member->cedula,
                            $member->phone,
                            $member->relationshipLabel(),
                        ])));
                    @endphp
                    <article class="samuel-member-row" role="listitem" data-member-search="{{ $searchBlob }}">
                        <a href="{{ route('admin.members.edit', $member) }}" class="samuel-member-row__main">
                            @include('partials.member-avatar', ['member' => $member, 'size' => 'md'])
                            <div class="samuel-member-row__body">
                                <div class="samuel-member-row__title">
                                    <span class="samuel-member-row__name">{{ $member->name }}</span>
                                    @if($member->is_admin)
                                        <span class="samuel-admin-badge">Admin</span>
                                    @endif
                                    @if(! $member->is_active)
                                        <span class="samuel-member-row__status samuel-member-row__status--off">Inactivo</span>
                                    @endif
                                </div>
                                <div class="samuel-member-row__meta">
                                    <span>Cédula {{ $member->cedula }}</span>
                                    @if($member->phone)
                                        <span class="samuel-member-row__dot" aria-hidden="true">·</span>
                                        <span>{{ $member->phone }}</span>
                                    @endif
                                    @if($member->relationshipLabel())
                                        <span class="samuel-member-row__dot" aria-hidden="true">·</span>
                                        <span>{{ $member->relationshipLabel() }}</span>
                                    @endif
                                </div>
                                <div class="samuel-member-row__pin">
                                    @if($pin = $member->adminVisiblePin())
                                        <span class="samuel-member-row__pin-label">PIN</span>
                                        <span class="samuel-member-row__pin-code">{{ $pin }}</span>
                                    @else
                                        <span class="samuel-member-row__pin-missing">Sin PIN guardado</span>
                                    @endif
                                </div>
                            </div>
                            <span class="samuel-member-row__chevron" aria-hidden="true"><i class="ri-pencil-line"></i></span>
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection

@push('scripts')
@php $membersSearchJsV = file_exists(public_path('js/samuel-members-search.js')) ? filemtime(public_path('js/samuel-members-search.js')) : 1; @endphp
<script src="{{ asset('js/samuel-members-search.js') }}?v={{ $membersSearchJsV }}" defer></script>
@endpush
