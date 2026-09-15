@extends('layouts.app')

@section('titulo', 'Admisión / Ingreso - '.config('app.name'))

@section('content')

<!-- Carga de librerías para Escáner Profesional: OpenCV.js, jsPDF y scanner_doc.js -->
<script async src="https://cdn.jsdelivr.net/npm/@techstark/opencv-js@4.9.0-release.2/opencv.js" onload="window.dispatchEvent(new Event('opencv-ready'))"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="{{ asset('assets/js/scanner_doc.js') }}"></script>

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

            <form method="POST" action="" enctype="multipart/form-data" id="formIngreso">
                
                <!-- SECCIÓN 0: ATENCIÓN PREFERENCIAL / PRIORIDAD DEL INGRESO -->
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

                <!-- SECCIÓN 1: DATOS DE IDENTIFICACIÓN -->
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

                        <!-- Nombres y Apellidos Separados -->
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

                        <!-- Demográficos Básicos -->
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

                <!-- SECCIÓN 2: UBICACIÓN Y RESIDENCIA -->
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

                <!-- SECCIÓN 3: AFILIACIÓN AL SGSSS & SALUD -->
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

                <!-- SECCIÓN 4: DATOS DEMOGRÁFICOS, ÉTNICOS & EMERGENCIA -->
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

                <!-- SECCIÓN 6: PERSONA QUE RECLAMA LOS MEDICAMENTOS -->
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

                <!-- SECCIÓN 7: DOCUMENTOS ADJUNTOS POR SEPARADO (OCULTA INICIALMENTE) -->
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

                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 shadow">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Registrar Ingreso & Generar Tiquete
                    </button>
                </div>
            </form>
        </div>
        @endif
    </div>
</div>

<!-- MODAL DE NOTIFICACIONES BOOTSTRAP PERSONALIZADO (REEMPLAZA LOS ALERTS DEL NAVEGADOR) -->
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

