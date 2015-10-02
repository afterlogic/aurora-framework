<?php

class CalendarModule extends AApiModule
{
	public $oApiCalendarManager = null;
	public $oApiCapabilityManager = null;
	
	public function init() 
	{
		$this->oApiCalendarManager = $this->GetManager('main');
		$this->oApiCapabilityManager = \CApi::GetCoreManager('capability');
	}

	/**
	 * @return array
	 */
	public function GetCalendars($aParameters)
	{
		$mResult = false;
		$bIsPublic = (bool) $this->getParamValue($aParameters, 'IsPublic', false); 
		$oAccount = null;
				
		if ($bIsPublic)
		{
			$sPublicCalendarId = $this->getParamValue($aParameters, 'PublicCalendarId', '');
			$oCalendar = $this->oApiCalendarManager->getPublicCalendar($sPublicCalendarId);
			$mResult = array();
			if ($oCalendar instanceof \CCalendar)
			{
				$aCalendar = $oCalendar->toArray($oAccount);
				$mResult = array($aCalendar);
			}
		}
		else
		{
			$oAccount = $this->getDefaultAccountFromParam($aParameters);
			if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
			}
			$mResult = $this->oApiCalendarManager->getCalendars($oAccount);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function CreateCalendar($aParameters)
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sName = $this->getParamValue($aParameters, 'Name');
		$sDescription = $this->getParamValue($aParameters, 'Description'); 
		$sColor = $this->getParamValue($aParameters, 'Color'); 
		
		$mCalendarId = $this->oApiCalendarManager->createCalendar($oAccount, $sName, $sDescription, 0, $sColor);
		if ($mCalendarId)
		{
			$oCalendar = $this->oApiCalendarManager->getCalendar($oAccount, $mCalendarId);
			if ($oCalendar instanceof \CCalendar)
			{
				$mResult = $oCalendar->toArray($oAccount);
			}
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function UpdateCalendar($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sName = $this->getParamValue($aParameters, 'Name');
		$sDescription = $this->getParamValue($aParameters, 'Description'); 
		$sColor = $this->getParamValue($aParameters, 'Color'); 
		$sId = $this->getParamValue($aParameters, 'Id'); 
		
		$mResult = $this->oApiCalendarManager->updateCalendar($oAccount, $sId, $sName, $sDescription, 0, $sColor);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	

	/**
	 * @return array
	 */
	public function UpdateCalendarColor($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sColor = $this->getParamValue($aParameters, 'Color'); 
		$sId = $this->getParamValue($aParameters, 'Id'); 
		
		$mResult = $this->oApiCalendarManager->updateCalendarColor($oAccount, $sId, $sColor);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function UpdateCalendarShare($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		$sCalendarId = $this->getParamValue($aParameters, 'Id');
		$bIsPublic = (bool) $this->getParamValue($aParameters, 'IsPublic');
		$aShares = @json_decode($this->getParamValue($aParameters, 'Shares'), true);
		
		$bShareToAll = (bool) $this->getParamValue($aParameters, 'ShareToAll', false);
		$iShareToAllAccess = (int) $this->getParamValue($aParameters, 'ShareToAllAccess', \ECalendarPermission::Read);
		
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		// Share calendar to all users
		$aShares[] = array(
			'email' => $this->oApiCalendarManager->getTenantUser($oAccount),
			'access' => $bShareToAll ? $iShareToAllAccess : \ECalendarPermission::RemovePermission
		);
		
		// Public calendar
		$aShares[] = array(
			'email' => $this->oApiCalendarManager->getPublicUser(),
			'access' => $bIsPublic ? \ECalendarPermission::Read : \ECalendarPermission::RemovePermission
		);
		
		$mResult = $this->oApiCalendarManager->updateCalendarShares($oAccount, $sCalendarId, $aShares);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}		
	
	/**
	 * @return array
	 */
	public function UpdateCalendarPublic($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		$sCalendarId = $this->getParamValue($aParameters, 'Id');
		$bIsPublic = (bool) $this->getParamValue($aParameters, 'IsPublic');
		
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$mResult = $this->oApiCalendarManager->publicCalendar($oAccount, $sCalendarId, $bIsPublic);
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}		

	/**
	 * @return array
	 */
	public function DeleteCalendar($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		
		$sCalendarId = $this->getParamValue($aParameters, 'Id');
		$mResult = $this->oApiCalendarManager->deleteCalendar($oAccount, $sCalendarId);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function GetEvents($aParameters)
	{
		$mResult = false;
		$oAccount = null;
		$aCalendarIds = @json_decode($this->getParamValue($aParameters, 'CalendarIds'), true);
		$iStart = $this->getParamValue($aParameters,'Start'); 
		$iEnd = $this->getParamValue($aParameters, 'End'); 
		$bIsPublic = (bool) $this->getParamValue($aParameters, 'IsPublic'); 
		$iTimezoneOffset = $this->getParamValue($aParameters, 'TimezoneOffset'); 
		$sTimezone = $this->getParamValue($aParameters, 'Timezone'); 
		
		if ($bIsPublic)
		{
			$oPublicAccount = $this->oApiCalendarManager->getPublicAccount();
			$oPublicAccount->User->DefaultTimeZone = $iTimezoneOffset;
			$oPublicAccount->User->ClientTimeZone = $sTimezone;
			$mResult = $this->oApiCalendar->getEvents($oPublicAccount, $aCalendarIds, $iStart, $iEnd);
		}
		else
		{
			$oAccount = $this->getDefaultAccountFromParam($aParameters);
			if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
			}
			$mResult = $this->oApiCalendarManager->getEvents($oAccount, $aCalendarIds, $iStart, $iEnd);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function CreateEvent($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$oEvent = new \CEvent();

		$oEvent->IdCalendar = $this->getParamValue($aParameters, 'newCalendarId');
		$oEvent->Name = $this->getParamValue($aParameters, 'subject');
		$oEvent->Description = $this->getParamValue($aParameters, 'description');
		$oEvent->Location = $this->getParamValue($aParameters, 'location');
		$oEvent->Start = $this->getParamValue($aParameters, 'startTS');
		$oEvent->End = $this->getParamValue($aParameters, 'endTS');
		$oEvent->AllDay = (bool) $this->getParamValue($aParameters, 'allDay');
		$oEvent->Alarms = @json_decode($this->getParamValue($aParameters, 'alarms'), true);
		$oEvent->Attendees = @json_decode($this->getParamValue($aParameters, 'attendees'), true);

		$aRRule = @json_decode($this->getParamValue($aParameters, 'rrule'), true);
		if ($aRRule)
		{
			$oRRule = new \CRRule($oAccount);
			$oRRule->Populate($aRRule);
			$oEvent->RRule = $oRRule;
		}

		$mResult = $this->oApiCalendarManager->createEvent($oAccount, $oEvent);
		if ($mResult)
		{
			$iStart = $this->getParamValue('selectStart'); 
			$iEnd = $this->getParamValue('selectEnd'); 

			$mResult = $this->oApiCalendar->getExpandedEvent($oAccount, $oEvent->IdCalendar, $mResult, $iStart, $iEnd);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function UpdateEvent($aParameters)
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sNewCalendarId = $this->getParamValue($aParameters, 'newCalendarId'); 
		$oEvent = new \CEvent();

		$oEvent->IdCalendar = $this->getParamValue($aParameters, 'calendarId');
		$oEvent->Id = $this->getParamValue($aParameters, 'uid');
		$oEvent->Name = $this->getParamValue($aParameters, 'subject');
		$oEvent->Description = $this->getParamValue($aParameters, 'description');
		$oEvent->Location = $this->getParamValue($aParameters, 'location');
		$oEvent->Start = $this->getParamValue($aParameters, 'startTS');
		$oEvent->End = $this->getParamValue($aParameters, 'endTS');
		$oEvent->AllDay = (bool) $this->getParamValue($aParameters, 'allDay');
		$oEvent->Alarms = @json_decode($this->getParamValue($aParameters, 'alarms'), true);
		$oEvent->Attendees = @json_decode($this->getParamValue($aParameters, 'attendees'), true);
		
		$aRRule = @json_decode($this->getParamValue($aParameters, 'rrule'), true);
		if ($aRRule)
		{
			$oRRule = new \CRRule($oAccount);
			$oRRule->Populate($aRRule);
			$oEvent->RRule = $oRRule;
		}
		
		$iAllEvents = (int) $this->getParamValue($aParameters, 'allEvents');
		$sRecurrenceId = $this->getParamValue($aParameters, 'recurrenceId');
		
		if ($iAllEvents && $iAllEvents === 1)
		{
			$mResult = $this->oApiCalendarManager->updateExclusion($oAccount, $oEvent, $sRecurrenceId);
		}
		else
		{
			$mResult = $this->oApiCalendarManager->updateEvent($oAccount, $oEvent);
			if ($mResult && $sNewCalendarId !== $oEvent->IdCalendar)
			{
				$mResult = $this->oApiCalendarManager->moveEvent($oAccount, $oEvent->IdCalendar, $sNewCalendarId, $oEvent->Id);
				$oEvent->IdCalendar = $sNewCalendarId;
			}
		}
		if ($mResult)
		{
			$iStart = $this->getParamValue($aParameters, 'selectStart'); 
			$iEnd = $this->getParamValue($aParameters, 'selectEnd'); 

			$mResult = $this->oApiCalendarManager->getExpandedEvent($oAccount, $oEvent->IdCalendar, $oEvent->Id, $iStart, $iEnd);
		}
			
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function DeleteEvent($aParameters)
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		
		$sCalendarId = $this->getParamValue($aParameters, 'calendarId');
		$sId = $this->getParamValue($aParameters, 'uid');

		$iAllEvents = (int) $this->getParamValue($aParameters, 'allEvents');
		
		if ($iAllEvents && $iAllEvents === 1)
		{
			$oEvent = new \CEvent();
			$oEvent->IdCalendar = $sCalendarId;
			$oEvent->Id = $sId;
			
			$sRecurrenceId = $this->getParamValue($aParameters, 'recurrenceId');

			$mResult = $this->oApiCalendarManager->updateExclusion($oAccount, $oEvent, $sRecurrenceId, true);
		}
		else
		{
			$mResult = $this->oApiCalendarManager->deleteEvent($oAccount, $sCalendarId, $sId);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function SetAppointmentAction($aParameters)
	{
		$oAccount = $this->getAccountFromParam($aParameters);
		$oDefaultAccount = $this->getDefaultAccountFromParam($aParameters);
		
		$mResult = false;

		$sCalendarId = (string) $this->getParamValue($aParameters, 'CalendarId', '');
		$sEventId = (string) $this->getParamValue($aParameters, 'EventId', '');
		$sTempFile = (string) $this->getParamValue($aParameters, 'File', '');
		$sAction = (string) $this->getParamValue($aParameters, 'AppointmentAction', '');
		$sAttendee = (string) $this->getParamValue($aParameters, 'Attendee', '');
		
		if (empty($sAction) || empty($sCalendarId))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		if ($this->oApiCapabilityManager->isCalendarAppointmentsSupported($oDefaultAccount))
		{
			$sData = '';
			if (!empty($sEventId))
			{
				$aEventData =  $this->oApiCalendarManager->getEvent($oDefaultAccount, $sCalendarId, $sEventId);
				if (isset($aEventData) && isset($aEventData['vcal']) && $aEventData['vcal'] instanceof \Sabre\VObject\Component\VCalendar)
				{
					$oVCal = $aEventData['vcal'];
					$oVCal->METHOD = 'REQUEST';
					$sData = $oVCal->serialize();
				}
			}
			else if (!empty($sTempFile))
			{
				$oApiFileCache = /* @var $oApiFileCache \CApiFilecacheManager */ \CApi::Manager('filecache');
				$sData = $oApiFileCache->get($oAccount, $sTempFile);
			}
			if (!empty($sData))
			{
				$mProcessResult = $this->oApiCalendarManager->appointmentAction($oDefaultAccount, $sAttendee, $sAction, $sCalendarId, $sData);
				if ($mProcessResult)
				{
					$mResult = array(
						'Uid' => $mProcessResult
					);
				}
			}
		}

		return $this->DefaultResponse($oDefaultAccount, __FUNCTION__, $mResult);
	}	
	
	public function ExecuteMethod($sMethod, $aArguments) 
	{
		$mResult = parent::ExecuteMethod($sMethod, $aArguments);
		if (!$mResult && method_exists($this->oApiCalendarManager, $sMethod))
		{
			$mResult = call_user_func_array(array($this->oApiCalendarManager, $sMethod), $aArguments);
		}
		
		return $mResult;
	}
}

return new CalendarModule('1.0');
