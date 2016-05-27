<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require_once "init.php";

$oHttp = \MailSo\Base\Http::NewInstance();

$response = array(
	'error' => true,
	'message' => 'Unknown error occours',
	'result' => array()
);

if ($oHttp->HasPost('ObjectName'))
{
	$aResultItems = array();
	$oManagerApi = \CApi::GetCoreManager('eav', 'db');
	$aTypes = $oManagerApi->getTypes();
	
	$aItems = $oManagerApi->getObjects($oHttp->GetPost('ObjectName'));
	
	if (is_array($aItems))
	{
		$aProperties = array_merge(array('IdObject'), array_keys($aItems[0]->getMap()));
		
		foreach ($aItems as $oItem)
		{
			$aResultItems[] = $oItem->toArray();
		}
		
		$response['error'] = false;
		$response['message'] = '';
		$response['result'] = $aResultItems;
	}
}
else
{
	$response['message'] = 'Unknown object type';
}


echo json_encode($response);