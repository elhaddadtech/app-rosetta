<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table = 'students';
    protected $fillable = ['CNE','Apogee','date_naissance','id_group','id_branch','id_semester','id_institution','id_language','id_user','First_Access','Last_Access'];
}
