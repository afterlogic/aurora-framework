<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('action'))
{
//	header('Location: /adm/');
	switch ($oHttp->GetPost('action'))
	{
		case 'create': 
			\CApi::ExecuteMethod('Auth::CreateAccount', array(
				'Token' => $sToken,
				'AuthToken' => $sAuthToken,
				'IdUser' => $oHttp->GetPost('user_id', ''),
				'Login' => $oHttp->GetPost('login', ''),
				'Password' => $oHttp->GetPost('password', '')
			));
			break;
		case 'update':
			\CApi::ExecuteMethod('Auth::UpdateAccount', array(
				'IdAccount' => $oHttp->GetPost('id', ''),
				'Login' => $oHttp->GetPost('login', ''),
				'Password' => $oHttp->GetPost('password', '')
			));
			break;
		case 'delete':
			\CApi::ExecuteMethod('Auth::DeleteAccount', array(
				'Token' => $sToken,
				'AuthToken' => $sAuthToken,
				'IdAccount' => $oHttp->GetPost('id', 0)
			));
			break;
	}
}


