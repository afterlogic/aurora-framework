<?php

namespace Aurora\System\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Aurora\System\Utils;
use Aurora\System\SecureCrypt;

class Encrypt implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        $decrypted = Utils::DecryptValue($value);
        if ($decrypted === false) {
            return false;
        }

        // Legacy (XXTEA) values had a 6-char random salt prepended before
        // encryption; new AES-GCM values don't, so only strip it for legacy data.
        return SecureCrypt::isLegacyFormat($value) ? substr($decrypted, 6) : $decrypted;
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => Utils::EncryptValue($value)];
    }
}
