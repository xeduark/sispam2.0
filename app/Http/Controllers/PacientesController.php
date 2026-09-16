<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PacientesController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('q', ''));

        $pacientes = $this->filtrar($busqueda)
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('pacientes.index', compact('pacientes', 'busqueda'));
    }

    public function exportar(Request $request): BinaryFileResponse
    {
        $busqueda = trim((string) $request->query('q', ''));
        $pacientes = $this->filtrar($busqueda)->orderByDesc('id')->get();

        $rutaTemporal = tempnam(sys_get_temp_dir(), 'sispam_pacientes_').'.xlsx';

        $writer = new Writer();
        $writer->openToFile($rutaTemporal);
        $writer->addRow(Row::fromValues(['Tipo Doc', 'Número Doc', 'Nombres', 'Apellidos', 'EPS', 'Celular', 'Ciudad', 'Estado']));

        foreach ($pacientes as $paciente) {
            $writer->addRow(Row::fromValues([
                $paciente->tipo_documento,
                $paciente->numero_documento,
                $paciente->nombres,
                $paciente->apellidos,
                $paciente->eps_nombre,
                $paciente->numero_celular,
                $paciente->ciudad_residencia,
                $paciente->estado,
            ]));
        }

        $writer->close();

        return response()->download($rutaTemporal, 'pacientes_sispam_'.now()->format('Ymd_His').'.xlsx')
            ->deleteFileAfterSend(true);
    }

    private function filtrar(string $busqueda)
    {
        return Paciente::query()->when($busqueda !== '', function ($query) use ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('numero_documento', 'like', "%{$busqueda}%")
                    ->orWhere('nombres', 'like', "%{$busqueda}%")
                    ->orWhere('apellidos', 'like', "%{$busqueda}%");
            });
        });
    }

    public function importar(): View
    {
        return view('pacientes.importar');
    }

    public function descargarPlantilla(): BinaryFileResponse
    {
        abort_unless(file_exists(public_path('assets/plantilla_pacientes.csv')), 404);

        return response()->download(
            public_path('assets/plantilla_pacientes.csv'),
            'plantilla_pacientes_sispam.csv'
        );
    }

    public function procesar(Request $request): RedirectResponse
    {
        $request->validate([
            'archivo_csv' => ['required', 'file', 'mimes:csv,txt'],
        ], [], []);

        $handle = fopen($request->file('archivo_csv')->getRealPath(), 'r');

        if ($handle === false) {
            return back()->with('error', 'No se pudo leer el contenido del archivo CSV.');
        }

        $insertados = 0;
        $actualizados = 0;
        $omitidos = 0;

        fgetcsv($handle, 2000, ','); // encabezado

        while (($fila = fgetcsv($handle, 2000, ',')) !== false) {
            if (count($fila) < 3) {
                continue;
            }

            $tipoDoc = strtoupper(trim($fila[0] ?? 'CC'));
            $numDoc = preg_replace('/[^\d]/', '', trim($fila[1] ?? ''));
            $primerNombre = trim($fila[2] ?? '');
            $segundoNombre = trim($fila[3] ?? '');
            $primerApellido = trim($fila[4] ?? '');
            $segundoApellido = trim($fila[5] ?? '');
            $fechaNac = trim($fila[6] ?? '');
            $sexo = trim($fila[7] ?? 'Masculino');
            $eps = trim($fila[8] ?? 'Sura EPS');
            $celular = trim($fila[9] ?? '');
            $direccion = trim($fila[10] ?? '');
            $ciudadRes = trim($fila[11] ?? 'MEDELLIN-ANT-05001');

            if ($numDoc === '' || $primerNombre === '') {
                $omitidos++;

                continue;
            }

            $existente = Paciente::buscarPorDocumento($tipoDoc, $numDoc);

            Paciente::createOrUpdate([
                'tipo_documento' => $tipoDoc,
                'numero_documento' => $numDoc,
                'primer_nombre' => $primerNombre,
                'segundo_nombre' => $segundoNombre,
                'primer_apellido' => $primerApellido,
                'segundo_apellido' => $segundoApellido,
                'fecha_nacimiento' => $fechaNac !== '' ? date('Y-m-d', strtotime($fechaNac)) : null,
                'sexo' => in_array($sexo, ['Masculino', 'Femenino', 'Indeterminado o Intersexual'], true) ? $sexo : 'Masculino',
                'eps_nombre' => $eps ?: 'Sura EPS',
                'numero_celular' => $celular,
                'direccion_residencia' => $direccion,
                'ciudad_residencia' => $ciudadRes,
                'telefono' => $celular,
            ]);

            $existente ? $actualizados++ : $insertados++;
        }

        fclose($handle);

        return back()
            ->with('mensaje', 'Proceso de importación masiva finalizado exitosamente.')
            ->with('resumenImportacion', [
                'insertados' => $insertados,
                'actualizados' => $actualizados,
                'omitidos' => $omitidos,
                'total' => $insertados + $actualizados + $omitidos,
            ]);
    }
}
