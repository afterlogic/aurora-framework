<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

require_once APP_ROOTPATH.'/saas/api/IService.php';
require_once APP_ROOTPATH.'/saas/api/ICustomFields.php';

/**
 * Интерфейс области.
 *
 * Доступ к полям осуществляется через врапперы - field(), setField().
 * 
 * @author saydex
 *
 */
interface IChannel extends IService, ICustomFields
{

	function name();

	function setName($name);

	function setPassword($pass);

	function validatePassword($pass);

	//function quota();
	//function setQuota( $quota );
	//function diskUsage();
}
