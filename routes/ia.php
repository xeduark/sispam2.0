<?php

use App\Http\Controllers\IaColaController;
use App\Http\Controllers\IngresoController;
use Illuminate\Support\Facades\Route;

// Rutas del clúster IA (cola, patrones IPS, demo, verificación, escáner). Se incluye desde routes/web.php dentro del grupo 'auth'.

Route::middleware('role:ia_scanner,ia_cola,escaner_digitalizacion,Administrador')->group(function () {
    Route::match(['get', 'post'], '/ia-scanner/cola', [IaColaController::class, 'index'])->name('ia_scanner.cola');
    Route::post('/ia-scanner/cola/accion', [IaColaController::class, 'accion'])->name('ia_scanner.cola.accion');

    // El "escáner de digitalización" reutiliza el flujo de captura ya integrado en Ingreso (mismo formulario, mismo JS).
    Route::get('/escaner', [IngresoController::class, 'index'])->name('escaner.index');
});
