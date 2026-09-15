<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_sgsss' => 'date',
            'fecha_afiliacion' => 'date',
            'actualiza_citas_plan' => 'boolean',
            'estrato_socioeconomico' => 'integer',
        ];
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'paciente_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim($this->nombres.' '.$this->apellidos);
    }

    public static function buscarPorDocumento(string $tipoDoc, string $numDoc): ?self
    {
        return static::where('tipo_documento', $tipoDoc)
            ->where('numero_documento', $numDoc)
            ->first();
    }

    /**
     * Upsert por tipo+número de documento, con el mismo armado de nombres y
     * los mismos valores por defecto que usaba el modelo PDO legacy.
     */
    public static function createOrUpdate(array $data): self
    {
        $primerNombre = trim($data['primer_nombre'] ?? '');
        $segundoNombre = trim($data['segundo_nombre'] ?? '');
        $primerApellido = trim($data['primer_apellido'] ?? '');
        $segundoApellido = trim($data['segundo_apellido'] ?? '');

        $nombres = trim($primerNombre.' '.$segundoNombre) ?: trim($data['nombres'] ?? '');
        $apellidos = trim($primerApellido.' '.$segundoApellido) ?: trim($data['apellidos'] ?? '');
        $celular = $data['numero_celular'] ?? ($data['telefono'] ?? null);

        $fields = [
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'fecha_nacimiento' => ! empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null,
            'ciudad_expedicion' => $data['ciudad_expedicion'] ?? 'MEDELLIN-ANT-05001',
            'estado' => $data['estado'] ?? 'Activo',
            'primer_apellido' => $primerApellido ?: $apellidos,
            'segundo_apellido' => $segundoApellido,
            'primer_nombre' => $primerNombre ?: $nombres,
            'segundo_nombre' => $segundoNombre,
            'pais_nacimiento' => $data['pais_nacimiento'] ?? 'COLOMBIA',
            'nacionalidad' => $data['nacionalidad'] ?? 'COLOMBIANA',
            'ciudad_nacimiento' => $data['ciudad_nacimiento'] ?? 'MEDELLIN-ANT-05001',
            'sexo' => $data['sexo'] ?? 'Masculino',
            'identidad_genero' => $data['identidad_genero'] ?? null,
            'estado_civil' => $data['estado_civil'] ?? 'Soltero(a)',
            'grupo_sanguineo' => $data['grupo_sanguineo'] ?? 'O+',
            'sede_atencion' => $data['sede_atencion'] ?? 'Sede Prado',
            'contacto_emergencia_nombre' => $data['contacto_emergencia_nombre'] ?? null,
            'contacto_emergencia_telefono' => $data['contacto_emergencia_telefono'] ?? null,
            'contacto_emergencia_parentesco' => $data['contacto_emergencia_parentesco'] ?? null,
            'grupo_poblacional' => $data['grupo_poblacional'] ?? 'Otro Grupo Poblacional',
            'grupo_etnico' => $data['grupo_etnico'] ?? 'No Aplica',
            'comunidad_etnica' => $data['comunidad_etnica'] ?? null,
            'tipo_discapacidad' => $data['tipo_discapacidad'] ?? 'No Aplica',
            'tipo_escolaridad' => $data['tipo_escolaridad'] ?? 'NA',
            'direccion_residencia' => $data['direccion_residencia'] ?? null,
            'indicativo_1' => $data['indicativo_1'] ?? '+57',
            'numero_celular' => $celular,
            'indicativo_2' => $data['indicativo_2'] ?? '+57',
            'otro_telefono' => $data['otro_telefono'] ?? null,
            'telefono' => $celular,
            'email' => $data['email'] ?? null,
            'ciudad_residencia' => $data['ciudad_residencia'] ?? 'MEDELLIN-ANT-05001',
            'zona' => $data['zona'] ?? 'Urbana',
            'barrio' => $data['barrio'] ?? 'El Poblado',
            'direccion_laboral' => $data['direccion_laboral'] ?? null,
            'telefono_laboral' => $data['telefono_laboral'] ?? null,
            'ocupacion' => $data['ocupacion'] ?? 'Empleado',
            'tipo_afiliado' => $data['tipo_afiliado'] ?? 'Contributivo Cotizante',
            'eps_nombre' => $data['eps_nombre'] ?? 'Particular / Sin EPS',
            'plan_salud' => $data['plan_salud'] ?? 'Plan Básico',
            'actualiza_citas_plan' => ! empty($data['actualiza_citas_plan']) ? 1 : 0,
            'nivel_socioeconomico' => $data['nivel_socioeconomico'] ?? 'CATEGORIA A',
            'estrato_socioeconomico' => (int) ($data['estrato_socioeconomico'] ?? 3),
            'fecha_sgsss' => ! empty($data['fecha_sgsss']) ? $data['fecha_sgsss'] : null,
            'fecha_afiliacion' => ! empty($data['fecha_afiliacion']) ? $data['fecha_afiliacion'] : null,
            'municipio_afiliacion' => $data['municipio_afiliacion'] ?? 'MEDELLIN-ANT-05001',
            'ips_primaria' => $data['ips_primaria'] ?? '900294794 - COMITE DE ESTUDIOS MEDICOS SAS',
            'ips_remite' => $data['ips_remite'] ?? null,
            'empleador' => $data['empleador'] ?? null,
        ];

        return static::updateOrCreate(
            [
                'tipo_documento' => $data['tipo_documento'],
                'numero_documento' => $data['numero_documento'],
            ],
            $fields
        );
    }
}
