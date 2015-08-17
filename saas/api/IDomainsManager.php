<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

require_once APP_ROOTPATH.'/saas/api/IServiceManager.php';

/**
 * Интерфейс управления доменами.
 * 
 * @author saydex
 *
 */
interface IDomainsManager extends IServiceManager
{

	/**
	 * Поиск домена по имени
	 * @param unknown_type $name
	 */
	function findByName($name);
}