<!-- MODAL ESCÁNER DE DOCUMENTOS PROFESIONAL (CAMSCANNER MULTIPÁGINA & REINICIO DE CÁMARA) -->
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
                <!-- Alerta HTTPS para Servidor de Producción -->
                <div id="camera-https-alert" class="alert alert-warning alert-dismissible fade show d-none small mb-2">
                    <i class="fa-solid fa-lock me-1"></i> <strong>Conexión Segura Requerida:</strong> Para usar la cámara en vivo en Hostinger, ingrese por HTTPS.
                    <a href="javascript:void(0)" onclick="window.location.href = window.location.href.replace('http:', 'https:')" class="fw-bold text-dark ms-2">Clic aquí para cambiar a HTTPS</a>
                </div>

                <!-- SELECCIÓN DEL TIPO DE DOCUMENTO DENTRO DEL ESCÁNER -->
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

                <!-- Botonera de Control de Entrada de Imagen & Reinicio de Cámara -->
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 bg-dark p-2 rounded">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-primary fw-bold" id="btn-start-cam" onclick="iniciarCamaraEscaner()">
                            <i class="fa-solid fa-video me-1"></i> Iniciar Cámara
                        </button>
                        <button type="button" class="btn btn-sm btn-warning fw-bold text-dark d-none" id="btn-snap-cam" onclick="capturarFotoEscaner()">
                            <i class="fa-solid fa-camera me-1"></i> Tomar Captura
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning fw-bold" id="btn-reset-cam" onclick="reiniciarCamaraEscaner()" title="Reiniciar cámara o descartar foto actual">
                            <i class="fa-solid fa-rotate-right me-1"></i> Reiniciar Cámara
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-light" id="btn-torch" onclick="toggleLinternaEscaner()">
                            <i class="fa-solid fa-bolt"></i> Linterna
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">o desde el equipo:</span>
                        <label class="btn btn-sm btn-outline-info fw-bold mb-0" title="Seleccionar foto existente o usar cámara nativa del celular/tableta">
                            <i class="fa-solid fa-image me-1"></i> Foto Nativa / Galería / Archivo
                            <input type="file" id="input-foto-nativa" accept="image/*,application/pdf" capture="environment" class="d-none" onchange="cargarFotoNativaEscaner(event)">
                        </label>
                    </div>
                </div>

                <!-- ÁREA 1: VISOR DE CÁMARA Y RECORTE INTERACTIVO DE PUNTOS -->
                <div id="paso-captura-container">
                    <div class="scanner-canvas-wrapper shadow-lg position-relative">
                        <!-- Video WebRTC en vivo -->
                        <video id="webcam-video" autoplay playsinline muted class="w-100 h-100"></video>
                        
                        <!-- Overlay Canvas para ajuste interactivo de esquinas -->
                        <canvas id="scanner-canvas-overlay" class="d-none position-absolute top-0 start-0"></canvas>
                        
                        <!-- Lupa de precisión táctil -->
                        <div id="loupe-container" class="d-none">
                            <canvas id="canvas-loupe"></canvas>
                        </div>

                        <!-- Guía visual de encuadre -->
                        <div class="camera-overlay-guide" id="camera-guide">
                            <div class="camera-guide-box">
                                <small class="text-info fw-bold"><i class="fa-solid fa-expand me-1"></i> Encuadre el documento aquí</small>
                            </div>
                        </div>
                    </div>

                    <!-- Presets Rápidos de Recorte -->
                    <div class="scanner-presets-bar d-none" id="bar-presets-recorte">
                        <button type="button" class="scanner-preset-btn preset-auto" onclick="scannerPro.setCropPreset('auto')">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> Auto-Detectar Bordes
                        </button>
                        <button type="button" class="scanner-preset-btn" onclick="scannerPro.setCropPreset('full')">
                            <i class="fa-solid fa-expand"></i> Hoja Completa
                        </button>
                        <button type="button" class="scanner-preset-btn" onclick="scannerPro.setCropPreset('a4')">
                            <i class="fa-solid fa-file"></i> Formato Carta/A4
                        </button>
                        <button type="button" class="scanner-preset-btn" onclick="scannerPro.setCropPreset('id')">
                            <i class="fa-solid fa-id-card"></i> Tarjeta / Cédula
                        </button>
                        <button type="button" class="scanner-preset-btn preset-rotate" onclick="scannerPro.rotateImage('right')">
                            <i class="fa-solid fa-rotate-right"></i> Rotar
                        </button>
                    </div>
                </div>

                <!-- ÁREA 2: PROCESAMIENTO, FILTROS Y VISTA PREVIA -->
                <div id="paso-procesado-container" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <div class="scanner-canvas-wrapper bg-dark text-center p-2 rounded">
                                <canvas id="canvas-processed" class="img-fluid rounded shadow"></canvas>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white fw-bold small"><i class="fa-solid fa-sliders me-1"></i> Filtros de Realce</label>
                            <div class="filter-btn-group">
                                <button type="button" class="filter-btn active" id="f-magic" onclick="aplicarFiltroEscaner('magic')">
                                    <i class="fa-solid fa-magic me-1"></i> Magic Color (CamScanner)
                                </button>
                                <button type="button" class="filter-btn" id="f-grayscale" onclick="aplicarFiltroEscaner('grayscale')">
                                    <i class="fa-solid fa-circle-half-stroke me-1"></i> Escala de Grises
                                </button>
                                <button type="button" class="filter-btn" id="f-binary" onclick="aplicarFiltroEscaner('binary')">
                                    <i class="fa-solid fa-toilet-paper me-1"></i> Alto Contraste (B/N)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tira de Miniaturas de Páginas (Multipágina) -->
                <div class="mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="text-white fw-bold"><i class="fa-solid fa-copy me-1"></i> Páginas escaneadas en este lote:</small>
                        <small class="text-info fw-bold" id="lbl-total-paginas">0 Páginas</small>
                    </div>
                    <div class="pages-thumbnail-strip" id="strip-miniaturas">
                        <span class="text-muted small p-2">No se han guardado páginas aún. Puedes tomar 1 o más capturas.</span>
                    </div>
                </div>
            </div>

            <div class="scanner-modal-footer">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="cerrarEscanerPro()">Cancelar</button>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-info btn-sm fw-bold d-none" id="btn-procesar-recorte" onclick="procesarRecorteEscaner()">
                        <i class="fa-solid fa-crop me-1"></i> Procesar Recorte
                    </button>

                    <button type="button" class="btn btn-warning btn-sm fw-bold text-dark d-none" id="btn-agregar-otra-pag" onclick="guardarPaginaYOtra()">
                        <i class="fa-solid fa-plus me-1"></i> Guardar y Escanear Otra Página
                    </button>

                    <button type="button" class="btn btn-success fw-bold shadow" id="btn-finalizar-pdf" onclick="finalizarYAdjuntarPDF()">
                        <i class="fa-solid fa-file-pdf me-1"></i> Finalizar & Adjuntar a este Soporte
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
let scannerPro = null;

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
                    statusSpan.innerText = '✓ Paciente registrado encontrado. Datos cargados automáticamente.';
                    autocompletarFormulario(res.data);
                } else {
                    statusSpan.className = 'small text-muted';
                    statusSpan.innerText = 'Nuevo paciente. Complete los datos obligatorios (*).';
                }
            })
            .catch(err => {
                console.error("Error al buscar paciente:", err);
                statusSpan.innerText = '';
            });
    }

    if (btnBuscar) btnBuscar.addEventListener('click', buscarPacienteAJAX);
    if (inputDoc) {
        inputDoc.addEventListener('blur', () => {
            if (inputDoc.value.trim().length >= 5) {
                buscarPacienteAJAX();
            }
        });
    }

    // Validar Requisito de Soportes según modalidad de reclamación (Paciente vs Acudiente/Tercero) antes de enviar
    const formIngreso = document.getElementById('formIngreso');
    if (formIngreso) {
        formIngreso.addEventListener('submit', (e) => {
            const selectPersona = document.getElementById('select_persona_reclama');
            const valPersona = selectPersona ? selectPersona.value : '';

            if (!valPersona) {
                e.preventDefault();
                mostrarNotificacionModal(
                    'Requisito Obligatorio',
                    'Debe seleccionar en la Sección 6 si los medicamentos son reclamados por el mismo paciente o por un tercero/acudiente.',
                    'warning'
                );
                selectPersona.focus();
                return false;
            }

            const rows = document.querySelectorAll('.item-documento');
            let tieneCedula = false;
            let tieneOrden = false;
            let tieneAutorizacion = false;

            rows.forEach(r => {
                const selectCat = r.querySelector('select[name="doc_tipo_categoria[]"]');
                const inputFile = r.querySelector('input[type="file"]');

                if (selectCat && inputFile && inputFile.files && inputFile.files.length > 0) {
                    if (selectCat.value === 'CEDULA') tieneCedula = true;
                    if (selectCat.value === 'ORDEN_MEDICA') tieneOrden = true;
                    if (selectCat.value === 'AUTORIZACION') tieneAutorizacion = true;
                }
            });

            if (valPersona === 'PACIENTE_DIRECTO' && (!tieneCedula || !tieneOrden)) {
                e.preventDefault();
                let faltantes = [];
                if (!tieneCedula) faltantes.push("• 1. Cédula / Documento de Identidad del Paciente");
                if (!tieneOrden) faltantes.push("• 2. Fórmula / Orden Médica");

                mostrarNotificacionModal(
                    'Requisito Obligatorio de Soportes (Paciente Directo)',
                    `Debe adjuntar por separado los 2 soportes requeridos para reclamación directa por el paciente:<br><br><div class="text-start ps-4">${faltantes.join('<br>')}</div><br><small class="text-muted">Puede adjuntarlos seleccionándolos en archivo o escaneándolos directamente.</small>`,
                    'danger'
                );
                return false;
            }

            if (valPersona === 'TERCERO_ACUDIENTE' && (!tieneCedula || !tieneOrden || !tieneAutorizacion)) {
                e.preventDefault();
                let faltantes = [];
                if (!tieneCedula) faltantes.push("• 1. Cédula / Documento de Identidad del Paciente");
                if (!tieneOrden) faltantes.push("• 2. Fórmula / Orden Médica");
                if (!tieneAutorizacion) faltantes.push("• 3. Autorización / Doc. del Tercero o Acudiente");

                mostrarNotificacionModal(
                    'Requisito Obligatorio de Soportes (Entrega a Terceros)',
                    `Debe adjuntar por separado los 3 soportes requeridos para entrega a nombre de otra persona:<br><br><div class="text-start ps-4">${faltantes.join('<br>')}</div><br><small class="text-muted">Puede adjuntarlos seleccionándolos en archivo o escaneándolos directamente.</small>`,
                    'danger'
                );
                return false;
            }
        });
    }
});

