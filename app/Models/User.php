<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
  /** @use HasFactory<\Database\Factories\UserFactory> */
  use HasFactory, Notifiable, HasApiTokens;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'role_id',
  ];

  public function getCneAttribute() {
    return $this->student ? $this->student?->cne : null;
  }
  public function getApogeeAttribute() {
    return $this->student ? $this->student?->apogee : null;
  }
  public function getBirthdateAttribute() {
    return $this->student ? $this->student?->birthdate : null;
  }
  public function getFirstAccessAttribute() {
    return $this->student ? $this->student?->first_access : null;
  }
  public function getLastAccessAttribute() {
    return $this->student ? $this->student?->last_access : null;
  }
  public function getGroupLibelleAttribute() {
    $student = $this->student?->group;

    return $student?->libelle;
  }
  public function getBranchLibelleAttribute() {
    $student = $this->student?->branch;

    return $student?->libelle;
  }
  public function getSemesterLibelleAttribute() {
    $student = $this->student?->semester;

    return $student?->libelle;
  }
  public function getInstitutionLibelleAttribute() {
    $student = $this->student?->institution;

    return $student?->libelle;
  }
  public function getFullNameAttribute() {
    return "{$this->first_name} {$this->last_name}";
  }
  public function getRoleLibelleAttribute() {
    return $this->role ? $this->role->libelle : null;
  }
  public function role() {
    return $this->belongsTo(Role::class, 'role_id');
  }
  public function student() {
    return $this->hasOne(Student::class);
  }

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected $hidden = [
    'remember_token',
    'first_name',
    'last_name',
    'created_at',
    'updated_at',
    'role',
    'student',
  ];
  protected $appends = [
    'full_name',
    'role_libelle',
    'cne',
    'apogee',
    'birthdate',
    'institution_libelle',
    'group_libelle',
    'branch_libelle',
    'semester_libelle',
    'first_access',
    'last_access',
  ];
  protected function casts(): array {
    return [
      'email_verified_at' => 'datetime',
      'password'          => 'hashed',
    ];
  }
}
