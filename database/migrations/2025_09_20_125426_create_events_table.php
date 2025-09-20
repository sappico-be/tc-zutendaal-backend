<?php
// Events Migration
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->longText('content')->nullable();
            $table->enum('type', ['tournament', 'training', 'social', 'meeting', 'other'])->default('other');
            $table->string('location')->nullable();
            $table->string('featured_image')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('registration_deadline')->nullable();
            $table->integer('max_participants')->nullable();
            $table->integer('min_participants')->nullable();
            $table->decimal('price_members', 10, 2)->default(0);
            $table->decimal('price_non_members', 10, 2)->nullable();
            $table->boolean('members_only')->default(false);
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->json('settings')->nullable(); // Voor extra opties
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            $table->index(['status', 'start_date']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
