<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Mengubah tipe kolom payment_proof dari string(255) menjadi longText
     * agar bisa menyimpan data gambar bukti bayar sebagai Base64 langsung di database.
     * Ini diperlukan karena Railway menggunakan ephemeral filesystem — file yang diupload
     * ke storage akan hilang setiap kali container direstart atau redeploy.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->longText('payment_proof')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_proof')->nullable()->change();
        });
    }
};
