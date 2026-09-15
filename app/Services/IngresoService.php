<?php

namespace App\Services;

use App\Models\Ingreso;
use App\Models\ModuloEntrega;
use App\Models\Paciente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de ingresos, portada del modelo PDO legacy.
 */
class IngresoService
{
    /** Prefijo TK-YYMMDD- + secuencia. Portado tal cual, condición de carrera incluida (sin locking, fuera de alcance). */
    public function generarTicketConsecutivo(): string
    {
        $prefix = 'TK-'.now()->format('ymd').'-';

        $ultimo = Ingreso::where('ticket_numero', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('ticket_numero');

        $secuencia = $ultimo ? ((int) substr(strrchr($ultimo, '-'), 1) + 1) : 1;

        return $prefix.str_pad((string) $secuencia, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Crea el ingreso, sube los documentos adjuntos al mismo árbol de carpetas
     * que usaba el sistema legacy (assets/uploads/pacientes/... bajo public/,
     * para no romper las rutas relativas ya guardadas en la BD) y arma el ticket.
     *
     * @param  array<string, UploadedFile>  $archivos  clave = categoría de documento (CEDULA, ORDEN_MEDICA, ...)
     * @return array{id: int, ticket: string}
     */
    public function crear(
        Paciente $paciente,
        int $orientadorId,
        array $archivos,
        string $prioridad = 'NORMAL',
        ?string $prioridadObs = null,
        string $personaReclama = 'PACIENTE_DIRECTO',
        ?string $ipsRemite = null
    ): array {
        $ticket = $this->generarTicketConsecutivo();
        $folderName = $paciente->numero_documento.'_'.now()->format('dmy');
        $relDir = "assets/uploads/pacientes/{$paciente->tipo_documento}_{$paciente->numero_documento}/{$folderName}/";

        return DB::transaction(function () use ($paciente, $orientadorId, $archivos, $prioridad, $prioridadObs, $personaReclama, $ipsRemite, $ticket, $relDir) {
            $ingreso = Ingreso::create([
                'ticket_numero' => $ticket,
                'paciente_id' => $paciente->id,
                'orientador_id' => $orientadorId,
                'fecha_ingreso' => now(),
                'estado_tramite' => 'INGRESADO',
                'prioridad' => $prioridad ?: 'NORMAL',
                'prioridad_observacion' => $prioridadObs ?: null,
                'persona_reclama' => $personaReclama ?: 'PACIENTE_DIRECTO',
                'ips_remite' => $ipsRemite ?: null,
            ]);

            foreach ($archivos as $tipoDocumento => $archivo) {
                if (! $archivo instanceof UploadedFile || ! $archivo->isValid()) {
                    continue;
                }

                $nombreLimpio = strtolower($tipoDocumento).'_'.time().'.'.$archivo->getClientOriginalExtension();
                $archivo->move(public_path($relDir), $nombreLimpio);

                $ingreso->documentos()->create([
                    'tipo_documento' => $tipoDocumento,
                    'ruta_archivo' => $relDir.$nombreLimpio,
                    'nombre_original' => $archivo->getClientOriginalName(),
                ]);
            }

            return ['id' => $ingreso->id, 'ticket' => $ticket];
        });
    }

    /**
     * Sede activa del sistema legacy. Ojo: en el sistema original estas claves de
     * sesión se leían pero nunca se escribían, así que el filtro multi-sede nunca
     * llegó a activarse. Se conserva la misma cadena de fallback para no cambiar
     * el comportamiento actual.
     */
    public function sedeActiva(): ?int
    {
        return session('active_sede_id') ?? session('sede_id');
    }

    private function esAdministrador(): bool
    {
        return (bool) auth()->user()?->esAdministrador();
    }

    private function deSedeActiva($query)
    {
        return $query->deSedeActiva($this->sedeActiva(), $this->esAdministrador());
    }

    /**
     * Bloqueo pesimista por fila del sistema legacy: barre los bloqueos de más
     * de 10 minutos y toma el registro si está libre o ya es de este usuario.
     * Portado tal cual, con el mismo mecanismo de columnas y el mismo timeout.
     */
    public function bloquear(int $ingresoId, int $usuarioId): bool
    {
        Ingreso::whereNotNull('locked_at')
            ->where('locked_at', '<', now()->subMinutes(10))
            ->update(['locked_by_user_id' => null, 'locked_at' => null]);

        $bloqueoActual = Ingreso::where('id', $ingresoId)->value('locked_by_user_id');

        if ($bloqueoActual && $bloqueoActual != $usuarioId) {
            return false;
        }

        Ingreso::where('id', $ingresoId)->update([
            'locked_by_user_id' => $usuarioId,
            'locked_at' => now(),
            'estado_tramite' => DB::raw("IF(estado_tramite = 'INGRESADO', 'EN_TRANSCRIPCION', estado_tramite)"),
        ]);

        return true;
    }

    /**
     * Libera el bloqueo. Con $forzado=true (solo Administrador) libera sin
     * importar quién lo tenga: el botón "Forzar Desbloqueo" ya existía en la UI
     * legacy pero llamaba al mismo unlockRecord() con chequeo de dueño, así que
     * nunca liberaba el registro de otro usuario. Se conecta aquí de verdad.
     */
    public function liberar(int $ingresoId, ?int $usuarioId = null, bool $forzado = false): void
    {
        $query = Ingreso::where('id', $ingresoId);

        if (! $forzado && $usuarioId !== null) {
            $query->where('locked_by_user_id', $usuarioId);
        }

        $query->update([
            'locked_by_user_id' => null,
            'locked_at' => null,
            'estado_tramite' => DB::raw("IF(estado_tramite = 'EN_TRANSCRIPCION', 'INGRESADO', estado_tramite)"),
        ]);
    }

    /** Guarda el PDF de la orden transcrita y pasa el ingreso a Alistamiento. */
    public function guardarTranscripcion(Ingreso $ingreso, ?\Illuminate\Http\UploadedFile $pdf = null): void
    {
        $rutaPdf = $ingreso->pdf_transcripcion_url;

        if ($pdf && $pdf->isValid()) {
            $relDir = "assets/uploads/pacientes/{$ingreso->paciente->tipo_documento}_{$ingreso->paciente->numero_documento}/transcripciones/";
            $nombre = 'transcripcion_'.$ingreso->ticket_numero.'_'.time().'.pdf';
            $pdf->move(public_path($relDir), $nombre);
            $rutaPdf = $relDir.$nombre;
        }

        $ingreso->update([
            'estado_tramite' => 'TRANSCRITO_COMPLETO',
            'pdf_transcripcion_url' => $rutaPdf,
            'locked_by_user_id' => null,
            'locked_at' => null,
        ]);
    }

    /**
     * Reparte los ingresos ALISTADOs entre los módulos activos, asignando el
     * que tenga menos carga (al azar entre empates). Portado tal cual.
     */
    public function obtenerModuloEquitativo(): array
    {
        $modulos = ModuloEntrega::activos()->get();

        if ($modulos->isEmpty()) {
            return ['modulo' => 'Ventanilla 1', 'cola_actual' => 0];
        }

        $conteos = Ingreso::where('estado_tramite', 'ALISTADO')
            ->selectRaw('modulo_entrega_asignado, COUNT(*) as total')
            ->groupBy('modulo_entrega_asignado')
            ->pluck('total', 'modulo_entrega_asignado');

        $colas = $modulos->mapWithKeys(fn (ModuloEntrega $m) => [$m->nombre => (int) ($conteos[$m->nombre] ?? 0)]);

        $minimo = $colas->min();
        $menosCargados = $colas->filter(fn ($cant) => $cant === $minimo)->keys();

        return [
            'modulo' => $menosCargados[array_rand($menosCargados->all())],
            'cola_actual' => $minimo,
        ];
    }

    /** Sube el PDF de empaque, asigna módulo de entrega y pasa el ingreso a ALISTADO. */
    public function guardarAlistamiento(
        Ingreso $ingreso,
        ?UploadedFile $pdf,
        string $faltantesText,
        int $usuarioId,
        string $moduloEntrega = 'AUTO'
    ): string {
        $rutaPdf = $ingreso->pdf_alistamiento;

        if ($pdf && $pdf->isValid()) {
            $relDir = "assets/uploads/pacientes/{$ingreso->paciente->tipo_documento}_{$ingreso->paciente->numero_documento}/alistamientos/";
            $nombre = 'alistamiento_'.$ingreso->ticket_numero.'_'.time().'.pdf';
            $pdf->move(public_path($relDir), $nombre);
            $rutaPdf = $relDir.$nombre;
        }

        if (empty($moduloEntrega) || strtoupper(trim($moduloEntrega)) === 'AUTO') {
            $moduloEntrega = $this->obtenerModuloEquitativo()['modulo'];
        }

        $ingreso->update([
            'estado_tramite' => 'ALISTADO',
            'pdf_alistamiento' => $rutaPdf,
            'faltantes_alistamiento' => $faltantesText ?: null,
            'alistado_por_user_id' => $usuarioId,
            'fecha_alistado' => now(),
            'modulo_entrega_asignado' => $moduloEntrega,
            'locked_by_user_id' => null,
            'locked_at' => null,
        ]);

        return $moduloEntrega;
    }

    /** Lista de trabajo de Transcripción. */
    public function listaTranscripcion(): Collection
    {
        return $this->deSedeActiva(
            Ingreso::query()
                ->with(['paciente', 'orientador', 'bloqueadoPor'])
                ->withCount('documentos')
                ->whereIn('estado_tramite', ['INGRESADO', 'EN_TRANSCRIPCION'])
        )->ordenAtencion()->get();
    }

    /** Lista de trabajo de Alistamiento. */
    public function listaAlistamiento(string $filtro = 'TODOS'): Collection
    {
        $query = $this->deSedeActiva(
            Ingreso::query()
                ->with(['paciente', 'bloqueadoPor'])
                ->whereIn('estado_tramite', ['TRANSCRITO_COMPLETO', 'TRANSCRITO_PENDIENTE', 'SIN_STOCK', 'EN_ALISTAMIENTO'])
        );

        if ($filtro !== '' && $filtro !== 'TODOS') {
            $query->where('estado_tramite', $filtro);
        }

        return $query->ordenAtencion()->get();
    }

    /** Lista de trabajo de Entrega. */
    public function listaEntrega(?string $moduloFiltro = null): Collection
    {
        $query = $this->deSedeActiva(
            Ingreso::query()
                ->with('paciente')
                ->where('estado_tramite', 'ALISTADO')
        );

        if ($moduloFiltro && $moduloFiltro !== 'TODOS') {
            $query->where('modulo_entrega_asignado', $moduloFiltro);
        }

        return $query->ordenAtencion('updated_at')->get();
    }
}
