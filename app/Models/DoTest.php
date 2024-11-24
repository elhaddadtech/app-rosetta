<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoTest extends Model
{
  protected $table = "do_test";
  protected $fillable =[
    "result_id",
    "student_id",
    "year",
  ];
  public function result()
  {
      return $this->belongsTo(Result::class);
  }

  public function student()
  {
      return $this->belongsTo(Student::class);
  }
}