// EVALUAR VISUALIZACIÓN DINÁMICA DE SECCIÓN DE SOPORTES Y GENERAR FILAS OBLIGATORIAS
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
        contenedorDocs.innerHTML = '';
        return;
    }

    container.classList.remove('d-none');
    infoBox.classList.remove('d-none');

    if (val === 'PACIENTE_DIRECTO') {
        infoBox.className = 'alert alert-info p-3 mb-0 small fw-semibold shadow-sm border-info';
        infoBox.innerHTML = `<i class="fa-solid fa-circle-info me-1 fs-5 align-middle"></i> <strong>Reclamación Directa por el Paciente:</strong><br>Se exige adjuntar por separado <strong>2 Soportes Obligatorios</strong>: Cédula de Identidad y Fórmula / Orden Médica.`;
        lblSub.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i> Requisito Obligatorio (2 Soportes): Se exige Cédula y Fórmula Médica por separado.`;
        
        generarFilasSoportes(['CEDULA', 'ORDEN_MEDICA']);
    } else if (val === 'TERCERO_ACUDIENTE') {
        infoBox.className = 'alert alert-warning p-3 mb-0 small fw-semibold shadow-sm border-warning';
        infoBox.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-1 fs-5 align-middle"></i> <strong>Entrega a Nombre de Otra Persona (Tercero/Acudiente):</strong><br>Se exige adjuntar por separado <strong>3 Soportes Obligatorios</strong>: Cédula del paciente, Fórmula Médica y Autorización / Doc. del Tercero.`;
        lblSub.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i> Requisito Obligatorio (3 Soportes): Se exige Cédula, Fórmula Médica y Autorización/Doc. del Tercero por separado.`;
        
        generarFilasSoportes(['CEDULA', 'ORDEN_MEDICA', 'AUTORIZACION']);
    }
}

function generarFilasSoportes(tiposRequeridos) {
    const contenedorDocs = document.getElementById('contenedor-documentos');
    if (!contenedorDocs) return;

    contenedorDocs.innerHTML = '';

    tiposRequeridos.forEach((tipoTag, index) => {
        let labelText = '';
        let iconClass = '';
        let defaultOpt = tipoTag;

        if (tipoTag === 'CEDULA') {
            labelText = `${index + 1}. Cédula / Doc. Identidad del Paciente`;
            iconClass = 'fa-id-card';
        } else if (tipoTag === 'ORDEN_MEDICA') {
            labelText = `${index + 1}. Fórmula / Orden Médica`;
            iconClass = 'fa-file-medical';
        } else if (tipoTag === 'AUTORIZACION') {
            labelText = `${index + 1}. Autorización de Acudiente / Tercero & Doc. Identidad`;
            iconClass = 'fa-file-signature';
        }

        const divRow = document.createElement('div');
        divRow.className = 'card border-primary border-1 mb-3 item-documento bg-white p-3 shadow-sm';
        divRow.innerHTML = `
            <div class="row align-items-center g-2">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-primary mb-1">
                        <i class="fa-solid ${iconClass} me-1"></i> ${labelText} <span class="text-danger">*</span>
                    </label>
                    <select name="doc_tipo_categoria[]" class="form-select form-select-sm fw-bold border-primary select-doc-cat">
                        <option value="CEDULA" ${defaultOpt === 'CEDULA' ? 'selected' : ''}>Cédula / Doc. Identidad *</option>
                        <option value="ORDEN_MEDICA" ${defaultOpt === 'ORDEN_MEDICA' ? 'selected' : ''}>Fórmula / Orden Médica *</option>
                        <option value="AUTORIZACION" ${defaultOpt === 'AUTORIZACION' ? 'selected' : ''}>Autorización / Doc. Tercero *</option>
                        <option value="HISTORIA_CLINICA">Historia Clínica / Anexo</option>
                        <option value="OTRO">Otro Documento</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold small mb-1">Adjuntar Archivo desde Dispositivo (PDF/JPG):</label>
                    <input type="file" name="doc_archivos[]" class="form-control form-control-sm input-doc-file" accept=".pdf,.jpg,.jpeg,.png">
                    <div class="status-doc-adjunto mt-1"></div>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-sm btn-success fw-bold w-100 mb-1" onclick="abrirEscanerProModal('${defaultOpt}')">
                        <i class="fa-solid fa-camera me-1"></i> Escanear (Pro)
                    </button>
                </div>
            </div>
        `;
        contenedorDocs.appendChild(divRow);
    });
}

// MOSTRAR NOTIFICACIONES FLOTANTES CON BOOTSTRAP MODAL
function mostrarNotificacionModal(titulo, mensajeHtml, tipo = 'success') {
    const modalEl = document.getElementById('modalNotificacionEscaner');
    const headerEl = document.getElementById('modalNotifHeader');
    const titleEl = document.getElementById('modalNotifTitle');
    const iconEl = document.getElementById('modalNotifIcon');
    const msgEl = document.getElementById('modalNotifMessage');

    if (!modalEl) {
        alert(mensajeHtml.replace(/<[^>]*>?/gm, ''));
        return;
    }

    if (tipo === 'success') {
        headerEl.className = 'modal-header bg-success text-white py-2';
        titleEl.innerHTML = `<i class="fa-solid fa-circle-check me-2"></i> ${titulo || '¡Proceso Exitoso!'}`;
        iconEl.innerHTML = `<i class="fa-solid fa-circle-check text-success"></i>`;
    } else if (tipo === 'warning') {
        headerEl.className = 'modal-header bg-warning text-dark py-2';
        titleEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation me-2"></i> ${titulo || 'Atención'}`;
        iconEl.innerHTML = `<i class="fa-solid fa-triangle-exclamation text-warning"></i>`;
    } else if (tipo === 'danger' || tipo === 'error') {
        headerEl.className = 'modal-header bg-danger text-white py-2';
        titleEl.innerHTML = `<i class="fa-solid fa-circle-xmark me-2"></i> ${titulo || 'Requisito Obligatorio'}`;
        iconEl.innerHTML = `<i class="fa-solid fa-circle-xmark text-danger"></i>`;
    } else {
        headerEl.className = 'modal-header bg-primary text-white py-2';
        titleEl.innerHTML = `<i class="fa-solid fa-info-circle me-2"></i> ${titulo || 'Información'}`;
        iconEl.innerHTML = `<i class="fa-solid fa-circle-info text-primary"></i>`;
    }

    msgEl.innerHTML = mensajeHtml;

    try {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
        modal.show();
    } catch (e) {
        alert(mensajeHtml.replace(/<[^>]*>?/gm, ''));
    }
}

