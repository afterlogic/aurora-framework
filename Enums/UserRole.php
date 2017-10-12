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

class UserRole extends AbstractEnumeration
{
	const SuperAdmin = 0;
	const TenantAdmin = 1;
	const NormalUser = 2;
	const Customer = 3;
	const Anonymous = 4;

	/**
	 * @var array
	 */
	protected $aConsts = array(
		'SuperAdmin' => self::SuperAdmin,
		'TenantAdmin' => self::TenantAdmin,
		'NormalUser' => self::NormalUser,
		'Customer' => self::Customer,
		'Anonymous' => self::Anonymous,
	);
}