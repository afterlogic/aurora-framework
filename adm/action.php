<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('manager'))
{
	$sManagerName = $oHttp->GetPost('manager');
	
	if (in_array($sManagerName, array('accounts', 'users')))
	{
		header('Location: /adm/');
		include $sManagerName."\actions.php";
		exit;
	}
	else if ($sManagerName === 'auth')
	{
		$result = \CApi::ExecuteMethod('Auth::Login2', array(
//			'AuthToken' => $oHttp->GetPost('login', ''),
			'login' => $oHttp->GetPost('login', ''),
			'password' => $oHttp->GetPost('password', '')
		));
		
		if ($result['AuthToken'])
		{
			$sAuthToken = $result['AuthToken'];
			setcookie('AUTH', $result['AuthToken']);
		}
	}
}


