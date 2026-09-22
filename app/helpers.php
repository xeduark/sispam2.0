<?php

if (! function_exists('get_estado_badge')) {
    function get_estado_badge(?string $estado): string
    {
        return match ($estado) {
            'INGRESADO' => '<span class="badge bg-secondary"><i class="fa-solid fa-user-clock me-1"></i> Ingresado</span>',
            'EN_TRANSCRIPCION' => '<span class="badge bg-primary"><i class="fa-solid fa-keyboard me-1"></i> En Transcripción</span>',
            'TRANSCRITO', 'TRANSCRITO_COMPLETO' => '<span class="badge text-white" style="background-color: #6f42c1;"><i class="fa-solid fa-file-pen me-1"></i> Transcrito (Por Verificar)</span>',
            'VERIFICADO', 'VERIFICADA' => '<span class="badge bg-success"><i class="fa-solid fa-user-check me-1"></i> Verificado (Alistamiento)</span>',
            'CON_ERRORES', 'CON_ERRORES_TRANSCRIPCION' => '<span class="badge bg-danger"><i class="fa-solid fa-circle-exclamation me-1"></i> Error Transcripción</span>',
            'TRANSCRITO_PENDIENTE' => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Con Pendientes</span>',
            'SIN_STOCK' => '<span class="badge bg-danger"><i class="fa-solid fa-boxes-packing me-1"></i> Sin Stock</span>',
            'ALISTADO', 'GESTIONADO', 'ESPERA_ENTREGA' => '<span class="badge bg-info text-dark"><i class="fa-solid fa-box-open me-1"></i> Alistado / En Espera Entrega</span>',
            'EN_ENTREGA' => '<span class="badge bg-warning text-dark"><i class="fa-solid fa-person-walking-arrow-right me-1"></i> Llamado a Ventanilla</span>',
            'ENTREGADO' => '<span class="badge bg-dark"><i class="fa-solid fa-square-check me-1"></i> Entregado</span>',
            'CANCELADO' => '<span class="badge bg-secondary bg-opacity-25 text-secondary">Cancelado</span>',
            default => '<span class="badge bg-secondary bg-opacity-25 text-secondary">'.e($estado).'</span>',
        };
    }
}

if (! function_exists('get_prioridad_badge')) {
    function get_prioridad_badge(?string $prioridad): string
    {
        return match ($prioridad) {
            'TERCERA_EDAD' => '<span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-person-cane me-1"></i> 👴 Tercera Edad</span>',
            'EMBARAZADA' => '<span class="badge bg-danger text-white fw-bold"><i class="fa-solid fa-person-pregnant me-1"></i> 🤰 Embarazada</span>',
            'DISCAPACIDAD' => '<span class="badge bg-info text-dark fw-bold"><i class="fa-solid fa-wheelchair me-1"></i> ♿ Discapacidad</span>',
            'NIÑO_LACTANTE' => '<span class="badge bg-primary text-white fw-bold"><i class="fa-solid fa-baby me-1"></i> 👶 Niño / Lactante</span>',
            'OTRO_PREFERENCIAL' => '<span class="badge bg-warning text-dark fw-bold"><i class="fa-solid fa-star me-1"></i> ⭐ Preferencial</span>',
            default => '',
        };
    }
}

if (! function_exists('sesion')) {
    /**
     * Reemplazo de los antiguos $_SESSION['...'] del sistema nativo: lee el dato
     * del usuario autenticado o de la sesión de Laravel (que NO llena $_SESSION).
     */
    function sesion(string $clave, mixed $default = null): mixed
    {
        $u = auth()->user();

        return match ($clave) {
            'user_id' => $u?->id ?? $default,
            'usuario' => $u?->usuario ?? $default,
            'nombre_completo' => $u?->nombre_completo ?? $default,
            'rol_nombre' => $u?->rol?->nombre ?? $default,
            'empresa_id' => $u?->empresa_id ?? $default,
            'sede_id' => $u?->sede_id ?? $default,
            default => session($clave, $default),
        };
    }
}

