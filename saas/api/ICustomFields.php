<?php

namespace saas\api;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../'));

/**
 * ИНтерфейс доступа к расширенным полям пользователя.
 * 
 * @author saydex
 *
 */
interface ICustomFields
{

	function cachedFields();

// 	function field( $name ) ;
// 	function fields( $aNames ) ;
// 	function setField( $name, $value ) ;
// 	function setFields( $aValues ) ;
// 	function customField( $name ) ;
// 	function setCustomField( $name, $value ) ;
// 	function customFields() ;
// 	function setCustomFields( $map ) ;
}
