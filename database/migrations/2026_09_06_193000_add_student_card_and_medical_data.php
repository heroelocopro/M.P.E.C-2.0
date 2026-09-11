<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->uuid('card_token')->unique()->nullable()->after('id');
        });

        Schema::create('student_medical_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('blood_type')->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('disabilities')->nullable();
            $table->string('health_provider')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('gender_identity')->nullable();
            $table->string('sexual_orientation')->nullable();
            $table->string('ethnic_identity')->nullable();
            $table->string('indigenous_people')->nullable();
            $table->boolean('data_consent')->default(false);
            $table->timestamps();
        });

        Schema::create('institutional_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by_id')->constrained('users')->restrictOnDelete();
            $table->date('date');
            $table->time('checked_in_at');
            $table->string('method')->default('qr');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'date']);
            $table->index(['date', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institutional_attendances');
        Schema::dropIfExists('student_medical_profiles');
        Schema::table('students', function (Blueprint $table): void {
            $table->dropUnique(['card_token']);
            $table->dropColumn('card_token');
        });
    }
};
