<?php

class HelpDeskModule extends AApiModule
{
	public $oApiHelpDeskManager = null;
	
	public function init() 
	{
		$this->oApiHelpDeskManager = $this->GetManager('main');
	}
	
	public function ExecuteMethod($sMethod, $aArguments) 
	{
		$mResult = false;
		if (method_exists($this->oApiHelpDeskManager, $sMethod))
		{
			$mResult = call_user_func_array(array($this->oApiHelpDeskManager, $sMethod), $aArguments);
		}
		
		return $mResult;
	}
}

return new HelpDeskModule('1.0');
