<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Buku extends Model
{
    use HasFactory;
    protected $table = 'bukus';
    protected $primaryKey = 'id';
    protected $fillable = [
        'book_id',
        'no_urut',
        'cover',
        'kode_klasifikasi',
        'judul',
        'penulis',
        'penerbit',
        'tahun_terbit',
        'isbn',
        'tanggal_masuk',
        'kode_rak',
        'stok',
        'denda_harian',
        'link_book',
        'dipinjam'
    ];

    public function master()
    {
        return $this->belongsTo(MasterBuku::class, 'book_id');
    }
}
