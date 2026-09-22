<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\Proveedor;
use App\Models\Sede;
use App\Models\TratamientoCronico;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventarioController extends Controller
{
    private Inventario $inv;

    public function __construct()
    {
        $this->inv = new Inventario();
    }

    private function sedeActiva(): ?int
    {
        $id = session('active_sede_id') ?? auth()->user()?->sede_id;

        return $id ? (int) $id : null;
    }

    private function errorSi(callable $accion, string $porDefecto): string
    {
        try {
            return $accion() ? '' : $porDefecto;
        } catch (\Throwable $e) {
            return 'Error: '.$e->getMessage();
        }
    }

    public function index(Request $request)
    {
        $active_sede_id = $this->sedeActiva();
        $filtroBodega = $request->filled('bodega_id') ? (int) $request->query('bodega_id') : null;
        $filtroSemaforo = trim((string) $request->query('semaforo', ''));
        $busqueda = trim((string) $request->query('q', ''));
        $perPage = max(10, min(200, (int) $request->query('per_page', 25)));
        $pageCurrent = max(1, (int) $request->query('p', 1));

        $bodegas = $this->inv->getBodegas($active_sede_id);
        $kpis = $this->inv->getResumenKPIs($active_sede_id);
        $totalRegistros = $this->inv->getStockGeneralCount($filtroBodega, $busqueda, $filtroSemaforo, $active_sede_id);
        $totalPages = max(1, (int) ceil($totalRegistros / $perPage));
        $pageCurrent = min($pageCurrent, $totalPages);
        $offset = ($pageCurrent - 1) * $perPage;
        $stockList = $this->inv->getStockGeneralPorBodega($filtroBodega, $busqueda, $filtroSemaforo, $active_sede_id, $perPage, $offset);

        return view('inventario.index', compact(
            'bodegas', 'kpis', 'stockList', 'totalRegistros', 'totalPages', 'pageCurrent', 'perPage',
            'filtroBodega', 'filtroSemaforo', 'busqueda', 'offset'
        ));
    }

    public function bodegas(Request $request)
    {
        if ($request->isMethod('post') && $request->has('action')) {
            return $this->bodegasAjax($request);
        }

        $filtroBuscar = trim((string) $request->query('q', ''));
        $filtroSede = $request->filled('sede_id') ? (int) $request->query('sede_id') : null;
        $filtroTipo = trim((string) $request->query('tipo_bodega', ''));
        $filtroEstado = $request->filled('estado') ? (int) $request->query('estado') : null;

        $bodegas = $this->inv->getBodegasConDetalles([
            'buscar' => $filtroBuscar, 'sede_id' => $filtroSede, 'tipo_bodega' => $filtroTipo, 'es_activa' => $filtroEstado,
        ]);
        $sedes = Sede::todasConEmpresa();
        $usuarios = DB::select("SELECT id, nombre_completo, usuario FROM usuarios WHERE estado = 'Activo' ORDER BY nombre_completo");
        $usuarios = array_map(fn ($u) => (array) $u, $usuarios);

        $totalBodegas = count($bodegas);
        $totalActivas = $totalSatelites = $totalVentanillas = $stockTotalUnidades = 0;
        foreach ($bodegas as $b) {
            $totalActivas += ($b['es_activa'] == 1) ? 1 : 0;
            $totalSatelites += ($b['tipo_bodega'] === 'SATELITE_SEDE') ? 1 : 0;
            $totalVentanillas += ($b['tipo_bodega'] === 'DISPENSACION_VENTANILLA') ? 1 : 0;
            $stockTotalUnidades += (int) ($b['total_unidades_stock'] ?? 0);
        }

        return view('inventario.bodegas', compact(
            'bodegas', 'sedes', 'usuarios', 'filtroBuscar', 'filtroSede', 'filtroTipo', 'filtroEstado',
            'totalBodegas', 'totalActivas', 'totalSatelites', 'totalVentanillas', 'stockTotalUnidades'
        ));
    }

    private function bodegasAjax(Request $request): JsonResponse
    {
        $ok = fn (bool $v, string $msg, string $err) => response()->json(['status' => $v ? 'ok' : 'error', 'message' => $v ? $msg : $err]);

        try {
            $id = (int) $request->input('id', 0);

            switch ($request->input('action')) {
                case 'guardar_bodega':
                    $codigo = trim((string) $request->input('codigo_bodega', ''));
                    $nombre = trim((string) $request->input('nombre_bodega', ''));
                    if ($codigo === '' || $nombre === '') {
                        return response()->json(['status' => 'error', 'message' => 'El código y el nombre de la bodega son obligatorios.']);
                    }
                    $guardado = $this->inv->guardarBodega([
                        'id' => $id,
                        'codigo_bodega' => $codigo,
                        'nombre_bodega' => $nombre,
                        'tipo_bodega' => trim((string) $request->input('tipo_bodega', 'SATELITE_SEDE')),
                        'sede_id' => $request->filled('sede_id') ? (int) $request->input('sede_id') : null,
                        'responsable_user_id' => $request->filled('responsable_user_id') ? (int) $request->input('responsable_user_id') : null,
                        'ubicacion_fisica' => trim((string) $request->input('ubicacion_fisica', '')),
                        'es_activa' => $request->has('es_activa') ? (int) $request->input('es_activa') : 1,
                    ]);

                    return $ok((bool) $guardado, $id > 0 ? 'Bodega actualizada con éxito.' : 'Bodega creada con éxito.', 'No fue posible guardar la bodega en la base de datos.');

                case 'cambiar_estado':
                    if ($id <= 0) {
                        return response()->json(['status' => 'error', 'message' => 'ID de bodega no válido.']);
                    }

                    return $ok((bool) $this->inv->cambiarEstadoBodega($id, (int) $request->input('estado', 0)), 'Estado actualizado correctamente.', 'Error al cambiar estado.');

                case 'eliminar_bodega':
                    if ($id <= 0) {
                        return response()->json(['status' => 'error', 'message' => 'ID de bodega no válido.']);
                    }
                    $this->inv->eliminarBodega($id);

                    return response()->json(['status' => 'ok', 'message' => 'Bodega eliminada o inactivada correctamente.']);

                case 'get_bodega':
                    $bodega = $this->inv->getBodegaById($id);

                    return $bodega ? response()->json(['status' => 'ok', 'data' => $bodega]) : response()->json(['status' => 'error', 'message' => 'Bodega no encontrada.']);
            }
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return response()->json(['status' => 'error', 'message' => 'Acción no reconocida.'], 422);
    }

    public function productos(Request $request)
    {
        $mensaje = $error = '';

        if ($request->isMethod('post') && $request->input('action') === 'guardar_producto') {
            try {
                $this->inv->guardarProducto($request->post())
                    ? $mensaje = 'Medicamento guardado exitosamente en el catálogo maestro.'
                    : $error = 'No se pudo guardar el medicamento.';
            } catch (\Throwable $e) {
                $error = 'Error: '.$e->getMessage();
            }
        }

        $buscar = trim((string) $request->query('q', ''));
        $filtroTipo = trim((string) $request->query('tipo_producto', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));
        $pageCurrent = max(1, (int) $request->query('p', 1));

        $filtros = ['buscar' => $buscar] + ($filtroTipo !== '' ? ['tipo_producto' => $filtroTipo] : []);
        $totalProductos = $this->inv->getProductosCount($filtros);
        $totalPages = max(1, (int) ceil($totalProductos / $perPage));
        $pageCurrent = min($pageCurrent, $totalPages);
        $offset = ($pageCurrent - 1) * $perPage;
        $productos = $this->inv->getProductos($filtros, $perPage, $offset);

        $tiposProducto = Inventario::getTiposProducto();
        $opciones = $this->inv->getOpcionesCatalogos();
        $listaLaboratorios = $opciones['laboratorios'];
        $listaClases = $opciones['clases_terapeuticas'];
        $listaFormas = $opciones['formas_farmaceuticas'];
        $listaPresentaciones = $opciones['presentaciones'];
        $startRecord = $totalProductos > 0 ? $offset + 1 : 0;
        $endRecord = min($offset + $perPage, $totalProductos);

        return view('inventario.productos', compact(
            'mensaje', 'error', 'buscar', 'filtroTipo', 'perPage', 'pageCurrent', 'totalProductos', 'totalPages', 'productos',
            'tiposProducto', 'listaLaboratorios', 'listaClases', 'listaFormas', 'listaPresentaciones', 'startRecord', 'endRecord'
        ));
    }

    public function entradas(Request $request)
    {
        $mensaje = $error = '';

        if ($request->isMethod('post') && $request->input('action') === 'registrar_compra') {
            try {
                $items = json_decode((string) $request->input('items_json', '[]'), true);
                if (empty($items)) {
                    throw new \Exception('Debe ingresar al menos un medicamento con lote y cantidad.');
                }
                $res = $this->inv->registrarEntradaCompra($request->post(), $items, auth()->id() ?? 1);
                $res['status'] === 'ok'
                    ? $mensaje = 'Recepción técnica y compra registrada exitosamente. Lotes acreditados a la bodega seleccionada.'
                    : $error = $res['message'] ?? 'Error al procesar la entrada.';
            } catch (\Throwable $e) {
                $error = 'Error: '.$e->getMessage();
            }
        }

        $proveedores = (new Proveedor())->getProveedores();
        $bodegas = $this->inv->getBodegas();
        $historialCompras = $this->inv->getComprasEntradas(30);

        return view('inventario.entradas', compact('mensaje', 'error', 'proveedores', 'bodegas', 'historialCompras'));
    }

    public function traslados(Request $request)
    {
        $mensaje = $error = '';

        if ($request->isMethod('post')) {
            try {
                if ($request->input('action') === 'crear_traslado') {
                    $items = json_decode((string) $request->input('items_json', '[]'), true);
                    $res = $this->inv->registrarTraslado($request->post(), $items, auth()->id() ?? 1);
                    $res['status'] === 'ok'
                        ? $mensaje = "Traslado #{$res['numero_traslado']} generado y despachado con éxito."
                        : $error = $res['message'] ?? 'Error al generar traslado.';
                } elseif ($request->input('action') === 'recibir_traslado') {
                    $res = $this->inv->confirmarRecepcionTraslado((int) $request->input('traslado_id', 0), auth()->id() ?? 1);
                    $res['status'] === 'ok'
                        ? $mensaje = $res['message']
                        : $error = $res['message'] ?? 'Error al confirmar recepción.';
                }
            } catch (\Throwable $e) {
                $error = 'Error: '.$e->getMessage();
            }
        }

        $bodegas = $this->inv->getBodegas();
        $traslados = $this->inv->getTraslados();

        return view('inventario.traslados', compact('mensaje', 'error', 'bodegas', 'traslados'));
    }

    public function kardex(Request $request)
    {
        $filtroBodega = $request->filled('bodega_id') ? (int) $request->query('bodega_id') : null;
        $filtroTipo = trim((string) $request->query('tipo_movimiento', ''));
        $filtroBuscar = trim((string) $request->query('buscar', ''));
        $filtroDesde = trim((string) $request->query('fecha_desde', ''));
        $filtroHasta = trim((string) $request->query('fecha_hasta', ''));
        $perPage = max(10, min(100, (int) $request->query('per_page', 25)));
        $pageCurrent = max(1, (int) $request->query('p', 1));

        $filtros = [
            'bodega_id' => $filtroBodega, 'tipo_movimiento' => $filtroTipo, 'buscar' => $filtroBuscar,
            'fecha_desde' => $filtroDesde, 'fecha_hasta' => $filtroHasta,
        ];

        $bodegas = $this->inv->getBodegas();
        $totalMovimientos = $this->inv->getKardexCount($filtros);
        $resumen = $this->inv->getKardexResumen($filtros);
        $totalPages = max(1, (int) ceil($totalMovimientos / $perPage));
        $pageCurrent = min($pageCurrent, $totalPages);
        $offset = ($pageCurrent - 1) * $perPage;
        $movimientos = $this->inv->getKardexMovimientos($filtros, $perPage, $offset);

        $totalEntradas = (int) ($resumen['total_entradas'] ?? 0);
        $totalSalidas = (int) ($resumen['total_salidas'] ?? 0);
        $valorTotal = (float) ($resumen['valor_total'] ?? 0);
        $startRecord = $totalMovimientos > 0 ? $offset + 1 : 0;
        $endRecord = min($offset + $perPage, $totalMovimientos);

        return view('inventario.kardex', compact(
            'filtroBodega', 'filtroTipo', 'filtroBuscar', 'filtroDesde', 'filtroHasta', 'perPage', 'pageCurrent', 'bodegas',
            'totalMovimientos', 'totalPages', 'movimientos', 'totalEntradas', 'totalSalidas', 'valorTotal', 'startRecord', 'endRecord'
        ));
    }

    public function proveedores(Request $request)
    {
        $mensaje = $error = '';
        $prov = new Proveedor();

        if ($request->isMethod('post') && $request->input('action') === 'guardar_proveedor') {
            try {
                $prov->guardar($request->post()) ? $mensaje = 'Proveedor guardado exitosamente.' : $error = 'No se pudo guardar el proveedor.';
            } catch (\Throwable $e) {
                $error = 'Error: '.$e->getMessage();
            }
        }

        $proveedores = $prov->getProveedores(false);

        return view('inventario.proveedores', compact('mensaje', 'error', 'proveedores'));
    }

    public function pendientes(Request $request)
    {
        $cronico = new TratamientoCronico();
        $userId = auth()->id();
        $mensaje = $error = '';
        $tabActiva = (string) $request->query('tab', 'cronicos');
        $eId = (int) $request->input('entrega_id', 0);
        $guia = trim((string) $request->input('guia_domicilio', ''));
        $mensajeria = trim((string) $request->input('empresa_mensajeria', ''));

        if ($request->isMethod('post')) {
            switch ($request->input('action')) {
                case 'actualizar_estado':
                    if ($this->inv->actualizarEstadoDomicilio((int) $request->input('id', 0), $request->input('estado_domicilio', 'PENDIENTE_ALISTAR'), $guia, $mensajeria, trim((string) $request->input('observaciones', '')))) {
                        $mensaje = 'Estado del pendiente / domicilio actualizado correctamente.';
                        $tabActiva = 'faltantes';
                    } else {
                        $error = 'No se pudo actualizar el registro.';
                    }
                    break;
                case 'actualizar_cronico_domicilio':
                    if ($cronico->actualizarEstadoDomicilio($eId, $request->input('estado', 'DESPACHADO_DOMICILIO'), $guia, $mensajeria, trim((string) $request->input('observaciones', '')), $userId)) {
                        $mensaje = '¡Despacho a domicilio de entrega crónica actualizado exitosamente!';
                        $tabActiva = 'cronicos';
                    } else {
                        $error = 'Error al actualizar la entrega crónica.';
                    }
                    break;
                case 'marcar_cronico_presencial':
                    if ($cronico->marcarEntregaPresencial($eId, null, $userId)) {
                        $mensaje = '¡Entrega multimes registrada como Entregada Presencialmente en Sede!';
                        $tabActiva = 'cronicos';
                    } else {
                        $error = 'Error al registrar la entrega presencial.';
                    }
                    break;
                case 'cambiar_modalidad_cronico':
                    $nueva = trim((string) $request->input('nueva_modalidad', 'PRESENCIAL'));
                    if ($cronico->cambiarModalidad($eId, $nueva)) {
                        $mensaje = 'Modalidad de entrega actualizada exitosamente a '.($nueva === 'PRESENCIAL' ? 'Presencial en Sede' : 'Domicilio').'.';
                    } else {
                        $error = 'Error al cambiar la modalidad de la entrega.';
                    }
                    break;
                case 'reprogramar_entrega_cronico':
                    if ($cronico->reprogramarEntrega(
                        $eId, trim((string) $request->input('nueva_fecha_programada', '')), trim((string) $request->input('modalidad', 'DOMICILIO')),
                        trim((string) $request->input('direccion_entrega', '')), trim((string) $request->input('telefono_contacto', '')),
                        trim((string) $request->input('observaciones_llamada', ''))
                    )) {
                        $mensaje = '¡Programación de entrega actualizada exitosamente! Fecha y modalidad registradas.';
                        $tabActiva = 'cronicos';
                    } else {
                        $error = 'Error al reprogramar la entrega.';
                    }
                    break;
            }
        }

        $filtroEstado = trim((string) $request->query('estado', ''));
        $buscar = trim((string) $request->query('q', ''));
        $filtroRango = trim((string) $request->query('rango', ''));
        $filtroMod = trim((string) $request->query('modalidad', ''));

        [$pacienteBuscado, $ticketsPaciente, $cronicosPaciente, $faltantesPaciente] = $buscar !== '' ? $this->paciente360($buscar) : [null, [], [], []];

        $pendientes = $this->inv->getPendientesDomicilio(['estado' => $filtroEstado, 'buscar' => $buscar]);
        $entregasCronicas = $cronico->getEntregasProgramadas([
            'estado' => $filtroEstado, 'buscar' => $buscar, 'rango_fecha' => $filtroRango, 'modalidad' => $filtroMod,
        ]);
        $metricasCronicas = $cronico->getMetricasEntregas();

        if ($request->query('export_cronicos') == '1') {
            return $this->exportarCronicos($entregasCronicas);
        }

        return view('inventario.pendientes', compact(
            'mensaje', 'error', 'tabActiva', 'filtroEstado', 'buscar', 'filtroRango', 'filtroMod', 'pacienteBuscado', 'ticketsPaciente',
            'cronicosPaciente', 'faltantesPaciente', 'pendientes', 'entregasCronicas', 'metricasCronicas'
        ));
    }

    /** Vista "Paciente 360": ficha, historial de tickets con lo dispensado, crónicos programados y faltantes. */
    private function paciente360(string $buscar): array
    {
        $db = DB::connection()->getPdo();
        $limpio = preg_replace('/\D/', '', $buscar);
        $like = '%'.$buscar.'%';

        $st = $db->prepare("
            SELECT * FROM pacientes
            WHERE numero_documento = :q
               OR (LENGTH(:q_clean) >= 4 AND REPLACE(REPLACE(REPLACE(numero_documento, '.', ''), ' ', ''), '-', '') = :q_clean2)
               OR nombres LIKE :q_like OR apellidos LIKE :q_like2
            ORDER BY (numero_documento = :q_exact) DESC, id DESC LIMIT 1");
        $st->execute([':q' => $buscar, ':q_clean' => $limpio, ':q_clean2' => $limpio, ':q_like' => $like, ':q_like2' => $like, ':q_exact' => $buscar]);
        $paciente = $st->fetch();

        if (! $paciente) {
            return [null, [], [], []];
        }

        $pid = $paciente['id'];
        $consulta = function (string $sql, array $params) use ($db) {
            $s = $db->prepare($sql);
            $s->execute($params);

            return $s->fetchAll();
        };

        $tickets = $consulta("
            SELECT i.*, COALESCE(s.nombre_sede, 'Sede Principal') AS sede_nombre, u.nombre_completo AS orientador_nombre, ua.nombre_completo AS alistador_nombre
            FROM ingresos i
            LEFT JOIN sedes s ON i.sede_id = s.id
            LEFT JOIN usuarios u ON i.orientador_id = u.id
            LEFT JOIN usuarios ua ON i.alistado_por_user_id = ua.id
            WHERE i.paciente_id = :pid ORDER BY i.fecha_ingreso DESC", [':pid' => $pid]);

        foreach ($tickets as &$t) {
            $t['medicamentos_dispensados'] = $consulta("
                SELECT imd.*, pm.nombre_generico, pm.nombre_comercial, pm.concentracion, pm.forma_farmaceutica, pm.codigo_sku, il.numero_lote
                FROM ingreso_medicamentos_dispensados imd
                JOIN productos_medicamentos pm ON imd.producto_id = pm.id
                LEFT JOIN inventario_lotes il ON imd.lote_id = il.id
                WHERE imd.ingreso_id = :iid", [':iid' => $t['id']]);
        }
        unset($t);

        $cronicos = $consulta("
            SELECT t.*, COALESCE(s.nombre_sede, 'Sede Principal') AS sede_nombre, p.nombres, p.apellidos, p.numero_documento, p.tipo_documento,
                   p.telefono, p.numero_celular, p.direccion_residencia, p.ciudad_residencia, p.eps_nombre
            FROM tratamientos_cronicos_entregas t
            JOIN pacientes p ON t.paciente_id = p.id
            LEFT JOIN ingresos i ON t.ingreso_origen_id = i.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE t.paciente_id = :pid ORDER BY t.fecha_programada ASC, t.periodo_numero ASC", [':pid' => $pid]);

        $faltantes = $consulta("
            SELECT imp.*, i.ticket_numero, i.fecha_ingreso, COALESCE(s.nombre_sede, 'Sede Principal') AS nombre_sede,
                   COALESCE(imp.nombre_medicamento, pm.nombre_generico) AS nombre_medicamento,
                   COALESCE(imp.concentracion, pm.concentracion) AS concentracion,
                   COALESCE(imp.forma_farmaceutica, pm.forma_farmaceutica) AS forma_farmaceutica,
                   p.nombres, p.apellidos, p.numero_documento, p.tipo_documento, p.telefono, p.numero_celular,
                   p.direccion_residencia, p.ciudad_residencia, p.eps_nombre
            FROM ingreso_medicamentos_pendientes imp
            JOIN ingresos i ON imp.ingreso_id = i.id
            JOIN pacientes p ON i.paciente_id = p.id
            LEFT JOIN productos_medicamentos pm ON imp.producto_id = pm.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE i.paciente_id = :pid ORDER BY imp.id DESC", [':pid' => $pid]);

        return [$paciente, $tickets, $cronicos, $faltantes];
    }

    private function exportarCronicos(array $entregas): StreamedResponse
    {
        return response()->streamDownload(function () use ($entregas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', 'FORMULA', 'FECHA_PROGRAMADA', 'PERIODO', 'PACIENTE', 'CEDULA', 'TELEFONO', 'DIRECCION', 'CIUDAD', 'EPS', 'MEDICAMENTO', 'CANTIDAD', 'MODALIDAD', 'ESTADO', 'GUIA_ENVIO']);
            foreach ($entregas as $ec) {
                fputcsv($out, [
                    $ec['id'], $ec['formula_numero'], $ec['fecha_programada'], "Periodo {$ec['periodo_numero']}/{$ec['total_periodos']}",
                    $ec['nombres'].' '.$ec['apellidos'], $ec['numero_documento'], $ec['telefono'] ?: ($ec['celular'] ?? ''),
                    $ec['direccion_entrega'] ?: $ec['direccion_residencia'], $ec['ciudad_residencia'], $ec['eps_nombre'],
                    $ec['medicamento_nombre'], $ec['cantidad_periodo'], $ec['modalidad'], $ec['estado'], $ec['guia_domicilio'],
                ]);
            }
            fclose($out);
        }, 'hoja_ruta_cronicos_'.date('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function dispensacion(Request $request)
    {
        $activeSede = $this->sedeActiva() ?? 1;
        $userId = auth()->id() ?? 1;

        if ($request->isMethod('post') && $request->has('action')) {
            return $this->dispensacionAjax($request, $userId);
        }

        if ($request->has('ajax_verificar_duplicados_mes')) {
            try {
                $duplicados = $this->inv->verificarDuplicidadEntregasMes((int) $request->query('paciente_id', 0), trim((string) $request->query('medicamento', '')));

                return response()->json(['status' => 'ok', 'duplicados' => $duplicados]);
            } catch (\Throwable $e) {
                return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
            }
        }

        $filtroBuscar = trim((string) $request->query('buscar', ''));
        $ordenesListas = $this->inv->getOrdenesListasDispensacion($activeSede, ['buscar' => $filtroBuscar]);

        $ingresoIdSeleccionado = (int) $request->query('ingreso_id', 0);
        if ($ingresoIdSeleccionado === 0 && ! empty($ordenesListas)) {
            $ingresoIdSeleccionado = (int) $ordenesListas[0]['ingreso_id'];
        }
        $detalleOrden = $ingresoIdSeleccionado > 0 ? $this->inv->getDetalleOrdenDispensacion($ingresoIdSeleccionado, $activeSede) : null;

        $todosProductos = $this->inv->getProductos(200);
        $bodegasAutorizadas = $this->inv->getBodegasParaUsuario($userId, $activeSede);

        $seleccionada = (int) $request->query('bodega_id', session('dispensacion_bodega_id', 0));
        $bodegaActiva = collect($bodegasAutorizadas)->firstWhere('id', $seleccionada)
            ?? collect($bodegasAutorizadas)->first(fn ($b) => ! empty($b['sede_id']) && $b['sede_id'] == $activeSede)
            ?? ($bodegasAutorizadas[0] ?? null);

        $bodegaId = $bodegaActiva ? (int) $bodegaActiva['id'] : 1;
        session(['dispensacion_bodega_id' => $bodegaId]);

        $st = DB::connection()->getPdo()->prepare("
            SELECT il.*, p.nombre_generico, p.nombre_comercial, p.concentracion, p.forma_farmaceutica, p.codigo_sku, p.codigo_cums
            FROM inventario_lotes il JOIN productos_medicamentos p ON il.producto_id = p.id
            WHERE il.bodega_id = :b_id AND il.cantidad_actual > 0 AND il.fecha_vencimiento > CURDATE()
            ORDER BY il.fecha_vencimiento ASC, il.cantidad_actual DESC");
        $st->execute([':b_id' => $bodegaId]);
        $lotesDisponiblesBD = $st->fetchAll();

        return view('inventario.dispensacion', compact(
            'filtroBuscar', 'ordenesListas', 'ingresoIdSeleccionado', 'detalleOrden', 'todosProductos', 'bodegasAutorizadas',
            'bodegaActiva', 'bodegaId', 'lotesDisponiblesBD'
        ) + ['bodegaIdSeleccionada' => $seleccionada]);
    }

    private function dispensacionAjax(Request $request, int $userId): JsonResponse
    {
        try {
            $ingresoId = (int) $request->input('ingreso_id', 0);

            switch ($request->input('action')) {
                case 'guardar_orden_alistamiento':
                case 'confirmar_dispensacion_ia':
                    return $this->guardarOrdenAlistamiento($request, $ingresoId, $userId);

                case 'reservar_stock_temporal':
                    $items = json_decode((string) $request->input('items', '[]'), true) ?: [];

                    return response()->json($this->inv->reservarLotesTemporales($ingresoId, $items, $userId, session()->getId() ?: 'token_'.$userId.'_'.time()));

                case 'liberar_stock_temporal':
                    $this->inv->liberarReservasPorIngreso($ingresoId);

                    return response()->json(['status' => 'ok', 'message' => 'Reservas liberadas']);
            }
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return response()->json(['status' => 'error', 'message' => 'Acción no reconocida.'], 422);
    }

    /** Genera la orden física de alistamiento (no descuenta inventario) y pasa el ingreso a ALISTADO. */
    private function guardarOrdenAlistamiento(Request $request, int $ingresoId, int $userId): JsonResponse
    {
        $dispensar = json_decode((string) $request->input('items_dispensar', '[]'), true) ?: [];
        $faltantes = json_decode((string) $request->input('items_faltantes', '[]'), true) ?: [];
        $observaciones = trim((string) $request->input('observaciones', ''));

        if ($ingresoId <= 0) {
            return response()->json(['status' => 'error', 'message' => 'ID de ingreso no válido.']);
        }
        if (! $dispensar && ! $faltantes) {
            return response()->json(['status' => 'error', 'message' => 'No hay medicamentos para procesar en la orden.']);
        }

        $ing = DB::table('ingresos')->where('id', $ingresoId)->first(['ticket_numero', 'paciente_id']);
        if (! $ing) {
            return response()->json(['status' => 'error', 'message' => 'Ingreso no encontrado.']);
        }

        $detalle = 'ENTREGA COMPLETA A SATISFACCIÓN (100% DISPENSADO)';
        if ($faltantes) {
            $detalle = "REPORTE DE MEDICAMENTOS PENDIENTES / FALTANTES (ALISTAMIENTO):\n";
            foreach ($faltantes as $f) {
                $detalle .= '• '.($f['nombre_medicamento'] ?? 'MEDICAMENTO').' - Solicitadas: '.(int) ($f['cantidad_solicitada'] ?? 0).', Faltantes: '.(int) ($f['cantidad_pendiente'] ?? 0)." unid.\n";
            }
        }

        DB::table('ingresos')->where('id', $ingresoId)->update([
            'estado_tramite' => 'ALISTADO', 'faltantes_alistamiento' => $detalle, 'observaciones_pendientes' => $detalle,
            'alistado_por_user_id' => $userId, 'fecha_alistado' => now(), 'updated_at' => now(),
        ]);

        $ia = DB::table('ingreso_formulas_ia')->where('ingreso_id', $ingresoId)->orderByDesc('id')->first(['id', 'datos_extraidos_json']);
        if ($ia) {
            $datos = json_decode($ia->datos_extraidos_json ?? '{}', true) ?: [];
            $datos['alistamiento'] = [
                'items_dispensar' => $dispensar, 'items_faltantes' => $faltantes, 'observaciones' => $observaciones,
                'fecha_alistado' => date('Y-m-d H:i:s'), 'alistado_por' => $userId,
            ];
            DB::table('ingreso_formulas_ia')->where('id', $ia->id)->update([
                'estado_ia' => 'ALISTADO', 'datos_extraidos_json' => json_encode($datos, JSON_UNESCAPED_UNICODE),
            ]);

            try {
                (new TratamientoCronico())->programarEntregasFuturas($ing->paciente_id, $ingresoId, $datos, 'DOMICILIO');
            } catch (\Throwable $e) {
                logger()->error('Error programando multimes en dispensación: '.$e->getMessage());
            }
        }

        $this->inv->consolidarReservas($ingresoId);
        registrar_log_auditoria('alistamiento', 'ORDEN_ALISTAMIENTO', $ingresoId, "Ticket {$ing->ticket_numero}");

        return response()->json([
            'status' => 'ok', 'message' => '¡Orden de Alistamiento Físico Generada con Éxito!', 'ingreso_id' => $ingresoId,
            'ticket_numero' => $ing->ticket_numero,
            'imprimir_url' => route('alistamiento.orden_unificada', ['ingreso' => $ingresoId, 'auto_print' => 1]),
            'items_dispensar' => $dispensar, 'items_faltantes' => $faltantes,
        ]);
    }
}
