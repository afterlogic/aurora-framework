<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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
