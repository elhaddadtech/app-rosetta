<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RangeCefefr extends Model {
  protected $fillable = [
    'language',
    'scaled_score',
    'semester',
  ];
  protected $table  = 'range_cefefrs';
  protected $hidden = ['created_at', 'updated_at'];

}
