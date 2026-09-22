<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Modelo de Gestión de Tratamientos Crónicos y Entregas Multimes Programadas (SISPAM)
 */
class TratamientoCronico {
    private $db;

    public function __construct() {
        $this->db = DB::connection()->getPdo();
    }

    /**
     * Parsea cualquier formato de fecha en español o ISO a un formato estándar YYYY-MM-DD
     */
    public static function parsearFechaFormula($fechaRaw) {
        if (empty($fechaRaw)) {
            return date('Y-m-d');
        }
        $fechaRaw = trim($fechaRaw);
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/', $fechaRaw, $m)) {
            $anio = intval($m[1]);
            $mes = str_pad(intval($m[2]), 2, '0', STR_PAD_LEFT);
            $dia = str_pad(intval($m[3]), 2, '0', STR_PAD_LEFT);
            return "{$anio}-{$mes}-{$dia}";
        }
        $meses = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12'
        ];
        if (preg_match('/(\d{1,2})\s+de\s+([a-zA-ZáéíóúÁÉÍÓÚ]+)\s+de\s+(\d{4})/i', $fechaRaw, $m)) {
            $dia = str_pad(intval($m[1]), 2, '0', STR_PAD_LEFT);
            $mesNom = mb_strtolower(trim($m[2]), 'UTF-8');
            $mesNum = $meses[$mesNom] ?? '01';
            $anio = intval($m[3]);
            return "{$anio}-{$mesNum}-{$dia}";
        }
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $fechaRaw, $m)) {
            $dia = str_pad(intval($m[1]), 2, '0', STR_PAD_LEFT);
            $mes = str_pad(intval($m[2]), 2, '0', STR_PAD_LEFT);
            $anio = intval($m[3]);
            if ($anio < 100) $anio += 2000;
            return "{$anio}-{$mes}-{$dia}";
        }
        $ts = strtotime($fechaRaw);
        if ($ts !== false && intval(date('Y', $ts)) >= 2000) {
            return date('Y-m-d', $ts);
        }
        return date('Y-m-d');
    }

    /**
     * Programa automáticamente las entregas posteriores (Periodo 2, 3... N) a partir de una fórmula multimes
     */
    public function programarEntregasFuturas($pacienteId, $ingresoId, $datosIA, $modalidadDefault = 'DOMICILIO') {
        if (empty($pacienteId) || empty($datosIA) || empty($datosIA['medicamentos'])) {
            return false;
        }

        // GUARDIA DE IDEMPOTENCIA: Limpiar programaciones previas no despachadas de este mismo ingreso_id
        // para evitar que múltiples llamadas (ej: IA + Entrega) dupliquen los periodos
        if (!empty($ingresoId)) {
            $stmtDel = $this->db->prepare("DELETE FROM tratamientos_cronicos_entregas WHERE ingreso_origen_id = :id AND estado = 'PROGRAMADO'");
            $stmtDel->execute([':id' => intval($ingresoId)]);
        }

        // Obtener datos de residencia del paciente para el domicilio
        $stmtP = $this->db->prepare("SELECT * FROM pacientes WHERE id = :id");
        $stmtP->execute([':id' => intval($pacienteId)]);
        $paciente = $stmtP->fetch(PDO::FETCH_ASSOC);

        $direccion = $paciente['direccion_residencia'] ?? '';
        $telefono = !empty($paciente['telefono']) ? $paciente['telefono'] : ($paciente['numero_celular'] ?? '');
        $formulaNum = !empty($datosIA['paciente']['episodio']) ? $datosIA['paciente']['episodio'] : (!empty($datosIA['paciente']['formula_numero']) ? $datosIA['paciente']['formula_numero'] : 'F-' . ($ingresoId ?: date('Ymd')));
        $fechaFormula = self::parsearFechaFormula($datosIA['paciente']['fecha_formula'] ?? '');

        $entregasCreadas = 0;

        foreach ($datosIA['medicamentos'] as $med) {
            $codigo = $med['codigo'] ?? '';
            $nombre = $med['descripcion'] ?? ($med['nombre_medicamento'] ?? '');
            $posologiaCompleta = trim(($med['dosis'] ?? '') . ' ' . ($med['frecuencia'] ?? '') . ' ' . ($med['duracion'] ?? '') . ' ' . ($med['observaciones'] ?? ''));
            $duracionTxt = mb_strtolower($posologiaCompleta, 'UTF-8');
            $frecTxt = mb_strtolower(($med['dosis'] ?? '') . ' ' . ($med['frecuencia'] ?? ''), 'UTF-8');

            // 1. Determinar dosis y consumo mensual esperado a partir de la posología clínica real
            $dosisDiaria = 1;
            $consumoMensualEsperado = 30;

            if (preg_match('/cada\s*6\s*horas?/iu', $frecTxt) || preg_match('/4\s*(?:veces|tabletas?|capsulas?|comprimidos?)\s*(?:al|por)\s*d[ií]a/iu', $frecTxt)) {
                $dosisDiaria = 4;
                $consumoMensualEsperado = 120;
            } elseif (preg_match('/cada\s*8\s*horas?/iu', $frecTxt) || preg_match('/3\s*(?:veces|tabletas?|capsulas?|comprimidos?)\s*(?:al|por)\s*d[ií]a/iu', $frecTxt)) {
                $dosisDiaria = 3;
                $consumoMensualEsperado = 90;
            } elseif (preg_match('/cada\s*12\s*horas?/iu', $frecTxt) || preg_match('/2\s*(?:veces|tabletas?|capsulas?|comprimidos?)\s*(?:al|por)\s*d[ií]a/iu', $frecTxt)) {
                $dosisDiaria = 2;
                $consumoMensualEsperado = 60;
            } elseif (preg_match('/cada\s*48\s*horas?/iu', $frecTxt) || preg_match('/d[ií]a\s*de\s*por\s*medio/iu', $frecTxt) || preg_match('/cada\s*2\s*d[ií]as?/iu', $frecTxt)) {
                $dosisDiaria = 0.5;
                $consumoMensualEsperado = 15;
            } elseif (preg_match('/cada\s*(?:semana|7\s*d[ií]as?)/iu', $frecTxt) || preg_match('/semanal(?:mente)?/iu', $frecTxt)) {
                $dosisDiaria = 1 / 7;
                $consumoMensualEsperado = 4;
            } elseif (preg_match('/cada\s*(?:2\s*semanas?|14\s*d[ií]as?)/iu', $frecTxt) || preg_match('/quincenal(?:mente)?/iu', $frecTxt)) {
                $dosisDiaria = 1 / 14;
                $consumoMensualEsperado = 2;
            } elseif (preg_match('/cada\s*(?:3\s*semanas?|21\s*d[ií]as?)/iu', $frecTxt)) {
                $dosisDiaria = 1 / 21;
                $consumoMensualEsperado = 2;
            } elseif (preg_match('/cada\s*(?:1\s*d[ií]as?|d[ií]a)|diari(?:o|amente)|cada\s*24\s*horas?|(?:1|una?)\s*(?:vez|tableta|capsula|comprimido)?\s*(?:al|por)\s*d[ií]a/iu', $frecTxt)) {
                $dosisDiaria = 1;
                $consumoMensualEsperado = 30;
            } elseif ((preg_match('/cada\s*(?:mes|4\s*semanas?|28\s*d[ií]as?|30\s*d[ií]as?)/iu', $frecTxt) || preg_match('/mensual(?:mente)?/iu', $frecTxt))
                      && !preg_match('/entrega|despacho|reclamar|formula/iu', $frecTxt)) {
                $dosisDiaria = 1 / 30;
                $consumoMensualEsperado = 1;
            } else {
                $dosisDiaria = 1;
                $consumoMensualEsperado = 30;
            }

            $esMultimesMed = !empty($med['es_multimes']);
            $totalPeriodosMed = intval($med['total_periodos'] ?? 1);
            $cantDispActual = intval($med['cantidad_periodo_actual'] ?? 0) ?: intval($med['cantidad_dispensar'] ?? 0);
            $cantTotal = intval($med['cantidad_total_prescrita'] ?? ($med['cantidad_solicitada'] ?? 0));

            $mesesMed = 1;
            if ($totalPeriodosMed > 1) {
                $mesesMed = $totalPeriodosMed;
            } elseif ($esMultimesMed) {
                $mesesMed = max(2, (int)round(($cantTotal ?: 90) / ($cantDispActual ?: 30)));
            } else {
                if (preg_match('/(?:1\s*a[ñn]o|365\s*d[ií]as?|360\s*d[ií]as?|anual(?:mente)?)/iu', $duracionTxt)) {
                    $mesesMed = 12;
                } elseif (preg_match('/(\d+)\s*mes(?:es)?/iu', $duracionTxt, $mMes)) {
                    $mesesMed = intval($mMes[1]);
                } elseif (preg_match('/(\d+)\s*d[ií]as?/iu', $duracionTxt, $mDias)) {
                    $dias = intval($mDias[1]);
                    if ($dias >= 350) {
                        $mesesMed = 12;
                    } elseif ($dias > 30) {
                        $mesesMed = max(1, (int)round($dias / 30.417));
                    }
                }
            }

            // REGLA FUNDAMENTAL SISPAM: Si el tratamiento es de 1 mes o menos (ej: 10 días, 5 días, 30 días),
            // NO se programa ninguna entrega futura. Todo se entrega en el Periodo 1 en ventanilla.
            if ($mesesMed <= 1) {
                continue;
            }

            // Determinar la cantidad mensual exacta que corresponde a cada entrega programada
            if ($cantDispActual > 0) {
                $cantPorPeriodo = $cantDispActual;
            } elseif ($cantTotal > 0 && $mesesMed > 1) {
                $totalEsperado = $consumoMensualEsperado * $mesesMed;
                $distMensual   = abs($cantTotal - $consumoMensualEsperado);
                $distTotal     = abs($cantTotal - $totalEsperado);

                if ($distMensual <= max(1, (int)round($consumoMensualEsperado * 0.25)) && $cantTotal < $totalEsperado) {
                    $cantPorPeriodo = $cantTotal;
                } elseif ($distTotal <= max(1, (int)round($totalEsperado * 0.25)) || $cantTotal >= $totalEsperado || abs($cantTotal - 365) <= 5) {
                    $cantPorPeriodo = $consumoMensualEsperado;
                } elseif ($cantTotal > $consumoMensualEsperado) {
                    $cantPorPeriodo = (int)ceil($cantTotal / $mesesMed);
                } else {
                    $cantPorPeriodo = $cantTotal;
                }
            } else {
                $cantPorPeriodo = $consumoMensualEsperado;
            }
            if ($cantPorPeriodo <= 0) $cantPorPeriodo = $consumoMensualEsperado;

            // Crear las entregas para los periodos siguientes (Periodo 2 en adelante)
            for ($p = 2; $p <= $mesesMed; $p++) {
                $diasSumar = ($p - 1) * 30;
                $fechaProg = date('Y-m-d', strtotime($fechaFormula . " +{$diasSumar} days"));

                $stmtIns = $this->db->prepare("
                    INSERT INTO tratamientos_cronicos_entregas
                    (paciente_id, ingreso_origen_id, formula_numero, medicamento_codigo, medicamento_nombre, posologia, periodo_numero, total_periodos, cantidad_periodo, fecha_programada, modalidad, estado, direccion_entrega, telefono_contacto)
                    VALUES
                    (:pid, :ingreso_id, :fn, :cod, :med, :pos, :per, :tot, :cant, :fecha, :mod, 'PROGRAMADO', :dir, :tel)
                ");
                $stmtIns->execute([
                    ':pid'        => $pacienteId,
                    ':ingreso_id' => $ingresoId,
                    ':fn'         => $formulaNum,
                    ':cod'        => $codigo,
                    ':med'        => $nombre,
                    ':pos'        => $posologiaCompleta,
                    ':per'        => $p,
                    ':tot'        => $mesesMed,
                    ':cant'       => $cantPorPeriodo,
                    ':fecha'      => $fechaProg,
                    ':mod'        => $modalidadDefault,
                    ':dir'        => $direccion,
                    ':tel'        => $telefono
                ]);
                $entregasCreadas++;
            }
        }

        return $entregasCreadas;
    }

    /**
     * Obtiene las entregas programadas pendientes de un paciente (para alertas presenciales en turnero/ingreso)
     */
    public function getEntregasPendientesPaciente($pacienteId) {
        if (empty($pacienteId)) return [];

        $stmt = $this->db->prepare("
            SELECT t.*, 
                   COALESCE(NULLIF(CONCAT(TRIM(COALESCE(p.primer_nombre,'')), ' ', TRIM(COALESCE(p.primer_apellido,''))), ''), p.nombres, 'Paciente') as nombres,
                   p.primer_nombre, p.primer_apellido, p.numero_documento, p.tipo_documento
            FROM tratamientos_cronicos_entregas t
            INNER JOIN pacientes p ON t.paciente_id = p.id
            WHERE t.paciente_id = :pid
              AND t.estado IN ('PROGRAMADO', 'EN_ALISTAMIENTO')
            ORDER BY t.fecha_programada ASC, t.periodo_numero ASC
        ");
        $stmt->execute([':pid' => intval($pacienteId)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el listado general de entregas programadas con filtros (para la bandeja de logística y domicilios)
     */
    public function getEntregasProgramadas($filtros = []) {
        $sql = "
            SELECT t.*, 
                   COALESCE(NULLIF(CONCAT(TRIM(COALESCE(p.primer_nombre,'')), ' ', TRIM(COALESCE(p.primer_apellido,''))), ''), p.nombres, 'Paciente') as nombres,
                   p.apellidos, p.numero_documento, p.tipo_documento, p.direccion_residencia, p.telefono, p.numero_celular as celular, p.ciudad_residencia,
                   COALESCE(p.eps_nombre, 'SAVIA SALUD EPS') as eps_nombre,
                   COALESCE(s.nombre_sede, 'Sede Principal') as sede_nombre
            FROM tratamientos_cronicos_entregas t
            INNER JOIN pacientes p ON t.paciente_id = p.id
            LEFT JOIN ingresos i ON t.ingreso_origen_id = i.id
            LEFT JOIN sedes s ON i.sede_id = s.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filtros['estado'])) {
            $sql .= " AND t.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }

        if (!empty($filtros['modalidad'])) {
            $sql .= " AND t.modalidad = :modalidad";
            $params[':modalidad'] = $filtros['modalidad'];
        }

        if (!empty($filtros['rango_fecha'])) {
            if ($filtros['rango_fecha'] === 'hoy') {
                $sql .= " AND t.fecha_programada = CURDATE()";
            } else if ($filtros['rango_fecha'] === 'semana') {
                $sql .= " AND t.fecha_programada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            } else if ($filtros['rango_fecha'] === 'vencidas') {
                $sql .= " AND t.fecha_programada < CURDATE() AND t.estado = 'PROGRAMADO'";
            } else if ($filtros['rango_fecha'] === 'futuras') {
                $sql .= " AND t.fecha_programada > DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
            }
        }

        if (!empty($filtros['buscar'])) {
            $q = '%' . trim($filtros['buscar']) . '%';
            $sql .= " AND (p.numero_documento LIKE :q1 OR p.primer_nombre LIKE :q2 OR p.primer_apellido LIKE :q3 OR p.nombres LIKE :q4 OR t.medicamento_nombre LIKE :q5 OR t.formula_numero LIKE :q6)";
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
            $params[':q4'] = $q;
            $params[':q5'] = $q;
            $params[':q6'] = $q;
        }

        $sql .= " ORDER BY 
            CASE 
                WHEN t.estado IN ('ENTREGADO_PRESENCIAL', 'DESPACHADO_DOMICILIO', 'ENTREGADO') THEN 5
                WHEN t.fecha_programada < CURDATE() AND t.estado = 'PROGRAMADO' THEN 1
                WHEN t.fecha_programada = CURDATE() AND t.estado = 'PROGRAMADO' THEN 2
                WHEN t.fecha_programada <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND t.estado = 'PROGRAMADO' THEN 3
                ELSE 4
            END ASC,
            t.fecha_programada ASC,
            t.paciente_id ASC,
            t.periodo_numero ASC,
            t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marca una entrega programada como entregada de forma presencial
     */
    public function marcarEntregaPresencial($entregaId, $ingresoActualId = null, $usuarioId = null) {
        $stmt = $this->db->prepare("
            UPDATE tratamientos_cronicos_entregas SET
                estado = 'ENTREGADO_PRESENCIAL',
                modalidad = 'PRESENCIAL',
                ingreso_entrega_id = :ingreso_id,
                usuario_despacho_id = :usuario_id,
                fecha_despacho = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':ingreso_id' => $ingresoActualId,
            ':usuario_id' => $usuarioId ?: (sesion('user_id') ?? null),
            ':id'         => intval($entregaId)
        ]);
    }

    /**
     * Actualiza el estado y guía de un despacho a domicilio programado
     */
    public function actualizarEstadoDomicilio($entregaId, $estado, $guia = '', $mensajeria = '', $obs = '', $usuarioId = null) {
        $stmt = $this->db->prepare("
            UPDATE tratamientos_cronicos_entregas SET
                estado = :estado,
                guia_domicilio = :guia,
                empresa_mensajeria = :mensajeria,
                observaciones = :obs,
                usuario_despacho_id = :usuario_id,
                fecha_despacho = IF(:estado_check = 'DESPACHADO_DOMICILIO', NOW(), fecha_despacho)
            WHERE id = :id
        ");
        return $stmt->execute([
            ':estado'       => $estado,
            ':estado_check' => $estado,
            ':guia'         => $guia,
            ':mensajeria'   => $mensajeria,
            ':obs'          => $obs,
            ':usuario_id'   => $usuarioId ?: (sesion('user_id') ?? null),
            ':id'           => intval($entregaId)
        ]);
    }

    /**
     * Cambia la modalidad de entrega de un tratamiento crónico (DOMICILIO <-> PRESENCIAL)
     */
    public function cambiarModalidad($entregaId, $nuevaModalidad, $obs = '') {
        $stmt = $this->db->prepare("
            UPDATE tratamientos_cronicos_entregas SET
                modalidad = :modalidad,
                observaciones = CONCAT(COALESCE(observaciones, ''), ' | Modalidad cambiada a ', :mod_txt, ' el ', NOW())
            WHERE id = :id
        ");
        return $stmt->execute([
            ':modalidad' => in_array($nuevaModalidad, ['DOMICILIO', 'PRESENCIAL']) ? $nuevaModalidad : 'DOMICILIO',
            ':mod_txt'   => $nuevaModalidad,
            ':id'        => intval($entregaId)
        ]);
    }

    /**
     * Reprograma la fecha de entrega, modalidad y datos de contacto de una entrega crónica
     */
    public function reprogramarEntrega($entregaId, $nuevaFecha, $modalidad, $direccion = '', $telefono = '', $obs = '') {
        $fechaLimpia = self::parsearFechaFormula($nuevaFecha);
        $stmt = $this->db->prepare("
            UPDATE tratamientos_cronicos_entregas SET
                fecha_programada = :fecha,
                modalidad = :modalidad,
                direccion_entrega = IF(:dir != '', :dir_val, direccion_entrega),
                telefono_contacto = IF(:tel != '', :tel_val, telefono_contacto),
                observaciones = CONCAT(COALESCE(observaciones, ''), IF(:obs != '', CONCAT(' | ', :obs_val), ''))
            WHERE id = :id
        ");
        return $stmt->execute([
            ':fecha'     => $fechaLimpia,
            ':modalidad' => in_array($modalidad, ['DOMICILIO', 'PRESENCIAL']) ? $modalidad : 'DOMICILIO',
            ':dir'       => trim($direccion),
            ':dir_val'   => trim($direccion),
            ':tel'       => trim($telefono),
            ':tel_val'   => trim($telefono),
            ':obs'       => trim($obs),
            ':obs_val'   => trim($obs),
            ':id'        => intval($entregaId)
        ]);
    }

    /**
     * Conteo rápido de entregas programadas para hoy y la semana
     */
    public function getMetricasEntregas() {
        $stats = [
            'vencidas' => 0,
            'hoy' => 0,
            'semana' => 0,
            'futuras' => 0,
            'domicilios_pendientes' => 0,
            'entregadas_mes' => 0
        ];

        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM tratamientos_cronicos_entregas WHERE fecha_programada < CURDATE() AND estado IN ('PROGRAMADO', 'EN_ALISTAMIENTO')");
            $stats['vencidas'] = intval($stmt->fetchColumn());

            $stmt = $this->db->query("SELECT COUNT(*) FROM tratamientos_cronicos_entregas WHERE fecha_programada = CURDATE() AND estado IN ('PROGRAMADO', 'EN_ALISTAMIENTO')");
            $stats['hoy'] = intval($stmt->fetchColumn());

            $stmt = $this->db->query("SELECT COUNT(*) FROM tratamientos_cronicos_entregas WHERE fecha_programada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND estado IN ('PROGRAMADO', 'EN_ALISTAMIENTO')");
            $stats['semana'] = intval($stmt->fetchColumn());

            $stmt = $this->db->query("SELECT COUNT(*) FROM tratamientos_cronicos_entregas WHERE fecha_programada > DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND estado IN ('PROGRAMADO', 'EN_ALISTAMIENTO')");
            $stats['futuras'] = intval($stmt->fetchColumn());

            $stmt = $this->db->query("SELECT COUNT(*) FROM tratamientos_cronicos_entregas WHERE modalidad = 'DOMICILIO' AND estado IN ('PROGRAMADO', 'EN_ALISTAMIENTO')");
            $stats['domicilios_pendientes'] = intval($stmt->fetchColumn());

            $stmt = $this->db->query("SELECT COUNT(*) FROM tratamientos_cronicos_entregas WHERE estado IN ('ENTREGADO_PRESENCIAL', 'DESPACHADO_DOMICILIO') AND MONTH(fecha_despacho) = MONTH(CURDATE()) AND YEAR(fecha_despacho) = YEAR(CURDATE())");
            $stats['entregadas_mes'] = intval($stmt->fetchColumn());
        } catch (Exception $e) {}

        return $stats;
    }
}