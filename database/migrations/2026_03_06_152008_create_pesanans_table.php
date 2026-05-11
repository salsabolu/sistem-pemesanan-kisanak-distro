<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('id_pembeli');
            $table->unsignedInteger('id_produk');
            $table->integer('jumlah');
            $table->decimal('total_harga', 12, 2);
            $table->dateTime('tenggat_waktu');
            $table->text('catatan')->nullable();
            $table->enum('prioritas', ['Normal', 'Tinggi'])->default('Normal');
            $table->enum('status', ['Pesanan Baru', 'Dalam Produksi', 'Selesai', 'Dibatalkan'])->default('Pesanan Baru');
            $table->dateTime('estimasi_selesai');
            $table->timestamps();

            $table->foreign('id_pembeli')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_produk')->references('id')->on('produk')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
