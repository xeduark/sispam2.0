<?php

namespace App\Services;

use App\Models\Ingreso;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio de ingresos, portada del modelo PDO legacy.
 */
class IngresoService
{
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

    public function liberar(int $ingresoId, ?int $usuarioId = null): void
    {
        $query = Ingreso::where('id', $ingresoId);

        if ($usuarioId !== null) {
            $query->where('locked_by_user_id', $usuarioId);
        }

        $query->update([
            'locked_by_user_id' => null,
            'locked_at' => null,
            'estado_tramite' => DB::raw("IF(estado_tramite = 'EN_TRANSCRIPCION', 'INGRESADO', estado_tramite)"),
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
