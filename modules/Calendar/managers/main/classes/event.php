<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property mixed  $Id
 * @property mixed  $IdCalendar
 * @property string $Start
 * @property string $End
 * @property bool   $AllDay
 * @property string $Name
 * @property string $Description
 * @property CRRule $RRule
 * @property array  $Alarms
 * @property array  $Attendees;
 * @property bool $Deleted;
 * @property bool $Modified;
 * @property int $sequence
 *
 * @package Calendar
 * @subpackage Classes
 */
class CEvent
{
	public $Id;
	public $IdCalendar;
	public $Start;
	public $End;
	public $AllDay;
	public $Name;
	public $Description;
	public $Location;
	public $RRule;
	public $Alarms;
	public $Attendees;
    public $Deleted;
	public $Modified;
    public $Sequence;

	public function __construct()
	{
		$this->Id			  = null;
		$this->IdCalendar	  = null;
		$this->Start		  = null;
		$this->End			  = null;
		$this->AllDay		  = false;
		$this->Name			  = null;
		$this->Description	  = null;
		$this->Location		  = null;
		$this->RRule		  = null;
		$this->Alarms		  = array();
		$this->Attendees	  = array();
		$this->Deleted		  = null;
		$this->Modified		  = false;
        $this->Sequence       = 0;
	}
}

/**
 * @property mixed $IdRecurrence;
 * @property mixed $IdRepeat
 * @property string $StartTime;
 * @property bool $Deleted;
 *
 * @package Calendar
 * @subpackage Classes
 */
class CExclusion
{
	public $IdRecurrence;
	public $IdRepeat;
	public $StartTime;
    public $Deleted;

	public function __construct()
	{
		$this->IdRecurrence = null;
		$this->IdRepeat   = null;
		$this->StartTime  = null;
		$this->Deleted    = null;
	}
}

/**
 * @package Calendar
 * @subpackage Classes
 */
class CRRule
{
	public $StartBase;
	public $EndBase;
	public $Period;
	public $Count;
	public $Until;
	public $Interval;
	public $End;
	public $WeekNum;
	public $ByDays;
	protected $Account;
	
	public function __construct($oAccount)
	{
		$this->Account = $oAccount;
		$this->StartBase  = null;
		$this->EndBase    = null;
		$this->Period	  = null;
		$this->Count	  = null;
		$this->Until	  = null;
		$this->Interval	  = null;
		$this->End		  = null;
		$this->WeekNum	  = null;
		$this->ByDays	  = array();
	}
	
	public function Populate($aRRule)
	{
		$this->Period = isset($aRRule['period']) ? (int)$aRRule['period'] : null;
		$this->Count = isset($aRRule['count']) ? $aRRule['count'] : null;
		$this->Until = isset($aRRule['until']) ? $aRRule['until'] : null;
		$this->Interval = isset($aRRule['interval']) ? $aRRule['interval'] : null;
		$this->End = isset($aRRule['end']) ? $aRRule['end'] : null;
		$this->WeekNum = isset($aRRule['weekNum']) ? $aRRule['weekNum'] : null;
		$this->ByDays = isset($aRRule['byDays']) ? $aRRule['byDays'] : array();
	}
	
	public function toArray()
	{
		return array(
			'startBase' => $this->StartBase,
			'endBase' => $this->EndBase,
			'period' => $this->Period,
			'interval' => $this->Interval,
			'end' => !isset($this->End) ? 0 : $this->End,
			'until' => $this->Until,
			'weekNum' => $this->WeekNum,
			'count' => $this->Count,
			'byDays' => $this->ByDays
		);
	}
	
    public function __toString()
	{
		$aPeriods = array(
			EPeriodStr::Secondly,
			EPeriodStr::Minutely,
			EPeriodStr::Hourly,
			EPeriodStr::Daily,
			EPeriodStr::Weekly,
			EPeriodStr::Monthly,
			EPeriodStr::Yearly
		);

		$sRule = '';

		if (null !== $this->Period)
		{
			$iWeekNumber = null;
			if (($this->Period == EPeriod::Monthly || $this->Period == EPeriod::Yearly) && (null !== $this->WeekNum))
			{
				$iWeekNumber = ((int)$this->WeekNum < 0 || (int)$this->WeekNum > 4) ? 0 : (int)$this->WeekNum;
			}

			$sUntil = '';
			if (null !== $this->Until)
			{
				$oDTUntil = CCalendarHelper::prepareDateTime($this->Until, $this->GetTimeZone());
				$sUntil = $oDTUntil->format('Ymd');
			}

			$iInterval = (null !== $this->Interval) ? (int)$this->Interval : 0;
			$iEnd = (null === $this->End || (int)$this->End < 0 || (int)$this->End > 3) ? 0 : (int)$this->End;

			$sFreq = strtoupper($aPeriods[$this->Period + 2]);
			$sRule = 'FREQ=' . $sFreq . ';INTERVAL=' . $iInterval;
			if ($iEnd === ERepeatEnd::Count)
			{
				$sRule .= ';COUNT=' . (null !== $this->Count) ? (int)$this->Count : 0;
			}
			else if ($iEnd === ERepeatEnd::Date)
			{
				$sRule .= ';UNTIL=' . $sUntil;
			}

			$sByDay = null;
			if (in_array($sFreq, array('WEEKLY', 'MONTHLY', 'YEARLY')))
			{
				$sByDay = implode(',', $this->ByDays);
			}
			if (!empty($sByDay))
			{
				if (in_array($sFreq, array('MONTHLY', 'YEARLY')) && isset($iWeekNumber))
				{
					if ($iWeekNumber >= 0 && $iWeekNumber < 4)
					{
						$sByDay = (int)$iWeekNumber + 1 . $sByDay;
					}
					else if ($iWeekNumber === 4)
					{
						$sByDay = '-1' . $sByDay;
					}
				}
				$sRule .= ';BYDAY=' . $sByDay;
			}
		}
        return $sRule;
	}
	
	public function GetTimeZone()
	{
		return $this->Account->getDefaultStrTimeZone();
	}
}
