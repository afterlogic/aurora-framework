<?php

namespace Aurora\System\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;
use Aurora\System\Utils;

class Encrypt implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return substr(Utils::DecryptValue($value), 6);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => Utils::EncryptValue(Str::random(6) . $value)];
    }
}