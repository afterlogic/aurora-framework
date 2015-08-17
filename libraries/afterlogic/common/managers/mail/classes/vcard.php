<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CApiMailIcs class is used for work with attachment that contains contact card.
 * 
 * @internal
 * 
 * @package Mail
 * @subpackage Classes
 */
class CApiMailVcard
{
	/**
	 * Contact identifier.
	 * 
	 * @var string
	 */
	public $Uid;

	/**
	 * Temp file name of the .vcf file.
	 * 
	 * @var string
	 */
	public $File;

	/**
	 * If **true** this contact already exists in address book.
	 * 
	 * @var bool
	 */
	public $Exists;

	/**
	 * Contact name.
	 * 
	 * @var string
	 */
	public $Name;

	/**
	 * Contact email.
	 * 
	 * @var string
	 */
	public $Email;

	private function __construct()
	{
		$this->Uid = '';
		$this->File = '';
		$this->Exists = false;
		$this->Name = '';
		$this->Email = '';
	}

	/**
	 * Creates new empty instance.
	 * 
	 * @return CApiMailVcard
	 */
	public static function createInstance()
	{
		return new self();
	}
}
