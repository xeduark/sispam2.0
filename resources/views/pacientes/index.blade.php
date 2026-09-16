@extends('layouts.app')

@section('titulo', 'Pacientes - '.config('app.name'))
@section('subtitulo', 'Directorio de Pacientes')
@section('subtitulo_desc', 'Consulta y exportación del registro de pacientes')

@section('content')

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-hospital-user me-2"></i> Directorio de Pacientes</h4>
        <p class="text-muted small mb-0">Consulta y exporta el listado de pacientes registrados en el sistema.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('pacientes.exportar', ['q' => $busqueda]) }}" class="btn btn-outline-success fw-bold shadow-sm">
            <i class="fa-solid fa-file-excel me-1"></i> Exportar Excel
        </a>
        <a href="{{ route('pacientes.importar') }}" class="btn btn-outline-primary fw-bold shadow-sm">
            <i class="fa-solid fa-file-csv me-1"></i> Carga Masiva
        </a>
    </div>
</div>

@include('partials.alertas')

<form method="GET" action="{{ route('pacientes.index') }}" class="mb-3">
    <div class="input-group" style="max-width: 420px;">
        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
        <input type="text" name="q" value="{{ $busqueda }}" class="form-control" placeholder="Buscar por documento o nombre...">
        <button class="btn btn-primary" type="submit">Buscar</button>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Documento</th>
                    <th>Nombre</th>
                    <th>EPS</th>
                    <th>Celular</th>
                    <th>Ciudad</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pacientes as $paciente)
                    <tr>
                        <td>{{ $paciente->tipo_documento }} {{ $paciente->numero_documento }}</td>
                        <td>{{ $paciente->nombre_completo }}</td>
                        <td>{{ $paciente->eps_nombre }}</td>
                        <td>{{ $paciente->numero_celular }}</td>
                        <td>{{ $paciente->ciudad_residencia }}</td>
                        <td>
                            <span class="badge {{ $paciente->estado === 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $paciente->estado }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No se encontraron pacientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">
    {{ $pacientes->links() }}
</div>

@endsection
