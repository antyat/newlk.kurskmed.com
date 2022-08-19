<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EngHStudent extends Model
{
    use HasFactory;

    protected $connection = 'soda';
    public $table = "eng_h_students";
}
