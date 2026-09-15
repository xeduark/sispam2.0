<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline del schema heredado del sistema PHP nativo.
 *
 * El DDL vive en database/schema/baseline.sql, generado con mysqldump --no-data
 * desde la base de datos real. Se usa verbatim en vez de reescribirlo con
 * Blueprint para que no haya divergencias de tipo, default o collation con las
 * tablas que ya están en producción con datos.
 *
 * En un entorno que ya tiene estas tablas (el caso de la migración), esta
 * migration se marca como aplicada sin ejecutarse. Las tablas se crean solo en
 * un entorno vacío.
 */
return new class extends Migration
{
    public function up(): void
    {
        $sql = file_get_contents(database_path('schema/baseline.sql'));

        foreach ($this->createTableStatements($sql) as $tabla => $ddl) {
            if (! Schema::hasTable($tabla)) {
                DB::unprepared($ddl);
            }
        }
    }

    public function down(): void
    {
        // Sin rollback: borraría datos reales de pacientes e ingresos.
    }

    /** @return array<string, string> nombre de tabla => sentencia CREATE TABLE */
    private function createTableStatements(string $sql): array
    {
        preg_match_all('/CREATE TABLE `(\w+)`.*?;/s', $sql, $matches, PREG_SET_ORDER);

        return array_column(
            array_map(fn ($m) => [$m[1], $m[0]], $matches),
            1,
            0
        );
    }
};