if (! function_exists('registrar_log_auditoria')) {
    function registrar_log_auditoria($modulo, $accion, $registro_id = null, $detalles = null)
    {
        return \App\Models\AuditLog::registrar($modulo, $accion, $registro_id, $detalles);
    }
}

if (! function_exists('has_permission')) {
    function has_permission($module_key)
    {
        $u = auth()->user();
        if (! $u) {
            return false;
        }
        return $u->hasPermission($module_key);
    }
}

if (! function_exists('check_auth')) {
    function check_auth()
    {
        if (! auth()->check()) {
            abort(401, 'No autenticado.');
        }
    }
}

if (! function_exists('get_festivos_colombia')) {
    function get_festivos_colombia($year)
    {
        $pascua = easter_date((int)$year);

        $mover_lunes = function($mes, $dia) use ($year) {
            $t = mktime(0, 0, 0, $mes, $dia, $year);
            $dw = (int)date('N', $t);
            if ($dw === 1) return date('Y-m-d', $t);
            $diff = 8 - $dw;
            return date('Y-m-d', strtotime("+{$diff} days", $t));
        };

        $festivos = [];
        $festivos[] = sprintf('%04d-01-01', $year);
        $festivos[] = sprintf('%04d-05-01', $year);
        $festivos[] = sprintf('%04d-07-20', $year);
        $festivos[] = sprintf('%04d-08-07', $year);
        $festivos[] = sprintf('%04d-12-08', $year);
        $festivos[] = sprintf('%04d-12-25', $year);

        $festivos[] = $mover_lunes(1, 6);
        $festivos[] = $mover_lunes(3, 19);
        $festivos[] = $mover_lunes(6, 29);
        $festivos[] = $mover_lunes(8, 15);
        $festivos[] = $mover_lunes(10, 12);
        $festivos[] = $mover_lunes(11, 1);
        $festivos[] = $mover_lunes(11, 11);

        $festivos[] = date('Y-m-d', strtotime('-3 days', $pascua));
        $festivos[] = date('Y-m-d', strtotime('-2 days', $pascua));
        $festivos[] = date('Y-m-d', strtotime('+43 days', $pascua));
        $festivos[] = date('Y-m-d', strtotime('+64 days', $pascua));

        return $festivos;
    }
}

if (! function_exists('es_festivo_colombia')) {
    function es_festivo_colombia($fecha_str)
    {
        $year = (int)date('Y', strtotime($fecha_str));
        $festivos = get_festivos_colombia($year);
        $soloFecha = date('Y-m-d', strtotime($fecha_str));
        return in_array($soloFecha, $festivos);
    }
}

if (! function_exists('get_horario_apertura_dia')) {
    function get_horario_apertura_dia($fecha_str, $hora_semana_custom = null, $hora_festivo_custom = null)
    {
        $dw = (int)date('N', strtotime($fecha_str));
        $esFestivo = es_festivo_colombia($fecha_str);

        $hora_semana  = !empty($hora_semana_custom) ? $hora_semana_custom : '07:00:00';
        $hora_festivo = !empty($hora_festivo_custom) ? $hora_festivo_custom : '08:00:00';

        if ($dw === 6 || $dw === 7 || $esFestivo) {
            $tipo = $esFestivo ? 'FESTIVO' : ($dw === 6 ? 'SABADO' : 'DOMINGO');
            $label = $esFestivo ? 'Día Festivo' : ($dw === 6 ? 'Sábado' : 'Domingo');
            return [
                'hora' => $hora_festivo,
                'tipo_dia' => $tipo,
                'es_festivo_o_finde' => true,
                'label' => "{$label} (Apertura " . date('h:i A', strtotime($hora_festivo)) . ")"
            ];
        }

        return [
            'hora' => $hora_semana,
            'tipo_dia' => 'HABIL',
            'es_festivo_o_finde' => false,
            'label' => "Lunes a Viernes (Apertura " . date('h:i A', strtotime($hora_semana)) . ")"
        ];
    }
}


