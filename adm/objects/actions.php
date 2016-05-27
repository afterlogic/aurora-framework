<?php
$oHttp = \MailSo\Base\Http::NewInstance();

$oManagerApi = \CApi::GetCoreManager('eav', 'db');

switch ($oHttp->GetPost('action'))
{
	case 'update':
		if ($oHttp->HasPost('ObjectName'))
		{
			$sObjectType = $oHttp->GetPost('ObjectName');
			$oObject = call_user_func($sObjectType . '::createInstance');

			$aMap = $oObject->GetMap();
			$aViewProperties = array_keys($aMap);

			if ($oHttp->HasPost('iObjectId'))
			{
				$oObject->iObjectId = (int)$oHttp->GetPost('iObjectId');

				foreach ($aViewProperties as $property)
				{
					if ($oHttp->HasPost($property))
					{
						$oObject->{$property} = $oHttp->GetPost($property);
					}
				}
			}
			
			$oManagerApi->saveObject($oObject);
		}
		break;
	
	case 'delete':
		$oManagerApi->deleteObject($oHttp->GetPost('iObjectId'));
		break;
	case 'delete_multiple':
		if ($oHttp->HasPost('ids'))
		{
			$aIds = explode(',', $oHttp->GetPost('ids'));
		}
		foreach ($aIds as $id) {
			$oManagerApi->deleteObject((int)$id);
		}
		break;
}

header('Location: ' . $_SERVER['REQUEST_URI']);
