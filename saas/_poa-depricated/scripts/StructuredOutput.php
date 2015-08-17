<?php

function makeMsg($settingId, $message, $msgId = 0)
{
	return array('id' => $msgId, 'setting-id' => $settingId, 'message' => $message);
}

function structuredBegin($xw)
{
	$xw->openMemory();
	$xw->setIndent(true);
	$xw->setIndentString(' ');
	$xw->startDocument('1.0');
	$xw->startElement('output');
	$xw->writeAttribute('xmlns', 'http://apstandard.com/ns/1/configure-output');
	return $xw;
}

function structuredEnd($xw)
{
	$xw->endElement();
	$xw->endDocument();
	return $xw;
}

function structuredWriteError($xw, $msg)
{
	$xw->startElement('error');
	$xw->writeAttribute('id', $msg['id']);
	$xw->writeAttribute('setting-id', $msg['setting-id']);
	$xw->writeElement('message', $msg['message']);
	$xw->writeElement('system', isset($msg['system']) ? $msg['system'] : $msg['message'] );
	$xw->endElement();
	return $xw;
}

function structuredWriteSetting($xw, $setting, $value)
{
	$xw->startElement('setting');
	$xw->writeAttribute('id', $setting);
	if (is_array($value))
	{
		if ($value['choice'])
		{
			foreach ($value['choice'] as $choice)
			{
				$xw->startElement('choice');
				$xw->writeAttribute('id', $choice['id']);
				$xw->writeElement('name', $choice['name']);
				$xw->endElement();
			}
		}
		else
		{
			foreach ($value as $elem)
			{
				$xw->writeElement('value', $elem);
			}
		}
	}
	else
	{
		$xw->writeElement('value', $value);
	}
	$xw->endElement();

	return $xw;
}

function structuredWriteErrors($xw, $errorMsgs)
{
	$xw->startElement('errors');

	foreach ($errorMsgs as $msg)
		structuredWriteError($xw, $msg);

	$xw->endElement();

	return $xw;
}

function structuredWriteSettings($xw, $settings)
{
	$xw->startElement('settings');

	foreach ($settings as $setting => $value)
		structuredWriteSetting($xw, $setting, $value);

	$xw->endElement();

	return $xw;
}

function reportErrors($messages)
{
	$xw = structuredBegin(new XMLWriter);

	structuredWriteErrors($xw, $messages);

	echo structuredEnd($xw)->outputMemory();
}

function reportSettings($settings)
{
	$xw = structuredBegin(new XMLWriter);

	structuredWriteSettings($xw, $settings);

	echo structuredEnd($xw)->outputMemory();
}

function reportError($message)
{
	reportErrors(array($message));
}

function reportSetting($setting)
{
	reportSettings(array($setting));
}

function reportMixed($settings, $errors)
{
	$xw = structuredBegin(new XMLWriter);

	structuredWriteErrors($xw, $errors);
	structuredWriteSettings($xw, $settings);

	echo structuredEnd($xw)->outputMemory();
}
