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
            $table->integer('total');
            $table->enum('prioritas', ['Normal', 'Tinggi'])->default('Normal');
            $table->enum('status', ['Pesanan Baru', 'Dalam Produksi', 'Selesai', 'Dibatalkan'])->default('Pesanan Baru');
            $table->date('tenggat_waktu')->nullable();
            $table->date('estimasi_selesai')->nullable();
            $table->timestamps();

            $table->foreign('id_pembeli')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('detail_pesanan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_pesanan');
            $table->unsignedInteger('id_produk');
            $table->integer('jumlah');
            $table->integer('subtotal');

            $table->foreign('id_pesanan')->references('id')->on('pesanan')->cascadeOnDelete();
            $table->foreign('id_produk')->references('id')->on('produk')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_pesanan');
        Schema::dropIfExists('pesanan');
    }
};
