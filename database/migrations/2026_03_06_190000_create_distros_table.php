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
        Schema::create('distro', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nama', 255);
            $table->text('alamat');
            $table->time('jam_buka');
            $table->time('jam_tutup');
            $table->string('hari_buka', 50);
            $table->string('hari_tutup', 50);
            $table->string('whatsapp', 20);
            $table->string('instagram', 255);
            $table->string('tiktok', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distro');
    }
};
