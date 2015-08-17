<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

require_once APP_ROOTPATH.'/saas/api/IServiceManager.php';

/**
 * Менеджер пользователей.
 * 
 * @author saydex
 *
 */
interface IUsersManager extends IServiceManager
{

	/**
	 * Возвращает пользователя по его имени.
	 * @param integer $name
	 */
	function findByUserName($id);
}

