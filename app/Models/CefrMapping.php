<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CefrMapping extends Model {
  use HasFactory;

  protected $table = 'cefr_mappings';

  protected $fillable = ['level', 'language', 'lesson', 'seuil_heures_jours', 'noteCC1_ratio', 'noteCC2_ratio'];
  protected $hidden   = ['created_at', 'updated_at'];
}