function cerrarNotificacionModal() {
    const modalEl = document.getElementById('modalNotificacionEscaner');
    if (!modalEl) return;

    try {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        } else {
            modalEl.classList.remove('show');
            modalEl.style.display = 'none';
        }
    } catch (e) {
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
    }

    // Asegurar que si el modal del escáner sigue visible, el cuerpo conserve la clase modal-open
    setTimeout(() => {
        const escanerModalEl = document.getElementById('modalEscanerDocPro');
        if (escanerModalEl && (escanerModalEl.classList.contains('show') || escanerModalEl.style.display === 'block')) {
            document.body.classList.add('modal-open');
        }
    }, 150);
}

document.addEventListener('DOMContentLoaded', () => {
    const notifModalEl = document.getElementById('modalNotificacionEscaner');
    if (notifModalEl) {
        notifModalEl.addEventListener('show.bs.modal', function () {
            notifModalEl.style.zIndex = '1090';
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 1) {
                    const lastBackdrop = backdrops[backdrops.length - 1];
                    lastBackdrop.style.zIndex = '1085';
                }
            }, 10);
        });

        notifModalEl.addEventListener('hidden.bs.modal', function () {
            const escanerModalEl = document.getElementById('modalEscanerDocPro');
            if (escanerModalEl && (escanerModalEl.classList.contains('show') || escanerModalEl.style.display === 'block')) {
                document.body.classList.add('modal-open');
            }
        });
    }
});

