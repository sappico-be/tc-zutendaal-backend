<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if payments table exists
        if (!Schema::hasTable('payments')) {
            // Create the table if it doesn't exist
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_id')->unique();
                $table->morphs('payable'); // Voor polymorphic relaties
                $table->foreignId('user_id')->constrained();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('EUR');
                $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
                $table->string('payment_method')->nullable();
                $table->string('provider')->nullable();
                $table->string('provider_payment_id')->nullable();
                $table->json('provider_response')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->timestamps();
                
                $table->index(['status', 'created_at']);
                $table->index('transaction_id');
            });
        } else {
            // Table exists, check if we need to add missing columns
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'transaction_id')) {
                    $table->string('transaction_id')->unique();
                }
                if (!Schema::hasColumn('payments', 'payable_type')) {
                    $table->morphs('payable');
                }
                if (!Schema::hasColumn('payments', 'currency')) {
                    $table->string('currency', 3)->default('EUR');
                }
                if (!Schema::hasColumn('payments', 'provider')) {
                    $table->string('provider')->nullable();
                }
                if (!Schema::hasColumn('payments', 'provider_payment_id')) {
                    $table->string('provider_payment_id')->nullable();
                }
                if (!Schema::hasColumn('payments', 'provider_response')) {
                    $table->json('provider_response')->nullable();
                }
                if (!Schema::hasColumn('payments', 'refunded_at')) {
                    $table->timestamp('refunded_at')->nullable();
                }
            });
            
            // Check en voeg indexes toe als ze niet bestaan
            $indexes = collect(Schema::getIndexes('payments'))->pluck('name')->toArray();
            
            Schema::table('payments', function (Blueprint $table) use ($indexes) {
                if (!in_array('payments_status_created_at_index', $indexes)) {
                    $table->index(['status', 'created_at'], 'payments_status_created_at_index');
                }
                if (!in_array('payments_transaction_id_index', $indexes)) {
                    $table->index('transaction_id', 'payments_transaction_id_index');
                }
            });
        }
    }

    public function down(): void
    {
        // Optioneel: we laten de payments table intact bij rollback
        // Schema::dropIfExists('payments');
    }
};
