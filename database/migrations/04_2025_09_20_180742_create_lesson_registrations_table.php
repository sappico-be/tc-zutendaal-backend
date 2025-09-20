<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_package_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('available_days'); // Dagen waarop lid kan
            $table->json('preferred_partners')->nullable(); // Namen/emails van mensen waarmee ze willen
            $table->text('remarks')->nullable();
            $table->enum('level', ['beginner', 'intermediate', 'advanced'])->nullable();
            $table->foreignId('assigned_group_id')->nullable()->constrained('lesson_groups');
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded'])->default('unpaid');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lesson_registrations');
    }
};
