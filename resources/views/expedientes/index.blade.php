@extends('layouts.app')

@section('titulo', 'Consulta de Expedientes - '.config('app.name'))

@section('content')

<div class="row mb-4">
    <div class="col-md-8">
        <h4 class="fw-bold text-primary mb-1"><i class="fa-solid fa-folder-tree me-2"></i> Módulo de Consulta de Expedientes</h4>
        <p class="text-muted small">Busca el historial de ingresos, tiquetes, órdenes médicas adjuntas y reimprime las actas de entrega firmadas.</p>
    </div>
</div>

<div class="card card-glass p-4 shadow-sm border-0 mb-4">
    <form method="GET" action="{{ route('expedientes.index') }}" class="row g-3 align-items-center">
        <div class="col-md-6">
            <label class="form-label fw-semibold" for="num_doc">Número de Documento del Paciente</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="fa-solid fa-id-card text-muted"></i></span>
                <input type="text" id="num_doc" name="num_doc" class="form-control" placeholder="Ej: 88197902" value="{{ $numDoc }}" required autofocus>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar Histórico
                </button>
            </div>
        </div>
    </form>
</div>

@if ($numDoc !== '')
    @if ($resultados->isNotEmpty())
        @php $paciente = $resultados->first()->paciente; @endphp
        <div class="card card-glass border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="fa-solid fa-user me-2"></i> Paciente: {{ $paciente->nombres }} {{ $paciente->apellidos }}
                    <span class="badge bg-secondary ms-2">{{ $paciente->tipo_documento }} {{ $paciente->numero_documento }}</span>
                    <span class="badge bg-info text-dark ms-1">{{ $paciente->eps_nombre }}</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tiquete</th>
                                <th>Fecha Ingreso</th>
                                <th>Estado</th>
                                <th>Orientador</th>
                                <th>Documentos &amp; Firma</th>
                                <th class="text-end pe-3">Reimpresión &amp; Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($resultados as $ing)
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">{{ $ing->ticket_numero }}</td>
                                    <td>{{ $ing->fecha_ingreso?->format('d/m/Y h:i A') }}</td>
                                    <td>
                                        {!! get_estado_badge($ing->estado_tramite) !!}
                                        {!! get_prioridad_badge($ing->prioridad ?? 'NORMAL') !!}
                                    </td>
                                    <td>{{ $ing->orientador?->nombre_completo }}</td>
                                    <td>
                                        @foreach ($ing->documentos as $doc)
                                            <a href="{{ asset($doc->ruta_archivo) }}" target="_blank" class="btn btn-sm btn-outline-dark me-1 mb-1" title="{{ $doc->tipo_documento }}">
                                                <i class="fa-solid fa-file-pdf text-danger me-1"></i> {{ $doc->tipo_documento }}
                                            </a>
                                        @endforeach

                                        @if ($ing->pdf_transcripcion_url)
                                            <a href="{{ asset($ing->pdf_transcripcion_url) }}" target="_blank" class="btn btn-sm btn-outline-success me-1 mb-1" title="PDF Transcrito">
                                                <i class="fa-solid fa-file-circle-check me-1"></i> Transcripción
                                            </a>
                                        @endif

                                        @if ($ing->firma_paciente_url)
                                            <a href="{{ asset($ing->firma_paciente_url) }}" target="_blank" class="btn btn-sm btn-outline-info me-1 mb-1" title="Firma Digital Paciente">
                                                <i class="fa-solid fa-signature me-1"></i> Ver Firma
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                                            <a href="{{ route('ingreso.ticket', $ing->id) }}" target="_blank" class="btn btn-sm btn-outline-primary fw-bold shadow-sm" title="Reimprimir Tiquete Térmico de Turno con datos del paciente y módulo">
                                                <i class="fa-solid fa-print me-1"></i> Reimprimir Ticket
                                            </a>

                                            @if ($ing->estado_tramite === 'ENTREGADO' || $ing->firma_paciente_url)
                                                <a href="{{ route('entrega.acta', $ing->id) }}" target="_blank" class="btn btn-sm btn-success fw-bold shadow-sm" title="Reimprimir Acta de Entrega Firmada">
                                                    <i class="fa-solid fa-file-signature me-1"></i> Reimprimir Acta Firmada
                                                </a>
                                            @else
                                                <span class="badge bg-light text-muted border align-self-center">Sin Entrega Final</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning text-center p-4">
            <i class="fa-solid fa-triangle-exclamation fs-3 me-2"></i> No se encontraron registros de órdenes o ingresos para el documento <strong>{{ $numDoc }}</strong>.
        </div>
    @endif
@endif

@endsection
