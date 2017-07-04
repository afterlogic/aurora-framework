<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Aurora\System\Enums;

class TimeFormat extends AbstractEnumeration
{
	const F12 = 1;
	const F24 = 0;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'F12' => self::F12,
		'F24' => self::F24
	);
}
