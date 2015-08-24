<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace MailSo\Cache;

/**
 * @category MailSo
 * @package Cache
 */
interface DriverInterface
{
	/**
	 * @param string $sKey
	 * @param string $sValue
	 *
	 * @return bool
	 */
	public function Set($sKey, $sValue);

	/**
	 * @param string $sKey
	 *
	 * @return string
	 */
	public function get($sKey);

	/**
	 * @param string $sKey
	 *
	 * @return void
	 */
	public function Delete($sKey);

	/**
	 * @param int $iTimeToClearInHours = 24
	 *
	 * @return bool
	 */
	public function gc($iTimeToClearInHours = 24);
}
