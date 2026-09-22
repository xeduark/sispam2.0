<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sedes adicionales por usuario (el sistema nativo creaba esta tabla al vuelo).
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('usuario_sedes')) {
            return;
        }

        Schema::create('usuario_sedes', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('usuario_id');
            $t->unsignedInteger('sede_id');
            $t->dateTime('created_at')->useCurrent();
            $t->unique(['usuario_id', 'sede_id'], 'uq_user_sede');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_sedes');
    }
};
