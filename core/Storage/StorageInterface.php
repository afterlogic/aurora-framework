<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Core\Storage;

/**
 * @category Core
 * @package Storage
 */
interface StorageInterface
{
	/**
	 * @param \CAccount $oAccount
	 * @param int $iStorageType
	 * @param string $sKey
	 * @param string $sValue
	 *
	 * @return bool
	 */
	public function put(\CAccount $oAccount, $iStorageType, $sKey, $sValue);

	/**
	 * @param \CAccount $oAccount
	 * @param int $iStorageType
	 * @param string $sKey
	 *
	 * @return string | bool
	 */
	public function get(\CAccount $oAccount, $iStorageType, $sKey);

	/**
	 * @param \CAccount $oAccount
	 * @param int $iStorageType
	 * @param string $sKey
	 *
	 * @return bool
	 */
	public function clear(\CAccount $oAccount, $iStorageType, $sKey);
}