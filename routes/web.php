<?php

use App\Http\Controllers\AlistamientoController;
use App\Http\Controllers\Api\InventarioApiController;
use App\Http\Controllers\Api\LockApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PacienteApiController;
use App\Http\Controllers\Api\QrystalosApiController;
use App\Http\Controllers\Api\TtsApiController;
use App\Http\Controllers\Api\TurneroApiController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\EscanerIaController;
use App\Http\Controllers\ExpedientesController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\InventarioExportarController;
use App\Http\Controllers\InventarioImportarController;
use App\Http\Controllers\ModulosController;
use App\Http\Controllers\MonitoreoController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\SalidaController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\TranscripcionController;
use App\Http\Controllers\TurneroController;
use App\Http\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Pantallas públicas de sala de espera (kiosko), sin autenticación, igual que el sistema legacy.
Route::get('/turnero1', [TurneroController::class, 'uno'])->name('turnero.uno');
Route::get('/turnero2', [TurneroController::class, 'dos'])->name('turnero.dos');

// Sin middleware auth: con ?rawbt=1 lo pide la app externa de impresión térmica,
// que no manda cookie de sesión. El controller exige sesión en los demás casos.
Route::get('/ingreso/{ingreso}/ticket', [IngresoController::class, 'ticket'])->name('ingreso.ticket');
Route::get('/alistamiento/{ingreso}/ticket', [AlistamientoController::class, 'ticket'])->name('alistamiento.ticket');

/*
 * Endpoints JSON. Van en web.php (no en routes/api.php) a propósito: usan la
 * misma sesión de cookies que el resto de la app, igual que el sistema legacy,
 * y así no hace falta montar Sanctum ni tokens para nada.
 */
