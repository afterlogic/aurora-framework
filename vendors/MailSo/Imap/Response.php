<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace MailSo\Imap;

/**
 * @category MailSo
 * @package Imap
 */
class Response
{
	/**
	 * @var array
	 */
	public $ResponseList;

	/**
	 * @var array | null
	 */
	public $OptionalResponse;

	/**
	 * @var string
	 */
	public $StatusOrIndex;

	/**
	 * @var string
	 */
	public $HumanReadable;

	/**
	 * @var bool
	 */
	public $IsStatusResponse;

	/**
	 * @var string
	 */
	public $ResponseType;

	/**
	 * @var string
	 */
	public $Tag;

	/**
	 * @access private
	 */
	private function __construct()
	{
		$this->ResponseList = array();
		$this->OptionalResponse = null;
		$this->StatusOrIndex = '';
		$this->HumanReadable = '';
		$this->IsStatusResponse = false;
		$this->ResponseType = \MailSo\Imap\Enumerations\ResponseType::UNKNOWN;
		$this->Tag = '';
	}

	/**
	 * @return \MailSo\Imap\Response
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @param string $aList
	 * 
	 * @return string
	 */
	private function recToLine($aList)
	{
		$aResult = array();
		if (\is_array($aList))
		{
			foreach ($aList as $mItem)
			{
				$aResult[] = \is_array($mItem) ? '('.$this->recToLine($mItem).')' : (string) $mItem;
			}
		}

		return \implode(' ', $aResult);
	}
	

	/**
	 * @return string
	 */
	public function ToLine()
	{
		return $this->recToLine($this->ResponseList);
	}
}