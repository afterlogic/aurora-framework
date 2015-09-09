<?php

class FilesModule extends AApiModule
{
	public $oApiFilesManager = null;
	
	public function init() 
	{
		$this->oApiFilesManager = $this->GetManager('main');
	}
	
	public function ExecuteMethod($sMethod, $aArguments) 
	{
		$mResult = false;
		if (method_exists($this->oApiFilesManager, $sMethod))
		{
			$mResult = call_user_func_array(array($this->oApiFilesManager, $sMethod), $aArguments);
		}
		
		return $mResult;
	}
}

return new FilesModule('1.0');
