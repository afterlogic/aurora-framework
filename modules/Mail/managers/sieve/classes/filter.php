<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $IdAccount
 * @property bool $Enable
 * @property int $Field
 * @property string $Filter
 * @property int $Condition
 * @property int $Action
 * @property string $FolderFullName
 *
 * @package Sieve
 * @subpackage Classes
 */
class CFilter extends api_AContainer
{
	/**
	 * @param CAccount $oAccount
	 */
	public function __construct(CAccount $oAccount)
	{
		parent::__construct(get_class($this));

		$this->SetDefaults(array(
			'IdAccount'	=> $oAccount->IdAccount,
			'Enable'	=> true,
			'Field'		=> EFilterFiels::From,
			'Filter'	=> '',
			'Condition'	=> EFilterCondition::ContainSubstring,
			'Action'	=> EFilterAction::DoNothing,
			'FolderFullName' => ''
		));
	}
	
	/**
	 * @return array
	 */
	public function getMap()
	{
		return self::getStaticMap();
	}

	/**
	 * @return array
	 */
	public static function getStaticMap()
	{
		return array(
			'IdAccount'	=> array('int'),
			'Enable'	=> array('bool'),
			'Field'		=> array('int'),
			'Filter'	=> array('string'),
			'Condition'	=> array('int'),
			'Action'	=> array('int'),
			'FolderFullName' => array('string')
		);
	}
	
	public function toResponseArray()
	{
		return array(
			'Enable' => $this->Enable,
			'Field' => $this->Field,
			'Filter' => $this->Filter,
			'Condition' => $this->Condition,
			'Action' => $this->Action,
			'FolderFullName' => $this->FolderFullName,
		);		
	}
}
