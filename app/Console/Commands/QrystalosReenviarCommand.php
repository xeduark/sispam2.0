<?php

namespace App\Console\Commands;

use App\Models\Paciente;
use App\Services\QrystalosService;
use Illuminate\Console\Command;

class QrystalosReenviarCommand extends Command
{
    protected $signature = 'qrystalos:reenviar {paciente_id? : ID del paciente a reintentar (omitir para reintentar todos los pendientes)}';

    protected $description = 'Reintenta el envío a Qrystalos de un paciente puntual, o de todos los que quedaron en KO';

    public function handle(QrystalosService $qrystalos): int
    {
        $pacienteId = $this->argument('paciente_id');

        $pacientes = $pacienteId
            ? Paciente::where('id', $pacienteId)->get()
            : Paciente::whereNotNull('qrystalos_last_error')->get();

        if ($pacientes->isEmpty()) {
            $this->info('No hay pacientes pendientes de reenvío.');

            return self::SUCCESS;
        }

        foreach ($pacientes as $paciente) {
            $resultado = $qrystalos->insertarPaciente($paciente);

            if ($resultado['ok']) {
                $this->info("Paciente #{$paciente->id} ({$paciente->numero_documento}): OK");
            } else {
                $this->error("Paciente #{$paciente->id} ({$paciente->numero_documento}): KO - ".implode(' | ', $resultado['errores']));
            }
        }

        return self::SUCCESS;
    }
}
