<?php

namespace Aurora\System\Models;

use Aurora\System\Classes\Model;
use Aurora\Modules\Core\Models\User;

/**
 * Aurora\System\Models\AuthToken
 *
 * @property integer $Id
 * @property integer $UserId
 * @property string $Token
 * @property integer $LastUsageDateTime
 * @property \Illuminate\Support\Carbon|null $CreatedAt
 * @property \Illuminate\Support\Carbon|null $UpdatedAt
 * @property-read mixed $entity_id
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\System\Models\AuthToken firstWhere(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken query()
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\System\Models\AuthToken where(Closure|string|array|\Illuminate\Database\Query\Expression $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Aurora\System\Models\AuthToken whereIn(string $column, mixed $values, string $boolean = 'and', bool $not = false)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken whereLastUsageDateTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AuthToken whereUserId($value)
 * @mixin \Eloquent
 */
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
