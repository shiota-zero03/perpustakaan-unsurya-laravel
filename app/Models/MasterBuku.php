<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBuku extends Model
{
    use HasFactory;
    protected $table = 'master_bukus';
    protected $primaryKey = 'id';
    protected $fillable = [
        'book_id',
        'type',
    ];

    public function buku()
    {
        return $this->hasOne(Buku::class, 'book_id');
    }

    public function karya()
    {
        return $this->hasOne(KaryaTulis::class, 'book_id');
    }
}
