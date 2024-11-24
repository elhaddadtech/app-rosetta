<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model {
  protected $table = 'students';
  use HasFactory;
  //  SoftDeletes;

  protected $fillable = [
    'cne',
    'apogee',
    'birthdate',
    'group_id',
    'branch_id',
    'semester_id',
    'institution_id',
    'language_id',
    'user_id',
    'first_access',
    'last_access',
  ];

  public function group() {
    return $this->belongsTo(Group::class);
  }

  public function branch() {
    return $this->belongsTo(Branche::class);
  }

  public function semester() {
    return $this->belongsTo(Semester::class);
  }

  public function institution() {
    return $this->belongsTo(Institution::class);
  }

  public function language() {
    return $this->belongsTo(Language::class);
  }

  public function user() {
    return $this->belongsTo(User::class);
  }
}
