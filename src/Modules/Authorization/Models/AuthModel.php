<?php

namespace App\Modules\Authorization\Models;
use Illuminate\Database\Eloquent\Model;

class AuthModel extends Model {
    protected $table = 'employees';

    protected $fillable = [
        'email',
        'password',
        'id'
    ];
}

