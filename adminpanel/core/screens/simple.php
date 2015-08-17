<?php

/* -AFTERLOGIC LICENSE HEADER- */

class ap_Simple_Screen extends ap_Screen
{
	/**
	 * @param CAdminPanel $oAdminPanel
	 * @return ap_Simple_Screen
	 */
	public function __construct(CAdminPanel &$oAdminPanel, $sGlobalTemplateName, $aData = array())
	{
		parent::__construct($oAdminPanel, CAdminPanel::RootPath().'core/templates/'.$sGlobalTemplateName);

		foreach ($aData as $sKey => $sText)
		{
			$this->Data->SetValue($sKey, $sText);
		}
	}
}
