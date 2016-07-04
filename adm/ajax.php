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
	$oManagerApi = \CApi::GetSystemManager('eav', 'db');
	$aTypes = $oManagerApi->getTypes();

	$aItems = $oManagerApi->getEntities($oHttp->GetPost('ObjectName'));
	if (is_array($aItems))
	{
		foreach ($aItems as $oItem)
		{
			$itemData = $oItem->toArray();
			
			$aResultItems[] = $itemData;
		}

		//TODO: fix password encoder
		if ($oHttp->GetPost('ObjectName') == 'CAccount') {
			foreach ($aResultItems as &$oResultItem) {
				$oResultItem['Password'] = htmlspecialchars($oResultItem['Password']);
			}
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

echo json_encode($response, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
