<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/saas/api/IAuthenticator.php';
require_once APP_ROOTPATH.'/saas/MasterUser.php';

class Authenticator implements \saas\api\IAuthenticator
{

	private $oMasterUser = null;
	private $oChannelsManager = null;
	private $oTenantsManager = null;
	private $oUsersManager = null;

	function __construct()
	{
		$this->oMasterUser = new MasterUser();
		$this->oChannelsManager = $this->oMasterUser->serviceManager('channels');
		$this->oTenantsManager = $this->oMasterUser->serviceManager('tenants');
		$this->oUsersManager = $this->oMasterUser->serviceManager('users');
	}

	function authenticateMaster($creds)
	{
		// Search master account
		$oUser = $this->findMasterUser($creds->login);
		return ($oUser && isset($creds->password) && $oUser->validatePassword($creds->password)) ? $oUser : false;
	}

	function authenticateChannel($creds)
	{
		// Search channel
		$oChannel = $this->findChannel($creds->login);
		return ($oChannel && isset($creds->password) && $oChannel->validatePassword($creds->password)) ? $oChannel : false;
	}

	function authenticateCustomer($creds)
	{
		// Search master account
		$oUser = $this->oTenantsManager->findByName($creds->login);
		return ($oUser && isset($creds->password) && $oUser->validatePassword($creds->password)) ? $oUser : false;
	}

	/**
	 * @todo ???
	 * @param mixed $creds
	 * @return boo
	 */
	function authenticateUser($creds)
	{
		return false;
// 		$oUser = $this->oUsersManager->findByUserName($creds->login);
//		return ($oUser && isset($creds->password) && $oUser->validatePassword( $creds->password)) ? $oUser : false;
	}

	/**
	 * @brief Аутентификация пользователя.
	 * 
	 * Аутентификация пока что только администраторов.
	 * 
	 * @return IAdminUser, IUserCtrl
	 */
	function authenticate($creds)
	{
		// Search master account
		$oUser = $this->findMasterUser($creds->login);

		// Search channel admin
		if (!$oUser)
		{
			$oUser = $this->findChannel($creds->login);
		}

		// Search sub admin account
		if (!$oUser)
		{
			$oUser = $this->findTenant($creds->login);
		}

		if (!$oUser && isset($creds->tenant_name))
		{
			$oUser = $this->findTenantUser($creds->tenant_name, $creds->login);
		}

		if (!$oUser)
		{
			return false;
		}

		if (isset($creds->password) && $oUser->validatePassword($creds->password))
		{
			return $oUser;
		}

		return false;
	}

	/**
	 * @param string $sLogin
	 * @return bool
	 */
	function findMasterUser($sLogin)
	{
		$oUser = $this->oMasterUser;
		return ($oUser->userName() !== $sLogin) ? false : $oUser;
	}

	function findChannel($login)
	{
		return $this->oChannelsManager->findByName($login);
	}

	function findTenant($name)
	{
		return $this->oTenantsManager->findByName($name);
	}

	function findTenantUser($tenant_name, $username)
	{
		$tenant = $this->oTenantsManager->findByName($tenant_name);
		if (!$tenant)
		{
			return false;
		}

		$uman = $tenant->serviceManager('users');
		return $uman ? $uman->findByUserName($username) : false;
	}
}
