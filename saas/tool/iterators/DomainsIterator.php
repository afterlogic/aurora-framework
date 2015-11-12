<?php

namespace saas\tool\iterators;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/Domain.php';

/**
 * @brief Iterator of tenant users.
 */
class DomainsIterator implements \Iterator
{
	private $tenantId;  ///< Tenant Id
	private $aDomainList; ///< DomainList

	protected function nativeDomainsManager()
	{
		return \CApi::Manager('domains');
	}

	function __construct($tenantId = 0)
	{
		$this->tenantId = $tenantId;
		$this->aDomainList = $this->nativeDomainsManager()->getFullDomainsList($tenantId);
	}

	function rewind()
	{
		reset($this->aDomainList);
	}

	function current()
	{
		$key = $this->key();
		$domain = new \saas\Domain($this->tenantId, $key);
		$domain->fromIterator(current($this->aDomainList));

		return $domain;
	}

	function key()
	{
		return key($this->aDomainList);
	}

	function next()
	{
		next($this->aDomainList);
	}

	function valid()
	{
		return current($this->aDomainList) !== false;
	}
}
