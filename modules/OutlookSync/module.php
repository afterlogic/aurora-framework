<?php

class OutlookSyncModule extends AApiModule
{
	public function init()
	{
	}
	
	public function GetAppData($oUser = null)
	{
		return array(
			'Plugin32DownloadLink' => '', // AppData.Links.OutlookSyncPlugin32
			'Plugin64DownloadLink' => '', // AppData.Links.OutlookSyncPlugin64
			'PluginReadMoreLink' => '' // AppData.Links.OutlookSyncPluginReadMore
		);
	}
}
