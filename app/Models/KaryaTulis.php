<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KaryaTulis extends Model
{
    use HasFactory;
    protected $table = 'karya_tulis';
    protected $primaryKey = 'id';
    protected $fillable = [
        'book_id',
        'judul',
        'cover',
        'penulis',
        'nim',
        'facultyId',
        'studyProgramId',
        'tahun_terbit',
        'jenis',
        'no_urut',
        'kode_klasifikasi',
        'tanggal_masuk',
        'kode_rak',
        'denda_harian',
        'abstrak',
    ];

    public function master()
    {
        return $this->belongsTo(MasterBuku::class, 'book_id');
    }

    public function fakultas()
    {
        return $this->belongsTo(Faculty::class, 'facultyId');
    }

    public function prodi()
    {
        return $this->belongsTo(StudyProgram::class, 'studyProgramId');
    }

    public function dokumen()
    {
        return $this->hasMany(DokumenKaryaTulis::class, 'karya_id');
    }
}
