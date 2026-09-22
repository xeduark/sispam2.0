<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\ContratoEps;
use App\Models\ReciboCaja;
use App\Services\Facturacion\FacturadorManager;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FacturacionController extends Controller
{
    private Factura $facturaModel;
    private ContratoEps $contratoModel;
    private ReciboCaja $reciboModel;

    public function __construct()
    {
        $this->facturaModel = new Factura();
        $this->contratoModel = new ContratoEps();
        $this->reciboModel = new ReciboCaja();
    }

    public function facturas(Request $request)
    {
        $filtros = [
            'tipo_factura' => $request->input('tipo_factura', ''),
            'eps_nombre' => $request->input('eps_nombre', ''),
            'fecha_desde' => $request->input('fecha_desde', ''),
            'fecha_hasta' => $request->input('fecha_hasta', ''),
            'estado_factura' => $request->input('estado_factura', '')
        ];

        $facturas = $this->facturaModel->obtenerFacturas($filtros);
        $contratos = $this->contratoModel->getAll();

        return view('facturacion.facturas', compact('facturas', 'contratos', 'filtros'));
    }

    public function consolidada(Request $request)
    {
        $epsFiltro = $request->input('eps_nombre', '');
        $fechaDesde = $request->input('fecha_desde', date('Y-m-01'));
        $fechaHasta = $request->input('fecha_hasta', date('Y-m-d'));

        $pendientes = $this->facturaModel->obtenerActasPendientesConsolidacion($epsFiltro, $fechaDesde, $fechaHasta);
        $contratos = $this->contratoModel->getAll();

        return view('facturacion.consolidada', compact('pendientes', 'contratos', 'epsFiltro', 'fechaDesde', 'fechaHasta'));
    }

    public function contratos(Request $request)
    {
        $contratos = $this->contratoModel->getAll();
        return view('facturacion.contratos', compact('contratos'));
    }

    public function configuracion(Request $request)
    {
        $mgr = new FacturadorManager();
        $config = $mgr->getActiveConfig();
        $adapters = $mgr->getAvailableAdapters();

        return view('facturacion.configuracion', compact('config', 'adapters'));
    }

    public function imprimirFactura(Request $request, $id = null)
    {
        $id = $id ?? $request->query('id');
        $f = $this->facturaModel->obtenerDetalleFactura((int) $id);
        if (! $f) {
            abort(404, 'Factura no encontrada.');
        }

        return view('facturacion.imprimir_factura', compact('f'));
    }

    public function imprimirRecibo(Request $request, $id = null)
    {
        $id = $id ?? $request->query('id');
        $r = $this->reciboModel->getById((int) $id);
        if (! $r) {
            abort(404, 'Recibo de caja no encontrado.');
        }

        return view('facturacion.imprimir_recibo', compact('r'));
    }

    public function api(Request $request): JsonResponse
    {
        $action = $request->input('action', $request->query('action', ''));
        $userId = auth()->id() ?? 1;

        switch ($action) {
            case 'emitir_individual':
                $ingresoId = (int) $request->input('ingreso_id', 0);
                $generarRecibo = !empty($request->input('generar_recibo_caja'));
                $metodoPago = $request->input('metodo_pago', 'EFECTIVO');
                $refPago = $request->input('referencia_pago', '');

                if ($ingresoId <= 0) {
                    return response()->json(['success' => false, 'message' => 'ID de ingreso no válido.'], 422);
                }

                $res = $this->facturaModel->crearFacturaIndividual($ingresoId, $userId, [
                    'generar_recibo_caja' => $generarRecibo,
                    'metodo_pago' => $metodoPago,
                    'referencia_pago' => $refPago
                ]);
                return response()->json($res);

            case 'emitir_consolidada':
                $epsNombre = trim($request->input('eps_nombre', ''));
                $desde = trim($request->input('fecha_desde', date('Y-m-01')));
                $hasta = trim($request->input('fecha_hasta', date('Y-m-d')));

                if (empty($epsNombre)) {
                    return response()->json(['success' => false, 'message' => 'Debe especificar la EPS a consolidar.'], 422);
                }

                $res = $this->facturaModel->crearFacturaConsolidada($epsNombre, $desde, $hasta, $userId);
                return response()->json($res);

            case 'calcular_copago':
                $ingresoId = (int) $request->input('ingreso_id', 0);
                $pac = DB::table('ingresos as i')
                    ->join('pacientes as p', 'i.paciente_id', '=', 'p.id')
                    ->where('i.id', $ingresoId)
                    ->select('i.*', 'p.tipo_afiliado', 'p.nivel_socioeconomico', 'p.grupo_poblacional', 'p.eps_nombre')
                    ->first();

                if (! $pac) {
                    return response()->json(['success' => false, 'message' => 'No encontrado'], 404);
                }

                $contrato = $this->contratoModel->getByEps($pac->eps_nombre ?? '');
                $calc = $this->facturaModel->calcularCopagoOCuotaModeradora((array) $pac, [], $contrato);
                return response()->json([
                    'success' => true,
                    'calculo' => $calc,
                    'contrato' => $contrato
                ]);

            case 'guardar_recibo_copago':
                $ingresoId = (int) $request->input('ingreso_id', 0);
                $pacienteId = (int) $request->input('paciente_id', 0);
                $valor = (float) $request->input('valor', 0);
                $concepto = $request->input('concepto', 'CUOTA_MODERADORA');
                $metodo = $request->input('metodo_pago', 'EFECTIVO');
                $ref = $request->input('referencia_pago', '');
                $obs = $request->input('observaciones', '');

                $res = $this->reciboModel->generarReciboCopago($ingresoId, $pacienteId, $valor, $concepto, $metodo, $ref, $userId, $obs);
                return response()->json($res);

            case 'guardar_config_proveedor':
                $id = (int) $request->input('id', 0);
                $codigo = trim($request->input('proveedor_codigo', 'SIMULADOR'));
                $nombre = trim($request->input('nombre_proveedor', 'Simulador DIAN'));
                $ambiente = $request->input('ambiente', 'PRUEBAS');
                $apiUrl = trim($request->input('api_url', ''));
                $apiKey = trim($request->input('api_key', ''));
                $apiToken = trim($request->input('api_token', ''));
                $softwareId = trim($request->input('software_id', ''));
                $pinSoftware = trim($request->input('pin_software', ''));
                $resolucion = trim($request->input('resolucion_numero', ''));
                $prefijo = trim($request->input('prefijo', 'SETP'));
                $desde = (int) $request->input('rango_desde', 1);
                $hasta = (int) $request->input('rango_hasta', 500000);
                $consecutivo = (int) $request->input('consecutivo_actual', 1);
                $fDesde = $request->input('fecha_vigencia_desde') ?: null;
                $fHasta = $request->input('fecha_vigencia_hasta') ?: null;
                $claveTec = trim($request->input('clave_tecnica', ''));

                DB::table('facturas_config_proveedor')->update(['activo' => 0]);

                $data = [
                    'proveedor_codigo' => $codigo,
                    'nombre_proveedor' => $nombre,
                    'ambiente' => $ambiente,
                    'api_url' => $apiUrl,
                    'api_key' => $apiKey,
                    'api_token' => $apiToken,
                    'software_id' => $softwareId,
                    'pin_software' => $pinSoftware,
                    'resolucion_numero' => $resolucion,
                    'prefijo' => $prefijo,
                    'rango_desde' => $desde,
                    'rango_hasta' => $hasta,
                    'consecutivo_actual' => $consecutivo,
                    'fecha_vigencia_desde' => $fDesde,
                    'fecha_vigencia_hasta' => $fHasta,
                    'clave_tecnica' => $claveTec,
                    'activo' => 1
                ];

                if ($id > 0) {
                    DB::table('facturas_config_proveedor')->where('id', $id)->update($data);
                } else {
                    $data['empresa_id'] = 1;
                    DB::table('facturas_config_proveedor')->insert($data);
                }

                return response()->json(['success' => true, 'message' => 'Configuración de facturación electrónica actualizada correctamente.']);

            case 'guardar_contrato_eps':
                $ok = $this->contratoModel->guardar($request->all());
                return response()->json(['success' => $ok, 'message' => $ok ? 'Contrato EPS guardado correctamente.' : 'Error al guardar contrato.']);

            case 'detalle_factura':
                $facturaId = (int) $request->input('id', 0);
                $det = $this->facturaModel->obtenerDetalleFactura($facturaId);
                return response()->json(['success' => (bool) $det, 'factura' => $det]);

            case 'descargar_rips_json':
                $facturaId = (int) $request->input('id', 0);
                $factura = $this->facturaModel->obtenerDetalleFactura($facturaId);
                if (! $factura) {
                    return response()->json(['success' => false, 'message' => 'Factura no encontrada.'], 404);
                }

                $mgr = new FacturadorManager();
                $rips = $mgr->generarRips([
                    'nit_empresa' => $factura['empresa_nit'] ?? '900123456',
                    'prefijo' => $factura['prefijo'],
                    'numero_factura' => $factura['numero_factura'],
                    'codigo_prestador' => '050010000101',
                    'numero_autorizacion' => 'AUT-001',
                    'diagnostico_cie10' => 'Z760',
                    'detalles' => array_map(function($it) {
                        return [
                            'codigo_cums' => $it['codigo_cums'],
                            'nombre_medicamento' => $it['nombre_medicamento'],
                            'concentracion' => $it['concentracion'],
                            'forma_farmaceutica' => $it['forma_farmaceutica'],
                            'cantidad' => $it['cantidad'],
                            'duracion_dias' => 30,
                            'valor_unitario' => $it['valor_unitario'],
                            'valor_total' => $it['valor_total'],
                            'copago_aplicado' => $it['copago_aplicado'],
                            'numero_prescripcion_mipres' => $it['numero_prescripcion_mipres'],
                            'tipo_medicamento_rips' => $it['tipo_medicamento_rips']
                        ];
                    }, $factura['items'] ?? [])
                ]);

                return response()->json($rips, 200, [
                    'Content-Disposition' => 'attachment; filename="RIPS_' . $factura['prefijo'] . $factura['numero_factura'] . '.json"',
                ]);

            default:
                return response()->json(['success' => false, 'message' => 'Acción no reconocida.'], 400);
        }
    }
}

