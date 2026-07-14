<?php

namespace Aurora\System\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Aurora\System\Utils;

class Encrypt implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return Utils::DecryptValue($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => Utils::EncryptValue($value)];
    }
}
