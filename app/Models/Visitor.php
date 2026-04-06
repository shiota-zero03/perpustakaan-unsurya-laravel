<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;
    protected $table = 'visitors';
    protected $primaryKey = 'id';
    protected $fillable = [
        'userId',
        'name',
        'activity',
        'date',
        'time',
        'email',
        'prodi',
        'id_anggota'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
