<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oDecorator = \CApi::GetModuleDecorator('Core');
	
//	header('Location: /adm/');
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			$mResult = $oDecorator->CreateTenant(
				$oHttp->GetPost('channel_id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('password', '')
			);
			
			break;
		case 'update':
			$mResult = $oDecorator->UpdateTenant(
				$oHttp->GetPost('id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('description', ''),
				$oHttp->GetPost('channel_id', '')
			);
			break;
		case 'delete':
			$mResult = $oDecorator->DeleteTenant(
				$oHttp->GetPost('id', '')
			);
			break;
	}
}


