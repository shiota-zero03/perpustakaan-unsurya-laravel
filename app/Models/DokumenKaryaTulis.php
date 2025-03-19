<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumenKaryaTulis extends Model
{
    use HasFactory;
    protected $table = 'dokumen_karya_tulis';
    protected $primaryKey = 'id';
    protected $fillable = [
        'karya_id',
        'judul_dokumen',
        'file_dokumen',
    ];
}
