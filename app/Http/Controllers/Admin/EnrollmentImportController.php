<?php

namespace App\Http\Controllers\Admin;

use App\Actions\EnrollStudent;
use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportEnrollmentsRequest;
use App\Imports\EnrollmentSpreadsheetImporter;
use App\Models\Classroom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

class EnrollmentImportController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ImportEnrollmentsRequest $request,
        EnrollmentSpreadsheetImporter $importer,
        EnrollStudent $enrollStudent,
    ): RedirectResponse {
        $file = $request->file('file');
        $rows = $importer->rows($file->getRealPath(), strtolower($file->getClientOriginalExtension()));
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, [
                'numero_matricula' => ['required', 'string', 'max:50', 'unique:enrollments,enrollment_number'],
                'ano_lectivo' => ['required', 'exists:academic_years,name'],
                'grado' => ['required', 'exists:grades,name'],
                'sede' => ['required', 'exists:campuses,name'],
                'jornada' => ['required', 'exists:school_shifts,name'],
                'grupo' => ['required', 'string', 'max:80'],
                'tipo_documento' => ['required', Rule::enum(DocumentType::class)],
                'numero_documento' => ['required', 'string', 'max:50'],
                'primer_nombre' => ['required', 'string', 'max:100'],
                'segundo_nombre' => ['nullable', 'string', 'max:100'],
                'primer_apellido' => ['required', 'string', 'max:100'],
                'segundo_apellido' => ['nullable', 'string', 'max:100'],
                'fecha_nacimiento' => ['required', 'date', 'before:today'],
                'correo' => ['required', 'email', 'max:255'],
                'fecha_matricula' => ['required', 'date'],
                'telefono' => ['nullable', 'string', 'max:30'],
                'direccion' => ['nullable', 'string', 'max:255'],
                'observaciones' => ['nullable', 'string', 'max:1000'],
            ]);

            if ($validator->fails()) {
                $errors[] = 'Fila '.($index + 2).': '.$validator->errors()->first();

                continue;
            }

            try {
                $data = $validator->validated();
                $classroom = Classroom::query()->where('name', $data['grupo'])
                    ->whereHas('campus', fn ($query) => $query->where('name', $data['sede']))
                    ->whereHas('schoolShift', fn ($query) => $query->where('name', $data['jornada']))
                    ->whereHas('grade', fn ($query) => $query->where('name', $data['grado']))
                    ->whereHas('academicYear', fn ($query) => $query->where('name', $data['ano_lectivo']))
                    ->firstOrFail();
                $enrollStudent->handle([
                    'enrollment_number' => $data['numero_matricula'],
                    'document_type' => $data['tipo_documento'],
                    'document_number' => $data['numero_documento'],
                    'first_name' => $data['primer_nombre'],
                    'middle_name' => $data['segundo_nombre'] ?? null,
                    'last_name' => $data['primer_apellido'],
                    'second_last_name' => $data['segundo_apellido'] ?? null,
                    'birth_date' => $data['fecha_nacimiento'],
                    'phone' => $data['telefono'] ?? null,
                    'address' => $data['direccion'] ?? null,
                    'email' => $data['correo'],
                    'enrolled_at' => $data['fecha_matricula'],
                    'observations' => $data['observaciones'] ?? null,
                    'classroom_id' => $classroom->id,
                ], $classroom->academicYear, $classroom->grade);
                $imported++;
            } catch (Throwable $exception) {
                report($exception);
                $errors[] = 'Fila '.($index + 2).': '.$exception->getMessage();
            }
        }

        if ($imported === 0) {
            return back()->withErrors(['file' => $errors[0] ?? 'El archivo no contiene registros para importar.']);
        }

        $message = "Se importaron {$imported} matrícula(s).";
        if ($errors !== []) {
            $message .= ' '.count($errors).' fila(s) no pudieron importarse.';
        }
        Inertia::flash('toast', ['type' => $errors === [] ? 'success' : 'warning', 'message' => $message]);

        return to_route('admin.enrollments');
    }
}