if (! function_exists('getUrlPaginacionRep')) {
    /** URL del reporte actual conservando filtros, con otra página (y opcionalmente otro tamaño). */
    function getUrlPaginacionRep($pageNum, $porPag = null): string
    {
        $params = request()->query();
        $params['pagina'] = $pageNum;
        if ($porPag !== null) {
            $params['por_pagina'] = $porPag;
        }

        return route('reportes.index').'?'.http_build_query($params);
    }
}

if (! function_exists('renderPaginacionReporte')) {
    /** Pie de tabla de los reportes: rango mostrado y paginador. Devuelve HTML. */
    function renderPaginacionReporte($pagina, $totalPaginas, $totalRegistros, $por_pagina, $nombreItems = 'registros'): string
    {
        if ($totalRegistros <= 0) {
            return '';
        }

        $inicio = (($pagina - 1) * $por_pagina) + 1;
        $fin = min($pagina * $por_pagina, $totalRegistros);
        $html = '<div class="card-footer border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">'
            .'<div class="small text-muted">Mostrando '.e($nombreItems).' del <strong>'.number_format($inicio).'</strong> al <strong>'.number_format($fin)
            .'</strong> de <strong>'.number_format($totalRegistros).'</strong> en total</div>';

        if ($totalPaginas > 1) {
            $item = fn ($n, $texto, $activo = false, $deshabilitado = false) => '<li class="page-item '.($activo ? 'active' : '').($deshabilitado ? ' disabled' : '').'">'
                .($deshabilitado && $texto === '...' ? '<span class="page-link">...</span>' : '<a class="page-link" href="'.e(getUrlPaginacionRep($n)).'">'.$texto.'</a>').'</li>';

            $ini = max(1, $pagina - 2);
            $fn = min($totalPaginas, $ini + 4);
            if ($fn - $ini < 4) {
                $ini = max(1, $fn - 4);
            }

            $html .= '<nav aria-label="Paginación de resultados"><ul class="pagination pagination-sm mb-0 shadow-sm">'
                .$item(max(1, $pagina - 1), '&laquo; Anterior', false, $pagina <= 1);

            if ($ini > 1) {
                $html .= $item(1, '1');
                $html .= $ini > 2 ? $item(0, '...', false, true) : '';
            }
            for ($p = $ini; $p <= $fn; $p++) {
                $html .= $item($p, (string) $p, $p == $pagina);
            }
            if ($fn < $totalPaginas) {
                $html .= $fn < $totalPaginas - 1 ? $item(0, '...', false, true) : '';
                $html .= $item($totalPaginas, (string) $totalPaginas);
            }

            $html .= $item(min($totalPaginas, $pagina + 1), 'Siguiente &raquo;', false, $pagina >= $totalPaginas).'</ul></nav>';
        }

        return $html.'</div>';
    }
}

if (! function_exists('formatear_minutos_horas')) {
    /** Convierte minutos a "2h 05m" / "35 min"; opcionalmente agrega el total en minutos. */
    function formatear_minutos_horas($minutos, $mostrar_min_secundario = false): string
    {
        if ($minutos === null || $minutos === '' || $minutos === false) {
            return '---';
        }

        $total = round((float) $minutos, 1);
        if ($total <= 0) {
            return '0 min';
        }

        $horas = (int) floor($total / 60);
        $mins = (int) round(fmod($total, 60));
        if ($mins === 60) {
            $horas++;
            $mins = 0;
        }

        $texto = $horas > 0 ? "{$horas}h ".str_pad((string) $mins, 2, '0', STR_PAD_LEFT).'m' : "{$mins} min";

        return $mostrar_min_secundario && $horas > 0 ? "{$texto} ({$total} min)" : $texto;
    }
}

if (! function_exists('buildKardexUrl')) {
    function buildKardexUrl($page, $perPage, $bodega, $tipo, $buscar, $desde, $hasta): string
    {
        $params = ['p' => $page, 'per_page' => $perPage];
        foreach (['bodega_id' => $bodega, 'tipo_movimiento' => $tipo, 'buscar' => $buscar, 'fecha_desde' => $desde, 'fecha_hasta' => $hasta] as $k => $v) {
            if ($v !== null && $v !== '') {
                $params[$k] = $v;
            }
        }

        return route('inventario.kardex').'?'.http_build_query($params);
    }
}
