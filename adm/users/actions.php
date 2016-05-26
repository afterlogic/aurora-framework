<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oCoreDecorator = \CApi::GetModuleDecorator('Core');

	switch ($oHttp->GetPost('action'))
	{
		case 'create':
			$mResult = $oCoreDecorator->CreateUser(
				$oHttp->GetPost('tenant_id', 0),
				$oHttp->GetPost('name', 0)
			);
			
			break;
		case 'update': 
			$mResult = $oCoreDecorator->UpdateUser(
				$oHttp->GetPost('id', 0),
				$oHttp->GetPost('name', 0),
				$oHttp->GetPost('tenant_id', 0)
			);
			break;
		case 'delete': 
			$mResult = $oCoreDecorator->DeleteUser(
				$oHttp->GetPost('id', 0)
			);
			break;
	}
	
	header('Location: ' . $_SERVER['REQUEST_URI']);
}


