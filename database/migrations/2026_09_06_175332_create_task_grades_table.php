<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('graded_by_id')->constrained('users')->restrictOnDelete();
            $table->decimal('score', 3, 2);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['academic_task_id', 'enrollment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_grades');
    }
};
