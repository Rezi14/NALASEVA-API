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
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('polyclinic_id')->constrained('polyclinics')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->string('queue_number');
            $table->date('date');
            $table->enum('status', ['booked', 'waiting', 'examining', 'completed', 'cancelled'])->default('booked');
            $table->timestamp('check_in_time')->nullable();
            $table->boolean('is_priority')->default(false)->comment('Jika true, trigger alert ke dokter');
            $table->timestamps();
            $table->softDeletes();
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
