<?php

class CalendarModule extends AApiModule
{
	public $oApiCalendarManager = null;
	
	public function init() 
	{
		$this->oApiCalendarManager = $this->GetManager('main', 'sabredav');
		$this->AddEntries(array(
				'invite' => 'EntryInvite',
				'calendar-pub' => 'EntryCalendarPub'
			)
		);
		
		$this->subscribeEvent('Mail::GetBodyStructureParts', array($this, 'onGetBodyStructureParts'));
		$this->subscribeEvent('Mail::ExtendMessageData', array($this, 'onExtendMessageData'));
	}
	
	/**
	 * @return array
	 */
	public function GetCalendars($bIsPublic = false, $sPublicCalendarId = '')
	{
		$mResult = false;
		$mCalendars = false;
		
		if ($bIsPublic) {
			
			$oCalendar = $this->oApiCalendarManager->getPublicCalendar($sPublicCalendarId);
			$mCalendars = array($oCalendar);
		} else {
			
			$iUserId = \CApi::getLogginedUserId();
			if (!$this->oApiCapabilityManager->isCalendarSupported($iUserId)) {
				
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
			}
	
			$mCalendars = $this->oApiCalendarManager->getCalendars($iUserId);
		}
		
		if ($mCalendars) {
			
			$mResult['Calendars'] = $mCalendars;
		}
		
		return $mResult;
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

			if (isset($aValues['CalendarId'])) {
				
				$sCalendarId = $aValues['CalendarId'];

				$sOutput = $this->oApiCalendarManager->exportCalendarToIcs($oAccount, $sCalendarId);
				if (false !== $sOutput) {
					
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
	public function CreateCalendar($sName, $sDescription, $sColor)
	{
		$mResult = false;
		$iUserId = \CApi::getLogginedUserId();
		if (!$this->oApiCapabilityManager->isCalendarSupported($iUserId))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$mCalendarId = $this->oApiCalendarManager->createCalendar($iUserId, $sName, $sDescription, 0, $sColor);
		if ($mCalendarId)
		{
			$oCalendar = $this->oApiCalendarManager->getCalendar($iUserId, $mCalendarId);
			if ($oCalendar instanceof \CCalendar)
			{
				$mResult = $oCalendar->toResponseArray($iUserId);
			}
		}
		
		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateCalendar($sName, $sDescription, $sColor, $sId)
	{
		$iUserId = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($iUserId))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		return $this->oApiCalendarManager->updateCalendar($iUserId, $sId, $sName, $sDescription, 0, $sColor);
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
		
		return $this->oApiCalendarManager->updateCalendarColor($oAccount, $sId, $sColor);
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
		
		return $this->oApiCalendarManager->updateCalendarShares($oAccount, $sCalendarId, $aShares);
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
		
		return $this->oApiCalendarManager->publicCalendar($oAccount, $sCalendarId, $bIsPublic);
	}		

	/**
	 * @return array
	 */
	public function DeleteCalendar()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		
		$sCalendarId = $this->getParamValue('Id');
		$mResult = $this->oApiCalendarManager->deleteCalendar($oAccount, $sCalendarId);
		
		return $this->DefaultResponse(__FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function GetBaseEvent()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapabilityManager->isCalendarSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
		}
		
		$sCalendarId = $this->getParamValue('calendarId');
		$sEventId = $this->getParamValue('uid');
		
		return $this->oApiCalendarManager->getBaseEvent($oAccount, $sCalendarId, $sEventId);
	}	
	
	
	/**
	 * @return array
	 */
	public function GetEvents($aCalendarIds, $iStart, $iEnd, $bIsPublic, $iTimezoneOffset, $sTimezone)
	{
		$mResult = false;
		
		if ($bIsPublic)
		{
			$oPublicAccount = $this->oApiCalendarManager->getPublicAccount();
			$oPublicAccount->User->DefaultTimeZone = $iTimezoneOffset;
			$oPublicAccount->User->ClientTimeZone = $sTimezone;
			$mResult = $this->oApiCalendarManager->getEvents($oPublicAccount, $aCalendarIds, $iStart, $iEnd);
		}
		else
		{
			$iUserId = \CApi::getLogginedUserId();
			if (!$this->oApiCapabilityManager->isCalendarSupported($iUserId))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CalendarsNotAllowed);
			}
			$mResult = $this->oApiCalendarManager->getEvents($iUserId, $aCalendarIds, $iStart, $iEnd);
		}
		
		return $mResult;
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
		
		return $mResult;
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
			
		return $mResult;
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
		
		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function AddEventsFromFile()
	{
		$oAccount = $this->getDefaultAccountFromParam();

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

		return $mResult;
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
				$oApiFileCache = /* @var $oApiFileCache \CApiFilecacheManager */ \CApi::GetCoreManager('filecache');
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

		return $mResult;
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
							$sResult = file_get_contents($this->GetPath().'/templates/CalendarEventInviteExternal.html');

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
			$oCoreModule = \CApi::GetModule('Core');
			if ($oCoreModule instanceof \AApiModule) {
				$sResult = file_get_contents($oCoreModule->GetPath().'/templates/Index.html');
				if (is_string($sResult)) {
					$sFrameOptions = \CApi::GetConf('labs.x-frame-options', '');
					if (0 < \strlen($sFrameOptions)) {
						@\header('X-Frame-Options: '.$sFrameOptions);
					}
					
					$sAuthToken = isset($_COOKIE[\Core\Service::AUTH_TOKEN_KEY]) ? $_COOKIE[\Core\Service::AUTH_TOKEN_KEY] : '';
					$sResult = strtr($sResult, array(
						'{{AppVersion}}' => PSEVEN_APP_VERSION,
						'{{IntegratorDir}}' => $oApiIntegrator->isRtl() ? 'rtl' : 'ltr',
						'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink(),
						'{{IntegratorBody}}' => $oApiIntegrator->buildBody('-calendar-pub')
					));
				}
			}
		}

		return $sResult;	
	}
	
