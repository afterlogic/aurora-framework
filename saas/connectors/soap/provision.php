<?php

/*
 * Отработка провизии.
 * 
 * @author saydex
 */

ini_set('soap.wsdl_cache_enabled', '0');
defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../'));

require_once APP_ROOTPATH.'/saas/connectors/soap/ProvisionService.php';
require_once APP_ROOTPATH.'/saas/Authenticator.php';

$server = new SoapServer(APP_ROOTPATH.'/saas/connectors/soap/ProvisionService.wsdl');
$server->setClass('\saas\connectors\soap\ProvisionService', new \saas\Authenticator());
$server->handle();
