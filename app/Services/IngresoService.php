<?php

namespace App\Services;

use App\Models\Ingreso;
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
