<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chief extends Model {
  use HasFactory,SoftDeletes;


  protected $fillable = [
    'institution_id',
    'user_id',
  ];
  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
    'institution',
    'user',
  ];
  protected $appends = [
    'institution_libelle',
    'email',
    'full_name',
    'role',
    'cne',
    'apogee',
    'group',
    'branch',
    'semester',
  ];
  public function getInstitutionLibelleAttribute() {
    $student = $this->institution;

    return $student?->libelle;
  }
  public function getEmailAttribute() {
    return $this->user?->email;
  }
  public function getFullNameAttribute() {
    return $this->user?->full_name;
  }
  public function getRoleAttribute() {
    return $this->user?->role_libelle;
  }

  public function getCneAttribute(){
    return $this->user?->cne;
  }
  public function getApogeeAttribute(){
    return $this->user?->apogee;
  }
  public function getGroupAttribute(){
    return $this->user?->group_libelle;
  }
  public function getBranchAttribute(){
    return $this->user?->branch_libelle;
  }
  public function getSemesterAttribute(){
    return $this->user?->semester_libelle;
  }

  public function institution() {
    return $this->belongsTo(Institution::class);
  }

  public function user() {
    return $this->belongsTo(User::class);
  }
}
