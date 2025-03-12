<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branche extends Model {
  use SoftDeletes;
  protected $table    = 'branches';
  protected $fillable = ['libelle'];
  protected $hidden   = [
    'deleted_at',
    'created_at',
    'updated_at',
  ];
}
