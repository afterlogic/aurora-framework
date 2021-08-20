<?php

namespace Aurora\System\Models;

use Aurora\System\Classes\Model;
use Aurora\Modules\Core\Models\User;

class AuthToken extends Model
{
    protected $table = 'core_auth_tokens';

	protected $foreignModel = User::class;
	protected $foreignModelIdColumn = 'UserId'; // Column that refers to an external table

    protected $fillable = [
        'Id',
        'UserId',
        'Token'
    ];
}