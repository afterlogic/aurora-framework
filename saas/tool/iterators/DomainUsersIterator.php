<?php

namespace saas\tool\iterators;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../../'));

require_once APP_ROOTPATH.'/core/api.php';
require_once APP_ROOTPATH.'/saas/tool/iterators/PageIterator.php';

/**
 * @brief Итератор пользователей области.
 */
class DomainUsersIterator extends PageIterator
{
	private $iDomainId; ///< Идентификатор домена
	private $iItemsPerPage; ///< Кол-во элементов на страницу
	private $iPage;  ///< Текущий номер страницы
	private $oUsersManager; ///< Менеджер пользователей

	function __construct($sDomain, $iItemsPerPage = 10)
	{
		$this->iPage = 0;
		$this->iItemsPerPage = $iItemsPerPage;
		$this->oUsersManager = \CApi::GetCoreManager('users');

		$oDomainManager = \CApi::GetCoreManager('domains');
		$oDomain = $oDomainManager->getDomainByName($sDomain);
		$this->iDomainId = $oDomain ? $oDomain->IdDomain : false;

		parent::__construct();
	}

	protected function next_page()
	{
		$this->iPage++;
		return $this->get_page_data();
	}

	protected function rewind_page()
	{
		$this->iPage = 1;
		return $this->get_page_data();
	}

	function key()
	{
		$details = $this->current();
		return $details[4];
	}

	protected function get_page_data()
	{
		if ($this->iDomainId === false)
		{
			return false;
		}

		$res = $this->oUsersManager->getUserList($this->iDomainId, $this->iPage, $this->iItemsPerPage);
		return ($res !== false && !empty($res)) ? $res : false;
	}

}