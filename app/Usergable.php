<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usergable extends Model
{
    use HasFactory;
    protected $connection = 'soda';
    public $table = "usergables";

    public function usergable() {
        return $this->MorphTo();
    }
}
