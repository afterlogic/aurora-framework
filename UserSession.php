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
    public const TOKEN_VERSION = '3.0';

    public static $aTokensCache = [];

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
        if ($iExpire > 0) {
            $aData['@expire'] = $iExpire;
        }
        $sAuthToken = Api::EncodeKeyValues(
            $aData
        );

        if (\Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false)) {
            $this->SetToDB($aData['id'], $sAuthToken);
        }

        return $sAuthToken;
    }

    public function UpdateTimestamp($sAuthToken, $iTime = 0)
    {
        $aData = $this->Get($sAuthToken);
        return $this->Set($aData, $iTime);
    }

    public function Get($sAuthToken)
    {
        $mAuthTokenData = false;
        $mResult = true;

        if (strlen((string)$sAuthToken) !== 0) {
            $bStoreAuthTokenInDB = \Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false);

            // check if the auth token is stored in the database
            if ($bStoreAuthTokenInDB && !$this->GetFromDB($sAuthToken)) {
                return false;
            }

            $mAuthTokenData = Api::DecodeKeyValues($sAuthToken);

            // checking the validity of auth token data
            if ($mAuthTokenData && isset($mAuthTokenData['id'])) {
                $oUser = Api::getUserById((int) $mAuthTokenData['id']);

                // check if user is disabled
                if ($oUser && $oUser->IsDisabled) {
                    $mResult = false;
                }

                // check auth token version
                if ($mResult) {
                    if ((isset($mAuthTokenData['@ver']) && $mAuthTokenData['@ver'] !== self::TOKEN_VERSION) || !isset($mAuthTokenData['@ver'])) {
                        $mResult = false;
                    }
                }

                // check auth token expiration date
                if ($mResult) {
                    $iExpireTime = (int) isset($mAuthTokenData['@expire']) ? $mAuthTokenData['@expire'] : 0;
                    if ($iExpireTime > 0 && $iExpireTime < time()) {
                        $mResult = false;
                    }
                }

                // checking the token is valid from timestamp
                if ($mResult) {
                    $iTime = (int) $mAuthTokenData['@time']; // 0 means that signMe was true when user logged in, so there is no need to check it in that case
                    if ($oUser && $iTime !== 0 && (int) $oUser->TokensValidFromTimestamp > $iTime) {
                        $mResult = false;
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
                        }
                    }
                }
            } else {
                $mResult = false;
            }

            if (!$mResult) {
                $this->Delete($sAuthToken);
                $mAuthTokenData = $mResult;

                \Aurora\System\Api::Log('User session expired: ');
                \Aurora\System\Api::LogObject($mAuthTokenData);
            }
        }

        return $mAuthTokenData;
    }

    public function Delete($sAuthToken)
    {
        if (\Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false)) {
            try {
                $this->DeleteFromDB($sAuthToken);
            } catch (\Aurora\System\Exceptions\DbException $oEx) {
                // DB is not configured
            }
        }
    }

    public function DeleteAllUserSessions($iUserId)
    {
        return AuthToken::where('UserId', $iUserId)->delete();
    }

    public function SetToDB($iUserId, $sAuthToken)
    {
        $oAuthToken = AuthToken::where('UserId', $iUserId)->where('Token', $sAuthToken)->first();

        if (!$oAuthToken) {
            $oAuthToken = new AuthToken();
        }
        $oAuthToken->UserId = $iUserId;
        $oAuthToken->Token = $sAuthToken;
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
            try {
                $oAuthToken = AuthToken::firstWhere('Token', $sAuthToken);
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
        AuthToken::where('Token', $sAuthToken)->delete();
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
