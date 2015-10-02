<?php

class HelpDeskModule extends AApiModule
{
	public $oApiHelpDeskManager = null;
	
	public function init() 
	{
		$this->oApiHelpDeskManager = $this->GetManager('main');
	}
}

return new HelpDeskModule('1.0');
