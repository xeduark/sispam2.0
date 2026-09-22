@extends('layouts.app')

@section('titulo', 'Configuración de Facturación - '.config('app.name'))

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-dark">
                <i class="fa-solid fa-sliders text-primary me-2"></i>Configuración de Facturación Electrónica DIAN
            </h3>
            <p class="text-muted small mb-0">Parametrización del proveedor tecnológico, credenciales API, ambiente de pruebas/producción y rangos de resolución DIAN</p>
        </div>
        <div>
            <a href="{{ route('facturacion.facturas') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Facturas
            </a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card card-glass shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-network-wired me-2 text-info"></i>Parámetros del Proveedor Tecnológico
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form id="formConfigProveedor" onsubmit="guardarConfig(event)">
                        <input type="hidden" name="id" value="{{ $config['id'] ?? 0 }}">

                        <div class="row g-3">
                            <!-- 1. Selección de Proveedor -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Proveedor Tecnológico / Conector:</label>
                                <select name="proveedor_codigo" id="selectProveedor" class="form-select" onchange="cambiarProveedor(this.value)" required>
                                    
@foreach ($adapters as $code => $name)

                                        <option value="{{ $code }}" {{ ($config['proveedor_codigo'] ?? '') === $code ? 'selected' : '' }}>
                                            {{ $name }}
                                        </option>
                                    
@endforeach

                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Nombre Descriptivo / Alias:</label>
                                <input type="text" name="nombre_proveedor" id="nombreProveedor" class="form-control" value="{{ $config['nombre_proveedor'] ?? 'Simulador Interno DIAN' }}" required>
                            </div>

                            <!-- 2. Ambiente -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Ambiente de Operación:</label>
                                <select name="ambiente" class="form-select" required>
                                    <option value="PRUEBAS" {{ ($config['ambiente'] ?? '') === 'PRUEBAS' ? 'selected' : '' }}>Habilitación / Pruebas (TestSetId)</option>
                                    <option value="PRODUCCION" {{ ($config['ambiente'] ?? '') === 'PRODUCCION' ? 'selected' : '' }}>Producción Real DIAN</option>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted">Endpoint Base / URL API REST:</label>
                                <input type="url" name="api_url" class="form-control font-mono" placeholder="https://api.tu-proveedor.com/v1/invoices" value="{{ $config['api_url'] ?? '' }}">
                                <small class="text-muted" style="font-size: 0.72rem;">Dejar en blanco para usar la URL por defecto del conector seleccionado.</small>
                            </div>

                            <!-- 3. Credenciales de Autenticación -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">API Key / Usuario:</label>
                                <input type="text" name="api_key" class="form-control font-mono" value="{{ $config['api_key'] ?? '' }}" placeholder="Clave pública o usuario">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">API Token / Bearer Secret:</label>
                                <input type="password" name="api_token" class="form-control font-mono" value="{{ $config['api_token'] ?? '' }}" placeholder="Token secreto de autenticación">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">Software ID (DIAN):</label>
                                <input type="text" name="software_id" class="form-control font-mono" value="{{ $config['software_id'] ?? '' }}" placeholder="Identificador de software emitido por la DIAN">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-muted">PIN de Software (DIAN):</label>
                                <input type="password" name="pin_software" class="form-control font-mono" value="{{ $config['pin_software'] ?? '' }}" placeholder="PIN de 5 dígitos">
                            </div>

                            <hr class="my-3 text-muted">

                            <!-- 4. Resolución DIAN y Rangos -->
                            <h6 class="fw-bold text-dark mb-0">
                                <i class="fa-solid fa-stamp me-2 text-warning"></i>Resolución y Numeración Autorizada por la DIAN
                            </h6>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">No. de Resolución DIAN:</label>
                                <input type="text" name="resolucion_numero" class="form-control" value="{{ $config['resolucion_numero'] ?? '18760000001' }}" required>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label fw-bold small text-muted">Prefijo:</label>
                                <input type="text" name="prefijo" class="form-control text-uppercase font-mono fw-bold" value="{{ $config['prefijo'] ?? 'SETP' }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Rango Desde:</label>
                                <input type="number" name="rango_desde" class="form-control font-mono" value="{{ intval($config['rango_desde'] ?? 1) }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Rango Hasta:</label>
                                <input type="number" name="rango_hasta" class="form-control font-mono" value="{{ intval($config['rango_hasta'] ?? 500000) }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Consecutivo Actual:</label>
                                <input type="number" name="consecutivo_actual" class="form-control font-mono fw-bold text-primary" value="{{ intval($config['consecutivo_actual'] ?? 1) }}" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Vigencia Desde:</label>
                                <input type="date" name="fecha_vigencia_desde" class="form-control" value="{{ $config['fecha_vigencia_desde'] ?? '2026-01-01' }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Vigencia Hasta:</label>
                                <input type="date" name="fecha_vigencia_hasta" class="form-control" value="{{ $config['fecha_vigencia_hasta'] ?? '2027-12-31' }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-bold small text-muted">Clave Técnica DIAN:</label>
                                <input type="password" name="clave_tecnica" class="form-control font-mono" value="{{ $config['clave_tecnica'] ?? 'fc8eac422eba16e122fc8eac422eba16e12' }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm" id="btnGuardarConfig">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cambiarProveedor(val) {
    const aliasInput = document.getElementById('nombreProveedor');
    if (val === 'SIMULADOR') {
        aliasInput.value = 'Simulador Interno DIAN (FEV-RIPS Pruebas)';
    } else if (val === 'FACTURATECH') {
        aliasInput.value = 'FacturaTech Colombia API';
    } else if (val === 'SIIGO') {
        aliasInput.value = 'Siigo API Cloud';
    } else if (val === 'ALEGRA') {
        aliasInput.value = 'Alegra Facturación DIAN';
    } else if (val === 'THE_FACTORY') {
        aliasInput.value = 'The Factory HKA Web Service';
    } else if (val === 'CUSTOM_REST') {
        aliasInput.value = 'Proveedor Tecnológico Externo (API REST)';
    }
}

function guardarConfig(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarConfig');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Guardando...';

    const form = document.getElementById('formConfigProveedor');
    const formData = new FormData(form);

    fetch('api/facturacion.php?action=guardar_config_proveedor', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Configuración';
        if (data.success) {
            window.modalAlert(data.message, () => { location.reload(); });
        } else {
            window.modalAlert(`Error al guardar: ${data.message}`);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-floppy-disk me-1"></i> Guardar Configuración';
        window.modalAlert(`Error de comunicación: ${err.message}`);
    });
}
</script>
@endsection
