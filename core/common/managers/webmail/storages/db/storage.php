<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package WebMail
 * @subpackage Storages
 */
class CApiWebmailDbStorage extends CApiWebmailStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiMysqlWebmailCommandCreator
	 */
	protected $oCommandCreator;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiWebmailCommandCreatorMySQL',
				EDbType::PostgreSQL => 'CApiWebmailCommandCreatorPostgreSQL'
			)
		);
	}
}