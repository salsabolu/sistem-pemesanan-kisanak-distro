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
        Schema::create('kategori', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 255);
            $table->timestamps();
        });

        Schema::create('warna', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 255);
            $table->timestamps();
        });

        Schema::create('ukuran', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 255);
            $table->timestamps();
        });

        Schema::create('produk', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_kategori');
            $table->unsignedInteger('id_warna');
            $table->unsignedInteger('id_ukuran');
            $table->string('nama', 255);
            $table->string('satuan', 255);
            $table->decimal('harga', 12, 2);
            $table->integer('stok');
            $table->integer('stok_minimum');
            $table->integer('durasi_produksi');
            $table->integer('durasi_restok');
            $table->text('deskripsi')->nullable();
            $table->string('gambar');
            $table->enum('status', ['Aktif', 'Non-Aktif'])->default('Aktif');
            $table->timestamps();

            $table->foreign('id_kategori')->references('id')->on('kategori')->cascadeOnDelete();
            $table->foreign('id_warna')->references('id')->on('warna')->cascadeOnDelete();
            $table->foreign('id_ukuran')->references('id')->on('ukuran')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk');
        Schema::dropIfExists('ukuran');
        Schema::dropIfExists('warna');
        Schema::dropIfExists('kategori');
    }
};
