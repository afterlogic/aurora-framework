<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Collaboration
 */
class CApiCollaborationManager extends AApiManager
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '')
	{
		parent::__construct('collaboration', $oManager);
	}
}
