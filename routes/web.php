<?php

use App\Http\Controllers\AlistamientoController;
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

    Route::get('/reportes', [ReportesController::class, 'index'])
        ->middleware('role:reportes')->name('reportes.index');

    Route::get('/expedientes', [ExpedientesController::class, 'index'])
        ->middleware('role:expedientes,ingreso')->name('expedientes.index');

    Route::get('/empresa', [EmpresaController::class, 'edit'])
        ->middleware('role:Administrador')->name('empresa.edit');

    Route::get('/pacientes/importar', [PacientesController::class, 'importar'])
        ->middleware('role:Administrador,empresa,usuarios')->name('pacientes.importar');

    Route::get('/usuarios', [UsuariosController::class, 'index'])
        ->middleware('role:Administrador')->name('usuarios.index');

    Route::get('/modulos', [ModulosController::class, 'index'])
        ->middleware('role:Administrador')->name('modulos.index');
});
