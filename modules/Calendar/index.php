<?php

class CalendarModule extends AApiModule
{
	public $oApiCalendarManager = null;
	public $oApiCapabilityManager = null;
	
	public function init() 
	{
		$this->oApiCalendarManager = $this->GetManager('main', 'sabredav');
		$this->oApiCapabilityManager = \CApi::GetCoreManager('capability');
	}

	/**
	 * @return array
	 */
	public function GetCalendars()
	{
		$mResult = false;
		$bIsPublic = (bool) $this->getParamValue('IsPublic', false); 
		$oAccount = null;
				
		if ($bIsPublic)
		{
			$sPublicCalendarId = $this->getParamValue('PublicCalendarId', '');
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
			$oAccount = $this->getDefaultAccountFromParam();
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
	public function CreateCalendar()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sName = $this->getParamValue('Name');
		$sDescription = $this->getParamValue('Description'); 
		$sColor = $this->getParamValue('Color'); 
		
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
	public function UpdateCalendar()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sName = $this->getParamValue('Name');
		$sDescription = $this->getParamValue('Description'); 
		$sColor = $this->getParamValue('Color'); 
		$sId = $this->getParamValue('Id'); 
		
		$mResult = $this->oApiCalendarManager->updateCalendar($oAccount, $sId, $sName, $sDescription, 0, $sColor);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	

	/**
	 * @return array
	 */
	public function UpdateCalendarColor()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sColor = $this->getParamValue('Color'); 
		$sId = $this->getParamValue('Id'); 
		
		$mResult = $this->oApiCalendarManager->updateCalendarColor($oAccount, $sId, $sColor);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function UpdateCalendarShare()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$sCalendarId = $this->getParamValue('Id');
		$bIsPublic = (bool) $this->getParamValue('IsPublic');
		$aShares = @json_decode($this->getParamValue('Shares'), true);
		
		$bShareToAll = (bool) $this->getParamValue('ShareToAll', false);
		$iShareToAllAccess = (int) $this->getParamValue('ShareToAllAccess', \ECalendarPermission::Read);
		
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
	public function UpdateCalendarPublic()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$sCalendarId = $this->getParamValue('Id');
		$bIsPublic = (bool) $this->getParamValue('IsPublic');
		
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
	public function DeleteCalendar()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		
		$sCalendarId = $this->getParamValue('Id');
		$mResult = $this->oApiCalendarManager->deleteCalendar($oAccount, $sCalendarId);
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function GetEvents()
	{
		$mResult = false;
		$oAccount = null;
		$aCalendarIds = @json_decode($this->getParamValue('CalendarIds'), true);
		$iStart = $this->getParamValue('Start'); 
		$iEnd = $this->getParamValue('End'); 
		$bIsPublic = (bool) $this->getParamValue('IsPublic'); 
		$iTimezoneOffset = $this->getParamValue('TimezoneOffset'); 
		$sTimezone = $this->getParamValue('Timezone'); 
		
		if ($bIsPublic)
		{
			$oPublicAccount = $this->oApiCalendarManager->getPublicAccount();
			$oPublicAccount->User->DefaultTimeZone = $iTimezoneOffset;
			$oPublicAccount->User->ClientTimeZone = $sTimezone;
			$mResult = $this->oApiCalendarManager->getEvents($oPublicAccount, $aCalendarIds, $iStart, $iEnd);
		}
		else
		{
			$oAccount = $this->getDefaultAccountFromParam();
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
	public function CreateEvent()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$oEvent = new \CEvent();

		$oEvent->IdCalendar = $this->getParamValue('newCalendarId');
		$oEvent->Name = $this->getParamValue('subject');
		$oEvent->Description = $this->getParamValue('description');
		$oEvent->Location = $this->getParamValue('location');
		$oEvent->Start = $this->getParamValue('startTS');
		$oEvent->End = $this->getParamValue('endTS');
		$oEvent->AllDay = (bool) $this->getParamValue('allDay');
		$oEvent->Alarms = @json_decode($this->getParamValue('alarms'), true);
		$oEvent->Attendees = @json_decode($this->getParamValue('attendees'), true);

		$aRRule = @json_decode($this->getParamValue('rrule'), true);
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

			$mResult = $this->oApiCalendarManager->getExpandedEvent($oAccount, $oEvent->IdCalendar, $mResult, $iStart, $iEnd);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function UpdateEvent()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sNewCalendarId = $this->getParamValue('newCalendarId'); 
		$oEvent = new \CEvent();

		$oEvent->IdCalendar = $this->getParamValue('calendarId');
		$oEvent->Id = $this->getParamValue('uid');
		$oEvent->Name = $this->getParamValue('subject');
		$oEvent->Description = $this->getParamValue('description');
		$oEvent->Location = $this->getParamValue('location');
		$oEvent->Start = $this->getParamValue('startTS');
		$oEvent->End = $this->getParamValue('endTS');
		$oEvent->AllDay = (bool) $this->getParamValue('allDay');
		$oEvent->Alarms = @json_decode($this->getParamValue('alarms'), true);
		$oEvent->Attendees = @json_decode($this->getParamValue('attendees'), true);
		
		$aRRule = @json_decode($this->getParamValue('rrule'), true);
		if ($aRRule)
		{
			$oRRule = new \CRRule($oAccount);
			$oRRule->Populate($aRRule);
			$oEvent->RRule = $oRRule;
		}
		
		$iAllEvents = (int) $this->getParamValue('allEvents');
		$sRecurrenceId = $this->getParamValue('recurrenceId');
		
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
			$iStart = $this->getParamValue('selectStart'); 
			$iEnd = $this->getParamValue('selectEnd'); 

			$mResult = $this->oApiCalendarManager->getExpandedEvent($oAccount, $oEvent->IdCalendar, $oEvent->Id, $iStart, $iEnd);
		}
			
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function DeleteEvent()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();
		
		$sCalendarId = $this->getParamValue('calendarId');
		$sId = $this->getParamValue('uid');

		$iAllEvents = (int) $this->getParamValue('allEvents');
		
		if ($iAllEvents && $iAllEvents === 1)
		{
			$oEvent = new \CEvent();
			$oEvent->IdCalendar = $sCalendarId;
			$oEvent->Id = $sId;
			
			$sRecurrenceId = $this->getParamValue('recurrenceId');

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
	public function SetAppointmentAction()
	{
		$oAccount = $this->getAccountFromParam();
		$oDefaultAccount = $this->getDefaultAccountFromParam();
		
		$mResult = false;

		$sCalendarId = (string) $this->getParamValue('CalendarId', '');
		$sEventId = (string) $this->getParamValue('EventId', '');
		$sTempFile = (string) $this->getParamValue('File', '');
		$sAction = (string) $this->getParamValue('AppointmentAction', '');
		$sAttendee = (string) $this->getParamValue('Attendee', '');
		
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
}

return new CalendarModule('1.0');
