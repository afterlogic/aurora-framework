<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CCompletedStep extends AInstallerStep
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