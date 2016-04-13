<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
	$oAuthDecorator = \CApi::GetModuleDecorator('Auth');
	
//	header('Location: /adm/');
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			$mResult = $oAuthDecorator->CreateAccount(
				$oHttp->GetPost('user_id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('password', '')
			);
			
//			\CApi::ExecuteMethod('Auth::CreateAccount', array(
//				'IdUser' => $oHttp->GetPost('user_id', ''),
//				'Login' => $oHttp->GetPost('login', ''),
//				'Password' => $oHttp->GetPost('password', '')
//			));
			break;
		case 'update':
			$mResult = $oAuthDecorator->UpdateAccount(
				$oHttp->GetPost('id', ''),
				$oHttp->GetPost('login', ''),
				$oHttp->GetPost('password', '')
			);
			break;
		case 'delete':
			$mResult = $oAuthDecorator->DeleteAccount(
				$oHttp->GetPost('id', '')
			);
			break;
	}
}


