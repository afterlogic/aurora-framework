<?php

namespace saas\tool\iterators;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../../'));

require_once APP_ROOTPATH.'/libraries/afterlogic/api.php';
require_once APP_ROOTPATH.'/saas/tool/iterators/PageIterator.php';
require_once APP_ROOTPATH.'/saas/Channel.php';

/**
 * Channel iterator.
 */
class ChannelsIterator extends PageIterator
{
	private $oNativeChannelsManager;
	private $iItemsPerPage;
	private $iPage;

	function __construct($iItemsPerPage = 10)
	{
		$this->iPage = 0;
		$this->iItemsPerPage = $iItemsPerPage;
		$this->oNativeChannelsManager = \CApi::Manager('channels');
		
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

	function current()
	{
		// TODO: для производительности желательно добавить кеш-таблицу каналов
		$oChannel = new \saas\Channel($this->key());
		$oChannel->fromIterator(parent::current());
		return $oChannel;
	}

	protected function get_page_data()
	{
		$res = $this->oNativeChannelsManager->getChannelList($this->iPage, $this->iItemsPerPage);
		return ($res !== false && !empty($res)) ? $res : false;
	}
}
