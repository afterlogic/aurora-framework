<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

require_once APP_ROOTPATH.'/saas/api/IServiceManager.php';

/**
 * Интерфейс управления областями.
 * 
 * @author saydex
 *
 */
interface IChannelsManager extends IServiceManager
{

	/**
	 * Возвращает область по ее имени
	 * @param unknown_type $name
	 */
	function findByName($name);
}
