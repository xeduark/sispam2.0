<?php

namespace App\Models;

/**
 * Constantes y registro centralizado de eventos de atención (equivalente a la
 * clase Auditoria del sistema nativo).
 */
class Auditoria
{
    public const ATENCION_ABIERTA = 'ATENCION_ABIERTA';
    public const DERECHOS_VALIDADOS = 'DERECHOS_VALIDADOS';
    public const INGRESO_CREADO = 'INGRESO_CREADO';
    public const DOCUMENTO_ESCANEADO = 'DOCUMENTO_ESCANEADO';
    public const TRANSCRIPCION = 'TRANSCRIPCION';
    public const ALISTAMIENTO = 'ALISTAMIENTO';
    public const ENTREGA = 'ENTREGA';

    public const EXITO = 'EXITO';
    public const OMITIDO = 'OMITIDO';
    public const FALLO = 'FALLO';
    public const ADVERTENCIA = 'ADVERTENCIA';
    public const INFO = 'INFO';

    public static function registrar(string $evento, array $datos = []): bool
    {
        $modulo = $datos['modulo'] ?? ($datos['entidad_tipo'] ?? 'INGRESO');
        $registroId = $datos['atencion_id'] ?? ($datos['entidad_id'] ?? ($datos['registro_id'] ?? null));

        return AuditLog::registrar($modulo, $evento, $registroId, array_merge(['evento' => $evento], $datos));
    }
}
