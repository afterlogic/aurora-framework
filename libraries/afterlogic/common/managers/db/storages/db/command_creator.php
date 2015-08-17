<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Db
 * @subpackage Storages
 */
class CApiDbCommandCreator extends api_CommandCreator
{
}

/**
 * @package Db
 * @subpackage Storages
 */
class CApiDbCommandCreatorMySQL extends CApiDbCommandCreator
{
	/**
	 * @param string $sName
	 *
	 * @return string
	 */
	public function createDatabase($sName)
	{
		$oSql = 'CREATE DATABASE %s';
		return sprintf($oSql, $this->escapeColumn($sName));
	}
}

/**
 * @package Db
 * @subpackage Storages
 */
class CApiDbCommandCreatorPostgreSQL extends CApiDbCommandCreator
{
	/**
	 * @param string $sName
	 *
	 * @return string
	 */
	public function createDatabase($sName)
	{
		$oSql = 'CREATE DATABASE %s';
		return sprintf($oSql, $this->escapeColumn($sName));
	}
}
