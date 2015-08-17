<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

require_once APP_ROOTPATH.'/saas/api/IService.php';
require_once APP_ROOTPATH.'/saas/api/ICustomFields.php';

/**
 * Интерфейс пользователя.
 * 
 * Доступ к полям осуществляется через врапперы - 
 * field(), setField().
 * 
 * @author saydex
 *
 */
interface IUser extends IService, ICustomFields
{

	function userName();

	function setUserName($user);

	function primaryEmail();

	function setPrimaryEmail($email);

	function password();

	function setPassword($passwd);

	function validatePassword($password);

	function quota();

	function setQuota($quota);

	function diskUsage();

	/// Проверка состояния соответствующей фитчи (Capability).
	function hasCapa($name);

	/// Включение\выключение фитчи (capability).
	function setCapa($name, $en = true);
}

