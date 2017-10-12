<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */
 
namespace Aurora\System\Enums;

class DbType extends AbstractEnumeration
{
	const MySQL = "MySql";
	const PostgreSQL = "PostgreSQL";

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'MySQL' => self::MySQL,
		'PostgreSQL' => self::PostgreSQL
	);
}
