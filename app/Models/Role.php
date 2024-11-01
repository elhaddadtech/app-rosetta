<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    // use SoftDeletes;
    protected $fillable = [
        'Libelle',        
    ];
    protected $table = 'roles';
    public function users()
    {
        return $this->hasMany(User::class, 'id_role'); // Relationship with User model
    }
    // protected $hidden = [
    //     'created_at',
    //     'updated_at'
    // ];
}
