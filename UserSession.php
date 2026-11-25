<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

use Aurora\System\Models\AuthToken;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class UserSession
{
    public const TOKEN_VERSION = '4.0';
    private const SETUP_SESSION_KEY = 'SuperAdminSetupSession';

    public static $aTokensCache = [];

    private static function isSuperAdminToken(array $data)
    {
        return ($data['token'] ?? '') === 'admin' || (int) ($data['id'] ?? 0) === -1;
    }

    private static function adminSessions()
    {
        return \Illuminate\Database\Capsule\Manager::table('core_superadmin_sessions');
    }

    private function createAdminSession(array &$data)
    {
        $settings = Api::GetSettings();
        $days = (int) $settings->GetValue('SuperAdminAuthTokenExpirationLifetimeDays', 1);
        $data['@expire'] = time() + max(1, $days) * 86400;
        $data['@session'] = bin2hex(random_bytes(32));
        $hash = hash('sha256', $data['@session']);
        if ($settings->DBName === '') {
            // The setup UI needs a session to configure the database and run migrations.
            $data['@setup'] = true;
            Session::Set(self::SETUP_SESSION_KEY, $hash);
            return;
        }

        self::adminSessions()->where('ExpiresAt', '<=', time())->delete();
        self::adminSessions()->insert(['SessionHash' => $hash, 'ExpiresAt' => $data['@expire']]);
    }

    private function isActiveAdminSession(array $data)
    {
        if (($data['token'] ?? '') !== 'admin' || (int) ($data['id'] ?? 0) !== -1
            || !is_string($data['@session'] ?? null) || ($data['@expire'] ?? 0) <= time()) {
            return false;
        }
        $hash = hash('sha256', $data['@session']);
        if (!empty($data['@setup'])) {
            return hash_equals($hash, Session::get(self::SETUP_SESSION_KEY, ''));
        }
        return self::adminSessions()->where('SessionHash', $hash)->where('ExpiresAt', '>', time())->exists();
    }

    public static function getTokenData($oAccount, $bSignMe = true)
    {
        return [
            'token' => 'auth',
            'sign-me' => $bSignMe,
            'id' => $oAccount->IdUser,
            'account' => $oAccount->Id,
            'account_type' => get_class($oAccount)
        ];
    }


    public function Set($aData, $iTime = 0, $iExpire = 0)
    {
        $aData['@time'] = $iTime;
        $aData['@expire'] = $iExpire;
        $aData['@ver'] = self::TOKEN_VERSION;
        $isAdmin = self::isSuperAdminToken($aData);
        if ($isAdmin) {
            $this->createAdminSession($aData);
        }
        $sAuthToken = Api::EncodeKeyValues(
            $aData
        );

        if (!$isAdmin && \Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false)) {

            $account = $aData['account'] ?? 0;
            $account_type = $aData['account_type'] ?? '';

            $this->SetToDB($aData['id'], $account, $account_type, $sAuthToken);
        }

        return $sAuthToken;
    }

    public function UpdateTimestamp($sAuthToken, $iTime = 0)
    {
        $aData = $this->Get($sAuthToken);
        if (!$aData) {
            return false;
        }
        if (self::isSuperAdminToken($aData)) {
            // Keep the original deadline and session ID. A concurrent logout revokes both token values.
            $aData['@time'] = $iTime;
            $sNewAuthToken = Api::EncodeKeyValues($aData);
        } else {
            $sNewAuthToken = $this->Set($aData, $iTime);
        }

        // Reissuing the token invalidates anything keyed off the old token value (e.g. the
        // 2FA trusted-device AuthTokenHash in TwoFactorAuth\Manager::saveDevice()). Let
        // listeners re-key their state to the new token instead of silently going stale.
        $mResult = null;
        $aArgs = [
            'UserId' => $aData['id'] ?? null,
            'OldAuthToken' => $sAuthToken,
            'NewAuthToken' => $sNewAuthToken,
        ];
        Api::GetModuleManager()->broadcastEvent(
            'System',
            'UserSession::RefreshAuthToken::after',
            $aArgs,
            $mResult
        );

        return $sNewAuthToken;
    }

    public function Get($sAuthToken)
    {
        $mAuthTokenData = false;
        $mResult = true;

        if (is_string($sAuthToken) && strlen($sAuthToken) !== 0) {
            $mAuthTokenData = Api::DecodeKeyValues($sAuthToken);
            if (self::isSuperAdminToken($mAuthTokenData)) {
                if (!$this->isActiveAdminSession($mAuthTokenData)) {
                    return false;
                }
            } elseif (\Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false) && !$this->GetFromDB($sAuthToken)) {
                return false;
            }

            $sReason = '';

            // checking the validity of auth token data
            if ($mAuthTokenData && isset($mAuthTokenData['id'])) {
                $oUser = Api::getUserById((int) $mAuthTokenData['id']);

                // check if user still exists and is not disabled
                if (!$oUser) {
                    $mResult = false;
                    $sReason = 'user not found';
                } elseif ($oUser->IsDisabled) {
                    $mResult = false;
                    $sReason = 'user is disabled';
                }

                // check auth token version
                if ($mResult) {
                    if ((isset($mAuthTokenData['@ver']) && $mAuthTokenData['@ver'] !== self::TOKEN_VERSION) || !isset($mAuthTokenData['@ver'])) {
                        $mResult = false;
                        $sReason = 'auth token version mismatch';
                    }
                }

                // check auth token expiration date
                if ($mResult) {
                    $iExpireTime = (int) isset($mAuthTokenData['@expire']) ? $mAuthTokenData['@expire'] : 0;
                    if ($iExpireTime > 0 && $iExpireTime < time()) {
                        $mResult = false;
                        $sReason = 'auth token expired';
                    }
                }

                // checking the token is valid from timestamp
                if ($mResult && isset($mAuthTokenData['account'], $mAuthTokenData['account_type']) && class_exists($mAuthTokenData['account_type'])) {
                    $iTime = (int) $mAuthTokenData['@time'];
                    $oAccount = $mAuthTokenData['account_type']::where('Id', $mAuthTokenData['account'])->first();
                    $iTokensValidFromTimestamp = (int) ($oAccount ? $oAccount->getExtendedProp('TokensValidFromTimestamp') : 0);

                    // Any token created before a password-change invalidation timestamp should be rejected,
                    // including tokens that were created without an explicit timestamp (for example, remember-me sessions).
                    if ($oAccount && $iTokensValidFromTimestamp > 0 && ($iTime === 0 || $iTokensValidFromTimestamp > $iTime)) {
                        $mResult = false;
                        $sReason = 'auth token invalidated by password change';
                    }
                }

                // check user sessions that are considered expired
                if ($mResult) {
                    if ((isset($mAuthTokenData['sign-me']) && !((bool) $mAuthTokenData['sign-me'])) || (!isset($mAuthTokenData['sign-me']))) {
                        $iTime = 0;
                        if (isset($mAuthTokenData['@time'])) {
                            $iTime = (int) $mAuthTokenData['@time'];
                        }
                        $iExpireUserSessionsBeforeTimestamp = \Aurora\System\Api::GetSettings()->GetValue("ExpireUserSessionsBeforeTimestamp", 0);
                        if ($iExpireUserSessionsBeforeTimestamp > $iTime && $iTime > 0) {
                            $mResult = false;
                            $sReason = 'user sessions expired by admin';
                        }
                    }
                }
            } else {
                $mResult = false;
                $sReason = 'auth token could not be decoded';
            }

            if (!$mResult) {
                $this->Delete($sAuthToken);
                $mAuthTokenData = $mResult;

                \Aurora\System\Api::Log('Auth token rejected: ' . ($sReason !== '' ? $sReason : 'unknown reason'));
                \Aurora\System\Api::LogObject($mAuthTokenData);
            }
        }

        return $mAuthTokenData;
    }

    public function Delete($sAuthToken)
    {
        if (!is_string($sAuthToken)) {
            return;
        }
        $data = Api::DecodeKeyValues($sAuthToken);
        if (self::isSuperAdminToken($data)) {
            if (is_string($data['@session'] ?? null)) {
                $hash = hash('sha256', $data['@session']);
                if (empty($data['@setup'])) {
                    self::adminSessions()->where('SessionHash', $hash)->delete();
                } elseif (hash_equals($hash, Session::get(self::SETUP_SESSION_KEY, ''))) {
                    Session::clear(self::SETUP_SESSION_KEY);
                }
            }
        } elseif (\Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false)) {
            try {
                $this->DeleteFromDB($sAuthToken);
            } catch (\Aurora\System\Exceptions\DbException $oEx) {
                // DB is not configured
            }
        }

        unset(self::$aTokensCache[$sAuthToken]);
    }

    public function DeleteAllUserSessions($iUserId)
    {
        return AuthToken::where('UserId', $iUserId)->delete();
    }

    public function DeleteAllAccountSessions($oAccount)
    {
        if ($oAccount instanceof \Aurora\System\Classes\Account) {
            $iAccountId = $oAccount->Id;
            $sAccountType = get_class($oAccount);
            if (\Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false)) {
                try {
                    AuthToken::where('AccountId', $iAccountId)->where('AccountType', $sAccountType)->delete();
                } catch (\Aurora\System\Exceptions\DbException $oEx) {
                    // DB is not configured
                }
            } else {
                if (class_exists($sAccountType)) {
                    $oAccount = $sAccountType::where('Id', $iAccountId)->first();
                    if ($oAccount) {
                        $oAccount->setExtendedProp('TokensValidFromTimestamp', time());
                        $oAccount->save();
                    }
                }
            }

            self::$aTokensCache = [];
        }
    }

    public function SetToDB($iUserId, $iAccountId, $sAccountType, $sAuthToken)
    {
        $sTokenHash = hash('sha256', $sAuthToken);

        $oAuthToken = AuthToken::where('UserId', $iUserId)
            ->where('AccountId', $iAccountId)
            ->where('AccountType', $sAccountType)
            ->where('TokenHash', $sTokenHash)
            ->first();

        if (!$oAuthToken) {
            $oAuthToken = AuthToken::where('UserId', $iUserId)
                ->where('AccountId', $iAccountId)
                ->where('AccountType', $sAccountType)
                ->first();
            if (!$oAuthToken) {
                $oAuthToken = new AuthToken();
            }
        }
        $oAuthToken->UserId = $iUserId;
        $oAuthToken->AccountId = $iAccountId;
        $oAuthToken->AccountType = $sAccountType;
        $oAuthToken->TokenHash = $sTokenHash;
        $oAuthToken->LastUsageDateTime = time();

        try {
            $oAuthToken->save();
        } catch (\Aurora\System\Exceptions\DbException $oEx) {
            // DB is not configured
        }
    }

    public function GetFromDB($sAuthToken)
    {
        if (!isset(self::$aTokensCache[$sAuthToken])) {
            $sTokenHash = hash('sha256', $sAuthToken);
            try {
                $oAuthToken = AuthToken::where('TokenHash', $sTokenHash)->first();
                if ($oAuthToken) {
                    $oAuthToken->LastUsageDateTime = time();
                    $oAuthToken->save();
                    self::$aTokensCache[$sAuthToken] = $oAuthToken;
                }
            } catch (\Aurora\System\Exceptions\DbException $oEx) {
                \Aurora\Api::LogException($oEx);
            }
        }
        return isset(self::$aTokensCache[$sAuthToken]) ? self::$aTokensCache[$sAuthToken] : false;
    }

    public function DeleteFromDB($sAuthToken)
    {
        $sTokenHash = hash('sha256', $sAuthToken);
        AuthToken::where('TokenHash', $sTokenHash)->delete();
    }

    public function DeleteByTokenHash($sTokenHash)
    {
        if (is_string($sTokenHash) && \Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false)) {
            try {
                AuthToken::where('TokenHash', $sTokenHash)->delete();
            } catch (\Aurora\System\Exceptions\DbException $oEx) {
                // DB is not configured
            }
        }
    }

    public function GetExpiredAuthTokens($iDays)
    {
        $iTime = $iDays * 86400;
        return AuthToken::query()->whereRaw('(LastUsageDateTime + ' . $iTime . ') < UNIX_TIMESTAMP()')->get();
    }

    public function DeleteExpiredAuthTokens($iDays)
    {
        $iTime = $iDays * 86400;
        return AuthToken::query()->whereRaw('(LastUsageDateTime + ' . $iTime . ') < UNIX_TIMESTAMP()')->delete();
    }

    public function GetUserSessionsFromDB($iUserId)
    {
        return AuthToken::where('UserId', $iUserId)->get();
    }
}
