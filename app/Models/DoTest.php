<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DoTest extends Model {
  use SoftDeletes;
  protected $table    = 'do_test';
  protected $fillable = [
    'result_id',
    'student_id',
    'year',
  ];
  protected $hidden = [
    'deleted_at',
    'create_at',
    'update_at',
  ];
  public function result() {
    return $this->belongsTo(Result::class);
  }

  public function student() {
    return $this->belongsTo(Student::class);
  }
}
