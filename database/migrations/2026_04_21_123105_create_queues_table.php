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
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->index()->constrained('patients')->onDelete('cascade');
            $table->foreignId('polyclinic_id')->index()->constrained('polyclinics')->onDelete('cascade');
            $table->foreignId('doctor_id')->index()->constrained('doctors')->onDelete('cascade');
            $table->string('queue_number');
            $table->date('date')->index();
            $table->enum('status', ['booked', 'waiting', 'examining', 'completed', 'cancelled'])->default('booked')->index();
            $table->timestamp('check_in_time')->nullable();
            $table->timestamp('called_time')->nullable();
            $table->boolean('is_priority')->default(false)->comment('Jika true, trigger alert ke dokter');
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for fast polyclinic/doctor daily queue lookups
            $table->index(['polyclinic_id', 'date', 'status'], 'queues_poly_date_status_idx');
            $table->index(['doctor_id', 'date', 'status'], 'queues_doc_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
