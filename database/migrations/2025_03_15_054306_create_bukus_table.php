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
        Schema::create('bukus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('master_bukus')->onDelete('cascade');
            $table->string('judul')->nullable();
            $table->string('cover')->nullable();
            $table->string('penulis')->nullable();
            $table->string('penerbit')->nullable();
            $table->string('stok')->nullable();
            $table->integer('tahun_terbit')->nullable();
            $table->string('isbn')->nullable();
            $table->string('no_urut')->nullable();
            $table->string('kode_klasifikasi')->nullable();
            $table->string('link_book')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->string('kode_rak')->nullable();
            $table->double('denda_harian')->default(1000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bukus');
    }
};