function autocompletarFormulario(data) {
    const mapFields = [
        'primer_apellido', 'segundo_apellido', 'primer_nombre', 'segundo_nombre',
        'fecha_nacimiento', 'ciudad_expedicion', 'estado', 'sexo', 'estado_civil',
        'pais_nacimiento', 'nacionalidad', 'ciudad_nacimiento', 'identidad_genero',
        'direccion_residencia', 'indicativo_1', 'numero_celular', 'indicativo_2',
        'otro_telefono', 'email', 'ciudad_residencia', 'zona', 'barrio',
        'direccion_laboral', 'telefono_laboral', 'eps_nombre', 'plan_salud',
        'tipo_afiliado', 'nivel_socioeconomico', 'estrato_socioeconomico',
        'ocupacion', 'grupo_sanguineo', 'sede_atencion', 'ips_primaria', 'ips_remite',
        'municipio_afiliacion', 'fecha_sgsss', 'fecha_afiliacion', 'empleador',
        'grupo_poblacional', 'grupo_etnico', 'comunidad_etnica', 'tipo_discapacidad',
        'tipo_escolaridad', 'contacto_emergencia_nombre', 'contacto_emergencia_telefono',
        'contacto_emergencia_parentesco'
    ];

    mapFields.forEach(field => {
        const el = document.getElementById(field);
        if (el && data[field] !== undefined && data[field] !== null) {
            el.value = data[field];
        }
    });

    if (!data.primer_apellido && data.apellidos) {
        const partsA = data.apellidos.trim().split(' ');
        document.getElementById('primer_apellido').value = partsA[0] || '';
        document.getElementById('segundo_apellido').value = partsA.slice(1).join(' ') || '';
    }
    if (!data.primer_nombre && data.nombres) {
        const partsN = data.nombres.trim().split(' ');
        document.getElementById('primer_nombre').value = partsN[0] || '';
        document.getElementById('segundo_nombre').value = partsN.slice(1).join(' ') || '';
    }

    if (document.getElementById('actualiza_citas_plan')) {
        document.getElementById('actualiza_citas_plan').checked = (data.actualiza_citas_plan == 1);
    }
}

