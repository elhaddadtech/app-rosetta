<?php

namespace App\Models;

use App\Models\Result;
use Illuminate\Database\Eloquent\Model;

class Course extends Model {
  protected $table    = 'courses';
  protected $fillable = [
    'cours_name',
    'cours_progress',
    'cours_grade',
    'total_lessons',
    'note_lesson',
    'result_id', // Foreign key to results table
    'file',
  ];

  protected $hidden = [
    'create_at',
    'update_at',
  ];

  public function result() {
    return $this->belongsTo(Result::class, 'result_id');
  }

}
