<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @internal
 * 
 * @package Calendar
 * @subpackage Storages
 */
class CApiCalendarStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('calendar', $sStorageName, $oManager);
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function init($oAccount)
	{
	}

	/**
	 * @param CalendarInfo  $oCalendar
	 */
	public function initCalendar(&$oCalendar)
	{
	}

	public function getCalendarAccess($oAccount, $sCalendarId)
	{
		return ECalendarPermission::Write;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 *
	 * @return null
	 */
	public function getCalendar($oAccount, $sCalendarId)
	{
		return null;
	}

	/*
	 * @param string $sCalendar
	 *
	 * @return false
	 */
	public function getPublicCalendar($sCalendar)
	{

		return false;
	}

	/*
	 * @param string $sHash
	 *
	 * @return false
	 */
	public function getPublicCalendarByHash($sHash)
	{
		return false;
	}

	/*
	 * @param string $sCalendarId
	 *
	 * @return false
	 */
	public function getPublicCalendarHash($sCalendarId) //TODO
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return array
	 */
	public function GetCalendarsSharedToAll($oAccount)
	{
		return array();
	}

	/**
	}
	 * @param CAccount $oAccount
	 *
	 * @return array
	 */
	public function getCalendars($oAccount)
	{
		return array();
	}

	/**
	 * @param CAccount $oAccount
	 *
     * @return array
	 */
	public function GetCalendarNames($oAccount)
	{
		return array();
	}	

	/**
	 * @param CAccount $oAccount
	 * @param string $sName
	 * @param string $sDescription
	 * @param int $iOrder
	 * @param string $sColor
	 *
	 * @return false
	 */
	public function createCalendar($oAccount, $sName, $sDescription, $iOrder, $sColor)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sName
	 * @param string $sDescription
	 * @param int $iOrder
	 * @param string $sColor
	 *
	 * @return false
	 */
	public function updateCalendar($oAccount, $sCalendarId, $sName, $sDescription, $iOrder,
			$sColor)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sColor
	 *
	 * @return false
	 */
	public function updateCalendarColor($oAccount, $sCalendarId, $sColor)
	{
		return false;
	}

	/**
	 * @param string $sCalendarId
	 * @param int $iVisible
	 */
	public function updateCalendarVisible($sCalendarId, $iVisible)
	{
		@setcookie($sCalendarId, $iVisible, time() + 86400);
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 *
	 * @return false
	 */
	public function deleteCalendar($oAccount, $sCalendarId)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sUserId
	 * @param int $iPerms
	 *
	 * @return false
	 */
	public function updateCalendarShare($oAccount, $sCalendarId, $sUserId, $iPerms = ECalendarPermission::RemovePermission)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param bool $bIsPublic
	 *
	 * @return false
	 */
	public function publicCalendar($oAccount, $sCalendarId, $bIsPublic)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $oCalendar
	 *
	 * @return array
	 */
	public function getCalendarUsers($oAccount, $oCalendar)
	{
		return array();
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $dStart
	 * @param string $dFinish
	 *
	 * @return array
	 */
	public function getEvents($oAccount, $sCalendarId, $dStart, $dFinish)
	{
		return array();
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sEventId
	 *
	 * @return array
	 */
	public function getEvent($oAccount, $sCalendarId, $sEventId)
	{
		return array();
	}

	/**
	}
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param \Sabre\VObject\Component\VCalendar $vCal
	 *
	 * @return null
	 */
	public function createEvent($oAccount, $sCalendarId, $sEventId, $vCal)
	{
		return null;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sEventId
	 * @param string $sData
	 *
	 * @return true
	 */
	public function updateEventRaw($oAccount, $sCalendarId, $sEventId, $sData)
	{
		return true;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sEventId
	 * @param array $aArgs
	 *
	 * @return false
	 */
	public function updateEvent($oAccount, $sCalendarId, $sEventId, $aArgs)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sNewCalendarId
	 * @param string $sEventId
	 * @param string $sData
	 *
	 * @return false
	 */
	public function moveEvent($oAccount, $sCalendarId, $sNewCalendarId, $sEventId, $sData)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sCalendarId
	 * @param string $sEventId
	 *
	 * @return false
	 */
	public function deleteEvent($oAccount, $sCalendarId, $sEventId)
	{
		return false;
	}

	public function getReminders($start, $end)
	{
		return false;
	}

	public function AddReminder($sEmail, $calendarUri, $eventid, $time = null)
	{
		return false;
	}

	public function updateReminder($sEmail, $calendarUri, $eventId, $sData)
	{
		return false;
	}

	public function deleteReminder($eventId)
	{
		return false;
	}

	public function deleteReminderByCalendar($calendarUri)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function clearAllCalendars($oAccount)
	{
		return true;
	}
}

