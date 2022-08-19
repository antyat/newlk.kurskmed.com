<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RusHStudent extends Model
{
    use HasFactory;
    protected $connection = 'soda';
    public $table = "rus_h_students";
}
