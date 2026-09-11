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
        Schema::table('academic_tasks', function (Blueprint $table) {
            $table->foreignId('academic_period_id')
                ->nullable()
                ->after('classroom_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('academic_period_id');
        });
    }
};
