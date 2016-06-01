<?php

class ChangePasswordModule extends AApiModule
{
	public function init()
	{
	}
	
	public function GetAppData($oUser = null)
	{
		return array(
			'PasswordMinLength' => 0, // AppData.App.PasswordMinLength
			'PasswordMustBeComplex' => false // AppData.App.PasswordMustBeComplex
		);
	}
}
