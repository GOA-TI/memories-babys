@extends('layouts.mobile', ['mobileNavActive' => 'albums'])

@section('title', 'Carpetas')

@section('content')
    <div class="samuel-section-title" style="margin-top:0.85rem;">
        <span><i class="ri-folder-2-line"></i> Carpetas bonitas</span>
        @if($familyMember->is_admin)
            <a href="{{ route('admin.albums.index') }}" class="small text-decoration-none">Admin</a>
        @endif
    </div>

    <div class="samuel-album-grid">
        @foreach($albums as $album)
            <a href="{{ route('albums.show', $album) }}" class="samuel-album-tile" style="background: linear-gradient(145deg, {{ $album->color }}, #fff);">
                <span class="samuel-album-tile__emoji">{{ $album->emoji }}</span>
                <span>
                    <span class="samuel-album-tile__title d-block">{{ $album->title }}</span>
                    <span class="small text-muted">{{ $album->posts_count }} publicaciones</span>
                </span>
            </a>
        @endforeach
    </div>
@endsection
