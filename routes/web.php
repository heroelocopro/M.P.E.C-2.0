<?php

use App\Http\Controllers\AcademicTaskAttachmentController;
use App\Http\Controllers\Admin\AcademicStructureController;
use App\Http\Controllers\Admin\CommunicationController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\EnrollmentImportController;
use App\Http\Controllers\Admin\GradeReportController;
use App\Http\Controllers\Admin\GradingScaleController;
use App\Http\Controllers\Admin\InstitutionalAttendanceController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Student\CardController;
use App\Http\Controllers\Student\GradeController as StudentGradeController;
use App\Http\Controllers\Student\ScheduleController;
use App\Http\Controllers\Student\TaskController;
use App\Http\Controllers\Student\TaskSubmissionController;
use App\Http\Controllers\Teacher\AttendanceController;
use App\Http\Controllers\Teacher\GradeController;
use App\Http\Controllers\Teacher\ScheduleController as TeacherScheduleController;
use App\Http\Controllers\Teacher\StudentReportController;
use App\Http\Controllers\Teacher\TaskController as TeacherTaskController;
use App\Http\Middleware\EnsureUserIsAdministrator;
use App\Http\Middleware\EnsureUserIsGuardian;
use App\Http\Middleware\EnsureUserIsStudent;
use App\Http\Middleware\EnsureUserIsTeacher;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('evaluaciones', [EvaluationController::class, 'index'])->name('evaluations.index');
    Route::post('evaluaciones', [EvaluationController::class, 'store'])->name('evaluations.store');
    Route::get('evaluaciones/{evaluation}', [EvaluationController::class, 'show'])->name('evaluations.show');
    Route::post('evaluaciones/{evaluation}/responder', [EvaluationController::class, 'submit'])->name('evaluations.submit');
    Route::patch('evaluaciones/{evaluation}/intentos/{attempt}', [EvaluationController::class, 'grade'])->name('evaluations.grade');
    Route::get('foro', [ForumController::class, 'index'])->name('forum.index');
    Route::post('foro', [ForumController::class, 'store'])->middleware('throttle:20,1')->name('forum.store');
    Route::get('foro/{forumTopic}', [ForumController::class, 'show'])->name('forum.show');
    Route::post('foro/{forumTopic}/respuestas', [ForumController::class, 'reply'])->middleware('throttle:30,1')->name('forum.reply');
    Route::get('notificaciones', [NotificationController::class, 'index'])->name('notifications');
    Route::patch('notificaciones/todas', [NotificationController::class, 'updateAll'])->name('notifications.read-all');
    Route::patch('notificaciones/{notification}', [NotificationController::class, 'update'])->name('notifications.read');
    Route::get('avisos', [AnnouncementController::class, 'index'])->name('announcements');
    Route::post('avisos', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('archivos-tareas/{academicTaskAttachment}', AcademicTaskAttachmentController::class)->name('task-attachments.download');
    Route::get('entregas-tareas/{submission}', [TaskSubmissionController::class, 'download'])->name('task-submissions.download');

    Route::middleware(EnsureUserIsStudent::class)->prefix('estudiante')->name('student.')->group(function () {
        Route::get('tareas', TaskController::class)->name('tasks');
        Route::post('tareas/{academicTask}/entrega', [TaskSubmissionController::class, 'store'])->name('tasks.submit');
        Route::get('horario', ScheduleController::class)->name('schedule');
        Route::get('notas', [StudentGradeController::class, 'index'])->name('grades');
        Route::get('notas/{period}/pdf', [StudentGradeController::class, 'download'])->name('grades.pdf');
        Route::get('mi-qr', CardController::class)->name('card');
    });

    Route::middleware(EnsureUserIsTeacher::class)->prefix('profesor')->name('teacher.')->group(function () {
        Route::get('asistencia', [AttendanceController::class, 'index'])->name('attendance');
        Route::post('asistencia', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::get('horario', TeacherScheduleController::class)->name('schedule');
        Route::get('notas', [GradeController::class, 'index'])->name('grades');
        Route::post('notas', [GradeController::class, 'store'])->name('grades.store');
        Route::get('tareas', [TeacherTaskController::class, 'index'])->name('tasks');
        Route::post('tareas', [TeacherTaskController::class, 'store'])->name('tasks.store');
        Route::post('estudiantes/{student}/reportes', [StudentReportController::class, 'store'])->name('student-reports.store');
        Route::get('reportes-estudiantes', [StudentReportController::class, 'index'])->name('student-reports');
        Route::patch('tareas/{academicTask}', [TeacherTaskController::class, 'update'])->name('tasks.update');
    });

    Route::middleware(EnsureUserIsGuardian::class)->prefix('acudiente')->name('guardian.')->group(function () {
        Route::get('panel', App\Http\Controllers\Guardian\DashboardController::class)->name('dashboard');
    });

    Route::get('admin/usuarios', UserController::class)
        ->middleware(EnsureUserIsAdministrator::class)
        ->name('admin.users');

    Route::middleware(EnsureUserIsAdministrator::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('configuracion-academica', [AcademicStructureController::class, 'index'])->name('academic-settings');
        Route::put('escala-notas', [GradingScaleController::class, 'update'])->name('grading-scale.update');
        Route::post('configuracion-academica/{type}', [AcademicStructureController::class, 'store'])->name('academic-settings.store');
        Route::put('configuracion-academica/{type}/{id}', [AcademicStructureController::class, 'update'])->name('academic-settings.update');
        Route::delete('configuracion-academica/{type}/{id}', [AcademicStructureController::class, 'destroy'])->name('academic-settings.destroy');
        Route::post('periodos/{period}/cerrar', [AcademicStructureController::class, 'closePeriod'])->name('periods.close');
        Route::get('profesores', [TeacherController::class, 'index'])->name('teachers');
        Route::post('profesores', [TeacherController::class, 'store'])->name('teachers.store');
        Route::post('profesores/asignaciones', [TeacherController::class, 'assign'])->name('teachers.assign');
        Route::delete('profesores/asignaciones/{teacherAssignment}', [TeacherController::class, 'unassign'])->name('teachers.unassign');
        Route::get('matriculas', [EnrollmentController::class, 'index'])->name('enrollments');
        Route::get('matriculas/crear', [EnrollmentController::class, 'create'])->name('enrollments.create');
        Route::post('matriculas', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::post('matriculas/importar', EnrollmentImportController::class)->name('enrollments.import');
        Route::get('estudiantes', [StudentController::class, 'active'])->name('students');
        Route::get('egresados', [StudentController::class, 'graduates'])->name('graduates');
        Route::get('estudiantes/{student}', [StudentController::class, 'show'])->name('students.show');
        Route::put('estudiantes/{student}/informacion-medica', [StudentController::class, 'updateMedical'])->name('students.medical.update');
        Route::post('estudiantes/{student}/correo', [StudentController::class, 'email'])->name('students.email');
        Route::get('asistencia-institucional', [InstitutionalAttendanceController::class, 'index'])->name('institutional-attendance');
        Route::get('asistencia-institucional/scan/{token}', [InstitutionalAttendanceController::class, 'scan'])->name('institutional-attendance.scan');
        Route::get('comunicaciones', [CommunicationController::class, 'index'])->name('communication');
        Route::post('comunicaciones/enviar', [CommunicationController::class, 'sendAll'])->name('communication.send-all');
        Route::get('notas', [GradeReportController::class, 'index'])->name('grades');
        Route::put('usuarios/{user}/contrasena', [UserController::class, 'updatePassword'])
            ->middleware('throttle:6,1')
            ->name('users.password.update');
    });
});

require __DIR__.'/settings.php';
