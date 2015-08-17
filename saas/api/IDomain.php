<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

require_once APP_ROOTPATH.'/saas/api/IService.php';

/**
 * Настройки домена.
 * 
 * Доступ к полям домена осуществляется через врапперы - 
 * field(), setField().
 * 
 * @author saydex
 *
 */
interface IDomain extends IService, ICustomFields
{

	function name();

	function setName($name);

	function skin();

	function setSkin($name);

	function timeZone();

	function setTimeZone($tz);

	function siteName();

	function setSiteName($name);

	function language();

	function setLanguage($lang);

	function msgsPerPage();

	function setMsgsPerPage($num);

	function checkPeriod();

	function setCheckPeriod($period);

	function externalMailBoxes();

	function setExternalMailBoxes($en);

	function weekStartsOn();

	function setWeekStartsOn($day);
}
