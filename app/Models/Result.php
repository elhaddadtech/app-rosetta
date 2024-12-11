<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model {
  use HasFactory, SoftDeletes;

  protected $fillable = [
    'type_test_1',
    'date_test_1',
    'score_test_1',
    'level_test_1',
    'desktop_time',
    'mobile_time',
    'test_Time_1',
    'total_time',
    'type_test_2',
    'date_test_2',
    'score_test_2',
    'level_test_2',
    'type_test_3',
    'date_test_3',
    'score_test_3',
    'level_test_3',
    'student_id',
    'language_id',
    'file',
  ];
  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
    'student',
    'language',
  ];
  protected $appends = [
    'language_libelle',
    'email',
    'full_name',
    'cne',
    'apogee',
    'group_libelle',
    'semester_libelle',
    'institution_libelle',
    'branch_libelle',

  ];
  public function getLanguageLibelleAttribute() {
    return $this->language ? $this->language?->libelle : null;
  }
  public function getFullNameAttribute() {
    $user = $this->student?->user;

    return $user?->first_name . ' ' . $user?->last_name;
  }
  public function getCneAttribute() {
    return $this->student ? $this->student?->cne : null;
  }
  public function getApogeeAttribute() {
    return $this->student ? $this->student?->apogee : null;
  }
  public function getEmailAttribute() {
    $user = $this->student?->user;

    return $user?->email;
  }
  public function getGroupLibelleAttribute() {
    $user = $this->student?->group;

    return $user?->libelle;
  }
  public function getSemesterLibelleAttribute() {
    $user = $this->student?->semester;

    return $user?->libelle;
  }
  public function getInstitutionLibelleAttribute() {
    $user = $this->student?->institution;

    return $user?->libelle;
  }
  public function getBranchLibelleAttribute() {
    $user = $this->student?->branch;

    return $user?->libelle;
  }
  public function student() {
    return $this->belongsTo(Student::class);
  }

  public function language() {
    return $this->belongsTo(Language::class);
  }
  public function doTests() {
    return $this->hasMany(DoTest::class);
  }
}
