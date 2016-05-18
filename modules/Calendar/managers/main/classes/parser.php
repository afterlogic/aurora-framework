<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @internal
 * 
 * @package Calendar
 * @subpackage Classes
 */
class CalendarParser
{
	/**
	 * @param CAccount $oAccount
	 * @param CCalendar $oCalendar
	 * @param \Sabre\VObject\Component\VCalendar $oVCal
	 * @param \Sabre\VObject\Component\VCalendar $oVCalOriginal Default value is **null**.
	 *
	 * @return array
	 */
	public static function parseEvent($oAccount, $oCalendar, $oVCal, $oVCalOriginal = null)
	{
		$ApiCapabilityManager = CApi::GetCoreManager('capability');
		$ApiUsersManager = CApi::GetCoreManager('users');

		$aResult = array();
		$aRules = array();
		$aExcludedRecurrences = array();
		
		if (isset($oVCalOriginal))
		{
			$aRules = CalendarParser::getRRules($oAccount, $oVCalOriginal);
			$aExcludedRecurrences = CalendarParser::getExcludedRecurrences($oVCalOriginal);
		}

		if (isset($oVCal, $oVCal->VEVENT))
		{
			foreach ($oVCal->VEVENT as $oVEvent)
			{
				$sOwnerEmail = $oCalendar->Owner;
				$aEvent = array();
				
				if (isset($oVEvent, $oVEvent->UID))
				{
					$sUid = (string)$oVEvent->UID;
					$sRecurrenceId = CCalendarHelper::getRecurrenceId($oVEvent);

					$sId = $sUid . '-' . $sRecurrenceId;
					
					if (array_key_exists($sId, $aExcludedRecurrences))
					{
						$oVEvent = $aExcludedRecurrences[$sId];
						$aEvent['excluded'] = true;
					}

					$bIsAppointment = false;
					$aEvent['attendees'] = array();
					if ($ApiCapabilityManager->isCalendarAppointmentsSupported($oAccount) && isset($oVEvent->ATTENDEE))
					{
						$aEvent['attendees'] = self::parseAttendees($oVEvent);

						if (isset($oVEvent->ORGANIZER))
						{
							$sOwnerEmail = str_replace('mailto:', '', strtolower((string)$oVEvent->ORGANIZER));
						}
						$bIsAppointment = ($sOwnerEmail !== $oAccount->Email);
					}
					
					$oOwner = $ApiUsersManager->getAccountByEmail($sOwnerEmail);
					$sOwnerName = ($oOwner) ? $oOwner->FriendlyName : '';
					
					$aEvent['appointment'] = $bIsAppointment;
					$aEvent['appointmentAccess'] = 0;
					
					$aEvent['alarms'] = self::parseAlarms($oVEvent);

					$bAllDay = (isset($oVEvent->DTSTART) && !$oVEvent->DTSTART->hasTime());
					$sTimeZone = /*($bAllDay) ? 'UTC' : $oAccount->getDefaultStrTimeZone()*/ 'UTC';

					if (!isset($oVEvent->DTEND))
					{
						$dtStart = $oVEvent->DTSTART->getDateTime();
						if ($dtStart)
						{
							$dtStart->add(new DateInterval('PT1H'));
							$oVEvent->DTEND = $dtStart;
						}
					}
					
					$aEvent['calendarId'] = $oCalendar->Id;
					$aEvent['id'] = $sId;
					$aEvent['uid'] = $sUid;
					$aEvent['subject'] = $oVEvent->SUMMARY ? (string)$oVEvent->SUMMARY : '';
					$aDescription = $oVEvent->DESCRIPTION ? \Sabre\VObject\Parser\MimeDir::unescapeValue((string)$oVEvent->DESCRIPTION) : array('');
					$aEvent['description'] = $aDescription[0];
					$aEvent['location'] = $oVEvent->LOCATION ? (string)$oVEvent->LOCATION : '';
					$aEvent['start'] = CCalendarHelper::getStrDate($oVEvent->DTSTART, $sTimeZone);
					$aEvent['startTS'] = CCalendarHelper::getTimestamp($oVEvent->DTSTART, $sTimeZone);
					$aEvent['end'] = CCalendarHelper::getStrDate($oVEvent->DTEND, $sTimeZone);
					$aEvent['allDay'] = $bAllDay;
					$aEvent['owner'] = $sOwnerEmail;
					$aEvent['ownerName'] = $sOwnerName;
					$aEvent['modified'] = false;
					$aEvent['recurrenceId'] = $sRecurrenceId;
					if (isset($aRules[$sUid]) && $aRules[$sUid] instanceof \CRRule)
					{
						$aEvent['rrule'] = $aRules[$sUid]->toArray();
					}
				}
				
				$aResult[] = $aEvent;
			}
		}

		return $aResult;
	}

	/**
	 * @param \Sabre\VObject\Component\VEvent $oVEvent
	 *
	 * @return array
	 */
	public static function parseAlarms($oVEvent)
	{
		$aResult = array();
		
		if ($oVEvent->VALARM)
		{
			foreach($oVEvent->VALARM as $oVAlarm)
			{
				if (isset($oVAlarm->TRIGGER) && $oVAlarm->TRIGGER instanceof \Sabre\VObject\Property\ICalendar\Duration)
				{
					$aResult[] = CCalendarHelper::getOffsetInMinutes($oVAlarm->TRIGGER->getDateInterval());
				}
			}
			rsort($aResult);
		}	
		
		return $aResult;
	}

