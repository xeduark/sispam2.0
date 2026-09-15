<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sedes', function (Blueprint $table) {
            $table->string('qrystalos_id_sede', 10)->nullable()->after('id');
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('qrystalos_idadministradora', 20)->nullable();
            $table->string('qrystalos_idplan', 10)->nullable();
            $table->string('qrystalos_idciudad', 10)->nullable();
            $table->string('qrystalos_idbarrio', 20)->nullable();
            $table->string('qrystalos_idsede', 10)->nullable();
            $table->string('qrystalos_id_afiliado', 30)->nullable();
            $table->timestamp('qrystalos_synced_at')->nullable();
            $table->text('qrystalos_last_error')->nullable();
        });

        Schema::create('qrystalos_planes', function (Blueprint $table) {
            $table->id();
            $table->string('idtercero', 20)->index();
            $table->string('razonsocial', 200);
            $table->string('idplan', 10);
            $table->string('descplan', 150);
        });

        Schema::create('qrystalos_barrios', function (Blueprint $table) {
            $table->id();
            $table->string('idciudad', 10)->index();
            $table->string('nombre_ciudad', 100);
            $table->string('idbarrio', 20)->nullable();
            $table->string('nombre_barrio', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sedes', fn (Blueprint $table) => $table->dropColumn('qrystalos_id_sede'));

        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn([
                'qrystalos_idadministradora',
                'qrystalos_idplan',
                'qrystalos_idciudad',
                'qrystalos_idbarrio',
                'qrystalos_idsede',
                'qrystalos_id_afiliado',
                'qrystalos_synced_at',
                'qrystalos_last_error',
            ]);
        });

        Schema::dropIfExists('qrystalos_planes');
        Schema::dropIfExists('qrystalos_barrios');
    }
};
