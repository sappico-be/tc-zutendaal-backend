<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Trainer hour registrations
        Schema::create('trainer_hour_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lesson_schedule_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 4, 2); // Calculated hours
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->enum('type', ['lesson', 'preparation', 'meeting', 'tournament', 'other'])->default('lesson');
            $table->string('description')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'date']);
            $table->index(['status', 'date']);
            $table->index('lesson_schedule_id');
        });

        // Trainer contracts/rates
        Schema::create('trainer_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('preparation_rate', 10, 2)->nullable(); // Rate for prep time
            $table->decimal('tournament_rate', 10, 2)->nullable(); // Rate for tournaments
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('contract_type', ['freelance', 'employee', 'volunteer'])->default('freelance');
            $table->integer('max_hours_per_week')->nullable();
            $table->integer('max_hours_per_month')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Extra settings
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });

        // Monthly summaries for payroll
        Schema::create('trainer_hour_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('year');
            $table->integer('month');
            $table->decimal('total_hours', 6, 2);
            $table->decimal('lesson_hours', 6, 2)->default(0);
            $table->decimal('preparation_hours', 6, 2)->default(0);
            $table->decimal('meeting_hours', 6, 2)->default(0);
            $table->decimal('tournament_hours', 6, 2)->default(0);
            $table->decimal('other_hours', 6, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['draft', 'submitted', 'approved', 'paid'])->default('draft');
            $table->date('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'year', 'month']);
            $table->index(['year', 'month']);
            $table->index('status');
        });

        // Add hourly tracking preference to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'tracks_hours')) {
                $table->boolean('tracks_hours')->default(false)->after('role');
            }
            if (!Schema::hasColumn('users', 'default_hourly_rate')) {
                $table->decimal('default_hourly_rate', 10, 2)->nullable()->after('tracks_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_hour_summaries');
        Schema::dropIfExists('trainer_contracts');
        Schema::dropIfExists('trainer_hour_registrations');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tracks_hours', 'default_hourly_rate']);
        });
    }
};
