<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'waitlist'])->default('pending');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'refunded'])->default('unpaid');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('additional_info')->nullable(); // Voor extra velden per event
            $table->timestamps();
            
            $table->unique(['event_id', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
