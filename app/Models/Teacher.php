<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model {
  use HasFactory;
  protected $table    = 'teachers';
  protected $fillable = [
    'status', 'role_teach', 'group_id',
    'branch_id', 'institution_id', 'user_id',
  ];
  protected $hidden = [
    'created_at',
    'updated_at',
    'group',
    'branch',
    'institution',
    'user',
  ];
  protected $appends = [
    // 'role_libelle',
    'user_libelle',
     "group_libelle",
     "branch_libelle",
     "institution_libelle",
     "user_email",
     "user_role",
  ];
  // Relationships

  public function getGroupLibelleAttribute() {
    return $this->group? $this->group->libelle : null;
  }
  public function getInstitutionLibelleAttribute() {
    return $this->institution? $this->institution->libelle : null;
  }
  public function getBranchLibelleAttribute() {
    return $this->branch? $this->branch->libelle : null;
  }
  public function getUserLibelleAttribute() {
    return $this->user? $this->user->first_name.' '.$this->user->last_name : null;
  }
public function getUserEmailAttribute() {
  return $this->user? $this->user->email : null;
}
public function getUserRoleAttribute() {
  return $this->user? $this->user->role->libelle : null;
}
  // public function getRoleLibelleAttribute() {
  //   return $this->role? $this->role->libelle : null;
  // }
  public function group() {
    return $this->belongsTo(Group::class);
  }

  public function branch() {
    return $this->belongsTo(Branche::class); // Assuming 'Branche' is the correct model name
  }

  public function institution() {
    return $this->belongsTo(Institution::class);
  }

  public function user() {
    return $this->belongsTo(User::class);
  }
}
