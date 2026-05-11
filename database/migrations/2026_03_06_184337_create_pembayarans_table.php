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
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('id_pembeli');
            $table->unsignedInteger('id_pesanan');
            $table->string('bukti_pembayaran')->nullable();
            $table->enum('status', ['Menunggu', 'Belum Konfirmasi', 'Terkonfirmasi'])->default('Menunggu');
            $table->timestamps();

            $table->foreign('id_pembeli')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_pesanan')->references('id')->on('pesanan')->cascadeOnDelete();
            $table->unique('id_pesanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
