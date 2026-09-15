<?php

use App\Http\Controllers\AlistamientoController;
use App\Http\Controllers\Api\LockApiController;
use App\Http\Controllers\Api\NotificacionApiController;
use App\Http\Controllers\Api\PacienteApiController;
use App\Http\Controllers\Api\TurneroApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\ExpedientesController;
use App\Http\Controllers\IngresoController;
use App\Http\Controllers\ModulosController;
use App\Http\Controllers\PacientesController;
use App\Http\Controllers\ReportesController;
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

/*
 * Endpoints JSON. Van en web.php (no en routes/api.php) a propósito: usan la
 * misma sesión de cookies que el resto de la app, igual que el sistema legacy,
 * y así no hace falta montar Sanctum ni tokens para nada.
 */
Route::prefix('api')->name('api.')->group(function () {
    // Pantalla pública de sala de espera, sin autenticación.
    Route::get('/turnero-data', [TurneroApiController::class, 'data'])->name('turnero.data');

    Route::middleware('auth')->group(function () {
        Route::get('/pacientes/buscar', [PacienteApiController::class, 'buscar'])->name('pacientes.buscar');
        Route::post('/ingresos/{ingreso}/lock', [LockApiController::class, 'bloquear'])->name('ingresos.lock');
        Route::delete('/ingresos/{ingreso}/lock', [LockApiController::class, 'liberar'])->name('ingresos.unlock');
        Route::get('/notificaciones', [NotificacionApiController::class, 'index'])->name('notificaciones.index');
        Route::post('/notificaciones/{notificacion}/leida', [NotificacionApiController::class, 'marcarLeido'])->name('notificaciones.leida');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/ingreso', [IngresoController::class, 'index'])
        ->middleware('role:ingreso')->name('ingreso.index');

    Route::get('/transcripcion', [TranscripcionController::class, 'index'])
        ->middleware('role:transcripcion')->name('transcripcion.index');

    Route::get('/alistamiento', [AlistamientoController::class, 'index'])
        ->middleware('role:alistamiento')->name('alistamiento.index');

    Route::get('/entrega', [EntregaController::class, 'index'])
        ->middleware('role:entrega')->name('entrega.index');

    Route::get('/entrega/{ingreso}/acta', [EntregaController::class, 'acta'])->name('entrega.acta');

    Route::get('/reportes', [ReportesController::class, 'index'])
        ->middleware('role:reportes')->name('reportes.index');

    Route::get('/expedientes', [ExpedientesController::class, 'index'])
        ->middleware('role:expedientes,ingreso')->name('expedientes.index');

    Route::middleware('role:Administrador')->group(function () {
        Route::get('/empresa', [EmpresaController::class, 'edit'])->name('empresa.edit');
        Route::post('/empresa/general', [EmpresaController::class, 'guardarGeneral'])->name('empresa.general');
        Route::post('/empresa/empresas', [EmpresaController::class, 'crearEmpresa'])->name('empresa.empresas.store');
        Route::put('/empresa/empresas/{empresa}', [EmpresaController::class, 'actualizarEmpresa'])->name('empresa.empresas.update');
        Route::post('/empresa/sedes', [EmpresaController::class, 'crearSede'])->name('empresa.sedes.store');
        Route::put('/empresa/sedes/{sede}', [EmpresaController::class, 'actualizarSede'])->name('empresa.sedes.update');
    });

    Route::get('/pacientes/importar', [PacientesController::class, 'importar'])
        ->middleware('role:Administrador,empresa,usuarios')->name('pacientes.importar');

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
