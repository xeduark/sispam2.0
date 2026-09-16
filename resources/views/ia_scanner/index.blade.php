@extends('layouts.app')

@section('titulo', 'Escáner IA - '.config('app.name'))
@section('subtitulo', 'Escáner IA')
@section('subtitulo_desc', 'Extracción automática de fórmulas médicas')

@section('content')

<div class="mb-4">
    <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-wand-magic-sparkles me-2"></i> Escáner IA de Fórmulas Médicas</h4>
    <p class="text-muted small mb-0">Sube una foto o PDF de la fórmula y la IA extrae los datos del paciente y los medicamentos.</p>
</div>

@if ($modoDemo)
    <div class="alert alert-warning">
        <i class="fa-solid fa-flask me-1"></i> <strong>Modo demostración:</strong> no hay una API Key de Google Gemini configurada (<code>GEMINI_API_KEY</code> en <code>.env</code>), así que se muestra un resultado simulado.
        Sube un archivo cuyo nombre contenga <code>nueva_eps</code> o <code>savia</code> para ver ejemplos distintos.
    </div>
@endif

@include('partials.alertas')

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="POST" action="{{ route('ia_scanner.procesar') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-3 align-items-end">
            @csrf
            <div class="flex-grow-1" style="min-width: 260px;">
                <label class="form-label fw-semibold">Fórmula médica (PDF, JPG, PNG o WEBP)</label>
                <input type="file" name="formula" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                @error('formula')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary fw-bold">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Extraer con IA
            </button>
        </form>
    </div>
</div>

@if ($resultado)
    @if ($resultado['status'] !== 'ok')
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation me-1"></i> {{ $resultado['message'] }}
        </div>
    @else
        @php($p = $resultado['data']['paciente'] ?? [])
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                <span><i class="fa-solid fa-user me-2"></i> Datos del Paciente</span>
                <span class="badge bg-secondary">{{ $resultado['motor'] ?? '' }} · {{ $resultado['tiempo_segundos'] ?? 0 }}s</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Nombre:</strong> {{ $p['nombre_completo'] ?? '-' }}</div>
                    <div class="col-md-2"><strong>Documento:</strong> {{ $p['tipo_documento'] ?? '' }} {{ $p['numero_documento'] ?? '-' }}</div>
                    <div class="col-md-3"><strong>EPS:</strong> {{ $p['eps'] ?? '-' }}</div>
                    <div class="col-md-3"><strong>IPS:</strong> {{ $p['ips'] ?? '-' }}</div>
                    <div class="col-md-3"><strong>Fecha fórmula:</strong> {{ $p['fecha_formula'] ?? '-' }}</div>
                    <div class="col-md-2"><strong>Edad/Sexo:</strong> {{ $p['edad_sexo'] ?? '-' }}</div>
                    <div class="col-md-3"><strong>Episodio:</strong> {{ $p['episodio'] ?? '-' }}</div>
                    <div class="col-md-4"><strong>Diagnóstico:</strong> {{ $p['diagnostico_cie10'] ?? '-' }}</div>
                    <div class="col-md-4"><strong>Médico:</strong> {{ $p['medico'] ?? '-' }}</div>
                    <div class="col-md-2"><strong>RM:</strong> {{ $p['registro_medico'] ?? '-' }}</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light fw-bold"><i class="fa-solid fa-pills me-2"></i> Medicamentos Detectados</div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Descripción</th>
                            <th>Dosis</th>
                            <th>Frecuencia</th>
                            <th>Duración</th>
                            <th>Cant.</th>
                            <th>Vía</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resultado['data']['medicamentos'] ?? [] as $med)
                            <tr>
                                <td>{{ $med['item'] ?? '-' }}</td>
                                <td>{{ $med['descripcion'] ?? '-' }}</td>
                                <td>{{ $med['dosis'] ?? '-' }}</td>
                                <td>{{ $med['frecuencia'] ?? '-' }}</td>
                                <td>{{ $med['duracion'] ?? '-' }}</td>
                                <td>{{ $med['cantidad_dispensar'] ?? '-' }} {{ $med['unidad_medida'] ?? '' }}</td>
                                <td>{{ $med['via'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No se detectaron medicamentos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (! empty($resultado['data']['observaciones_generales']))
            <div class="alert alert-light border">
                <strong>Observaciones:</strong> {{ $resultado['data']['observaciones_generales'] }}
            </div>
        @endif
    @endif
@endif

@endsection
