<?php

namespace Aurora\System\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Str;
use Aurora\System\Utils;

class Encrypt implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        $decrypted = Utils::DecryptValue($value);
        if ($decrypted) {
            return substr($decrypted, 6);
        }

        return false;
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => Utils::EncryptValue(Str::random(6) . $value)];
    }
}
