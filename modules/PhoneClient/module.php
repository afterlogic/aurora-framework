<?php

class PhoneClientModule extends AApiModule
{
	public function GetAppData($oUser = null)
	{
		return array(
			'SipImpi' => '102', // AppData.User.SipImpi
			'SipOutboundProxyUrl' => '', // AppData.User.SipOutboundProxyUrl
			'SipPassword' => 'user02', // AppData.User.SipPassword
			'SipRealm' => '192.168.0.59', // AppData.User.SipRealm
			'SipWebsocketProxyUrl' => 'ws://192.168.0.59:8088/ws', // AppData.User.SipWebsocketProxyUrl
			'VoiceProvider' => '' // AppData.User.VoiceProvider
		);
	}
}
