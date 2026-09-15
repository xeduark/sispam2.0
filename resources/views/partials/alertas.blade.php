@if (session('mensaje'))
    <div class="alert alert-success alert-dismissible fade show small">
        <i class="fa-solid fa-circle-check me-1"></i> {!! session('mensaje') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show small">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show small">
        <i class="fa-solid fa-triangle-exclamation me-1"></i>
        @foreach ($errors->all() as $mensajeError)
            <div>{{ $mensajeError }}</div>
        @endforeach
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
