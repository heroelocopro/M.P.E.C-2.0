<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\SchoolShift;
use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchoolStructureSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AcademicYear::query()->updateOrCreate(
            ['name' => '2025'],
            ['starts_at' => '2025-01-20', 'ends_at' => '2025-11-28', 'is_active' => false],
        );

        AcademicYear::query()->updateOrCreate(
            ['name' => '2026'],
            ['starts_at' => '2026-01-19', 'ends_at' => '2026-11-27', 'is_active' => true],
        );

        foreach (AcademicYear::query()->get() as $academicYear) {
            foreach (
                [
                    ['number' => 1, 'starts_at' => "{$academicYear->name}-01-19", 'ends_at' => "{$academicYear->name}-03-31"],
                    ['number' => 2, 'starts_at' => "{$academicYear->name}-04-01", 'ends_at' => "{$academicYear->name}-06-15"],
                    ['number' => 3, 'starts_at' => "{$academicYear->name}-06-16", 'ends_at' => "{$academicYear->name}-08-31"],
                    ['number' => 4, 'starts_at' => "{$academicYear->name}-09-01", 'ends_at' => "{$academicYear->name}-11-27"],
                ] as $period
            ) {
                AcademicPeriod::query()->updateOrCreate(
                    ['academic_year_id' => $academicYear->id, 'number' => $period['number']],
                    ['name' => 'Periodo '.$period['number'], 'starts_at' => $period['starts_at'], 'ends_at' => $period['ends_at']],
                );
            }
        }

        $grades = [
            ['name' => 'Transición', 'code' => '00', 'sort_order' => 0],
            ['name' => 'Primero', 'code' => '01', 'sort_order' => 1],
            ['name' => 'Segundo', 'code' => '02', 'sort_order' => 2],
            ['name' => 'Tercero', 'code' => '03', 'sort_order' => 3],
            ['name' => 'Cuarto', 'code' => '04', 'sort_order' => 4],
            ['name' => 'Quinto', 'code' => '05', 'sort_order' => 5],
            ['name' => 'Sexto', 'code' => '06', 'sort_order' => 6],
            ['name' => 'Séptimo', 'code' => '07', 'sort_order' => 7],
            ['name' => 'Octavo', 'code' => '08', 'sort_order' => 8],
            ['name' => 'Noveno', 'code' => '09', 'sort_order' => 9],
            ['name' => 'Décimo', 'code' => '10', 'sort_order' => 10],
            ['name' => 'Undécimo', 'code' => '11', 'sort_order' => 11],
        ];

        foreach ($grades as $grade) {
            Grade::query()->updateOrCreate(
                ['code' => $grade['code']],
                [...$grade, 'is_final' => $grade['code'] === '11', 'is_active' => true],
            );
        }

        $mainCampus = Campus::query()->updateOrCreate(['code' => 'PRI'], ['name' => 'Sede Principal', 'address' => 'Carrera 10 # 20-30', 'is_active' => true]);
        $northCampus = Campus::query()->updateOrCreate(['code' => 'NOR'], ['name' => 'Sede Norte', 'address' => 'Calle 45 # 8-12', 'is_active' => true]);
        $morning = SchoolShift::query()->updateOrCreate(['name' => 'Mañana'], ['starts_at' => '06:30', 'ends_at' => '12:00', 'is_active' => true]);
        $afternoon = SchoolShift::query()->updateOrCreate(['name' => 'Tarde'], ['starts_at' => '12:30', 'ends_at' => '18:00', 'is_active' => true]);

        foreach ([['Matemáticas', 'MAT'], ['Lenguaje', 'LEN'], ['Ciencias naturales', 'CIE'], ['Ciencias sociales', 'SOC'], ['Inglés', 'ING']] as [$name, $code]) {
            Subject::query()->updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        $currentYear = AcademicYear::query()->where('name', '2026')->firstOrFail();
        $sixth = Grade::query()->where('code', '06')->firstOrFail();
        foreach ([[$mainCampus, $morning], [$mainCampus, $afternoon], [$northCampus, $morning]] as [$campus, $shift]) {
            Classroom::query()->updateOrCreate([
                'campus_id' => $campus->id,
                'school_shift_id' => $shift->id,
                'grade_id' => $sixth->id,
                'academic_year_id' => $currentYear->id,
                'name' => 'A',
            ], ['capacity' => 35, 'is_active' => true]);
        }
    }
}
