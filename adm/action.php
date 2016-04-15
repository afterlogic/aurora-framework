<?php
$oHttp = \MailSo\Base\Http::NewInstance();

if ($oHttp->HasPost('manager'))
{
	$sManagerName = $oHttp->GetPost('manager');
	
	if (in_array($sManagerName, array('channels', 'tenants', 'accounts', 'users', 'contacts')))
	{
//		header('Location: /adm/');
		include $sManagerName."\actions.php";
		exit;
	}
	else if ($sManagerName === 'auth')
	{
		if ($oHttp->HasPost('action'))
		{
			switch ($oHttp->GetPost('action'))
			{
				case 'login': 
					$result = \CApi::ExecuteMethod('Auth::Login', array(
						'login' => $oHttp->GetPost('login', ''),
						'password' => $oHttp->GetPost('password', '')
					));
					
					if ($result['AuthToken'])
					{
						$sAuthToken = $result['AuthToken'];
						setcookie('AUTH', $result['AuthToken']);
					}
					break;
				case 'logout': 
					$result = \CApi::ExecuteMethod('Auth::Logout', array(
						'AuthToken' => $sAuthToken
					));
					
					if ($result)
					{
						$sAuthToken = '';
						setcookie ("AUTH", "", time() - 3600);
					}
					break;
			}
		}
		
	}
}


