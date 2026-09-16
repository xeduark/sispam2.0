@extends('layouts.app')

@section('titulo', $modulo.' - '.config('app.name'))

@section('content')
    <div class="card card-glass p-4 text-center">
        <div class="py-5">
            <i class="fa-solid fa-screwdriver-wrench fs-1 text-warning mb-3"></i>
            <h4 class="fw-bold">{{ $modulo }}</h4>
            <p class="text-muted mb-0">Módulo en proceso de migración a Laravel. La versión anterior sigue disponible en el respaldo del repositorio.</p>
        </div>
    </div>
@endsection
