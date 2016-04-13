<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
//	header('Location: /adm/');
	
	$oCoreDecorator = \CApi::GetModuleDecorator('Core');

	switch ($oHttp->GetPost('action'))
	{
		case 'create':
			$mResult = $oCoreDecorator->CreateUser(
				$oHttp->GetPost('tenant_id', 0),
				$oHttp->GetPost('name', 0)
			);
			
//			\CApi::ExecuteMethod('Core::CreateUser', array(
//				
//			));
			break;
		case 'update': 
			$mResult = $oCoreDecorator->UpdateUser(
				$oHttp->GetPost('id', 0),
				$oHttp->GetPost('name', 0)
			);
//			\CApi::ExecuteMethod('Core::UpdateUser', array(
				//'Token' => $sToken,
				//'AuthToken' => $sAuthToken,
//				'IdUser' => $oHttp->GetPost('id', 0),
//				'Name' => $oHttp->GetPost('name', 0)
//			));
			break;
		case 'delete': 
			$mResult = $oCoreDecorator->DeleteUser(
				$oHttp->GetPost('id', 0)
			);
//			\CApi::ExecuteMethod('Core::DeleteUser', array(
				//'Token' => $sToken,
				//'AuthToken' => $sAuthToken,
//				'IdUser' => $oHttp->GetPost('id', 0)
//			));
			break;
	}
}


