<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersAd extends Model
{
    use HasFactory;
    protected $connection = 'soda';
    public $table = "users_ad";

    public function user()
    {
        return $this->morphOne(Usergable::class, 'usergable');
    }
}
