<?php

class ChangePasswordClientModule extends AApiModule
{
	public function GetAppData($oUser = null)
	{
		return array(
			'PasswordMinLength' => 0, // AppData.App.PasswordMinLength
			'PasswordMustBeComplex' => false // AppData.App.PasswordMustBeComplex
		);
	}
}
