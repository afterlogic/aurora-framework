<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

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
			'account' => $oAccount->EntityId,
			'account_type' => $oAccount->getName()
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
		$aAuthTokens = (new \Aurora\System\EAV\Query(\Aurora\System\Classes\AuthToken::class))
		->where(
			[
				'UserId' => $iUserId
			]
		)
		->exec();
		if (\is_array($aAuthTokens) && count($aAuthTokens) > 0)
		{
			foreach ($aAuthTokens as $oAuthToken)
			{
				$oAuthToken->delete();
			}
		}
	}

	public function SetToDB($iUserId, $sAuthToken)
	{
		$oAuthToken = (new \Aurora\System\EAV\Query(\Aurora\System\Classes\AuthToken::class))
			->where(
				[
					'UserId' => $iUserId,
					'Token' => $sAuthToken
				]
			)
			->one()
			->exec();
		if (!$oAuthToken)
		{
			$oAuthToken = new \Aurora\System\Classes\AuthToken('System');
		}
		$oAuthToken->UserId = $iUserId;
		$oAuthToken->Token = $sAuthToken;
		$oAuthToken->LastUsageDateTime = time();

		try
		{
			\Aurora\System\Managers\Eav::getInstance()->saveEntity($oAuthToken);
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
				$aEntities = \Aurora\System\Managers\Eav::getInstance()->getEntities(
					\Aurora\System\Classes\AuthToken::class,
					[],
					0,
					1,
					['Token' => $sAuthToken]
				);

				if (is_array($aEntities) && count($aEntities) === 1)
				{
					$oAuthToken = $aEntities[0];
					$oAuthToken->LastUsageDateTime = time();
					$oAuthToken->saveAttributes(['LastUsageDateTime']);
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
		$oAuthToken = $this->GetFromDB($sAuthToken);
		if ($oAuthToken instanceof \Aurora\System\Classes\AuthToken)
		{
			\Aurora\System\Managers\Eav::getInstance()->deleteEntity($oAuthToken->EntityId);
		}
	}

	public function GetExpiredAuthTokens($iDays)
	{
		$oDateTime = new \DateTime('-'.$iDays.' days');
		$iTime = $oDateTime->getTimestamp();

		return \Aurora\System\Managers\Eav::getInstance()->getEntities(
			\Aurora\System\Classes\AuthToken::class,
			[],
			0,
			0,
			['LastUsageDateTime' => [$iTime , '<']]
		);
	}

	public function GetUserSessionsFromDB($iUserId)
	{
		return (new \Aurora\System\EAV\Query(\Aurora\System\Classes\AuthToken::class))
			->where(
				[
					'UserId' => $iUserId
				]
			)
			->exec();
	}

}
