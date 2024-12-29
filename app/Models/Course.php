<?php

namespace App\Models;

use App\Models\Result;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
  protected $table = 'courses';
  protected $fillable = [
    'cours_name',
    'cours_progress',
    'cours_grade',
    'total_lessons',
    'result_id', // Foreign key to results table
    'file'
];

public function result()
    {
        return $this->belongsTo(Result::class, 'result_id');
    }

}
