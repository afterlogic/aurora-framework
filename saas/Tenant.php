<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/api/ITenant.php';
require_once APP_ROOTPATH.'/saas/NativeService.php';
require_once APP_ROOTPATH.'/saas/TenantUsersManager.php';
require_once APP_ROOTPATH.'/saas/DomainsManager.php';

/**
 * Tenant proxy.
 * 
 * @author saydex
 *
 */
class Tenant extends NativeService implements \saas\api\ITenant
{

	protected function idField($obj)
	{
		return $obj->IdTenant;
	}

	protected function createNativeService()
	{
		\CApi::Manager('tenants');
		return new \CTenant();
	}

	protected function createUsersServiceManager()
	{
		return new TenantUsersManager($this->nativeId());
	}

	protected function createDomainsServiceManager()
	{
		return new DomainsManager($this->nativeId());
	}

	protected function nativeManager()
	{
		return \CApi::Manager('tenants');
	}

	protected function nativeDomainManager()
	{
		return \CApi::Manager('domains');
	}

	protected function nativeChannelManager()
	{
		return \CApi::Manager('channels');
	}

	protected function findNativeById($id)
	{
		return $this->nativeManager()->getTenantById($id);
	}

	protected function nativeFieldMap()
	{
		return array('Login' => 'name', 'IsDisabled' => 'disabled', 'QuotaInMB' => 'quota',
			'AllocatedSpaceInMB' => 'diskUsage', 'DomainCountLimit' => 'domainCountLimit', 'UserCountLimit' => 'userCountLimit');
	}

	function update()
	{
		$manager = $this->nativeManager();
		$manager->updateTenant($this->nativeService());
	}

	function fromIterator($data)
	{
		$this->aCachedFields['Login'] = $data[0];
	}

	/**
	 * Конструктор проксика.
	 * @param CUser $oUser
	 */
	function __construct($tenantId = 0)
	{
		parent::__construct($tenantId);

		$this->aServiceManagers['users'] = false;
		$this->aServiceManagers['domains'] = false;
		$this->aServiceManagers['channels'] = false;
	}

	// IService implementation

	function disabled()
	{
		return $this->nativeField('IsDisabled');
	}

	function setDisabled($disabled = true)
	{
		$this->setNativeField('IsDisabled', $disabled);
	}

	// ITenant interface implementation

	function name()
	{
		return $this->nativeField('Login');
	}

	function setName($value)
	{
		$this->setNativeField('Login', $value);
	}

	function setPassword($passwd)
	{
		$this->nativeService()->setPassword($passwd);
	}

	function channelLogin()
	{
		return $this->nativeField('Login');
	}

	function setChannelLogin($value)
	{
		$idChannel = $this->nativeChannelManager()->getChannelIdByLogin($value);
		$this->setNativeField('IdChannel', $idChannel);
	}

	function validatePassword($passwd)
	{
		return $this->nativeService()->validatePassword($passwd);
	}

	function quota()
	{
		return $this->nativeField('QuotaInMB');
	}

	function setQuota($value)
	{
		$this->setNativeField('QuotaInMB', $value);
	}

	function userCountLimit()
	{
		return $this->nativeField('UserCountLimit');
	}

	function setUserCountLimit($val)
	{
		$this->setNativeField('UserCountLimit', $val);
	}

	function domainCountLimit()
	{
		return $this->nativeField('DomainCountLimit');
	}

	function setDomainCountLimit($val)
	{
		$this->setNativeField('DomainCountLimit', $val);
	}

	function diskUsage()
	{
		return $this->nativeField('AllocatedSpaceInMB');
	}

	function userCount()
	{
		return $this->nativeService()->getUserCount();
	}

	function domainCount()
	{
		return $this->nativeService()->getDomainCount();
	}

	function enableLogin()
	{
		return $this->nativeField('IsEnableAdminPanelLogin');
	}

	function setEnableLogin($val)
	{
		$this->setNativeField('IsEnableAdminPanelLogin', $val);
	}

	/**
	 * @todo
	 * @return int
	 */
	function gabVisibility()
	{
		// TODO: нет метода получения данного значения
		return 0;
	}

	function setGabVisibility($val)
	{
		$this->nativeService();
		$manager = $this->nativeDomainManager();
		$manager->setGlobalAddressBookVisibilityByTenantId($val, $this->nativeId());
	}
}
