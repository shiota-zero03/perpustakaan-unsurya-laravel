<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transactions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'transaction_code',
        'userId',
        'id_anggota',
        'nama_anggota',
        'bukuId',
        'id_buku',
        'judul_buku',
        'penulis',
        'tanggal_peminjaman',
        'jatuh_tempo',
        'keterangan_peminjaman',
        'tanggal_pengembalian',
        'status_pengembalian',
        'keterangan_pengembalian',
    ];

    public function denda()
    {
        return $this->hasOne(Denda::class, 'transactionId');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            do {
                $code = 'TRX-' . strtoupper(Str::random(8)); // Contoh: TRX-9A7BC1DE
            } while (self::where('transaction_code', $code)->exists());

            $transaction->transaction_code = $code;
        });
    }
}
