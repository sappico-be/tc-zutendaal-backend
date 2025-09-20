<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('total_lessons');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_deadline');
            $table->decimal('price_members', 10, 2);
            $table->decimal('price_non_members', 10, 2)->nullable();
            $table->enum('status', ['draft', 'open', 'closed', 'completed'])->default('draft');
            $table->integer('min_participants')->nullable();
            $table->integer('max_participants')->nullable();
            $table->json('available_days'); // ['monday', 'tuesday', etc]
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_packages');
    }
};
