<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->decimal('minimum', 8, 2);
            $table->decimal('maximum', 8, 2);
            $table->decimal('passing', 8, 2);
            $table->timestamps();
        });
        DB::table('grading_scales')->insert([
            'id' => 1, 'minimum' => 0, 'maximum' => 5, 'passing' => 3,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
    }
};
