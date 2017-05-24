<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Aurora\System\Enums;

class FileStorageType extends \Aurora\System\Enums\AbstractEnumeration
{
	const Personal = 'personal';
	const Corporate = 'corporate';
	const Shared = 'shared';

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'Personal' => self::Personal,
		'Corporate' => self::Corporate,
		'Shared' => self::Shared

	);
}
