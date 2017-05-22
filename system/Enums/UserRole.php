<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
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