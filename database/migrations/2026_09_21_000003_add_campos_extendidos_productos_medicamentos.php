<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_medicamentos', function (Blueprint $t) {
            $columnas = [
                'tipo_producto' => fn () => $t->string('tipo_producto', 40)->default('MEDICAMENTO'),
                'fabricante_laboratorio' => fn () => $t->string('fabricante_laboratorio', 150)->nullable(),
                'laboratorio_nit' => fn () => $t->string('laboratorio_nit', 30)->nullable(),
                'clase_terapeutica' => fn () => $t->string('clase_terapeutica', 120)->nullable(),
                'principio_activo' => fn () => $t->string('principio_activo', 200)->nullable(),
                'es_pos' => fn () => $t->boolean('es_pos')->default(true),
                'uso_institucional' => fn () => $t->boolean('uso_institucional')->default(false),
                'es_biologico' => fn () => $t->boolean('es_biologico')->default(false),
            ];
            foreach ($columnas as $nombre => $crear) {
                if (! Schema::hasColumn('productos_medicamentos', $nombre)) {
                    $crear();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos_medicamentos', fn (Blueprint $t) => $t->dropColumn([
            'tipo_producto', 'fabricante_laboratorio', 'laboratorio_nit', 'clase_terapeutica', 'principio_activo', 'es_pos', 'uso_institucional', 'es_biologico',
        ]));
    }
};
