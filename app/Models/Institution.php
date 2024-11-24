<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model {
  use SoftDeletes;
  protected $fillable = [
    'libelle',
  ];
  protected $hidden = [
    'deleted_at',
    'create_at',
    'update_at',
  ];
  protected $table = 'institutions';
}
