<?php
// database/migrations/2025_01_XX_create_lesson_attendances_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_schedule_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'excused', 'late'])->default('absent');
            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Unieke constraint: één gebruiker per les
            $table->unique(['lesson_schedule_id', 'user_id']);
            
            $table->index(['lesson_schedule_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attendances');
    }
};
