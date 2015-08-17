<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/libraries/afterlogic/api.php';
require_once APP_ROOTPATH.'/saas/api/ISessionManager.php';

/**
 * @todo
 */
class SessionManager implements \saas\api\ISessionManager
{
	function CreateSession($user)
	{
		if (!$user instanceof \saas\api\IUser)
		{
			return false;
		}

		return false;
	}

	function DeleteSession($session_id)
	{
//		$res = \CSession::DestroySessionById($session_id);
		return true;
	}
}
