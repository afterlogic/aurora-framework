<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

require_once APP_ROOTPATH.'/saas/api/IServiceFactory.php';

/**
 * Интерфейс управление экземплярами сервиса.
 * 
 * @author saydex
 *
 */
interface IServiceManager extends IServiceFactory
{

	/**
	 * Добавление экземпляра в базу.
	 * @param unknown_type $instance
	 */
	function addInstance($instance, $bTry = false);

	/**
	 * Удаление специфического экземляра.
	 * @param IService $instance Экемпляр сервиса
	 */
	function removeInstance($instance, $bTry = false);

	/**
	 * Вернет итератор экземпляров.
	 */
	function instances();
}
