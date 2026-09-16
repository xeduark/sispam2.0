@extends('layouts.app')

@section('titulo', 'Carga Masiva de Pacientes - '.config('app.name'))

@section('content')

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-file-csv me-2"></i> Carga Masiva de Pacientes (CSV)</h4>
        <p class="text-muted small">Importa o actualiza masivamente los registros de pacientes descargando la plantilla oficial de ejemplo.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <a href="{{ route('pacientes.plantilla') }}" class="btn btn-outline-success fw-bold shadow-sm">
            <i class="fa-solid fa-download me-1"></i> 📄 Descargar Plantilla CSV
        </a>
    </div>
</div>

@include('partials.alertas')

@if (session('resumenImportacion'))
    @php($resumen = session('resumenImportacion'))
    <div class="card card-glass border-primary mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-chart-bar me-2"></i> Resumen de la Importación Masiva</h5>
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border">
                        <div class="fs-4 fw-bold text-success">{{ $resumen['insertados'] }}</div>
                        <small class="text-muted fw-bold">Nuevos Registrados</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border">
                        <div class="fs-4 fw-bold text-info">{{ $resumen['actualizados'] }}</div>
                        <small class="text-muted fw-bold">Actualizados</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border">
                        <div class="fs-4 fw-bold text-warning">{{ $resumen['omitidos'] }}</div>
                        <small class="text-muted fw-bold">Sin Datos / Omitidos</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded border">
                        <div class="fs-4 fw-bold text-dark">{{ $resumen['total'] }}</div>
                        <small class="text-muted fw-bold">Total Filas Procesadas</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row g-4">
    <div class="col-md-6">
        <div class="card card-glass border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-upload me-2"></i> Subir Archivo CSV de Pacientes</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('pacientes.procesar') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark" for="archivo_csv">Seleccione el archivo CSV diligenciado:</label>
                        <input type="file" id="archivo_csv" name="archivo_csv" class="form-control form-control-lg border-primary" accept=".csv" required>
                        <div class="form-text">Asegúrese de usar la codificación UTF-8 o separar por comas.</div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg fw-bold w-100 shadow-sm">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Procesar e Importar Pacientes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-glass border-0 shadow-sm h-100">
            <div class="card-header bg-dark text-white py-3">
                <h5 class="fw-bold mb-0"><i class="fa-solid fa-circle-info me-2"></i> Instrucciones de la Plantilla</h5>
            </div>
            <div class="card-body p-4">
                <ol class="small lh-lg mb-3">
                    <li>Descargue la plantilla haciendo clic en el botón verde superior <strong>"Descargar Plantilla CSV"</strong>.</li>
                    <li>Abra el archivo en Excel o su editor preferido y diligencie las columnas respetando los encabezados.</li>
                    <li>Columnas obligatorias: <code>tipo_documento</code>, <code>numero_documento</code>, <code>primer_nombre</code>.</li>
                    <li>Formatos aceptados de Fecha de Nacimiento: <code>YYYY-MM-DD</code> (ej: <code>1990-05-20</code>).</li>
                    <li>Si el paciente ya existe en el sistema por número de documento, sus datos serán actualizados automáticamente.</li>
                </ol>
                <a href="{{ route('pacientes.plantilla') }}" class="btn btn-sm btn-outline-success fw-bold">
                    <i class="fa-solid fa-download me-1"></i> Descargar plantilla_pacientes.csv
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
