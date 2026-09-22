<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// El sistema nativo creaba esta tabla al vuelo; aquí se crea una vez y solo si no existe.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logs_auditoria')) {
            return;
        }

        Schema::create('logs_auditoria', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('usuario_id')->nullable()->index();
            $t->string('usuario_nombre', 255)->nullable();
            $t->string('rol_nombre', 100)->nullable();
            $t->string('modulo', 100)->index();
            $t->string('accion', 100)->index();
            $t->unsignedInteger('registro_id')->nullable();
            $t->text('detalles')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 255)->nullable();
            $t->dateTime('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
    }
};
