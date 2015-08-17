<?php

/*
 * @author saydex
 */

ini_set('soap.wsdl_cache_enabled', '0');
defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../'));

require_once APP_ROOTPATH.'/saas/connectors/soap/TenantsManager.php';

$server = new SoapServer(APP_ROOTPATH.'/saas/connectors/soap/TenantsManager.wsdl');
$server->setClass('\saas\connectors\soap\TenantsManager');
$server->handle();
