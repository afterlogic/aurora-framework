<?php

class OpenPgpModule extends AApiModule
{
	public function GetAppData($oUser = null)
	{
		return array(
			'EnableModule' => true // AppData.User.EnableOpenPgp
		);
	}
}
