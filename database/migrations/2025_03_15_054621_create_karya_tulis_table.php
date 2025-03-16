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
        Schema::create('karya_tulis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('master_bukus')->onDelete('cascade');
            $table->string('judul')->nullable();
            $table->string('cover')->nullable();
            $table->string('penulis')->nullable();
            $table->string('nim')->nullable();
            $table->foreignId('facultyId')->nullable()->constrained('faculties')->onDelete('set null');
            $table->foreignId('studyProgramId')->nullable()->constrained('study_programs')->onDelete('set null');
            $table->integer('tahun_terbit')->nullable();
            $table->enum('jenis', ['Skripsi', 'TA', 'Tesis', 'Disertasi']);
            $table->string('no_urut')->nullable();
            $table->string('kode_klasifikasi')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->string('kode_rak')->nullable();
            $table->double('denda_harian')->default(1000);
            $table->string('document_1')->nullable();
            $table->string('document_2')->nullable();
            $table->string('document_3')->nullable();
            $table->string('document_4')->nullable();
            $table->string('document_5')->nullable();
            $table->string('document_6')->nullable();
            $table->string('document_7')->nullable();
            $table->string('document_8')->nullable();
            $table->string('document_9')->nullable();
            $table->string('document_10')->nullable();
            $table->string('abstrak')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karya_tulis');
    }
};
