<?php

namespace App\Services;

use App\Models\Paciente;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente del API SISPAM de Qrystalos (ver database/example/API_SISPAM.md).
 *
 * Contrato confirmado por prueba real contra api-test.qrystalos.com:
 * - Los campos de catálogo van con código pelado ("N", "5", "29"...), no
 *   "código - descripción" (ese formato es de la herramienta de carga masiva
 *   por Excel de Qrystalos, no de este API JSON).
 * - TIPOUSUARIO usa códigos numéricos (01/02/03), no la letra del ejemplo
 *   del doc público.
 */
class QrystalosService
{
    public function baseUrl(): string
    {
        return config('qrystalos.env') === 'production'
            ? config('qrystalos.production_url')
            : config('qrystalos.test_url');
    }

    /**
     * Envía INSERTAR (upsert) para el paciente. Nunca lanza excepción de
     * negocio: un KO de Qrystalos no debe impedir que el ingreso quede
     * guardado en SISPAM. Devuelve ['ok' => bool, 'mensaje' => string,
     * 'errores' => string[]].
     */
    public function insertarPaciente(Paciente $paciente): array
    {
        try {
            $response = Http::withBasicAuth(config('qrystalos.auth_user'), config('qrystalos.auth_pass'))
                ->acceptJson()
                ->timeout(15)
                ->post($this->baseUrl().'json/', [
                    'MODELO' => 'SISPAM',
                    'METODO' => 'INSERTAR',
                    'USUARIO' => config('qrystalos.usuario'),
                    'PARAMETROS' => $this->armarParametros($paciente),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Qrystalos: fallo de red al enviar paciente', ['paciente_id' => $paciente->id, 'error' => $e->getMessage()]);

            return $this->guardarResultado($paciente, false, null, ['Error de red: '.$e->getMessage()]);
        }

        if (! $response->successful()) {
            return $this->guardarResultado($paciente, false, null, ['HTTP '.$response->status().': '.$response->body()]);
        }

        $recordsets = $response->json('result.recordsets', []);
        $primero = $recordsets[0][0] ?? [];
        $ok = ($primero['OK'] ?? 'KO') === 'OK';
        $consecutivo = $primero['CONSECUTIVO'] ?? null;

        $errores = [];
        foreach ($recordsets[1] ?? [] as $fila) {
            if (isset($fila['ERROR'])) {
                $errores[] = $fila['ERROR'];
            }
        }

        return $this->guardarResultado($paciente, $ok, $consecutivo, $errores, $primero['MENSAJE'] ?? null);
    }

    private function guardarResultado(Paciente $paciente, bool $ok, ?string $consecutivo, array $errores, ?string $mensaje = null): array
    {
        $paciente->update([
            'qrystalos_id_afiliado' => $consecutivo ?: $paciente->qrystalos_id_afiliado,
            'qrystalos_synced_at' => $ok ? now() : $paciente->qrystalos_synced_at,
            'qrystalos_last_error' => $ok ? null : implode(' | ', $errores),
        ]);

        return ['ok' => $ok, 'mensaje' => $mensaje, 'errores' => $errores];
    }

    private function armarParametros(Paciente $p): array
    {
        return array_filter([
            'TIPO_DOC' => $p->tipo_documento,
            'DOCIDAFILIADO' => $p->numero_documento,
            'FNACIMIENTO' => optional($p->fecha_nacimiento)->format('Y-m-d'),
            'PAPELLIDO' => $p->primer_apellido ?: $p->apellidos,
            'SAPELLIDO' => $p->segundo_apellido,
            'PNOMBRE' => $p->primer_nombre ?: $p->nombres,
            'SNOMBRE' => $p->segundo_nombre,
            // Qrystalos no tiene código para "Indeterminado o Intersexual": se fuerza a Masculino/Femenino.
            'SEXO' => in_array($p->sexo, ['Masculino', 'Femenino'], true) ? $p->sexo : 'Masculino',
            'ESTADO_CIVIL' => $p->estado_civil,
            'GRUPOPOB' => $p->grupo_poblacional,
            'GRUPOETNICO' => $p->grupo_etnico,
            'TIPODISCAPACIDAD' => $p->tipo_discapacidad,
            'IDESCOLARIDAD' => $p->tipo_escolaridad,
            'DIRECCION' => $p->direccion_residencia,
            'CELULAR' => $p->numero_celular,
            'PREFIJO_CELULAR' => $p->indicativo_1,
            'TELEFONORES' => $p->otro_telefono ?: '',
            'PREFIJO_TELEFONORES' => $p->indicativo_2,
            'EMAIL' => $p->email,
            'CIUDAD' => $p->qrystalos_idciudad,
            'ZONA' => $p->zona === 'Rural' ? 'R' : 'U',
            'IDBARRIO' => $p->qrystalos_idbarrio,
            'IDADMINISTRADORA' => $p->qrystalos_idadministradora,
            'IDPLAN' => $p->qrystalos_idplan,
            // TODO Qrystalos: catálogo real de NIVELSOCIOEC no confirmado; se manda el estrato (1-6) como mejor aproximación.
            'NIVELSOCIOEC' => (string) ($p->estrato_socioeconomico ?: 3),
            // TODO Qrystalos: solo confirmado 01/02/03 para régimen contributivo.
            'TIPOUSUARIO' => config("qrystalos.tipo_usuario_por_afiliado.{$p->tipo_afiliado}", '01'),
            'IDSEDE' => $p->qrystalos_idsede,
            'ESTADO' => 'Activo',
            'FECHAAFILIACION' => optional($p->fecha_afiliacion)->format('Y-m-d'),
            'PROCEDENCIA' => 'SISPAM',
            // TODO Qrystalos: estos 3 campos NO existen en su documentación (§5.1 no los
            // lista y §8 no lista sus mensajes de error), pero su backend los exige. Se
            // probaron ~40 variantes de nombre sin que reconociera ninguna. Comprobado
            // además que el ejemplo oficial del propio doc, enviado literal, también es
            // rechazado por estos 3 campos: la validación es de ellos y está sin documentar.
            // Hasta que confirmen el nombre real, el KO no bloquea el guardado en SISPAM.
            'NOMBRE_CONTACTO_EMERGENCIA' => $p->contacto_emergencia_nombre,
            'TELEFONO_CONTACTO_EMERGENCIA' => $p->contacto_emergencia_telefono,
            'PARENTESCO_CONTACTO_EMERGENCIA' => $p->contacto_emergencia_parentesco,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
