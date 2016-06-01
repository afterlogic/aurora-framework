<?php

class SessionTimeoutModule extends AApiModule
{
	public function init()
	{
	}
	
	public function GetAppData($oUser = null)
	{
		return array(
			'TimeoutSeconds' => 0 // AppData.App.IdleSessionTimeout
		);
	}
}
