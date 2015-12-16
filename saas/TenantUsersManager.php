<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/api/IUsersManager.php';
require_once APP_ROOTPATH.'/saas/tool/iterators/TenantUsersIterator.php';
require_once APP_ROOTPATH.'/saas/User.php';
require_once APP_ROOTPATH.'/saas/Exception.php';

/**
 * Менеджер пользователей области.
 * @author saydex
 */
class TenantUsersManager implements \saas\api\IUsersManager
{
	private $tenantId;	///< Tenant ID

	function __construct($tenantId)
	{
		$this->tenantId = $tenantId;
	}

	/**
	 * @param unknown_type $name
	 */
	function findByUserName($name)
	{
		$it = $this->instances();

		foreach ($it as $user)
		{
			if (strcasecmp($user->userName(), $name) === 0)
			{
				return $user;
			}
		}

		return false;
	}

	/**
	 * Search domain by name
	 * @param string $name
	 */
	function findById($reqId)
	{
		$it = $this->instances();

		foreach ($it as $id => $user)
		{
			if ($id == $reqId)
			{
				return $user;
			}
		}

		return false;
	}

	function nativeManager()
	{
		return \CApi::GetCoreManager('users');
	}

	function nativeDomainManager()
	{
		return \CApi::GetCoreManager('domains');
	}

	/**
	 * Добавление пользователя в базу.
	 * @param IUser $user
	 */
	function addInstance($user, $bTry = false)
	{
		if (!$user)
		{
			Exception::throwException(new \Exception('Invalid user'));
			return false;
		}

		$nativeAccount = $user->nativeService();
		if ($this->nativeManager()->accountExists($nativeAccount))
		{
			Exception::throwException(new \Exception('Account '.$user->userName().' already exists'));
			return false;
		}

		if (!$bTry)
		{
			if (!$this->nativeManager()->createAccount($nativeAccount))
			{
				Exception::throwException($this->nativeManager()->GetLastException());
				return false;
			}

			$user->postAddInstance();
		}

		return true;
	}

	/**
	 * Удаление пользователя.
	 * @param IService $instance Экемпляр сервиса
	 */
	function removeInstance($user, $bTry = false)
	{
		if (!$user)
			return false;

		if (!$bTry)
		{
			$nativeAccount = $user->nativeService();
			if (!$this->nativeManager()->deleteAccount($nativeAccount))
			{
				Exception::throwException($this->nativeManager()->GetLastException());
				return false;
			}

			$user->cleanup();
		}

		return true;
	}

	/**
	 * Вернет итератор списка пользователей.
	 */
	function instances()
	{
		return new \saas\tool\iterators\TenantUsersIterator($this->tenantId);
	}

	/**
	 * Возвращает экземпляр сервиса типа, определяемого реализацией.
	 */
	function createService()
	{
		return new \saas\User();
	}
}
