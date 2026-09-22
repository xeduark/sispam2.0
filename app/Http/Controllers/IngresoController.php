<?php

namespace App\Http\Controllers;

use App\Models\EmpresaConfig;
use App\Models\Ingreso;
use App\Models\Paciente;
use App\Models\Sede;
use App\Services\IngresoService;
use App\Services\QrystalosService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IngresoController extends Controller
{
    public function index(Request $request, IngresoService $ingresos, QrystalosService $qrystalos): View
    {
        $error = null;
        $avisoQrystalos = null;
        $ticketGenerado = null;
        $ingresoIdCreado = null;
        $sedesQrystalos = Sede::whereNotNull('qrystalos_id_sede')->orderBy('nombre_sede')->get();

        if ($request->isMethod('post')) {
            $datos = $request->validate([
                'tipo_documento' => ['required', 'string'],
                'numero_documento' => ['required', 'string'],
                'primer_apellido' => ['required', 'string'],
                'primer_nombre' => ['required', 'string'],
                'eps_nombre' => ['required', 'string'],
                'qrystalos_idadministradora' => ['required', 'string'],
                'qrystalos_idplan' => ['required', 'string'],
                'qrystalos_idciudad' => ['required', 'string'],
                'qrystalos_idbarrio' => ['required', 'string'],
                'qrystalos_idsede' => ['required', 'string'],
                'contacto_emergencia_nombre' => ['required', 'string'],
                'contacto_emergencia_telefono' => ['required', 'string'],
                'contacto_emergencia_parentesco' => ['required', 'string'],
                'persona_reclama' => ['nullable', 'string'],
                'doc_tipo_categoria' => ['array'],
                'doc_archivos' => ['array'],
                'doc_archivos.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:25600'],
            ], [], []);

            // Empareja cada archivo con su categoría por índice, igual que el $_FILES legacy.
            $categorias = $request->input('doc_tipo_categoria', []);
            $archivosSubidos = [];
            foreach ($request->file('doc_archivos', []) as $idx => $archivo) {
                if ($archivo && $archivo->isValid()) {
                    $archivosSubidos[$categorias[$idx] ?? 'OTRO'] = $archivo;
                }
            }

            $tiposAdjuntados = array_keys($archivosSubidos);
            $tieneCedula = in_array('CEDULA', $tiposAdjuntados, true);
            $tieneOrden = in_array('ORDEN_MEDICA', $tiposAdjuntados, true);
            $tieneAutorizacion = in_array('AUTORIZACION', $tiposAdjuntados, true);
            $personaReclama = $datos['persona_reclama'] ?? '';

            if (empty($personaReclama)) {
                $error = '⚠️ Deber seleccionar la modalidad de reclamación (si los medicamentos los reclama el paciente o un tercero/acudiente).';
            } elseif ($personaReclama === 'PACIENTE_DIRECTO' && (! $tieneCedula || ! $tieneOrden)) {
                $error = '⚠️ REQUISITO OBLIGATORIO DE SOPORTES: Para reclamación directa del paciente, debe adjuntar por separado los 2 soportes: 1) Cédula / Doc. Identidad y 2) Fórmula / Orden Médica.';
            } elseif ($personaReclama === 'TERCERO_ACUDIENTE' && (! $tieneCedula || ! $tieneOrden || ! $tieneAutorizacion)) {
                $error = '⚠️ REQUISITO OBLIGATORIO DE SOPORTES: Para entrega a nombre de otra persona (tercero/acudiente), debe adjuntar por separado los 3 soportes: 1) Cédula del paciente, 2) Fórmula / Orden Médica y 3) Autorización / Doc. del Tercero.';
            } else {
                $paciente = Paciente::createOrUpdate($request->all());

                $resultado = $ingresos->crear(
                    $paciente,
                    $request->user()->id,
                    $archivosSubidos,
                    $request->input('prioridad', 'NORMAL'),
                    trim((string) $request->input('prioridad_observacion', '')),
                    $personaReclama,
                    trim((string) $request->input('ips_remite', '')),
                    $request->boolean('es_alto_costo')
                );

                $ticketGenerado = $resultado['ticket'];
                $ingresoIdCreado = $resultado['id'];

                // El ingreso en SISPAM ya quedó guardado; un KO de Qrystalos solo se avisa, no revierte nada.
                $envio = $qrystalos->insertarPaciente($paciente);
                if (! $envio['ok']) {
                    $avisoQrystalos = 'Qrystalos rechazó el envío del paciente: '.implode(' | ', $envio['errores']);
                }
            }
        }

        return view('ingreso.index', compact('error', 'avisoQrystalos', 'ticketGenerado', 'ingresoIdCreado', 'sedesQrystalos'));
    }

    /**
     * Tiquete térmico de turno.
     *
     * La ruta no lleva middleware auth: con ?rawbt=1 la pide la app externa de
     * impresión térmica, que no manda cookie de sesión. Fuera de ese caso se
     * exige sesión, igual que el check_auth() del archivo legacy.
     */
    public function ticket(Request $request, int $id): View
    {
        $ingreso = Ingreso::with(['paciente', 'orientador'])->findOrFail($id);
        $config = EmpresaConfig::actual();

        if ($request->query('rawbt') === '1') {
            return view('ingreso.ticket_rawbt', compact('ingreso', 'config'));
        }

        abort_unless(auth()->check(), 403);

        return view('ingreso.ticket', [
            'ingreso' => $ingreso,
            'config' => $config,
            'rawbtUrl' => route('ingreso.ticket', ['ingreso' => $id, 'rawbt' => 1]),
        ]);
    }
}
