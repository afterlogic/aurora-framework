<?php

class SettingsModule extends AApiModule
{
	public function init()
	{
	}
	
	public function GetAppData($oUser = null)
	{
		return array(
			'TabsOrder' => array('common', 'mail', 'accounts', 'contacts', 'calendar', 'cloud-storage', 'mobile_sync', 'outlook_sync', 'helpdesk', 'pgp')
		);
	}
}
