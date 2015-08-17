<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CLicenseStep extends AInstallerStep
{
	public function DoPost()
	{
		return true;
	}

	public function TemplateValues()
	{
		return array();
	}
}