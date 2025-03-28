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
        Schema::create('dendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transactionId')->constrained('transactions')->onDelete('cascade');
            $table->integer('total_keterlambatan');
            $table->double('denda_keterlambatan');
            $table->double('total_denda');
            $table->double('sudah_dibayar');
            $table->enum('status_pembayaran', ['Belum Dibayar', 'Belum Lunas', 'Lunas'])->default('Belum Dibayar');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dendas');
    }
};
