<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Paciente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PacienteApiController extends Controller
{
    /**
     * Unifica los dos endpoints legacy (buscar_paciente.php y get_paciente.php),
     * que hacían lo mismo con distinto nombre de parámetro y de estado.
     */
    public function buscar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tipo_documento' => ['nullable', 'string'],
            'numero_documento' => ['required', 'string'],
        ]);

        $paciente = Paciente::buscarPorDocumento(
            $datos['tipo_documento'] ?? 'CC',
            trim($datos['numero_documento'])
        );

        return $paciente
            ? response()->json(['status' => 'found', 'data' => $paciente])
            : response()->json(['status' => 'not_found', 'message' => 'Paciente no encontrado']);
    }
}