function agregarFilaDoc() {
    const container = document.getElementById('contenedor-documentos');
    const div = document.createElement('div');
    div.className = 'card border-secondary border-1 mb-3 item-documento bg-white p-3 shadow-sm';
    div.innerHTML = `
        <div class="row align-items-center g-2">
            <div class="col-md-4">
                <label class="form-label fw-bold text-dark mb-1">Categoría del Soporte:</label>
                <select name="doc_tipo_categoria[]" class="form-select form-select-sm select-doc-cat">
                    <option value="CEDULA">Cédula / Doc. Identidad</option>
                    <option value="ORDEN_MEDICA">Fórmula / Orden Médica</option>
                    <option value="AUTORIZACION" selected>Autorización de Servicios</option>
                    <option value="HISTORIA_CLINICA">Historia Clínica / Anexo</option>
                    <option value="OTRO">Otro Documento</option>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label fw-semibold small mb-1">Adjuntar Archivo (PDF/JPG):</label>
                <input type="file" name="doc_archivos[]" class="form-control form-control-sm input-doc-file" accept=".pdf,.jpg,.jpeg,.png">
                <div class="status-doc-adjunto mt-1"></div>
            </div>
            <div class="col-md-3 text-end">
                <button type="button" class="btn btn-sm btn-outline-success fw-bold w-100 mb-1" onclick="abrirEscanerProModal('AUTORIZACION')">
                    <i class="fa-solid fa-camera me-1"></i> Escanear este Soporte
                </button>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="eliminarFilaDoc(this)"><i class="fa-solid fa-trash me-1"></i> Eliminar</button>
            </div>
        </div>
    `;
    container.appendChild(div);
}

function eliminarFilaDoc(btn) {
    const item = btn.closest('.item-documento');
    if (document.querySelectorAll('.item-documento').length > 1) {
        item.remove();
    } else {
        mostrarNotificacionModal('Atención', 'Debe conservar al menos un soporte de documento en la lista.', 'warning');
    }
}

// LÓGICA DEL MODAL DE ESCÁNER PROFESIONAL
function abrirEscanerProModal(categoriaTarget = 'ORDEN_MEDICA') {
    const modalEl = document.getElementById('modalEscanerDocPro');
    if (!modalEl) {
        mostrarNotificacionModal('Error', 'No se encontró la ventana modal del escáner.', 'danger');
        return;
    }

    // Pre-seleccionar la categoría requerida en el selector interno del modal
    const catSelect = document.getElementById('scanner-target-category');
    if (catSelect && categoriaTarget) {
        catSelect.value = categoriaTarget;
    }

    // Reiniciar escáner anterior para nuevo lote de páginas
    if (scannerPro) {
        scannerPro.resetDoc();
        actualizarTiraMiniaturas();
    }

    // 1. Mostrar Modal usando Bootstrap o Fallback Directo DOM
    try {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');
        }
    } catch (e) {
        console.warn("Bootstrap JS no respondió, usando apertura directa:", e);
        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        document.body.classList.add('modal-open');
    }

    // 2. Iniciar componente de escaneo
    reiniciarCamaraEscaner();
}

