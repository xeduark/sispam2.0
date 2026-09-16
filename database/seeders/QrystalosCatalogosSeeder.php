<?php

namespace Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Importa los catálogos reales de Qrystalos (database/example/LISTADO.xlsx,
 * ya volcado a JSON en database/seeders/data/).
 *
 * El reemplazo de `sedes` es solo para este entorno local: en producción esa
 * tabla tiene usuarios/ingresos reales atados por sede_id (ver plan). Ahí la
 * reconciliación de sedes es manual cuando se active el envío real.
 */
class QrystalosCatalogosSeeder extends Seeder
{
    public function run(): void
    {
        $dataDir = database_path('seeders/data');

        $this->seedPlanes(json_decode(file_get_contents($dataDir.'/qrystalos_planes.json'), true));
        $this->seedBarrios(json_decode(file_get_contents($dataDir.'/qrystalos_barrios.json'), true));
        $this->seedSedesLocal(json_decode(file_get_contents($dataDir.'/qrystalos_sedes.json'), true));
    }

    private function seedPlanes(array $planes): void
    {
        DB::table('qrystalos_planes')->truncate();

        foreach (array_chunk($planes, 500) as $chunk) {
            DB::table('qrystalos_planes')->insert(array_map(fn ($p) => [
                'idtercero' => $p['idtercero'],
                'razonsocial' => mb_substr($p['razonsocial'], 0, 200),
                'idplan' => $p['idplan'],
                'descplan' => mb_substr($p['descplan'], 0, 150),
            ], $chunk));
        }

        $this->command?->info('qrystalos_planes: '.count($planes).' filas');
    }

    private function seedBarrios(array $barrios): void
    {
        DB::table('qrystalos_barrios')->truncate();

        foreach (array_chunk($barrios, 500) as $chunk) {
            DB::table('qrystalos_barrios')->insert(array_map(fn ($b) => [
                'idciudad' => $b['idciudad'],
                'nombre_ciudad' => mb_substr($b['nombre_ciudad'], 0, 100),
                'idbarrio' => $b['idbarrio'] ?: null,
                'nombre_barrio' => $b['nombre_barrio'] ?: null,
            ], $chunk));
        }

        $this->command?->info('qrystalos_barrios: '.count($barrios).' filas');
    }

    /** Solo entorno local: reemplaza las sedes de prueba por las 35 reales de Qrystalos. */
    private function seedSedesLocal(array $sedes): void
    {
        $empresaId = DB::table('empresas')->value('id') ?? 1;

        foreach ($sedes as $s) {
            Sede::updateOrCreate(
                ['qrystalos_id_sede' => $s['idsede']],
                [
                    'empresa_id' => $empresaId,
                    'nombre_sede' => mb_substr($s['descripcion'], 0, 100),
                    'codigo_sede' => $s['idsede'],
                    'ciudad' => 'MEDELLIN',
                    'estado' => 'Activo',
                ]
            );
        }

        $this->command?->info('sedes (local, reemplazadas por catálogo Qrystalos): '.count($sedes));
    }
}
