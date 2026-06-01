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
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examination_id')->index()->constrained('examinations')->onDelete('cascade');
            $table->foreignId('medicine_id')->index()->constrained('medicines')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('instruction'); // e.g. 3x1 setelah makan
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