	/**
	 * @param \Sabre\VObject\Component\VEvent $oVEvent
	 *
	 * @return array
	 */
	public static function parseAttendees($oVEvent)
	{
		$aResult = array();
		
		if (isset($oVEvent->ATTENDEE))
		{
			foreach($oVEvent->ATTENDEE as $oAttendee)
			{
				$iStatus = \EAttendeeStatus::Unknown;
				if (isset($oAttendee['PARTSTAT']))
				{
					switch (strtoupper((string)$oAttendee['PARTSTAT']))
					{
						case 'ACCEPTED':
							$iStatus = \EAttendeeStatus::Accepted;
							break;
						case 'DECLINED':
							$iStatus = \EAttendeeStatus::Declined;
							break;
						case 'TENTATIVE':
							$iStatus = \EAttendeeStatus::Tentative;;
							break;
					}
				}

				$aResult[] = array(
					'access' => 0,
					'email' => isset($oAttendee['EMAIL']) ? (string)$oAttendee['EMAIL'] : str_replace('mailto:', '', strtolower($oAttendee->getValue())),
					'name' => isset($oAttendee['CN']) ? (string)$oAttendee['CN'] : '',
					'status' => $iStatus
				);
			}
		}

		return $aResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param \Sabre\VObject\Component\VEvent $oVEventBase
	 *
	 * @return \CRRule|null
	 */
	public static function parseRRule($oAccount, $oVEventBase)
	{
		$oResult = null;

		$aWeekDays = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
		$aPeriods = array(
			EPeriodStr::Secondly,
			EPeriodStr::Minutely,
			EPeriodStr::Hourly,
			EPeriodStr::Daily,
			EPeriodStr::Weekly,
			EPeriodStr::Monthly,
			EPeriodStr::Yearly
		);
		
		if (isset($oVEventBase->RRULE))
		{
			$oResult = new \CRRule($oAccount);
			$aRules = $oVEventBase->RRULE->getParts();
			if (isset($aRules['FREQ']))
			{
				$bIsPosiblePeriod = array_search(strtolower($aRules['FREQ']), array_map('strtolower', $aPeriods));
				if ($bIsPosiblePeriod !== false)
				{
					$oResult->Period = $bIsPosiblePeriod - 2;
				}
			}
			if (isset($aRules['INTERVAL']))
			{
				$oResult->Interval = $aRules['INTERVAL'];
			}
			if (isset($aRules['COUNT']))
			{
				$oResult->Count = $aRules['COUNT'];
			}
			if (isset($aRules['UNTIL']))
			{
				$oResult->Until = date_format(date_create($aRules['UNTIL']), 'U');
			}
			if (isset($oResult->Count))
			{
				$oResult->End = \ERepeatEnd::Count;
			}
			else if (isset($oResult->Until))
			{
				$oResult->End = \ERepeatEnd::Date;
			}
			else
			{
				$oResult->End = \ERepeatEnd::Infinity;
			}

			if (isset($aRules['BYDAY']) && is_array($aRules['BYDAY']))
			{
				foreach ($aRules['BYDAY'] as $sDay)
				{
					if (strlen($sDay) > 2)
					{
						$iNum = (int)substr($sDay, 0, -2);

						if ($iNum === 1) $oResult->WeekNum = 0;
						if ($iNum === 2) $oResult->WeekNum = 1;
						if ($iNum === 3) $oResult->WeekNum = 2;
						if ($iNum === 4) $oResult->WeekNum = 3;
						if ($iNum === -1) $oResult->WeekNum = 4;
					}

					foreach ($aWeekDays as $sWeekDay)
					{
						if (strpos($sDay, $sWeekDay) !== false) 
						{
							$oResult->ByDays[] = $sWeekDay;
						}
					}
				}
			}
			
			$oResult->StartBase = CCalendarHelper::getTimestamp($oVEventBase->DTSTART, $oAccount->getDefaultStrTimeZone());
			$oResult->EndBase = CCalendarHelper::getTimestamp($oVEventBase->DTEND, $oAccount->getDefaultStrTimeZone());
		}

		return $oResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param \Sabre\VObject\Component\VCalendar $oVCal
	 *
	 * @return array
	 */
	public static function getRRules($oAccount, $oVCal)
	{
		$aResult = array();
		
		foreach($oVCal->getBaseComponents('VEVENT') as $oVEventBase)
		{
			if (isset($oVEventBase->RRULE))
			{
				$oRRule = CalendarParser::parseRRule($oAccount, $oVEventBase);
				if ($oRRule)
				{
					$aResult[(string)$oVEventBase->UID] = $oRRule;
				}
			}
		}
		
		return $aResult;
	}

	/**
	 * @param \Sabre\VObject\Component\VCalendar $oVCal
	 *
	 * @return array
	 */
	public static function getExcludedRecurrences($oVCal)
	{
        $aRecurrences = array();
        foreach($oVCal->children as $oComponent) {

            if (!$oComponent instanceof \Sabre\VObject\Component)
			{
                continue;
			}

            if (isset($oComponent->{'RECURRENCE-ID'}))
			{
				$iRecurrenceId = CCalendarHelper::getRecurrenceId($oComponent);
				$aRecurrences[(string)$oComponent->UID . '-' . $iRecurrenceId] = $oComponent;
			}
        }

        return $aRecurrences;
	}
	
}
