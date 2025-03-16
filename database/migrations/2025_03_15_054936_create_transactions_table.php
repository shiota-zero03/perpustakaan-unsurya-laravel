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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->nullable();
            $table->foreignId('userId')->nullable()->constrained('users')->onDelete('set null');
            $table->string('id_anggota')->nullable();
            $table->string('nama_anggota')->nullable();
            $table->foreignId('bukuId')->nullable()->constrained('master_bukus')->onDelete('set null');
            $table->string('id_buku')->nullable();
            $table->string('judul_buku')->nullable();
            $table->string('penulis')->nullable();
            $table->date('tanggal_peminjaman');
            $table->date('jatuh_tempo');
            $table->text('keterangan_peminjaman')->nullable();
            $table->date('tanggal_pengembalian')->nullable();
            $table->enum('status_pengembalian', ['Tepat Waktu', 'Terlambat', 'Belum Dikembalikan'])->default('Belum Dikembalikan');
            $table->text('keterangan_pengembalian')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
