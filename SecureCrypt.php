<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

class SecureCrypt
{
    private const CIPHER = 'aes-256-gcm';
    private const KEY_LEN = 32;
    private const NONCE_LEN = 12;
    private const TAG_LEN = 16;

    /**
     * New key through KDF — used only for V3 (AES-GCM)
     */
    private static function getRawKey(): string
    {
        return hash_hkdf('sha256', Api::$sEncryptionKey, self::KEY_LEN, 'aurora-encrypt-v3');
    }

    /**
     * Key in legacy format — only needed for decrypting legacy data
     */
    private static function getLegacyKey()
    {
        return ctype_xdigit(Api::$sEncryptionKey)
            ? hex2bin(Api::$sEncryptionKey)
            : Api::$sEncryptionKey;
    }

     /**
     * Summary of EncryptValue
     * @param string $sValue
     * @return string|false
     */
    public static function EncryptValue(string $sValue)
    {
        $key = self::getRawKey();
        $nonce = random_bytes(self::NONCE_LEN);
        $tag = '';

        $cipher = openssl_encrypt(
            $sValue,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LEN
        );

        if ($cipher === false) {
            return false;
        }

        return Utils::UrlSafeBase64Encode('V2' . $nonce . $tag . $cipher);
    }

    /**
     * Summary of DecryptValue
     * @param string $sEncryptedValue
     * @return bool|string
     */
    public static function DecryptValue(?string $sEncryptedValue)
    {
        if ($sEncryptedValue === null || trim($sEncryptedValue) === '') {
            return false;
        }

        $raw = Utils::UrlSafeBase64Decode(trim($sEncryptedValue));

        if ($raw === false || $raw === '') {
            return false;
        }

        // New format: AES-256-GCM
        if (strncmp($raw, 'V2', 2) === 0) {
            return self::decryptV2($raw);
        }

        // Legacy: everything that was encrypted via XXTEA (V2 and the very old format)
        return self::decryptLegacy($raw);
    }

    /**
     * Summary of decryptV2
     * @param string $raw
     * @return bool|string
     */
    private static function decryptV2(string $raw)
    {
        $minLen = 2 + self::NONCE_LEN + self::TAG_LEN;
        if (strlen($raw) < $minLen) {
            return false;
        }

        $nonce = substr($raw, 2, self::NONCE_LEN);
        $tag = substr($raw, 2 + self::NONCE_LEN, self::TAG_LEN);
        $cipherText = substr($raw, $minLen);

        $key = self::getRawKey();

        return openssl_decrypt(
            $cipherText,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );
    }

    /**
     * Fully replicates the original DecryptValue logic,
     * but is used only as a fallback for legacy data.
     */
    private static function decryptLegacy(string $sEncryptedValue)
    {
        $mKey = self::getLegacyKey();
        $sCryptKey = '$2y$07$' . Api::$sEncryptionKey . '$';

        $aKeys = [
            $mKey,
            md5($sCryptKey),
            $sCryptKey,
        ];

        foreach ($aKeys as $key) {
            $sValue = \Aurora\System\Utils\Crypt::XxteaDecrypt($sEncryptedValue, $key);

            if ($sValue === false) {
                continue;
            }

            // Completely old format — no version and no salt
            self::logLegacyUsage('V1');
            return $sValue;
        }

        return false;
    }

    /**
     * Logs the usage of the legacy format to track migration.
     * NEVER log the actual decrypted data (passwords, tokens).
     */
    private static function logLegacyUsage(string $format): void
    {
        error_log(sprintf(
            '[SecureCrypt] Legacy format "%s" decrypted at %s',
            $format,
            date('Y-m-d H:i:s')
        ));
    }
}