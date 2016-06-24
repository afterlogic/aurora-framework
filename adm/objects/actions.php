<?php
$oHttp = \MailSo\Base\Http::NewInstance();

$oManagerApi = \CApi::GetSystemManager('eav', 'db');

switch ($oHttp->GetPost('action'))
{
	case 'edit':
		if ($oHttp->HasPost('ObjectName'))
		{
			$sObjectType = $oHttp->GetPost('ObjectName');
			$oObject = call_user_func($sObjectType . '::createInstance');

			$aMap = $oObject->GetMap();
			$aViewProperties = array_keys($aMap);

			if ($oHttp->HasPost('iObjectId'))
			{
				$oObject->iId = (int)$oHttp->GetPost('iObjectId');

				foreach ($aViewProperties as $property)
				{
					if ($oHttp->HasPost($property))
					{
						$oObject->{$property} = $oHttp->GetPost($property);
					}
				}
			}
			
			$oManagerApi->saveEntity($oObject);
		}
		break;
	
	case 'delete':
		$oManagerApi->deleteEntity($oHttp->GetPost('iObjectId'));
		break;
	case 'delete_multiple':
		if ($oHttp->HasPost('ids'))
		{
			$aIds = explode(',', $oHttp->GetPost('ids'));
		}
		foreach ($aIds as $id) {
			$oManagerApi->deleteEntity((int)$id);
		}
		break;
}

header('Location: ' . $_SERVER['REQUEST_URI']);
