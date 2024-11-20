<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model {
  protected $table    = 'students';
  protected $fillable = [
    'cne', 'apogee', 'birthdate',
    'group_id', 'branch_id', 'semester_id',
    'institution_id', 'language_id', 'user_id',
    'first_access', 'last_access'];
}
