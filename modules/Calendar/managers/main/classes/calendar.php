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
	public $SyncToken;

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
		$this->IsDefault = (basename($sId) === \Afterlogic\DAV\Constants::ADDRESSBOOK_DEFAULT_NAME);
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
		$this->SyncToken = null;
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
	
	public function toResponseArray($aParameters = array())
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
			'Etag' => $this->ETag,
			'SyncToken' => $this->SyncToken
		);
	}
	
	public static function createCalendar(\Sabre\CalDAV\Calendar $oCalDAVCalendar)
	{
		if (!($oCalDAVCalendar instanceof \Sabre\CalDAV\Calendar))
		{
			return false;
		}
		$aProps = $oCalDAVCalendar->getProperties(array());
		
		$oCalendar = new self($oCalDAVCalendar->getName());
		$oCalendar->IntId = 0;//TODO: $aProps['id'];

		if ($oCalDAVCalendar instanceof \Sabre\CalDAV\SharedCalendar)
		{
			$oCalendar->Shared = true;
			if (isset($aProps['{http://sabredav.org/ns}read-only']))
			{
				$oCalendar->Access = $aProps['{http://sabredav.org/ns}read-only'] ? ECalendarPermission::Read : ECalendarPermission::Write;
			}
			if (isset($aProps['{http://calendarserver.org/ns/}summary']))
			{
				$oCalendar->Description = $aProps['{http://calendarserver.org/ns/}summary'];
			}
		}
		else 
		{
			if (isset($aProps['{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}calendar-description']))
			{
				$oCalendar->Description = $aProps['{'.\Sabre\CalDAV\Plugin::NS_CALDAV.'}calendar-description'];
			}
		}

		if (isset($aProps['{DAV:}displayname']))
		{
			$oCalendar->DisplayName = $aProps['{DAV:}displayname'];
		}
		if (isset($aProps['{'.\Sabre\CalDAV\Plugin::NS_CALENDARSERVER.'}getctag']))
		{
			$oCalendar->CTag = $aProps['{'.\Sabre\CalDAV\Plugin::NS_CALENDARSERVER.'}getctag'];
		}
		if (isset($aProps['{http://apple.com/ns/ical/}calendar-color']))
		{
			$oCalendar->Color = $aProps['{http://apple.com/ns/ical/}calendar-color'];
		}
		if (isset($aProps['{http://apple.com/ns/ical/}calendar-order']))
		{
			$oCalendar->Order = $aProps['{http://apple.com/ns/ical/}calendar-order'];
		}
		if (isset($aProps['{http://sabredav.org/ns}owner-principal']))
		{
			$oCalendar->Principals = array($aProps['{http://sabredav.org/ns}owner-principal']);
		}
		else
		{
			$oCalendar->Principals = array($oCalDAVCalendar->getOwner());
		}

		$sPrincipal = $oCalendar->GetMainPrincipalUrl();
		$sEmail = basename(urldecode($sPrincipal));

		$oCalendar->Owner = (!empty($sEmail)) ? $sEmail : $this->Account->Email;
		$oCalendar->Url = '/calendars/'.$this->Account->Email.'/'.$oCalDAVCalendar->getName();
		$oCalendar->RealUrl = 'calendars/'.$oCalendar->Owner.'/'.$oCalDAVCalendar->getName();
		$oCalendar->SyncToken = $oCalDAVCalendar->getSyncToken();

		$aTenantPrincipal = \Afterlogic\DAV\Backend::Principal()->getPrincipalInfo($this->Account);
		if($aTenantPrincipal && $aTenantPrincipal['uri'] === $oCalDAVCalendar->getOwner())
		{
			$oCalendar->SharedToAll = true;
		}
		
		return $oCalendar;
		
	}
	
	
}