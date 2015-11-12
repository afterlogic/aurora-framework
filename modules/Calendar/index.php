<?php

class CalendarModule extends AApiModule
{
	public $oApiCalendarManager = null;
	
	public function init() 
	{
		$this->oApiCalendarManager = $this->GetManager('main', 'sabredav');
		$this->AddEntry('invite', 'EntryInvite');
		$this->AddEntry('calendar-pub', 'EntryCalendarPub');
	}

	/**
	 * @return array
	 */
	public function GetCalendars()
	{
		$mResult = false;
		$mCalendars = false;
		
		$bIsPublic = (bool) $this->getParamValue('IsPublic', false); 
		$oAccount = null;
				
		if ($bIsPublic)
		{
			$sPublicCalendarId = $this->getParamValue('PublicCalendarId', '');
			$oCalendar = $this->oApiCalendarManager->getPublicCalendar($sPublicCalendarId);
			$mCalendars = array($oCalendar);
		}
		else
		{
			$oAccount = $this->getDefaultAccountFromParam();
			if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
			}
			$mCalendars = $this->oApiCalendarManager->getCalendars($oAccount);
		}
		
		if ($mCalendars)
		{
			$oApiDavManager = \CApi::GetCoreManager('dav');
			$mResult['Calendars'] = $mCalendars;
			$mResult['ServerUrl'] = $oApiDavManager && $oAccount ? $oApiDavManager->getServerUrl($oAccount) : '';
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return bool
	 */
	public function DownloadCalendar()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if ($this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			$sRawKey = (string) $this->getParamValue('RawKey', '');
			$aValues = \CApi::DecodeKeyValues($sRawKey);

			if (isset($aValues['CalendarId']))
			{
				$sCalendarId = $aValues['CalendarId'];

				$sOutput = $this->oApiCalendarManager->exportCalendarToIcs($oAccount, $sCalendarId);
				if (false !== $sOutput)
				{
					header('Pragma: public');
					header('Content-Type: text/calendar');
					header('Content-Disposition: attachment; filename="'.$sCalendarId.'.ics";');
					header('Content-Transfer-Encoding: binary');

					echo $sOutput;
					return true;
				}
			}
		}

		return false;		
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
				$mResult = $oCalendar->toResponseArray($oAccount);
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
	public function GetBaseEvent()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sCalendarId = $this->getParamValue('calendarId');
		$sEventId = $this->getParamValue('uid');
		
		$mResult = $this->oApiCalendarManager->getBaseEvent($oAccount, $sCalendarId, $sEventId);
		
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
	public function AddEventsFromFile()
	{
		$oAccount = $this->getAccountFromParam();

		$mResult = false;

		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}

		$sCalendarId = (string) $this->getParamValue('CalendarId', '');
		$sTempFile = (string) $this->getParamValue('File', '');

		if (empty($sCalendarId) || empty($sTempFile))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oApiFileCache = /* @var $oApiFileCache \CApiFilecacheManager */ \CApi::GetCoreManager('filecache');
		$sData = $oApiFileCache->get($oAccount, $sTempFile);
		if (!empty($sData))
		{
			$mCreateEventResult = $this->oApiCalendarManager->createEventFromRaw($oAccount, $sCalendarId, null, $sData);
			if ($mCreateEventResult)
			{
				$mResult = array(
					'Uid' => (string) $mCreateEventResult
				);
			}
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
	
	public function EntryInvite()
	{
		$sResult = '';
		$aInviteValues = \CApi::DecodeKeyValues($this->oHttp->GetQuery('invite'));

		$oApiUsersManager = \CApi::GetCoreManager('users');
		if (isset($aInviteValues['organizer']))
		{
			$oAccountOrganizer = $oApiUsersManager->getAccountByEmail($aInviteValues['organizer']);
			if (isset($oAccountOrganizer, $aInviteValues['attendee'], $aInviteValues['calendarId'], $aInviteValues['eventId'], $aInviteValues['action']))
			{
				$oCalendar = $this->oApiCalendarManager->getCalendar($oAccountOrganizer, $aInviteValues['calendarId']);
				if ($oCalendar)
				{
					$oEvent = $this->oApiCalendarManager->getEvent($oAccountOrganizer, $aInviteValues['calendarId'], $aInviteValues['eventId']);
					if ($oEvent && is_array($oEvent) && 0 < count ($oEvent) && isset($oEvent[0]))
					{
						if (is_string($sResult))
						{
							$sResult = file_get_contents(PSEVEN_APP_ROOT_PATH.'templates/CalendarEventInviteExternal.html');

							$dt = new \DateTime();
							$dt->setTimestamp($oEvent[0]['startTS']);
							if (!$oEvent[0]['allDay'])
							{
								$sDefaultTimeZone = new \DateTimeZone($oAccountOrganizer->getDefaultStrTimeZone());
								$dt->setTimezone($sDefaultTimeZone);
							}

							$sAction = $aInviteValues['action'];
							$sActionColor = 'green';
							$sActionText = '';
							switch (strtoupper($sAction))
							{
								case 'ACCEPTED':
									$sActionColor = 'green';
									$sActionText = 'Accepted';
									break;
								case 'DECLINED':
									$sActionColor = 'red';
									$sActionText = 'Declined';
									break;
								case 'TENTATIVE':
									$sActionColor = '#A0A0A0';
									$sActionText = 'Tentative';
									break;
							}

							$sDateFormat = 'm/d/Y';
							$sTimeFormat = 'h:i A';
							switch ($oAccountOrganizer->User->DefaultDateFormat)
							{
								case \EDateFormat::DDMMYYYY:
									$sDateFormat = 'd/m/Y';
									break;
								case \EDateFormat::DD_MONTH_YYYY:
									$sDateFormat = 'd/m/Y';
									break;
								default:
									$sDateFormat = 'm/d/Y';
									break;
							}
							switch ($oAccountOrganizer->User->DefaultTimeFormat)
							{
								case \ETimeFormat::F24:
									$sTimeFormat = 'H:i';
									break;
								case \EDateFormat::DD_MONTH_YYYY:
									\ETimeFormat::F12;
									$sTimeFormat = 'h:i A';
									break;
								default:
									$sTimeFormat = 'h:i A';
									break;
							}
							$sDateTime = $dt->format($sDateFormat.' '.$sTimeFormat);

							$mResult = array(
								'{{COLOR}}' => $oCalendar->Color,
								'{{EVENT_NAME}}' => $oEvent[0]['subject'],
								'{{EVENT_BEGIN}}' => ucfirst(\CApi::ClientI18N('REMINDERS/EVENT_BEGIN', $oAccountOrganizer)),
								'{{EVENT_DATE}}' => $sDateTime,
								'{{CALENDAR}}' => ucfirst(\CApi::ClientI18N('REMINDERS/CALENDAR', $oAccountOrganizer)),
								'{{CALENDAR_NAME}}' => $oCalendar->DisplayName,
								'{{EVENT_DESCRIPTION}}' => $oEvent[0]['description'],
								'{{EVENT_ACTION}}' => $sActionText,
								'{{ACTION_COLOR}}' => $sActionColor,
							);

							$sResult = strtr($sResult, $mResult);
						}
						else
						{
							\CApi::Log('Empty template.', \ELogLevel::Error);
						}
					}
					else
					{
						\CApi::Log('Event not found.', \ELogLevel::Error);
					}
				}
				else
				{
					\CApi::Log('Calendar not found.', \ELogLevel::Error);
				}
				$sAttendee = $aInviteValues['attendee'];
				if (!empty($sAttendee))
				{
					$this->oApiCalendarManager->updateAppointment($oAccountOrganizer, $aInviteValues['calendarId'], $aInviteValues['eventId'], $sAttendee, $aInviteValues['action']);
				}
			}
		}
		return $sResult;
	}
	
	public function EntryCalendarPub()
	{
		$sResult = '';
		
		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		
		if ($oApiIntegrator)
		{
			@\header('Content-Type: text/html; charset=utf-8', true);
			
			if (!strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'firefox'))
			{
				@\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
			}
			
			if ((\CApi::GetConf('labs.cache-ctrl', true) && isset($_COOKIE['aft-cache-ctrl'])))
			{
				setcookie('aft-cache-ctrl', '', time() - 3600);
				\MailSo\Base\Http::NewInstance()->StatusHeader(304);
				exit();
			}
			$sResult = file_get_contents(PSEVEN_APP_ROOT_PATH.'templates/Index.html');
			if (is_string($sResult))
			{
				$sFrameOptions = \CApi::GetConf('labs.x-frame-options', '');
				if (0 < \strlen($sFrameOptions))
				{
					@\header('X-Frame-Options: '.$sFrameOptions);
				}

				$sResult = strtr($sResult, array(
					'{{AppVersion}}' => PSEVEN_APP_VERSION,
					'{{IntegratorDir}}' => $oApiIntegrator->isRtl() ? 'rtl' : 'ltr',
					'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink('', \MailSo\Base\Http::NewInstance()->GetQuery('calendar-pub')),
					'{{IntegratorBody}}' => $oApiIntegrator->buildBody('', \MailSo\Base\Http::NewInstance()->GetQuery('calendar-pub'))
				));
			}
		}

		return $sResult;	}
	
	
}

return new CalendarModule('1.0');
