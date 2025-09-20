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
        Schema::create('lesson_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_package_id')->constrained();
            $table->string('name');
            $table->foreignId('trainer_id')->nullable()->constrained('users');
            $table->foreignId('location_id')->nullable()->constrained('lesson_locations');
            $table->enum('level', ['beginner', 'intermediate', 'advanced']);
            $table->integer('max_participants');
            $table->json('schedule_days'); // Welke dagen deze groep les heeft
            $table->time('default_start_time')->nullable();
            $table->time('default_end_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_groups');
    }
};
