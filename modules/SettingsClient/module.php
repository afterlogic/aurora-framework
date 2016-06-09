<?php

class SettingsClientModule extends AApiModule
{
	public function GetAppData($oUser = null)
	{
		return array(
			'TabsOrder' => array('common', 'mail', 'mail-accounts', 'contacts', 'calendar', 'files', 'mobilesync', 'outlooksync', 'helpdesk', 'openpgp')
		);
	}
}
