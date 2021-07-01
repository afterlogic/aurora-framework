<?php

namespace Aurora\System\Models;

use Aurora\System\Classes\Model;

class AuthToken extends Model
{
    protected $fillable = [
        'UserId',
        'Toket',
        'LastUsageDateTime'
    ];
}