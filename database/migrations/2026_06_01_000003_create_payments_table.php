<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->index()->constrained('queues')->onDelete('cascade');
            $table->foreignId('examination_id')->nullable()->index()->constrained('examinations')->onDelete('set null');
            $table->string('transaction_number')->unique();
            $table->decimal('registration_fee', 10, 2)->default(10000.00);
            $table->decimal('medicine_fee', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->default('transfer_bank');
            $table->string('payment_proof')->nullable();
            $table->enum('status', ['pending', 'waiting_verification', 'paid', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
