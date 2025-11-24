<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'id';
    public $incrementing = false;      // 문자열 PK
    protected $keyType = 'string';

    protected $fillable = ['id', 'password', 'name'];

    protected $hidden = ['password'];
}
