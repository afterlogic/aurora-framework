<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/libraries/afterlogic/api.php';
require_once APP_ROOTPATH.'/saas/api/IChannel.php';
require_once APP_ROOTPATH.'/saas/NativeService.php';
require_once APP_ROOTPATH.'/saas/DomainsManager.php';

/**
 * Tenant proxy.
 * 
 * @author saydex
 *
 */
class Channel extends NativeService implements \saas\api\IChannel
{

	protected function idField($obj)
	{
		return $obj->IdChannel;
	}

	protected function createNativeService()
	{
		\CApi::Manager('channels');
		return new \CChannel();
	}

	protected function createDomainsServiceManager()
	{
		return new DomainsManager($this->nativeId());
	}

	protected function nativeManager()
	{
		return \CApi::Manager('channels');
	}

	protected function findNativeById($id)
	{
		return $this->nativeManager()->getChannelById($id);
	}

	protected function nativeFieldMap()
	{
		return array('Login' => 'name');
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

	protected function createTenantsServiceManager()
	{
		return new TenantsManager();
	}

	/**
	 * Конструктор проксика.
	 * @param CUser $oUser
	 */
	function __construct($channelId = 0)
	{
		parent::__construct($channelId);

		$this->aServiceManagers['channels'] = false;
		$this->aServiceManagers['tenants'] = false;
		$this->aServiceManagers['domains'] = false;
	}

	// IService implementation

	/**
	 * @todo
	 * @return bool
	 */
	function disabled()
	{
		return false;
	}

	/**
	 * @todo
	 * @param bool $disabled = true
	 */
	function setDisabled($disabled = true)
	{

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
		$this->nativeService()->Password = $passwd;
	}

	function validatePassword($passwd)
	{
		return $this->nativeService()->Password == $passwd;
	}
}
