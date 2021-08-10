<?php

namespace Aurora\System\Models;

use Aurora\System\Classes\Model;

class AuthToken extends Model
{
    protected $table = 'core_auth_tokens';
    protected $fillable = [
        'Id',
        'UserId',
        'Token'
    ];
}