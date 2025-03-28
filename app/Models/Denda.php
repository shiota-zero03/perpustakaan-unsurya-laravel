<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Denda extends Model
{
    use HasFactory;
    protected $table = 'dendas';
    protected $primaryKey = 'id';
    protected $fillable = [
        'transactionId',
        'total_keterlambatan',
        'denda_keterlambatan',
        'status_pembayaran',
        'tanggal_bayar'
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaction::class, 'transactionId');
    }
}
