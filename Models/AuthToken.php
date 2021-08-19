<?php

namespace Aurora\System\Models;

use Aurora\System\Classes\Model;

class AuthToken extends Model
{
    protected $table = 'core_auth_tokens';

	protected $foreignModel = 'Aurora\Modules\Core\Models\User';
	protected $foreignModelIdColumn = 'IdUser'; // Column that refers to an external table

    protected $fillable = [
        'Id',
        'UserId',
        'Token'
    ];
}