	public function UpdateAttendeeStatus()
	{
		$oAccount = $this->getAccountFromParam();
		
		$mResult = false;

		$sTempFile = (string) $this->getParamValue('File', '');
		$sFromEmail = (string) $this->getParamValue('FromEmail', '');
		
		if (empty($sTempFile) || empty($sFromEmail))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}
		if ($this->oApiCapabilityManager->isCalendarAppointmentsSupported($oAccount))
		{
			$oApiFileCache = /* @var $oApiFileCache \CApiFilecacheManager */ \CApi::GetCoreManager('filecache');
			$sData = $oApiFileCache->get($oAccount, $sTempFile);
			if (!empty($sData))
			{
				$mResult = $this->oApiCalendarManager->processICS($oAccount, $sData, $sFromEmail, true);
			}
		}

		return $mResult;		
		
	}	
	
	public function ProcessICS()
	{
		$oAccount = $this->getParamValue('Account', null);
		$sData = (string) $this->getParamValue('Data', '');
		$sFromEmail = (string) $this->getParamValue('FromEmail', '');

		return $this->oApiCalendarManager->processICS($oAccount, $sData, $sFromEmail);
	}
	
	/**
	 * @return array
	 */
	public function UploadCalendars()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		
		$aFileData = $this->getParamValue('FileData', null);
		$sAdditionalData = $this->getParamValue('AdditionalData', '{}');
		$aAdditionalData = @json_decode($sAdditionalData, true);
		
		$sCalendarId = isset($aAdditionalData['CalendarID']) ? $aAdditionalData['CalendarID'] : '';

		$sError = '';
		$aResponse = array(
			'ImportedCount' => 0
		);

		if (is_array($aFileData))
		{
			$bIsIcsExtension  = strtolower(pathinfo($aFileData['name'], PATHINFO_EXTENSION)) === 'ics';

			if ($bIsIcsExtension)
			{
				$oApiFileCacheManager = \CApi::GetCoreManager('filecache');
				$sSavedName = 'import-post-' . md5($aFileData['name'] . $aFileData['tmp_name']);
				if ($oApiFileCacheManager->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name'])) {
					
					$iImportedCount = $this->oApiCalendarManager->importToCalendarFromIcs(
							$oAccount, 
							$sCalendarId, 
							$oApiFileCacheManager->generateFullFilePath($oAccount, $sSavedName)
					);

					if (false !== $iImportedCount && -1 !== $iImportedCount) {
						$aResponse['ImportedCount'] = $iImportedCount;
					} else {
						$sError = 'unknown';
					}

					$oApiFileCacheManager->clear($oAccount, $sSavedName);
				}
				else
				{
					$sError = 'unknown';
				}
			}
			else
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::IncorrectFileExtension);
			}
		}
		else
		{
			$sError = 'unknown';
		}

		if (0 < strlen($sError))
		{
			$aResponse['Error'] = $sError;
		}		
		
		return $aResponse;
	}		
	
	public function onGetBodyStructureParts($aParts, &$aResultParts)
	{
		foreach ($aParts as $oPart) {
			if ($oPart instanceof \MailSo\Imap\BodyStructure && 
					$oPart->ContentType() === 'text/calendar'){
				
				$aResultParts[] = $oPart;
			}
		}
	}
	
	public function onExtendMessageData($oAccount, &$oMessage, $aData)
	{
		$oApiCapa = /* @var CApiCapabilityManager */ $this->oApiCapabilityManager;
		$oApiFileCache = /* @var CApiFilecacheManager */ CApi::GetCoreManager('filecache');
		$sFromEmail = '';
		$oFromCollection = $oMessage->getFrom();
		if ($oFromCollection && 0 < $oFromCollection->Count())
		{
			$oFrom =& $oFromCollection->GetByIndex(0);
			if ($oFrom)
			{
				$sFromEmail = trim($oFrom->GetEmail());
			}
		}
		foreach ($aData as $aDataItem)
		{
			if ($aDataItem['Part'] instanceof \MailSo\Imap\BodyStructure && $aDataItem['Part']->ContentType() === 'text/calendar')
			{
				$sData = $aDataItem['Data'];
				if (!empty($sData) && $oApiCapa->isCalendarSupported($oAccount))
				{
					$mResult = $this->oApiCalendarManager->processICS($oAccount, $sData, $sFromEmail);
					if (is_array($mResult) && !empty($mResult['Action']) && !empty($mResult['Body']))
					{
						$sTemptFile = md5($mResult['Body']).'.ics';
						if ($oApiFileCache && $oApiFileCache->put($oAccount, $sTemptFile, $mResult['Body']))
						{
							$oIcs = CApiMailIcs::createInstance();

							$oIcs->Uid = $mResult['UID'];
							$oIcs->Sequence = $mResult['Sequence'];
							$oIcs->File = $sTemptFile;
							$oIcs->Attendee = isset($mResult['Attendee']) ? $mResult['Attendee'] : null;
							$oIcs->Type = ($oApiCapa->isCalendarAppointmentsSupported($oAccount)) ? $mResult['Action'] : 'SAVE';
							$oIcs->Location = !empty($mResult['Location']) ? $mResult['Location'] : '';
							$oIcs->Description = !empty($mResult['Description']) ? $mResult['Description'] : '';
							$oIcs->When = !empty($mResult['When']) ? $mResult['When'] : '';
							$oIcs->CalendarId = !empty($mResult['CalendarId']) ? $mResult['CalendarId'] : '';

							$oMessage->addExtend('ICAL', $oIcs);
						}
						else
						{
							CApi::Log('Can\'t save temp file "'.$sTemptFile.'"', ELogLevel::Error);
						}
					}
				}				
			}
		}
	}
	
}

return new CalendarModule('1.0');