Route::prefix('api')->name('api.')->group(function () {
    // Pantalla pública de sala de espera, sin autenticación.
    Route::get('/turnero-data', [TurneroApiController::class, 'data'])->name('turnero.data');
    Route::get('/tts-voice', [TtsApiController::class, 'voz'])->middleware('throttle:120,1')->name('tts.voz');

    Route::middleware('auth')->group(function () {
        Route::get('/pacientes/buscar', [PacienteApiController::class, 'buscar'])->name('pacientes.buscar');
        Route::get('/medicamentos/buscar', [InventarioApiController::class, 'medicamentos'])->name('medicamentos.buscar');
        Route::get('/bodegas/lotes', [InventarioApiController::class, 'lotesBodega'])->name('bodegas.lotes');
        Route::get('/pacientes/historial', [PacienteApiController::class, 'historial'])->name('paciente.historial');
        Route::post('/lock-record', [LockApiController::class, 'porFormulario'])->name('lock_record');
        Route::post('/ingresos/{ingreso}/lock', [LockApiController::class, 'bloquear'])->name('ingresos.lock');
        Route::delete('/ingresos/{ingreso}/lock', [LockApiController::class, 'liberar'])->name('ingresos.unlock');
        Route::get('/notificaciones', [NotificacionApiController::class, 'index'])->name('notificaciones.index');
        Route::post('/notificaciones/{notificacion}/leida', [NotificacionApiController::class, 'marcarLeido'])->name('notificaciones.leida');

        // Catálogos Qrystalos para los selectores dependientes del formulario de Ingreso.
        Route::get('/qrystalos/aseguradoras', [QrystalosApiController::class, 'aseguradoras'])->name('qrystalos.aseguradoras');
        Route::get('/qrystalos/planes', [QrystalosApiController::class, 'planes'])->name('qrystalos.planes');
        Route::get('/qrystalos/ciudades', [QrystalosApiController::class, 'ciudades'])->name('qrystalos.ciudades');
        Route::get('/qrystalos/barrios', [QrystalosApiController::class, 'barrios'])->name('qrystalos.barrios');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/sede/{sede}/cambiar', [SedeController::class, 'cambiar'])->name('sede.cambiar');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::match(['get', 'post'], '/ingreso', [IngresoController::class, 'index'])
        ->middleware('role:ingreso')->name('ingreso.index');

    Route::middleware('role:transcripcion')->group(function () {
        Route::get('/transcripcion', [TranscripcionController::class, 'index'])->name('transcripcion.index');
        Route::post('/transcripcion', [TranscripcionController::class, 'guardarTranscripcion'])->name('transcripcion.guardar');
    });

    Route::middleware('role:monitoreo')->group(function () {
        Route::get('/monitoreo', [MonitoreoController::class, 'index'])->name('monitoreo.index');
        Route::post('/monitoreo/verificar', [MonitoreoController::class, 'guardarVerificacion'])->name('monitoreo.verificar');
    });

    Route::middleware('role:salida,entrega,Administrador')->group(function () {
        Route::get('/salida', [SalidaController::class, 'index'])->name('salida.index');
        Route::post('/salida/cerrar', [SalidaController::class, 'cerrarTicket'])->name('salida.cerrar');
        Route::post('/salida/cerrar-express', [SalidaController::class, 'cerrarExpressAjax'])->name('salida.cerrar_express');
    });


    Route::middleware('role:inventario,Administrador')->prefix('inventario')->name('inventario.')->group(function () {
        foreach (['index' => '', 'bodegas' => 'bodegas', 'productos' => 'productos', 'entradas' => 'entradas', 'traslados' => 'traslados',
                  'kardex' => 'kardex', 'pendientes' => 'pendientes', 'proveedores' => 'proveedores',
                  'dispensacion' => 'dispensacion'] as $metodo => $uri) {
            Route::match(['get', 'post'], '/'.$uri, [InventarioController::class, $metodo])->name($metodo);
        }
        Route::match(['get', 'post'], '/importar', [InventarioImportarController::class, 'index'])->name('importar');
        Route::get('/exportar', [InventarioExportarController::class, 'index'])->name('exportar');
    });

    Route::middleware('role:facturas,Administrador')->prefix('facturacion')->name('facturacion.')->group(function () {
        Route::get('/', [FacturacionController::class, 'facturas'])->name('facturas');
        Route::get('/consolidada', [FacturacionController::class, 'consolidada'])->name('consolidada');
        Route::get('/contratos', [FacturacionController::class, 'contratos'])->name('contratos');
        Route::get('/configuracion', [FacturacionController::class, 'configuracion'])->name('configuracion');
        Route::get('/imprimir-factura', [FacturacionController::class, 'imprimirFactura'])->name('imprimir_factura');
        Route::get('/imprimir-recibo', [FacturacionController::class, 'imprimirRecibo'])->name('imprimir_recibo');
    });
    Route::match(['get', 'post'], '/api/facturacion', [FacturacionController::class, 'api'])
        ->middleware('role:facturas,entrega,Administrador')->name('api.facturacion');

    require __DIR__.'/ia.php';

    Route::get('/auditoria', [AuditoriaController::class, 'index'])
        ->middleware('role:auditoria,Administrador')->name('auditoria.index');

    Route::middleware('role:alistamiento,supervision_alistamiento,monitoreo,Administrador')->group(function () {
        Route::get('/alistamiento', [AlistamientoController::class, 'index'])->name('alistamiento.index');
        Route::post('/alistamiento', [AlistamientoController::class, 'accion'])->name('alistamiento.guardar');
        Route::get('/alistamiento/{ingreso}/orden-unificada', [AlistamientoController::class, 'ordenUnificada'])->name('alistamiento.orden_unificada');
    });

    Route::middleware('role:entrega,monitoreo,Administrador')->group(function () {
        Route::get('/entrega', [EntregaController::class, 'index'])->name('entrega.index');
        Route::post('/entrega', [EntregaController::class, 'accion'])->name('entrega.finalizar');
    });

    Route::get('/entrega/{ingreso}/acta', [EntregaController::class, 'acta'])->name('entrega.acta');

    Route::middleware('role:reportes,analitica_tickets,Administrador')->group(function () {
        Route::get('/reportes', [ReportesController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/tickets-sede', [ReportesController::class, 'ticketsSede'])->name('reportes.tickets_sede');
    });

    Route::get('/expedientes', [ExpedientesController::class, 'index'])
        ->middleware('role:expedientes,Administrador')->name('expedientes.index');

    Route::middleware('role:ingreso,transcripcion')->group(function () {
        Route::get('/escaner-ia', [EscanerIaController::class, 'index'])->name('ia_scanner.index');
        Route::post('/escaner-ia', [EscanerIaController::class, 'procesar'])->name('ia_scanner.procesar');
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/empresa', [EmpresaController::class, 'edit'])->name('empresa.edit');
        Route::post('/empresa/general', [EmpresaController::class, 'guardarGeneral'])->name('empresa.general');
        Route::post('/empresa/empresas', [EmpresaController::class, 'crearEmpresa'])->name('empresa.empresas.store');
        Route::put('/empresa/empresas/{empresa}', [EmpresaController::class, 'actualizarEmpresa'])->name('empresa.empresas.update');
        Route::post('/empresa/sedes', [EmpresaController::class, 'crearSede'])->name('empresa.sedes.store');
        Route::put('/empresa/sedes/{sede}', [EmpresaController::class, 'actualizarSede'])->name('empresa.sedes.update');
    });

    Route::middleware('role:Administrador,empresa,usuarios')->group(function () {
        Route::get('/pacientes', [PacientesController::class, 'index'])->name('pacientes.index');
        Route::get('/pacientes/exportar', [PacientesController::class, 'exportar'])->name('pacientes.exportar');
        Route::get('/pacientes/importar', [PacientesController::class, 'importar'])->name('pacientes.importar');
        Route::get('/pacientes/plantilla', [PacientesController::class, 'descargarPlantilla'])->name('pacientes.plantilla');
        Route::post('/pacientes/importar', [PacientesController::class, 'procesar'])->name('pacientes.procesar');
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
        Route::put('/usuarios/{usuario}', [UsuariosController::class, 'update'])->name('usuarios.update');
        Route::post('/usuarios/{usuario}/toggle', [UsuariosController::class, 'toggleEstado'])->name('usuarios.toggle');
        Route::post('/usuarios/permisos', [UsuariosController::class, 'guardarMatrizPermisos'])->name('usuarios.permisos');
    });

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/modulos', [ModulosController::class, 'index'])->name('modulos.index');
        Route::post('/modulos', [ModulosController::class, 'store'])->name('modulos.store');
        Route::put('/modulos/{modulo}', [ModulosController::class, 'update'])->name('modulos.update');
        Route::post('/modulos/{modulo}/toggle', [ModulosController::class, 'toggleEstado'])->name('modulos.toggle');
    });
});
