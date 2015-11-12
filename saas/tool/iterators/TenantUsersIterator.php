<?php

namespace saas\tool\iterators;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/tool/iterators/PageIterator.php';
require_once APP_ROOTPATH.'/saas/User.php';
require_once APP_ROOTPATH.'/saas/Exception.php';

/**
 * @brief Iterator of tenant users.
 *
 */
class TenantUsersIterator extends PageIterator
{

	public $tenantId;  ///< Tenant Id
	private $iItemsPerPage; ///< Кол-во элементов на страницу
	private $iPage;  ///< Текущий номер страницы
	private $aDomainList;

	protected function nativeDomainsManager()
	{
		return \CApi::Manager('domains');
	}

	protected function nativeUsersManager()
	{
		return \CApi::Manager('users');
	}

	function __construct($tenantId, $iItemsPerPage = 10)
	{
		$this->iPage = 0;
		$this->tenantId = $tenantId;
		$this->iItemsPerPage = $iItemsPerPage;
		$this->aDomainList = $this->nativeDomainsManager()->getFullDomainsList($this->tenantId);

		parent::__construct();
	}

	function current()
	{
		// TODO: для производительности желательно добавить кеш-таблицу реалмов
		$user = new \saas\User($this->key());
		$user->fromIterator(parent::current());
		return $user;
	}

	protected function next_page()
	{
		$this->iPage++;
		$res = $this->get_page_data();
		return $res;
	}

	protected function rewind_page()
	{
		reset($this->aDomainList);
		$this->iPage = 1;
		return $this->get_page_data();
	}

	protected function get_page_data()
	{
		$res = false;
		$aDomainList = &$this->aDomainList;
		while (current($aDomainList))
		{
			$iDomainId = key($aDomainList);
			$res = $this->nativeUsersManager()->getUserList($iDomainId, $this->iPage, $this->iItemsPerPage);
			if ($res !== false && !empty($res))
			{
				break;
			}

			next($aDomainList);
			$this->iPage = 1;
		}

		return $res;
	}
}

