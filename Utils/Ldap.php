<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Utils;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Ldap
{
	/**
	 * @var resource
	 */
	private $rLink;

	/**
	 * @var resource
	 */
	private $rSearch;

	/**
	 * @var string
	 */
	private $sSearchDN;

	public function __construct($sSearchDN = '')
	{
		$this->rLink = null;
		$this->rSearch = null;
		$this->sSearchDN = $sSearchDN;
	}

	public function Escape($sStr, $bForDn = false)
	{
		if  ($bForDn)
		{
			$aMetaChars = array(',', '=', '+', '<', '>', ';', '\\', '"', '#');
		}
		else
		{
			$aMetaChars = array('*', '(', ')', '\\', chr(0));
		}

		$aQuotedMetaChars = array();
		foreach ($aMetaChars as $iKey => $sValue)
		{
			$aQuotedMetaChars[$iKey] = '\\'.str_pad(dechex(ord($sValue)), 2, '0');
		}

		return str_replace($aMetaChars,$aQuotedMetaChars, $sStr);
	}

	/**
	 * @param string $sSearchDN
	 * @return Ldap
	 */
	public function SetSearchDN($sSearchDN)
	{
		$this->sSearchDN = $sSearchDN;
		return $this;
	}

	/**
	 * @return string
	 */
	public function GetSearchDN()
	{
		return $this->sSearchDN;
	}

	/**
	 * @param string $sHost
	 * @param int $iPort
	 * @param string $sBindDb = ''
	 * @param string $sBindPassword = ''
	 * @param string $sHostBack = ''
	 * @param int $iPortBack = null
	 * @return bool
	 */
	public function Connect($sHost, $iPort, $sBindDb = '', $sBindPassword = '', $sHostBack = '', $iPortBack = null)
	{
		if (!extension_loaded('ldap'))
		{
			\Aurora\System\Api::Log('LDAP: Can\'t load LDAP extension.', \Aurora\System\Enums\LogLevel::Error);
			return false;
		}

		if (!is_resource($this->rLink))
		{
			\Aurora\System\Api::Log('LDAP: connect to '.$sHost.':'.$iPort);

			$rLink = ldap_connect($sHost, $iPort);
			if ($rLink)
			{
				@ldap_set_option($rLink, LDAP_OPT_PROTOCOL_VERSION, 3);
				@ldap_set_option($rLink, LDAP_OPT_REFERRALS, 0);

				\Aurora\System\Api::Log('LDAP: bind = "'.$sBindDb.'" / "'.$sBindPassword.'"');
				if (0 < strlen($sBindDb) && 0 < strlen($sBindPassword) ?
					!@ldap_bind($rLink, $sBindDb, $sBindPassword) : !@ldap_bind($rLink)
				)
				{
					$this->rLink = $rLink;

					$this->validateLdapErrorOnFalse(false);

					$this->rLink = null;
					if (0 < strlen($sHostBack))
					{
						return $this->Connect($sHostBack, $iPortBack, $sBindDb, $sBindPassword);
					}

					return false;
				}
				else
				{
//					@register_shutdown_function(array(&$this, 'Disconnect'));
					$this->rLink = $rLink;
				}
			}
			else
			{
				$this->validateLdapErrorOnFalse(false);
				if (0 < strlen($sHostBack))
				{
					return $this->Connect($sHostBack, $iPortBack, $sBindDb, $sBindPassword);
				}

				return false;
			}
		}

		return true;
	}

	/**
	 * @param string $sBindDb
	 * @param string $sBindPassword
	 * @return bool
	 */
	public function ReBind($sBindDb, $sBindPassword)
	{
		if (is_resource($this->rLink))
		{
			\Aurora\System\Api::Log('LDAP: rebind '.$sBindDb);

			if (!@ldap_bind($this->rLink, $sBindDb, $sBindPassword))
			{
				$this->validateLdapErrorOnFalse(false);
				$this->rLink = null;
			}
			else
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @param mixed $mReturn
	 * @return mixed
	 */
	private function validateLdapErrorOnFalse($mReturn)
	{
		if (false === $mReturn)
		{
			if ($this->rLink)
			{
				\Aurora\System\Api::Log('LDAP: error #'.@ldap_errno($this->rLink).': '.@ldap_error($this->rLink), \Aurora\System\Enums\LogLevel::Error);
			}
			else
			{
				\Aurora\System\Api::Log('LDAP: unknown ldap error', \Aurora\System\Enums\LogLevel::Error);
			}
		}

		return $mReturn;
	}

	/**
	 * @param string $sObjectFilter
	 * @return bool
	 */
	public function Search($sObjectFilter)
	{
		if ($this->rSearch && $this->sLastRequest === $this->sSearchDN.$sObjectFilter)
		{
			\Aurora\System\Api::Log('LDAP: search repeat = "'.$this->sSearchDN.'" / '.$sObjectFilter);

			$this->validateLdapErrorOnFalse($this->rSearch);
			return is_resource($this->rSearch);
		}
		else
		{
			\Aurora\System\Api::Log('LDAP: search = "'.$this->sSearchDN.'" / '.$sObjectFilter);
			$this->rSearch = @ldap_search($this->rLink, $this->sSearchDN, $sObjectFilter, array('*'), 0, 3000);

			$this->validateLdapErrorOnFalse($this->rSearch);

			$this->sLastRequest = $this->sSearchDN.$sObjectFilter;
			return is_resource($this->rSearch);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function Add($sNewDn, $aEntry)
	{
		\Aurora\System\Api::Log('ldap_add = '.((empty($sNewDn) ? '' : $sNewDn.',').$this->sSearchDN));
		\Aurora\System\Api::LogObject($aEntry);

		$bResult = !!@ldap_add($this->rLink, (empty($sNewDn) ? '' : $sNewDn.',').$this->sSearchDN, $aEntry);
		$this->validateLdapErrorOnFalse($bResult);

		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function Delete($sDeleteDn)
	{
		$bResult = false;
		if (!empty($sDeleteDn))
		{
			\Aurora\System\Api::Log('ldap_delete = '.($sDeleteDn.','.$this->sSearchDN));
			$bResult = !!@ldap_delete($this->rLink, $sDeleteDn.','.$this->sSearchDN);
			$this->validateLdapErrorOnFalse($bResult);
		}

		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function Modify($sModifyDn, $aModifyEntry)
	{
		$bResult = false;
		if (!empty($sModifyDn))
		{
			if (!empty($this->sSearchDN))
			{
				$sModifyDn = $sModifyDn.','.$this->sSearchDN;
			}

			\Aurora\System\Api::Log('ldap_modify = '.$sModifyDn);
			\Aurora\System\Api::LogObject($aModifyEntry);

			$bResult = !!@ldap_modify($this->rLink, $sModifyDn, $aModifyEntry);
			$this->validateLdapErrorOnFalse($bResult);
		}

		return $bResult;
	}

	/**
	 * @return int
	 */
	public function ResultCount()
	{
		$iResult = 0;

		$iCount = ldap_count_entries($this->rLink, $this->rSearch);
		$this->validateLdapErrorOnFalse($iCount);
		if (false !== $iCount)
		{
			$iResult = $iCount;
		}

		return $iResult;
	}

	/**
	 * @return mixed
	 */
	public function ResultItem()
	{
		$mResult = false;

		$aResurn = @ldap_get_entries($this->rLink, $this->rSearch);
		$this->validateLdapErrorOnFalse($aResurn);
		if (false !== $aResurn && isset($aResurn[0]) && is_array($aResurn[0]))
		{
			$mResult = $aResurn[0];
		}

		return $mResult;
	}

	/**
	 * @param string $sSortField
	 * @param string $bAsc 'asc' or 'desc'
	 * @param int $iOffset = null
	 * @param int $iRequestLimit = null
	 * @return array
	 */
	public function SortPaginate($sSortField, $bAsc = true, $iOffset = null, $iRequestLimit = null)
	{
		$iTotalEntries = @ldap_count_entries($this->rLink, $this->rSearch);

		$iEnd = 0;
		$iStart = 0;
		if ($iOffset === null || $iRequestLimit === null)
		{
			$iStart = 0;
			$iEnd = $iTotalEntries - 1;
		}
		else
		{
			$iStart = $iOffset;
			$iStart = ($iStart < 0) ? 0 : $iStart;

			$iEnd = $iStart + $iRequestLimit;
			$iEnd = ($iEnd > $iTotalEntries) ? $iTotalEntries : $iEnd;
		}

		if (0 < strlen($sSortField))
		{
			@ldap_sort($this->rLink, $this->rSearch, $sSortField);
		}

		$aList = array();
		$iCurrent = 0;
		$rEntry = ldap_first_entry($this->rLink, $this->rSearch);
		do
		{
			if ($iCurrent >= $iStart)
			{
				array_push($aList, ldap_get_attributes($this->rLink, $rEntry));
			}
			$rEntry = ldap_next_entry($this->rLink, $rEntry);
			$iCurrent++;
		}
		while ($iCurrent < $iEnd && is_resource($rEntry));

		return $bAsc ? $aList : array_reverse($aList);
	}
}
