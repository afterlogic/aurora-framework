<?php

class MailModule extends AApiModule
{
	public $oApiMailManager = null;
	
	public function init() 
	{
		$this->oApiMailManager = $this->GetManager('main');
	}
	
	public function ExecuteMethod($sMethod, $aArguments) 
	{
		$mResult = false;
		if (method_exists($this->oApiMailManager, $sMethod))
		{
			$mResult = call_user_func_array(array($this->oApiMailManager, $sMethod), $aArguments);
		}
		
		return $mResult;
	}
}

return new MailModule('1.0');
