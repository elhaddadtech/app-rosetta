<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Result extends Model {
  use HasFactory;

  protected $fillable = [
    'Test_1',
    'Score_Test_1',
    'Time_PC',
    'Time_Mobile',
    'Time_All',
    'Activity',
    'Pass_Score',
    'Score_All',
    'Test_2',
    'Score_Test_2',
    'Test_3',
    'Score_Test_3',
    'Test_4',
    'Score_Test_4',
    'student_id',
    'language_id',
  ];
  protected $hidden = [
    'created_at',
    'updated_at',
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
  public function doTests()
  {
      return $this->hasMany(DoTest::class);
  }
}
