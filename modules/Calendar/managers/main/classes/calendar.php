<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Calendar
 * @subpackage Classes
 */
class CCalendar
{
	public $Id;
	public $IntId;
	public $Url;
	public $IsDefault;
	public $DisplayName;
	public $CTag;
	public $ETag;
	public $Description;
	public $Color;
	public $Order;
	public $Shared;
	public $SharedToAll;
	public $SharedToAllAccess;
	public $Owner;
	public $Principals;
	public $Access;
	public $Shares;
	public $IsPublic;
	public $PubHash;
	public $RealUrl;

	/**
	 * @param string $sId
	 * @param string $sDisplayName Default value is **null**.
	 * @param string $sCTag Default value is **null**.
	 * @param string $sETag Default value is **null**.
	 * @param string $sDescription Default value is **null**.
	 */
	function __construct($sId, $sDisplayName = null, $sCTag = null, $sETag = null, $sDescription = null,
			$sColor = null, $sOrder = null)
	{
		$this->Id = rtrim(urldecode($sId), '/');
		$this->IntId = 0;
		$this->IsDefault = (basename($sId) === \afterlogic\DAV\Constants::ADDRESSBOOK_DEFAULT_NAME);
		$this->DisplayName = $sDisplayName;
		$this->CTag = $sCTag;
		$this->ETag = $sETag;
		$this->Description = $sDescription;
		$this->Color = $sColor;
		$this->Order = $sOrder;
		$this->Shared = false;
		$this->SharedToAll = false;
		$this->SharedToAllAccess = ECalendarPermission::Read;
		$this->Owner = '';
		$this->Principals = array();
		$this->Access = ECalendarPermission::Write;
		$this->Shares = array();
		$this->IsPublic = false;
		$this->PubHash = null;
	}

	/**
	 * @return string
	 */
	public function GetMainPrincipalUrl()
	{
		$sResult = '';
		if (is_array($this->Principals) && count($this->Principals) > 0)
		{
			$sResult = str_replace('/calendar-proxy-read', '', rtrim($this->Principals[0], '/'));
			$sResult = str_replace('/calendar-proxy-write', '', $sResult);
		}
		return $sResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	public function IsCalendarOwner($oAccount)
	{
		return ($oAccount->Email === $this->Owner);
	}
	
	public function toArray($oAccount = null)
	{
		return array(
			'Id' => $this->Id,
			'Url' => $this->Url,
			'ExportHash' => CApi::EncodeKeyValues(array('CalendarId' => $this->Id)),
			'Color' => $this->Color,
			'Description' => $this->Description,
			'Name' => $this->DisplayName,
			'Owner' => $this->Owner,
			'IsDefault' => $this->IsDefault,
			'PrincipalId' => $this->GetMainPrincipalUrl(),
			'Shared' => $this->Shared,
			'SharedToAll' => $this->SharedToAll,
			'SharedToAllAccess' => $this->SharedToAllAccess,
			'Access' => $this->Access,
			'IsPublic' => $this->IsPublic,
			'PubHash' => $this->PubHash,
			'Shares' => $this->Shares,
			'CTag' => $this->CTag,
			'Etag' => $this->ETag
		);
	}
	
	
}