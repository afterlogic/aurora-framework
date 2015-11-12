<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/api/IUser.php';
require_once APP_ROOTPATH.'/saas/TenantsManager.php';
require_once APP_ROOTPATH.'/saas/DomainsManager.php';
require_once APP_ROOTPATH.'/saas/ChannelsManager.php';

/**
 * @brief Интерфейс главного админа.
 */
class MasterUser implements \saas\api\IUser
{
	private $aServiceManagers = array();

	protected function nativeSettings()
	{
		return \CApi::GetSettings();
	}

	protected function nativeWebmail()
	{
		return \CApi::Manager('webmail');
	}

	protected function createChannelsServiceManager()
	{
		return new ChannelsManager(0);
	}

	protected function createTenantsServiceManager()
	{
		return new TenantsManager();
	}

	protected function createDomainsServiceManager()
	{
		return new DomainsManager(0);
	}

	function __construct()
	{
		$this->aServiceManagers['channels'] = false;
		$this->aServiceManagers['tenants'] = false;
		$this->aServiceManagers['domains'] = false;
	}

	function update()
	{
		$this->nativeSettings()->SaveToXml();
	}

	function userName()
	{
		return $this->nativeSettings()->GetConf('Common/AdminLogin');
	}

	function setUserName($user)
	{
		$this->nativeSettings()->SetConf('Common/AdminLogin', $user);
	}

	function password()
	{
		return $this->nativeSettings()->GetConf('Common/AdminPassword');
	}

	function setPassword($passwd)
	{
		$this->nativeSettings()->SetConf('Common/AdminPassword', $passwd);
	}

	function validatePassword($passwd)
	{
		return $this->nativeWebmail()->validateAdminPassword($passwd);
	}

	function primaryEmail()
	{

	}

	function setPrimaryEmail($email)
	{
		
	}

	function language()
	{

	}

	function setLanguage($lang)
	{
		
	}

	function quota()
	{
		return 0;
	}

	function setQuota($quota)
	{
	}

	function diskUsage()
	{
		return 0;
	}

	function disabled()
	{
		return false;
	}

	function setDisabled($enable = true)
	{
	}

	/**
	 * @param string $name expected values: 'tenant'
	 * @see saas\api.IUser::serviceManager()
	 */
	function serviceManager($name)
	{
		$aServiceManagers = &$this->aServiceManagers;
		if (strlen($name) <= 0 || !isset($aServiceManagers[$name]))
		{
			return false;
		}

		$service = &$aServiceManagers[$name];
		if (!$service)
		{
			$fname = 'create'.strtoupper($name[0]).substr($name, 1).'ServiceManager';
			$service = $this->$fname();
		}

		return $service;
	}

	function cachedFields()
	{
		$res = arrary();
		$res['userName'] = $this->userName();

		return $res;
	}

	function hasCapa($name)
	{
		return true;
	}

	function setCapa($name, $en = true)
	{
	}
}
