<?php

namespace saas;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/api/IDomainsManager.php';
require_once APP_ROOTPATH.'/saas/tool/iterators/DomainsIterator.php';
require_once APP_ROOTPATH.'/saas/Domain.php';
require_once APP_ROOTPATH.'/saas/Exception.php';

class DomainsManager implements \saas\api\IDomainsManager
{
	private $tenantId; ///< TenantId

	protected function nativeManager()
	{
		return \CApi::GetSystemManager('domains');
	}

	function __construct($tenantId = 0)
	{
		$this->tenantId = $tenantId;
	}

	/**
	 * Search domain by name
	 * @param string $name
	 */
	function findByName($name)
	{
		$it = $this->instances();

		foreach ($it as $domain)
		{
			if (strcasecmp($domain->name(), $name) === 0)
			{
				return $domain;
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

		foreach ($it as $id => $domain)
		{
			if ($id == $reqId)
			{
				return $domain;
			}
		}

		return false;
	}

	/**
	 * Возвращает экземпляр сервиса типа, определяемого реализацией.
	 */
	function createService()
	{
		return new Domain($this->tenantId);
	}

	/**
	 * Добавление экземпляра в базу.
	 * @param unknown_type $instance
	 */
	function addInstance($domain, $bTry = false)
	{
		if (!$domain)
		{
			Exception::throwException(new \Exception('Invalid domain'));
			return false;
		}

		if ($this->nativeManager()->getDomainByName($domain->name()))
		{
			Exception::throwException(new \Exception('Domain '.$domain->name().' already exists'));
			return false;
		}

		if (!$bTry)
		{
			if ($this->globalSearchMode())
			{
				return $this->invalidOperation();
			}

			$nativeDomain = $domain->nativeService();
			if (!$this->nativeManager()->createDomain($nativeDomain))
			{
				Exception::throwException($this->nativeManager()->GetLastException());
				return false;
			}

			$domain->postAddInstance();
		}

		return true;
	}

	/**
	 * Удаление специфического экземляра.
	 * @param IService $instance Экемпляр сервиса
	 */
	function removeInstance($domain, $bTry = false)
	{
		if (!$domain)
		{
			return false;
		}

		$nativeDomain = $domain->nativeService();
		if (!$this->nativeManager()->areDomainsEmpty(array($nativeDomain->IdDomain)))
		{
			Exception::throwException(new \Exception('Domain not empty'));
			return false;
		}

		if (!$bTry)
		{
			if ($this->globalSearchMode())
			{
				return $this->invalidOperation();
			}

			if (!$this->nativeManager()->deleteDomainById($nativeDomain->IdDomain))
			{
				Exception::throwException($this->nativeManager()->GetLastException());
				return false;
			}

			$domain->cleanup();
		}

		return true;
	}

	/**
	 * Вернет итератор списка областей.
	 */
	function instances()
	{
		return new \saas\tool\iterators\DomainsIterator($this->tenantId);
	}

	/**
	 * Проверка на режим глобального поиска.
	 */
	protected function globalSearchMode()
	{
		return $this->tenantId === false;
	}

	protected function invalidOperation()
	{
		Exception::throwException(new Exception('Operation not supported in current mode'));
		return false;
	}
}
