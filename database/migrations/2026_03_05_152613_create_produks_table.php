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
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('warna', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 255);
            $table->string('kode', 20); // Stores CMYK format e.g. '0,100,100,0'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('ukuran', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 255);
            $table->integer('panjang')->nullable(); // cm
            $table->integer('lebar')->nullable();   // cm
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bahan', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_kategori');
            $table->unsignedInteger('id_warna')->nullable();
            $table->unsignedInteger('id_ukuran');
            $table->string('nama', 255);
            $table->integer('stok');
            $table->integer('stok_minimum');
            $table->integer('durasi_produksi'); // Menit
            $table->integer('durasi_restok'); // Menit ditampilkan dalam format Hari
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('id_kategori')->references('id')->on('kategori')->cascadeOnDelete();
            $table->foreign('id_warna')->references('id')->on('warna')->cascadeOnDelete();
            $table->foreign('id_ukuran')->references('id')->on('ukuran')->cascadeOnDelete();
        });

        Schema::create('produk', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_bahan');
            $table->string('nama', 255);
            $table->integer('harga');
            $table->text('deskripsi')->nullable();
            $table->string('gambar');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('id_bahan')->references('id')->on('bahan')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk');
        Schema::dropIfExists('bahan');
        Schema::dropIfExists('ukuran');
        Schema::dropIfExists('warna');
        Schema::dropIfExists('kategori');
    }
};
