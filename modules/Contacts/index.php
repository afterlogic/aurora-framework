<?php

class ContactsModule extends AApiModule
{
	public $oApiContactsManager = null;
	
	public function init() 
	{
		$this->oApiContactsManager = $this->GetManager('main');
	}
	
	public function ExecuteMethod($sMethod, $aArguments) 
	{
		$mResult = parent::ExecuteMethod($sMethod, $aArguments);
		if (!$mResult && method_exists($this->oApiContactsManager, $sMethod))
		{
			$mResult = call_user_func_array(array($this->oApiContactsManager, $sMethod), $aArguments);
		}
		return $mResult;
	}
}

return new ContactsModule('1.0');
