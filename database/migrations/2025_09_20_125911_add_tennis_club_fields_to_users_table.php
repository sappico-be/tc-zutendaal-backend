<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Persoonlijke info
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            
            // Adres
            $table->string('street')->nullable();
            $table->string('house_number')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Belgium');
            
            // Tennis specifiek
            $table->string('member_number')->unique()->nullable();
            $table->enum('membership_type', ['junior', 'senior', 'veteran', 'honorary', 'non_member'])->default('non_member');
            $table->date('member_since')->nullable();
            $table->date('membership_expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('tennis_level', 3, 1)->nullable(); // VTV ranking (1.0 - 9.0)
            $table->string('vta_number')->nullable(); // Tennis Vlaanderen nummer
            
            // Rollen en permissies
            $table->enum('role', ['admin', 'board_member', 'trainer', 'member', 'guest'])->default('guest');
            $table->boolean('can_book_courts')->default(false);
            $table->boolean('receives_newsletter')->default(true);
            
            // Emergency contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            
            // Extra
            $table->text('notes')->nullable();
            $table->string('avatar')->nullable();
            $table->json('preferences')->nullable(); // Voor user preferences
            $table->timestamp('last_login_at')->nullable();
            
            // Indexes
            $table->index('member_number');
            $table->index('membership_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name', 'last_name', 'date_of_birth', 'gender',
                'phone', 'mobile', 'street', 'house_number', 'postal_code',
                'city', 'country', 'member_number', 'membership_type',
                'member_since', 'membership_expires_at', 'is_active',
                'tennis_level', 'vta_number', 'role', 'can_book_courts',
                'receives_newsletter', 'emergency_contact_name',
                'emergency_contact_phone', 'notes', 'avatar', 
                'preferences', 'last_login_at'
            ]);
        });
    }
};
