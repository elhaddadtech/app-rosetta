<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Semester extends Model {
  use SoftDeletes;
  protected $table    = 'semesters';
  protected $fillable = ['libelle'];
  protected $hidden   = [
    'deleted_at',
    'create_at',
    'update_at',
  ];
}
