@extends('layouts.mobile')

@section('title', 'Cloudflare R2')

@section('content')
    <div class="samuel-section-title" style="margin-top:0.85rem;">
        <span>Cloudflare R2</span>
        <a href="{{ route('admin.home') }}" class="small text-decoration-none">Volver</a>
    </div>

    <div class="samuel-form-card">
        <p class="small text-muted" style="margin-top:0;">
            Bucket <strong>{{ $bucket ?: '—' }}</strong> · driver <code>{{ $storageDriver }}</code>
        </p>
        <ul class="small mb-0" style="padding-left:1.1rem;">
            @foreach($checks as $var => $ok)
                <li>{{ $ok ? '✓' : '○' }} {{ $var }}</li>
            @endforeach
        </ul>
    </div>

    <div class="samuel-form-card">
        <p class="small text-muted"><strong>Endpoint</strong> (sin <code>/memorias-samuel</code> al final):</p>
        <code style="font-size:0.75rem;word-break:break-all;">https://2fed991bb08d5d18800d93e74a514270.r2.cloudflarestorage.com</code>

        <p class="small text-muted mt-3 mb-1">Pega en el <code>.env</code> del servidor:</p>
        <pre class="small" style="background:#f5f5f5;padding:0.75rem;border-radius:0.5rem;overflow:auto;">SAMUEL_STORAGE_DRIVER=auto
R2_ACCESS_KEY_ID=tu_access_key
R2_SECRET_ACCESS_KEY=tu_secret
R2_BUCKET=memorias-samuel
R2_ENDPOINT=https://2fed991bb08d5d18800d93e74a514270.r2.cloudflarestorage.com
R2_REGION=auto
R2_PUBLIC_URL=https://pub-xxxx.r2.dev
# o tu dominio público del bucket</pre>

        <p class="small text-muted mb-0">
            Las claves las creas en Cloudflare → R2 → <em>Manage R2 API Tokens</em> (Object Read &amp; Write).
            <strong>R2_PUBLIC_URL</strong>: en el bucket → Settings → Public access (dominio r2.dev o custom).
        </p>
    </div>

    <form method="post" action="{{ route('admin.r2.test') }}" class="samuel-form-card">
        @csrf
        <button type="submit" class="samuel-btn" style="width:100%;">Probar conexión R2</button>
    </form>
@endsection
