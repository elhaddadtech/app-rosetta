<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Teacher extends Model {
  use HasFactory;
  protected $table    = 'teachers';
  protected $fillable = [
    'status', 'role_teach', 'group_id',
    'branch_id', 'institution_id', 'user_id',
  ];
   // Relationships
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
