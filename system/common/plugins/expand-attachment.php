<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 */

/**
 * @package Api
 * @deprecated since 7.0.0
 */
abstract class AApiExpandAttachmentPlugin extends AApiPlugin
{
	/**
	 * @param string $sVersion
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct($sVersion, CApiPluginManager $oPluginManager)
	{
		parent::__construct($sVersion, $oPluginManager);

		$this->AddHook('webmail.supports-expanding-attachments', 'WebmailSupportsExpandingAttachments');
		$this->AddHook('webmail.expand-attachment', 'WebmailExpandAttachment');
	}

	abstract public function IsMimeTypeSupported($sMimeType, $sFileName = '');

	abstract public function ExpandAttachment($oAccount, $sMimeType, $sFullFilePath, $oApiFileCache);

	/**
	 * @param type $oAccount
	 * @param string $sMimeType
	 * @param type $mResult
	 * @param type $oApiFileCache
	 */
	public function WebmailSupportsExpandingAttachments(&$bResult, $sMimeType, $sFileName)
	{
		if (!$bResult)
		{
			$bResult = $this->IsMimeTypeSupported($sMimeType, $sFileName);
		}
	}

	/**
	 * @param type $oAccount
	 * @param string $sMimeType
	 * @param type $mResult
	 * @param type $oApiFileCache
	 */
	public function WebmailExpandAttachment($oAccount, $sMimeType, $sFileName, $sFullFilePath, &$mResult, $oApiFileCache)
	{
		if ($oAccount && $this->IsMimeTypeSupported($sMimeType, $sFileName) &&
			\file_exists($sFullFilePath) && \is_array($mResult) && $oApiFileCache)
		{
			$aNew = $this->ExpandAttachment($oAccount, $sMimeType, $sFullFilePath, $oApiFileCache);
			if (is_array($aNew))
			{
				foreach ($aNew as $aItem)
				{
					$mResult[] = $aItem;
				}
			}
		}
	}
}
