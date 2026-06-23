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
        Schema::create('desain', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_detail_pesanan');
            $table->longText('desain_json')->nullable();
            
            $table->foreign('id_detail_pesanan')->references('id')->on('detail_pesanan')->onDelete('cascade');
        });

        Schema::create('teks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_desain');
            $table->string('teks');
            
            $table->foreign('id_desain')->references('id')->on('desain')->onDelete('cascade');
        });

        Schema::create('gambar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_desain');
            $table->string('file');
            
            $table->foreign('id_desain')->references('id')->on('desain')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gambar');
        Schema::dropIfExists('teks');
        Schema::dropIfExists('desain');
    }
};

