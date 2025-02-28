<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudyProgram extends Model
{
    use HasFactory;
    protected $table = 'study_programs';
    protected $primaryKey = 'id';
    protected $fillable = [
        'fakultas_id',
        'name',
        'code',
    ];

    public function fakultas()
    {
        return $this->belongsTo(Faculty::class, 'fakultas_id');
    }
}
