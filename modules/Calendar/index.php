<?php

class CalendarModule extends AApiModule
{
	public $oApiCalendarManager = null;
	
	public function init() 
	{
		$this->oApiCalendarManager = $this->GetManager('main');
	}

	public function GetCalendars($oAccount)
	{
		print_r($oAccount); exit;
	}
	
	public function ExecuteMethod($sMethod, $aArguments) 
	{
		$mResult = parent::ExecuteMethod($sMethod, $aArguments);
		if (!$mResult = method_exists($this->oApiCalendarManager, $sMethod))
		{
			$mResult = call_user_func_array(array($this->oApiCalendarManager, $sMethod), $aArguments);
		}
		
		return $mResult;
	}
}

return new CalendarModule('1.0');
