<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersDbStorage extends CApiMailFetchersStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiFetchersCommandCreatorMySQL
	 */
	protected $oCommandCreator;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiMailFetchersCommandCreatorMySQL',
				EDbType::PostgreSQL => 'CApiMailFetchersCommandCreatorPostgreSQL'
			)
		);
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return array|bool
	 */
	public function getFetchers($oAccount)
	{
		$mResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getFetchers($oAccount)))
		{
			$oRow = null;
			$mResult = array();

			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$oFetcher = new CFetcher($oAccount);
				$oFetcher->InitByDbRow($oRow);
				
				$mResult[] = $oFetcher;
			}
		}

		$this->throwDbExceptionIfExist();
		return $mResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param CFetcher $oFetcher
	 * @return bool
	 */
	public function createFetcher($oAccount, &$oFetcher)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->createFetcher($oAccount, $oFetcher)))
		{
			$oFetcher->IdFetcher = $this->oConnection->GetLastInsertId('awm_fetchers', 'id_fetcher');
			$bResult = true;
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param CFetcher $oFetcher
	 *
	 * @return bool
	 */
	public function updateFetcher($oAccount, $oFetcher)
	{
		$bResult = (bool) $this->oConnection->Execute($this->oCommandCreator->updateFetcher($oAccount, $oFetcher));

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param int $iFetcherID
	 *
	 * @return bool
	 */
	public function deleteFetcher($oAccount, $iFetcherID)
	{
		$bResult = (bool) $this->oConnection->Execute($this->oCommandCreator->deleteFetcher($oAccount, $iFetcherID));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}
}
