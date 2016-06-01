<?php

class OpenPgpModule extends AApiModule
{
	public function init()
	{
	}
	
	public function GetAppData($oUser = null)
	{
		return array(
			'EnableModule' => true // AppData.User.EnableOpenPgp
		);
	}
}
