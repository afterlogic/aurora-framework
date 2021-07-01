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
	const TOKEN_VERSION = '2.4';

	static public $aTokensCache = [];

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
		if ($iExpire > 0)
		{
			$aData['@expire'] = $iExpire;
		}
		$sAuthToken = Api::EncodeKeyValues(
			$aData
		);

		if (\Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false))
		{
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
		$mResult = false;

		if (strlen($sAuthToken) !== 0)
		{
			$bStoreAuthTokenInDB = \Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false);
			if ($bStoreAuthTokenInDB && !$this->GetFromDB($sAuthToken))
			{
				return false;
			}

			$mResult = Api::DecodeKeyValues($sAuthToken);

			if ($mResult !== false && isset($mResult['id']))
			{
				if ((isset($mResult['@ver']) && $mResult['@ver'] !== self::TOKEN_VERSION) || !isset($mResult['@ver']))
				{
					$mResult = false;
				}
				else
				{
					$iExpireTime = (int) isset($mResult['@expire']) ? $mResult['@expire'] : 0;
					if ($iExpireTime > 0 && $iExpireTime < time())
					{
						$mResult = false;
					}
					else
					{
						$oUser = \Aurora\System\Managers\Integrator::getInstance()->getAuthenticatedUserByIdHelper($mResult['id']);
						$iTime = (int) $mResult['@time']; // 0 means that signMe was true when user logged in, so there is no need to check it in that case
						if ($oUser && $iTime !== 0 && (int) $oUser->TokensValidFromTimestamp > $iTime)
						{
							$mResult = false;
						}
						else if ((isset($mResult['sign-me']) && !((bool) $mResult['sign-me'])) || (!isset($mResult['sign-me'])))
						{
							$iTime = 0;
							if (isset($mResult['@time']))
							{
								$iTime = (int) $mResult['@time'];
							}
							$iExpireUserSessionsBeforeTimestamp = \Aurora\System\Api::GetSettings()->GetConf("ExpireUserSessionsBeforeTimestamp", 0);
							if ($iExpireUserSessionsBeforeTimestamp > $iTime && $iTime > 0)
							{
								\Aurora\System\Api::Log('User session expired: ');
								\Aurora\System\Api::LogObject($mResult);
								$mResult = false;
							}
						}
					}
				}
			}
			if ($mResult === false)
			{
				$this->Delete($sAuthToken);
			}
		}

		return $mResult;
	}

	public function Delete($sAuthToken)
	{
		if (\Aurora\Api::GetSettings()->GetValue('StoreAuthTokenInDB', false))
		{
			try
			{
				$this->DeleteFromDB($sAuthToken);
			}
			catch (\Aurora\System\Exceptions\DbException $oEx)
			{
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

		if (!$oAuthToken)
		{
			$oAuthToken = new AuthToken();
		}
		$oAuthToken->UserId = $iUserId;
		$oAuthToken->Token = $sAuthToken;
		$oAuthToken->LastUsageDateTime = time();

		try
		{
			$oAuthToken->save();
		}
		catch (\Aurora\System\Exceptions\DbException $oEx)
		{
			// DB is not configured
		}
	}

	public function GetFromDB($sAuthToken)
	{
		if (!isset(self::$aTokensCache[$sAuthToken]))
		{
			try
			{
				$oAuthToken = AuthToken::firstWhere('Token', $sAuthToken);
				if ($oAuthToken) {
					$oAuthToken->LastUsageDateTime = time();
					$oAuthToken->save();
					self::$aTokensCache[$sAuthToken] = $oAuthToken;
				}
			}
			catch (\Aurora\System\Exceptions\DbException $oEx)
			{
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
		$oDateTime = new \DateTime('-'.$iDays.' days');
		$iTime = $oDateTime->getTimestamp();
		return AuthToken::where('LastUsageDateTime', '>', $iTime)->get();
	}

	public function GetUserSessionsFromDB($iUserId)
	{
		return AuthToken::where('UserId', $iUserId)->get();

	}

}
