@extends('layouts.app')

@section('titulo', 'Admisión / Ingreso - '.config('app.name'))

@section('content')

<!-- Carga de librerías para Escáner Profesional: OpenCV.js, jsPDF y scanner_doc.js -->
<script async src="https://cdn.jsdelivr.net/npm/@techstark/opencv-js@4.9.0-release.2/opencv.js" onload="window.dispatchEvent(new Event('opencv-ready'))"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="{{ asset('assets/js/scanner_doc.js') }}"></script>

<style>
    /* Estilos personalizados para optimizar la UX del formulario en pasos */
    .step-indicator {
        font-size: 0.75rem;
        transition: all 0.3s ease;
        padding-bottom: 3px;
        border-bottom: 2px solid transparent;
    }
    .step-indicator.active {
        border-bottom: 2px solid #0d6efd;
        color: #0d6efd !important;
        transform: scale(1.05);
    }
    .wizard-step {
        animation: fadeIn 0.4s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="row justify-content-center">
    <div class="col-lg-11">
        @if ($ticketGenerado)
        <div class="card card-glass border-success border-2 mb-4 text-center p-4 shadow-lg">
            <div class="text-success display-4 mb-2"><i class="fa-solid fa-circle-check"></i></div>
            <h3 class="fw-bold text-dark mb-1">¡Registro de Ingreso Exitoso!</h3>
            <p class="text-muted">Se ha generado el tiquete para el paciente en el sistema.</p>
            
            <div class="my-3">
                <span class="fs-1 fw-bold text-primary px-4 py-2 bg-light border border-primary rounded shadow-sm">
                    {{ $ticketGenerado }}
                </span>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-3">
                <a href="{{ route('ingreso.ticket', $ingresoIdCreado) }}" target="_blank" class="btn btn-success btn-lg fw-bold shadow">
                    <i class="fa-solid fa-print me-2"></i> Imprimir Tiquete Térmico
                </a>
                <a href="{{ route('ingreso.index') }}" class="btn btn-outline-primary btn-lg fw-bold shadow-sm">
                    <i class="fa-solid fa-plus me-2"></i> Registrar Otro Ingreso
                </a>
            </div>
        </div>
        @else

        <div class="card card-glass p-4 shadow-sm border-0 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="fw-bold text-primary mb-0">
                    <i class="fa-solid fa-address-card me-2"></i> Módulo de Ingreso RIPS / SGSSS & Priorización
                </h4>
                <span class="badge bg-primary px-3 py-2 fs-6"><i class="fa-solid fa-hospital-user me-1"></i> Formulario Oficial de Salud</span>
            </div>
            <p class="text-muted small">Digita el documento para autocompletar la historia del paciente o diligencia los campos paramétricos oficiales RIPS/SGSSS.</p>

            @if ($error)
                <div class="alert alert-danger alert-dismissible fade show small fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $error }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif

            <!-- WIZARD PROGRESS BAR (UX IMPROVEMENT) -->
            <div class="mb-4 bg-light p-3 rounded border shadow-sm">
                <div class="progress" style="height: 10px;">
                    <div id="wizard-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 16.66%;"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 fw-bold text-muted text-uppercase flex-wrap gap-2">
                    <span class="step-indicator active" id="ind-step-1">1. Prioridad</span>
                    <span class="step-indicator" id="ind-step-2">2. Identificación</span>
                    <span class="step-indicator" id="ind-step-3">3. Ubicación</span>
                    <span class="step-indicator" id="ind-step-4">4. Afiliación</span>
                    <span class="step-indicator" id="ind-step-5">5. Caracterización</span>
                    <span class="step-indicator" id="ind-step-6">6. Soportes</span>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data" id="formIngreso">
                @csrf
                
                <!-- PASO 1: ATENCIÓN PREFERENCIAL / PRIORIDAD -->
                <div class="wizard-step" id="step-1">
                    <div class="card border-warning border-2 bg-warning bg-opacity-10 mb-4 shadow-sm">
                        <div class="card-header bg-warning text-dark fw-bold py-2 d-flex justify-content-between align-items-center">
                            <span><i class="fa-solid fa-star me-2"></i> 1. PRIORIZACIÓN Y ATENCIÓN PREFERENCIAL DEL INGRESO</span>
                            <span class="badge bg-dark text-white">Prioridad de Atenciones</span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark">Tipo de Prioridad de Atención <span class="text-danger">*</span></label>
                                    <select name="prioridad" id="prioridad" class="form-select form-select-lg fw-bold text-primary" required>
                                        @foreach (config('sispam.opciones_prioridad') as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Observaciones / Novedad de Prioridad</label>
                                    <input type="text" name="prioridad_observacion" id="prioridad_observacion" class="form-control" placeholder="Ej: Adulto mayor en silla de ruedas, embarazada en 3er trimestre...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: DATOS DE IDENTIFICACIÓN -->
                <div class="wizard-step d-none" id="step-2">
                    <div class="card border-0 bg-light p-3 mb-4 shadow-sm">
                        <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-id-card me-2"></i> 2. Datos Principales de Identificación</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Tipo de Documento <span class="text-danger">*</span></label>
                                <select name="tipo_documento" id="tipo_documento" class="form-select" required>
                                    @foreach (config('sispam.tipos_documento') as $code => $label)
                                        <option value="{{ $code }}">{{ $code }} - {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Número de Identificación <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" name="numero_documento" id="numero_documento" class="form-control fw-bold" placeholder="Ej: 88197902" required autocomplete="off">
                                    <button class="btn btn-primary" type="button" id="btnBuscarPaciente">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> Buscar
                                    </button>
                                </div>
                                <span id="search-status" class="small text-muted"></span>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha y Hora Ingreso</label>
                                <input type="text" class="form-control bg-white" value="{{ date('d/m/Y h:i A') }}" readonly>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label>
                                <select name="estado" id="estado" class="form-select fw-semibold" required>
                                    <option value="Activo" selected>Activo</option>
                                    <option value="Inactivo">Inactivo</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Primer Apellido <span class="text-danger">*</span></label>
                                <input type="text" name="primer_apellido" id="primer_apellido" class="form-control" required placeholder="Ej: Gómez">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Segundo Apellido</label>
                                <input type="text" name="segundo_apellido" id="segundo_apellido" class="form-control" placeholder="Ej: Rodríguez">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Primer Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="primer_nombre" id="primer_nombre" class="form-control" required placeholder="Ej: Juan">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Segundo Nombre</label>
                                <input type="text" name="segundo_nombre" id="segundo_nombre" class="form-control" placeholder="Ej: Pablo">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Fecha Nacimiento <span class="text-danger">*</span></label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Sexo <span class="text-danger">*</span></label>
                                <select name="sexo" id="sexo" class="form-select" required>
                                    <option value="Masculino" selected>Masculino</option>
                                    <option value="Femenino">Femenino</option>
                                    <option value="Indeterminado o Intersexual">Indeterminado o Intersexual</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Estado Civil <span class="text-danger">*</span></label>
                                <select name="estado_civil" id="estado_civil" class="form-select" required>
                                    @foreach (config('sispam.estados_civiles') as $ec)
                                        <option value="{{ $ec }}">{{ $ec }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Ciudad Expedición Doc. <span class="text-danger">*</span></label>
                                <input type="text" name="ciudad_expedicion" id="ciudad_expedicion" class="form-control" value="MEDELLIN-ANT-05001" required placeholder="Ej: MEDELLIN-ANT-05001">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">País de Nacimiento</label>
                                <input type="text" name="pais_nacimiento" id="pais_nacimiento" class="form-control" value="COLOMBIA">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Nacionalidad</label>
                                <input type="text" name="nacionalidad" id="nacionalidad" class="form-control" value="COLOMBIANA">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Ciudad de Nacimiento</label>
                                <input type="text" name="ciudad_nacimiento" id="ciudad_nacimiento" class="form-control" value="MEDELLIN-ANT-05001">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Identidad de Género</label>
                                <input type="text" name="identidad_genero" id="identidad_genero" class="form-control" placeholder="Ej: Cisgénero, Transgénero...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: UBICACIÓN Y RESIDENCIA -->
                <div class="wizard-step d-none" id="step-3">
                    <div class="card border-0 bg-light p-3 mb-4 shadow-sm">
                        <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-location-dot me-2"></i> 3. Ubicación, Residencia & Datos de Contacto</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Dirección de Residencia <span class="text-danger">*</span></label>
                                <input type="text" name="direccion_residencia" id="direccion_residencia" class="form-control" required placeholder="Ej: Calle 50 # 45-20, Apto 301">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Indicativo 1</label>
                                <select name="indicativo_1" id="indicativo_1" class="form-select">
                                    <option value="+57" selected>+57 (Colombia)</option>
                                    <option value="+1">+1 (EE.UU.)</option>
                                    <option value="+34">+34 (España)</option>
                                    <option value="+58">+58 (Venezuela)</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Número Celular <span class="text-danger">*</span></label>
                                <input type="text" name="numero_celular" id="numero_celular" class="form-control" required placeholder="Ej: 3109876543">
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Indicativo 2</label>
                                <select name="indicativo_2" id="indicativo_2" class="form-select">
                                    <option value="+57" selected>+57 (Colombia)</option>
                                    <option value="+1">+1 (EE.UU.)</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Otro Teléfono / Fijo</label>
                                <input type="text" name="otro_telefono" id="otro_telefono" class="form-control" placeholder="Ej: 6044440000">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Correo Electrónico (Email) <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control" required placeholder="ejemplo@correo.com">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ciudad de Residencia <span class="text-danger">*</span></label>
                                <input type="text" name="ciudad_residencia" id="ciudad_residencia" class="form-control" value="MEDELLIN-ANT-05001" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Zona <span class="text-danger">*</span></label>
                                <select name="zona" id="zona" class="form-select" required>
                                    <option value="Urbana" selected>Urbana</option>
                                    <option value="Rural">Rural</option>
                                </select>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Barrio de Residencia <span class="text-danger">*</span></label>
                                <select name="barrio" id="barrio" class="form-select" required>
                                    @foreach (config('sispam.barrios_medellin') as $b)
                                        <option value="{{ $b }}">{{ $b }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Dirección Laboral</label>
                                <input type="text" name="direccion_laboral" id="direccion_laboral" class="form-control" placeholder="Ej: Transversal 39 A # 70-12">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Teléfono Laboral</label>
                                <input type="text" name="telefono_laboral" id="telefono_laboral" class="form-control" placeholder="Ej: 6043120000">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 4: AFILIACIÓN AL SGSSS & SALUD -->
                <div class="wizard-step d-none" id="step-4">
                    <div class="card border-0 bg-light p-3 mb-4 shadow-sm">
                        <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-file-medical me-2"></i> 4. Afiliación al Sistema de Salud & EPS (RIPS)</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Aseguradora (EPS) <span class="text-danger">*</span></label>
                                <select name="eps_nombre" id="eps_nombre" class="form-select" required>
                                    <option value="">-- Seleccionar EPS --</option>
                                    @foreach (config('sispam.eps_colombia') as $eps)
                                        <option value="{{ $eps }}">{{ $eps }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Plan de Salud</label>
                                <input type="text" name="plan_salud" id="plan_salud" class="form-control" value="Plan Básico" placeholder="Ej: Plan Básico, Plan Complementario, POS">
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="actualiza_citas_plan" value="1" id="actualiza_citas_plan">
                                    <label class="form-check-label fw-semibold" for="actualiza_citas_plan">Actualiza Citas por Plan</label>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Tipo de Afiliado <span class="text-danger">*</span></label>
                                <select name="tipo_afiliado" id="tipo_afiliado" class="form-select" required>
                                    @foreach (config('sispam.tipos_afiliado') as $ta)
                                        <option value="{{ $ta }}">{{ $ta }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nivel Socioeconómico <span class="text-danger">*</span></label>
                                <select name="nivel_socioeconomico" id="nivel_socioeconomico" class="form-select" required>
                                    @foreach (config('sispam.niveles_socioeconomicos') as $ns)
                                        <option value="{{ $ns }}">{{ $ns }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Estrato Socioeconómico</label>
                                <select name="estrato_socioeconomico" id="estrato_socioeconomico" class="form-select">
                                    <option value="1">Estrato 1</option>
                                    <option value="2">Estrato 2</option>
                                    <option value="3" selected>Estrato 3</option>
                                    <option value="4">Estrato 4</option>
                                    <option value="5">Estrato 5</option>
                                    <option value="6">Estrato 6</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Ocupación</label>
                                <select name="ocupacion" id="ocupacion" class="form-select">
                                    @foreach (config('sispam.ocupaciones') as $oc)
                                        <option value="{{ $oc }}">{{ $oc }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Grupo Sanguíneo / RH</label>
                                <select name="grupo_sanguineo" id="grupo_sanguineo" class="form-select">
                                    <option value="O+" selected>O+</option>
                                    <option value="O-">O-</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Sede de Atención <span class="text-danger">*</span></label>
                                <select name="sede_atencion" id="sede_atencion" class="form-select" required>
                                    @foreach (config('sispam.sedes_atencion') as $sa)
                                        <option value="{{ $sa }}">{{ $sa }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">IPS Primaria (Predeterminada) <span class="text-danger">*</span></label>
                                <input type="text" name="ips_primaria" id="ips_primaria" class="form-control fw-semibold" value="900294794 - COMITE DE ESTUDIOS MEDICOS SAS" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-primary"><i class="fa-solid fa-hospital-user me-1"></i> IPS que Remite la Orden Médica <span class="text-danger">*</span></label>
                                <select name="ips_remite" id="ips_remite" class="form-select fw-semibold border-primary" required>
                                    <option value="" selected>-- Seleccionar IPS Remitente de Antioquia --</option>
                                    @foreach (config('sispam.ips_antioquia') as $ips)
                                        <option value="{{ $ips }}">{{ $ips }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Municipio de Afiliación</label>
                                <select name="municipio_afiliacion" id="municipio_afiliacion" class="form-select">
                                    @foreach (config('sispam.municipios_antioquia') as $ma)
                                        <option value="{{ $ma }}">{{ $ma }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha SGSSS</label>
                                <input type="date" name="fecha_sgsss" id="fecha_sgsss" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Fecha de Afiliación</label>
                                <input type="date" name="fecha_afiliacion" id="fecha_afiliacion" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Empleador / Empresa</label>
                                <input type="text" name="empleador" id="empleador" class="form-control" placeholder="Ej: Independiente / Nombre Empresa">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 5: CARACTERIZACIÓN POBLACIONAL, ÉTNICA & EMERGENCIA -->
                <div class="wizard-step d-none" id="step-5">
                    <div class="card border-0 bg-light p-3 mb-4 shadow-sm">
                        <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-people-roof me-2"></i> 5. Caracterización Poblacional, Étnica & Contacto de Emergencia</h5>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Grupo Poblacional <span class="text-danger">*</span></label>
                                <select name="grupo_poblacional" id="grupo_poblacional" class="form-select" required>
                                    @foreach (config('sispam.grupos_poblacionales') as $gp)
                                        <option value="{{ $gp }}">{{ $gp }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Grupo Étnico <span class="text-danger">*</span></label>
                                <select name="grupo_etnico" id="grupo_etnico" class="form-select" required>
                                    @foreach (config('sispam.grupos_etnicos') as $ge)
                                        <option value="{{ $ge }}">{{ $ge }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Comunidad Étnica</label>
                                <input type="text" name="comunidad_etnica" id="comunidad_etnica" class="form-control" placeholder="Ej: Cabildo Emberá...">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo de Discapacidad <span class="text-danger">*</span></label>
                                <select name="tipo_discapacidad" id="tipo_discapacidad" class="form-select" required>
                                    @foreach (config('sispam.tipos_discapacidad') as $td)
                                        <option value="{{ $td }}">{{ $td }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tipo de Escolaridad <span class="text-danger">*</span></label>
                                <select name="tipo_escolaridad" id="tipo_escolaridad" class="form-select" required>
                                    @foreach (config('sispam.tipos_escolaridad') as $te)
                                        <option value="{{ $te }}">{{ $te }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Contacto de Emergencia -->
                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Nombre Contacto de Emergencia</label>
                                <input type="text" name="contacto_emergencia_nombre" id="contacto_emergencia_nombre" class="form-control" placeholder="Ej: María Rodríguez">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Teléfono Contacto Emergencia</label>
                                <input type="text" name="contacto_emergencia_telefono" id="contacto_emergencia_telefono" class="form-control" placeholder="Ej: 3001234567">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Parentesco</label>
                                <input type="text" name="contacto_emergencia_parentesco" id="contacto_emergencia_parentesco" class="form-control" placeholder="Ej: Madre / Cónyuge">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASO 6: RECLAMACIÓN Y DOCUMENTOS ADJUNTOS -->
                <div class="wizard-step d-none" id="step-6">
                    <div class="card border-0 bg-light p-3 mb-4 shadow-sm">
                        <h5 class="fw-bold text-primary mb-3"><i class="fa-solid fa-user-check me-2"></i> 6. Persona que Reclama los Medicamentos</h5>
                        <div class="row align-items-center g-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark fs-6">
                                    ¿Los medicamentos son reclamados por el mismo paciente o por un tercero/acudiente? <span class="text-danger">*</span>
                                </label>
                                <select name="persona_reclama" id="select_persona_reclama" class="form-select form-select-lg fw-bold border-primary shadow-sm" required onchange="evaluarVisualizacionSoportes()">
                                    <option value="" selected>-- Seleccione Modalidad de Reclamación --</option>
                                    <option value="PACIENTE_DIRECTO">👤 El Mismo Paciente (Reclama Personalmente en Ventanilla)</option>
                                    <option value="TERCERO_ACUDIENTE">👥 Un Tercero / Acudiente Autorizado (Reclama a Nombre de Otra Persona)</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <div id="info_requisitos_reclamacion" class="alert alert-info p-3 mb-0 small fw-semibold d-none shadow-sm">
                                    <!-- Mensaje explicativo dinámico -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DOCUMENTOS ADJUNTOS POR SEPARADO -->
                    <div id="seccion_soportes_container" class="card border-0 bg-light p-3 mb-4 shadow-sm d-none">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold text-primary mb-0"><i class="fa-solid fa-file-arrow-up me-2"></i> 7. Documentos Adjuntos del Ingreso (Por Separado)</h5>
                                <div id="lbl_requisito_soportes_sub" class="small text-danger fw-semibold mt-1">
                                    <!-- Requisitos cargados dinámicamente -->
                                </div>
                            </div>
                        </div>

                        <div id="contenedor-documentos">
                            <!-- Las filas de soportes obligatorios se generan dinámicamente según la opción seleccionada -->
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 fw-semibold" onclick="agregarFilaDoc()">
                            <i class="fa-solid fa-plus me-1"></i> Agregar Otro Documento Adicional
                        </button>
                    </div>
                </div>

                <!-- CONTROLES DE NAVEGACIÓN UX -->
                <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-4">
                    <button type="button" class="btn btn-secondary fw-bold shadow-sm" id="btn-wizard-prev" onclick="navegarPaso(-1)" disabled>
                        <i class="fa-solid fa-arrow-left me-2"></i> Anterior
                    </button>
                    <small class="text-muted fw-semibold d-none d-md-block"><i class="fa-solid fa-keyboard me-1"></i> Tip: Puedes usar <kbd>Enter</kbd> para avanzar rápido</small>
                    <div>
                        <button type="button" class="btn btn-primary fw-bold px-4 shadow" id="btn-wizard-next" onclick="navegarPaso(1)">
                            Siguiente <i class="fa-solid fa-arrow-right ms-2"></i>
                        </button>
                        <button type="submit" class="btn btn-success btn-lg fw-bold px-5 shadow d-none" id="btn-wizard-submit">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Registrar Ingreso & Generar Tiquete
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

<!-- MODAL DE NOTIFICACIONES BOOTSTRAP PERSONALIZADO -->
<div class="modal fade" id="modalNotificacionEscaner" tabindex="-1" aria-hidden="true" style="z-index: 1090;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1095;">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header text-white" id="modalNotifHeader">
                <h5 class="modal-title fw-bold" id="modalNotifTitle">
                    <i class="fa-solid fa-bell me-2"></i> Notificación
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarNotificacionModal()" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="modalNotifIcon" class="display-3 mb-3"></div>
                <div id="modalNotifMessage" class="fs-5 text-dark fw-semibold"></div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="button" class="btn btn-primary fw-bold px-4 shadow-sm" onclick="cerrarNotificacionModal()" data-bs-dismiss="modal">
                    Entendido / Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ESCÁNER DE DOCUMENTOS PROFESIONAL -->
<div class="modal fade" id="modalEscanerDocPro" tabindex="-1" aria-hidden="true" style="z-index: 1055;">
    <div class="modal-dialog scanner-modal-dialog">
        <div class="modal-content scanner-modal-content">
            <div class="scanner-modal-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-camera-retro text-info fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white">Escáner Profesional de Documentos</h5>
                        <small class="text-muted">Captura 1 o más páginas seguidas para el soporte seleccionado.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarEscanerPro()"></button>
            </div>

            <div class="scanner-modal-body">
                <div id="camera-https-alert" class="alert alert-warning alert-dismissible fade show d-none small mb-2">
                    <i class="fa-solid fa-lock me-1"></i> <strong>Conexión Segura Requerida:</strong> Para usar la cámara en vivo, ingrese por HTTPS.
                </div>

                <div class="bg-primary bg-opacity-25 border border-info rounded p-2 mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-signature text-info fs-5"></i>
                        <span class="text-white fw-bold">Documento a Escanear:</span>
                    </div>
                    <div>
                        <select id="scanner-target-category" class="form-select form-select-sm fw-bold bg-dark text-info border-info" style="min-width: 220px;">
                            <option value="CEDULA">1. Cédula / Doc. Identidad</option>
                            <option value="ORDEN_MEDICA" selected>2. Fórmula / Orden Médica</option>
                            <option value="AUTORIZACION">3. Autorización de Servicios</option>
                            <option value="HISTORIA_CLINICA">4. Historia Clínica / Anexo</option>
                            <option value="OTRO">5. Otro Documento</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 bg-dark p-2 rounded">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-primary fw-bold" id="btn-start-cam" onclick="iniciarCamaraEscaner()">
                            <i class="fa-solid fa-video me-1"></i> Iniciar Cámara
                        </button>
                        <button type="button" class="btn btn-sm btn-warning fw-bold text-dark d-none" id="btn-snap-cam" onclick="capturarFotoEscaner()">
                            <i class="fa-solid fa-camera me-1"></i> Tomar Captura
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning fw-bold" id="btn-reset-cam" onclick="reiniciarCamaraEscaner()">
                            <i class="fa-solid fa-rotate-right me-1"></i> Reiniciar Cámara
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="btn btn-sm btn-outline-info fw-bold mb-0">
                            <i class="fa-solid fa-image me-1"></i> Galería / Archivo
                            <input type="file" id="input-foto-nativa" accept="image/*,application/pdf" class="d-none" onchange="cargarFotoNativaEscaner(event)">
                        </label>
                    </div>
                </div>

                <div id="paso-captura-container">
                    <div class="scanner-canvas-wrapper shadow-lg position-relative">
                        <video id="webcam-video" autoplay playsinline muted class="w-100 h-100"></video>
                        <canvas id="scanner-canvas-overlay" class="d-none position-absolute top-0 start-0"></canvas>
                    </div>
                </div>

                <div id="paso-procesado-container" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <canvas id="canvas-processed" class="img-fluid rounded shadow"></canvas>
                        </div>
                        <div class="col-md-3">
                            <label class="filter-btn active" id="f-magic" onclick="aplicarFiltroEscaner('magic')">Magic Color</label>
                            <label class="filter-btn" id="f-grayscale" onclick="aplicarFiltroEscaner('grayscale')">Grises</label>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="pages-thumbnail-strip" id="strip-miniaturas"></div>
                </div>
            </div>

            <div class="scanner-modal-footer">
                <button type="button" class="btn btn-outline-light btn-sm" onclick="cerrarEscanerPro()">Cancelar</button>
                <button type="button" class="btn btn-success fw-bold shadow" id="btn-finalizar-pdf" onclick="finalizarYAdjuntarPDF()">Finalizar</button>
            </div>
        </div>
    </div>
</div>

<script>
let scannerPro = null;
let currentStep = 1;
const totalSteps = 6;

document.addEventListener('DOMContentLoaded', () => {
    const btnBuscar = document.getElementById('btnBuscarPaciente');
    const inputDoc = document.getElementById('numero_documento');
    const selectTipoDoc = document.getElementById('tipo_documento');
    const statusSpan = document.getElementById('search-status');

    function buscarPacienteAJAX() {
        const numDoc = inputDoc.value.trim();
        const tipoDoc = selectTipoDoc.value;
        if (!numDoc) return;

        statusSpan.className = 'small text-primary';
        statusSpan.innerText = 'Buscando datos del paciente...';

        fetch(`{{ route('api.pacientes.buscar') }}?tipo_documento=${tipoDoc}&numero_documento=${numDoc}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'found' && res.data) {
                    statusSpan.className = 'small text-success fw-bold';
                    statusSpan.innerText = '✓ Paciente encontrado. Datos cargados automáticamente.';
                    autocompletarFormulario(res.data);
                    
                    // UX EXTRAPOLACIÓN DE PASOS: Salta automáticamente al paso 4 (Afiliación) al encontrar historial
                    setTimeout(() => {
                        currentStep = 3;
                        navegarPaso(1);
                        mostrarNotificacionModal('Historial Encontrado', 'Se cargaron los datos personales y demográficos. Saltando directo a aseguramiento EPS.', 'info');
                    }, 800);
                } else {
                    statusSpan.className = 'small text-muted';
                    statusSpan.innerText = 'Nuevo paciente. Complete los datos obligatorios (*).';
                }
            })
            .catch(err => {
                console.error("Error al buscar paciente:", err);
            });
    }

    if (btnBuscar) btnBuscar.addEventListener('click', buscarPacienteAJAX);
    
    // Soporte para avanzar los pasos utilizando la tecla Enter de forma rápida
    document.getElementById('formIngreso').addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.id !== 'btnBuscarPaciente') {
            e.preventDefault();
            if (currentStep < totalSteps) {
                navegarPaso(1);
            }
        }
    });
});

function navegarPaso(direccion) {
    if (direccion === 1 && !validarCamposPasoActual()) {
        return; // No avanza si faltan campos obligatorios HTML5
    }

    document.getElementById(`step-${currentStep}`).classList.add('d-none');
    currentStep += direccion;
    document.getElementById(`step-${currentStep}`).classList.remove('d-none');
    
    actualizarProgresoInterfaz();
}

function validarCamposPasoActual() {
    const pasoContenedor = document.getElementById(`step-${currentStep}`);
    const inputsRequeridos = pasoContenedor.querySelectorAll('[required]');
    let pasoValido = true;

    inputsRequeridos.forEach(input => {
        if (!input.checkValidity()) {
            input.reportValidity();
            pasoValido = false;
        }
    });
    return pasoValido;
}

function actualizarProgresoInterfaz() {
    const porcentaje = (currentStep / totalSteps) * 100;
    document.getElementById('wizard-progress').style.width = `${porcentaje}%`;

    for (let i = 1; i <= totalSteps; i++) {
        const ind = document.getElementById(`ind-step-${i}`);
        if (ind) {
            if (i === currentStep) ind.classList.add('active');
            else ind.classList.remove('active');
        }
    }

    document.getElementById('btn-wizard-prev').disabled = (currentStep === 1);
    if (currentStep === totalSteps) {
        document.getElementById('btn-wizard-next').classList.add('d-none');
        document.getElementById('btn-wizard-submit').classList.remove('d-none');
    } else {
        document.getElementById('btn-wizard-next').classList.remove('d-none');
        document.getElementById('btn-wizard-submit').classList.add('d-none');
    }
}

function evaluarVisualizacionSoportes() {
    const select = document.getElementById('select_persona_reclama');
    const container = document.getElementById('seccion_soportes_container');
    const infoBox = document.getElementById('info_requisitos_reclamacion');
    const lblSub = document.getElementById('lbl_requisito_soportes_sub');
    const contenedorDocs = document.getElementById('contenedor-documentos');

    if (!select || !container || !contenedorDocs) return;
    const val = select.value;

    if (!val) {
        container.classList.add('d-none');
        infoBox.classList.add('d-none');
        return;
    }

    container.classList.remove('d-none');
    infoBox.classList.remove('d-none');

    if (val === 'PACIENTE_DIRECTO') {
        infoBox.innerHTML = `<strong>Reclamación Directa:</strong> Exige Cédula y Fórmula Médica.`;
        lblSub.innerText = `Requisito Obligatorio (2 Soportes)`;
        generarFilasSoportes(['CEDULA', 'ORDEN_MEDICA']);
    } else {
        infoBox.innerHTML = `<strong>Entrega a Terceros:</strong> Exige Cédula, Fórmula y Autorización.`;
        lblSub.innerText = `Requisito Obligatorio (3 Soportes)`;
        generarFilasSoportes(['CEDULA', 'ORDEN_MEDICA', 'AUTORIZACION']);
    }
}

function generarFilasSoportes(tiposRequeridos) {
    const contenedorDocs = document.getElementById('contenedor-documentos');
    contenedorDocs.innerHTML = '';

    tiposRequeridos.forEach((tipoTag, index) => {
        const divRow = document.createElement('div');
        divRow.className = 'card border-primary border-1 mb-2 item-documento bg-white p-2 shadow-sm';
        divRow.innerHTML = `
            <div class="row align-items-center g-2">
                <div class="col-md-4">
                    <select name="doc_tipo_categoria[]" class="form-select form-select-sm fw-bold border-primary select-doc-cat">
                        <option value="CEDULA" ${tipoTag === 'CEDULA' ? 'selected' : ''}>Cédula / Doc. Identidad *</option>
                        <option value="ORDEN_MEDICA" ${tipoTag === 'ORDEN_MEDICA' ? 'selected' : ''}>Fórmula / Orden Médica *</option>
                        <option value="AUTORIZACION" ${tipoTag === 'AUTORIZACION' ? 'selected' : ''}>Autorización / Doc. Tercero *</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="file" name="doc_archivos[]" class="form-control form-control-sm input-doc-file" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="status-doc-adjunto mt-1"></div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-sm btn-success w-100" onclick="abrirEscanerProModal('${tipoTag}')">Escanear (Pro)</button>
                </div>
            </div>
        `;
        contenedorDocs.appendChild(divRow);
    });
}

function mostrarNotificacionModal(titulo, mensajeHtml, tipo = 'success') {
    const modalEl = document.getElementById('modalNotificacionEscaner');
    document.getElementById('modalNotifTitle').innerText = titulo;
    document.getElementById('modalNotifMessage').innerHTML = mensajeHtml;
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function cerrarNotificacionModal() {
    const modalEl = document.getElementById('modalNotificacionEscaner');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function autocompletarFormulario(data) {
    const mapFields = [
        'primer_apellido', 'segundo_apellido', 'primer_nombre', 'segundo_nombre',
        'fecha_nacimiento', 'ciudad_expedicion', 'estado', 'sexo', 'estado_civil',
        'direccion_residencia', 'numero_celular', 'email', 'ciudad_residencia', 'zona', 'barrio',
        'eps_nombre', 'plan_salud', 'tipo_afiliado', 'nivel_socioeconomico', 'sede_atencion'
    ];
    mapFields.forEach(field => {
        const el = document.getElementById(field);
        if (el && data[field]) el.value = data[field];
    });
}

function agregarFilaDoc() {
    const container = document.getElementById('contenedor-documentos');
    const div = document.createElement('div');
    div.className = 'card border-secondary border-1 mb-2 item-documento bg-white p-2 shadow-sm';
    div.innerHTML = `
        <div class="row align-items-center g-2">
            <div class="col-md-4">
                <select name="doc_tipo_categoria[]" class="form-select form-select-sm select-doc-cat">
                    <option value="AUTORIZACION" selected>Autorización de Servicios</option>
                    <option value="HISTORIA_CLINICA">Historia Clínica / Anexo</option>
                    <option value="OTRO">Otro Documento</option>
                </select>
            </div>
            <div class="col-md-5">
                <input type="file" name="doc_archivos[]" class="form-control form-control-sm input-doc-file" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="col-md-3 text-end">
                <button type="button" class="btn btn-sm btn-link text-danger" onclick="this.closest('.item-documento').remove()">Eliminar</button>
            </div>
        </div>
    `;
    container.appendChild(div);
}
</script>
@endsection