function cerrarEscanerPro() {
    if (scannerPro) {
        scannerPro.stopCamera();
    }

    const modalEl = document.getElementById('modalEscanerDocPro');
    if (modalEl) {
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        } catch (e) {}

        modalEl.style.display = 'none';
        modalEl.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

function reiniciarCamaraEscaner() {
    if (scannerPro) {
        scannerPro.rawImage = null;
    }

    // Resetear contenedores de vista del modal
    document.getElementById('paso-procesado-container').classList.add('d-none');
    document.getElementById('paso-captura-container').classList.remove('d-none');
    document.getElementById('scanner-canvas-overlay').classList.add('d-none');
    document.getElementById('webcam-video').classList.remove('d-none');
    document.getElementById('bar-presets-recorte').classList.add('d-none');
    document.getElementById('btn-procesar-recorte').classList.add('d-none');
    document.getElementById('btn-agregar-otra-pag').classList.add('d-none');
    
    const btnStart = document.getElementById('btn-start-cam');
    const btnSnap = document.getElementById('btn-snap-cam');
    if (btnStart) btnStart.classList.add('d-none');
    if (btnSnap) btnSnap.classList.remove('d-none');

    const guide = document.getElementById('camera-guide');
    if (guide) guide.classList.remove('d-none');

    iniciarCamaraEscaner();
}

async function iniciarCamaraEscaner() {
    if (typeof DocumentScannerPro === 'undefined') return;
    if (!scannerPro) scannerPro = new DocumentScannerPro();
    const success = await scannerPro.startCamera();

    const btnStart = document.getElementById('btn-start-cam');
    const btnSnap = document.getElementById('btn-snap-cam');
    const guide = document.getElementById('camera-guide');

    if (success) {
        if (btnStart) btnStart.classList.add('d-none');
        if (btnSnap) btnSnap.classList.remove('d-none');
        if (guide) guide.classList.remove('d-none');
    }
}

function capturarFotoEscaner() {
    if (!scannerPro) return;
    scannerPro.takeSnapshot();
    scannerPro.stopCamera();

    document.getElementById('webcam-video').classList.add('d-none');
    document.getElementById('scanner-canvas-overlay').classList.remove('d-none');
    document.getElementById('bar-presets-recorte').classList.remove('d-none');
    document.getElementById('btn-snap-cam').classList.add('d-none');
    document.getElementById('btn-start-cam').classList.remove('d-none');
    document.getElementById('btn-procesar-recorte').classList.remove('d-none');
    const guide = document.getElementById('camera-guide');
    if (guide) guide.classList.add('d-none');
}

function cargarFotoNativaEscaner(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
            if (!scannerPro) scannerPro = new DocumentScannerPro();
            scannerPro.stopCamera();
            scannerPro.loadCapturedImage(img);

            document.getElementById('webcam-video').classList.add('d-none');
            document.getElementById('scanner-canvas-overlay').classList.remove('d-none');
            document.getElementById('bar-presets-recorte').classList.remove('d-none');
            document.getElementById('btn-procesar-recorte').classList.remove('d-none');
            const guide = document.getElementById('camera-guide');
            if (guide) guide.classList.add('d-none');
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function procesarRecorteEscaner() {
    if (!scannerPro) return;
    scannerPro.processScan('magic');

    document.getElementById('paso-captura-container').classList.add('d-none');
    document.getElementById('paso-procesado-container').classList.remove('d-none');
    document.getElementById('btn-procesar-recorte').classList.add('d-none');
    document.getElementById('btn-agregar-otra-pag').classList.remove('d-none');
}

function aplicarFiltroEscaner(filterName) {
    if (!scannerPro) return;
    scannerPro.processScan(filterName);

    ['f-magic', 'f-grayscale', 'f-binary'].forEach(id => {
        const b = document.getElementById(id);
        if (b) b.classList.remove('active');
    });
    const activeBtn = document.getElementById('f-' + filterName);
    if (activeBtn) activeBtn.classList.add('active');
}

function guardarPaginaYOtra() {
    if (!scannerPro) return;
    const page = scannerPro.saveCurrentPageToDoc();
    scannerPro.rawImage = null; // Prevenir duplicación de página
    actualizarTiraMiniaturas();

    reiniciarCamaraEscaner();
}

function actualizarTiraMiniaturas() {
    if (!scannerPro) return;
    const strip = document.getElementById('strip-miniaturas');
    const pages = scannerPro.scannedPages;
    document.getElementById('lbl-total-paginas').innerText = `${pages.length} Páginas`;

    if (pages.length === 0) {
        strip.innerHTML = '<span class="text-muted small p-2">No se han guardado páginas aún. Puedes tomar 1 o más capturas.</span>';
        return;
    }

    let html = '';
    pages.forEach((p, idx) => {
        html += `
            <div class="page-thumb-item ${idx === scannerPro.currentPageIndex ? 'active' : ''}">
                <img src="${p.dataUrl}">
                <span class="page-thumb-badge">Pág ${idx + 1}</span>
                <button type="button" class="page-thumb-delete" onclick="borrarPaginaEscaner(${idx})"><i class="fa-solid fa-xmark"></i></button>
            </div>
        `;
    });
    strip.innerHTML = html;
}

function borrarPaginaEscaner(index) {
    if (!scannerPro) return;
    scannerPro.deletePage(index);
    actualizarTiraMiniaturas();
}

async function finalizarYAdjuntarPDF() {
    if (!scannerPro) return;

    // Solo guardar rawImage si NO ha sido guardada previamente en la tira de miniaturas
    if (scannerPro.rawImage && scannerPro.scannedPages.length === 0) {
        scannerPro.processScan(scannerPro.currentFilter || 'magic');
        scannerPro.saveCurrentPageToDoc();
        scannerPro.rawImage = null;
        actualizarTiraMiniaturas();
    }

    if (scannerPro.scannedPages.length === 0) {
        mostrarNotificacionModal('Atención', 'Primero debe capturar o seleccionar al menos una página para este soporte.', 'warning');
        return;
    }

    // Obtener la categoría de soporte seleccionada
    const catSelect = document.getElementById('scanner-target-category');
    const targetCat = catSelect ? catSelect.value : 'ORDEN_MEDICA';
    const catText = catSelect ? catSelect.options[catSelect.selectedIndex].text : 'Soporte';

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    for (let i = 0; i < scannerPro.scannedPages.length; i++) {
        const page = scannerPro.scannedPages[i];
        if (i > 0) doc.addPage();
        doc.addImage(page.dataUrl, 'JPEG', 0, 0, 210, 297);
    }

    const pdfBlob = doc.output('blob');
    const fileName = `${targetCat.toLowerCase()}_escaneada_${Date.now()}.pdf`;
    const file = new File([pdfBlob], fileName, { type: 'application/pdf' });

    // Buscar si existe un campo de archivo para esa categoría específica en formIngreso
    const rows = document.querySelectorAll('.item-documento');
    let targetInput = null;
    let targetStatusDiv = null;

    rows.forEach(r => {
        const select = r.querySelector('.select-doc-cat');
        if (select && select.value === targetCat) {
            targetInput = r.querySelector('.input-doc-file');
            targetStatusDiv = r.querySelector('.status-doc-adjunto');
        }
    });

    // Si no existía una fila para esa categoría, crearla
    if (!targetInput) {
        agregarFilaDoc();
        const lastRow = document.querySelector('.item-documento:last-child');
        if (lastRow) {
            const select = lastRow.querySelector('.select-doc-cat');
            if (select) select.value = targetCat;
            targetInput = lastRow.querySelector('.input-doc-file');
            targetStatusDiv = lastRow.querySelector('.status-doc-adjunto');
        }
    }

    // Asignar el archivo PDF generado al input correspondiente
    if (targetInput) {
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        targetInput.files = dataTransfer.files;

        if (targetStatusDiv) {
            targetStatusDiv.innerHTML = `
                <div class="alert alert-success p-1 px-2 mb-0 small fw-bold">
                    <i class="fa-solid fa-circle-check me-1"></i> Documento escaneado exitosamente (${scannerPro.scannedPages.length} pág(s) - ${fileName}).
                </div>
            `;
        }
    }

    const cantPaginas = scannerPro.scannedPages.length;
    cerrarEscanerPro();

    // Notificación en Modal Bootstrap
    mostrarNotificacionModal(
        '¡Escaneo Finalizado con Éxito!',
        `El documento PDF fue asignado exitosamente al soporte:<br><br><strong class="text-primary fs-5">${catText}</strong><br><span class="badge bg-success mt-2">${cantPaginas} página(s) escaneadas</span>`,
        'success'
    );
}

async function toggleLinternaEscaner() {
    if (!scannerPro) return;
    const active = await scannerPro.toggleTorch();
    const btn = document.getElementById('btn-torch');
    if (btn) {
        btn.className = active ? 'btn btn-sm btn-warning fw-bold text-dark' : 'btn btn-sm btn-outline-light';
    }
}
</script>

@endsection
