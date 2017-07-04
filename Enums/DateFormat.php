<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Aurora\System\Enums;

class DateFormat extends AbstractEnumeration
{
	const DD_MONTH_YYYY = 'DD Month YYYY';
	const MMDDYYYY = 'MM/DD/YYYY';
	const DDMMYYYY = 'DD/MM/YYYY';
	const MMDDYY = 'MM/DD/YY';
	const DDMMYY = 'DD/MM/YY';

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'DD Month YYYY' => self::DD_MONTH_YYYY,
		'MM/DD/YYYY' => self::MMDDYYYY,
		'DD/MM/YYYY' => self::DDMMYYYY,
		'MM/DD/YY' => self::MMDDYY,
		'DD/MM/YY' => self::DDMMYY
	);
}
