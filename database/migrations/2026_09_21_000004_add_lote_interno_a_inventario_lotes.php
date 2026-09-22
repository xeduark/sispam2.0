<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_lotes', function (Blueprint $t) {
            if (! Schema::hasColumn('inventario_lotes', 'lote_interno')) {
                $t->string('lote_interno', 60)->nullable()->after('numero_lote');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventario_lotes', fn (Blueprint $t) => $t->dropColumn('lote_interno'));
    }
};
