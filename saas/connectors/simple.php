<?php

	include_once __DIR__.'/../../core/api.php';
	if (!class_exists('CApi') || !CApi::IsValid())
	{
		exit(1);
	}

	CApi::Log('simple-saas-request:');
	
	CApi::LogObject(array(
		'$_GET: ' => isset($_GET) ? $_GET : null,
		'$_POST: ' => isset($_POST) ? $_POST : null
	));

	$aData = isset($_POST) && is_array($_POST) && 0 < count($_POST) ? $_POST : array();

	$sScript = empty($aData['script']) ? '' : $aData['script'];
	$sCommand = empty($aData['command']) ? '' : $aData['command'];

	$aResult = array(
		'script' => $sScript,
		'command' => $sCommand,
		'message' => '',
		'message-id' => '0',
		'message-settings-id' => '',
		'message-system' => '',
		'data' => array(),
		'result' => false
	);

	include_once __DIR__.'/simple/utils.php';

	if (in_array($sScript, array('mbox-configuration', 'tenant-configuration', 'tenant-resource', 'tenant-verify')))
	{
		$bDo = false;
		$sFile = __DIR__.'/simple/'.$sScript.'.php';
		if (file_exists($sFile))
		{
			include_once $sFile;
			if (function_exists('script_simple_task'))
			{
				$bDo = true;
				script_simple_task($aResult, $aData);
			}
		}

		if (!$bDo)
		{
			$aResult['message'] = 'Invalid script ('.$sScript.')';
			$aResult['message-settings-id'] = 'script';
		}
	}
	else
	{
		$aResult['message'] = 'Invalid script ('.$sScript.')';
		$aResult['message-settings-id'] = 'script';
	}

	@header('Content-Type: application/json; charset=utf-8');
	echo json_encode(output($aResult));
