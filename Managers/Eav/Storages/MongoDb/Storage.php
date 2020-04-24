<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers\Eav\Storages\MongoDb;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @internal
 *
 * @package EAV
 * @subpackage Storages
 */
class Storage extends \Aurora\System\Managers\Eav\Storages\Storage
{
	public function __construct(\Aurora\System\Managers\Eav &$oManager)
	{
		parent::__construct($oManager);
		$oSettings = \Aurora\System\Api::GetSettings();
		if($oSettings)
		{
			$this->sDBName = $oSettings->GetConf('DBName');
		}
	}

	/**
	 *
	 */
	public $sDBName = null;

	public function getNextSequence($sName)
	{
		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->counters;
		$oObject = $oCollection->findOne(
			['_id' => $sName]
		);
		if (!$oObject)
		{
			$oCollection->insertOne(
				[
					'_id' => $sName,
				   'seq'=> 0
				]
			);
		}

		$oRes = $oCollection->findOneAndUpdate(
			[
				'_id' => $sName
			],
			[
				'$inc' => [ 'seq' => 1 ]
			],
			[
				'projection' => [ 'seq' => 1 ],
				'returnDocument' => \MongoDB\Operation\FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
			]
		);

		return $oRes->seq;
	 }

	/**
	 *
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function isEntityExists($mIdOrUUID, $sType)
	{
		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->{\str_replace('\\', '.', $sType)};
		$oObject = null;
		if (\is_numeric($mIdOrUUID) && !empty($mIdOrUUID))
		{
			$oObject = $oCollection->findOne(
				['EntityId' => $mIdOrUUID]
			);
		}
		elseif (is_string($mIdOrUUID))
		{
			$sObjectId = null;
			try
			{
				$sObjectId = new \MongoDB\BSON\ObjectId($mIdOrUUID);
			}
			catch (\Exception $oEx) { }
			if (isset($sObjectId))
			{
				$oObject = $oCollection->findOne(
					['_id' => $sObjectId]
				);
			}
		}

		return isset($oObject);
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return array
	 */
	protected function prepareEntity($oEntity)
	{
		$aAttributes = $oEntity->getAttributes();
		$aEntity = [];
		foreach ($aAttributes as $oAttribute)
		{
			if ($oAttribute instanceof \Aurora\System\EAV\Attribute)
			{
				if ((!$oEntity->isDefaultValue($oAttribute->Name, $oAttribute->Value) || ($oEntity->isOverridedAttribute($oAttribute->Name))) && (!$oAttribute->Inherited))
				{
					if ($oAttribute->IsEncrypt && !$oAttribute->Encrypted)
					{
						$oAttribute->Encrypt();
					}
					$mValue = $oAttribute->Value;
					if ($oAttribute->Type === 'datetime')
					{
						$mValue = (new \MongoDB\BSON\UTCDateTime(new \DateTime($mValue)));
					}
					$aEntity[$oAttribute->Name] = $mValue;
				}
			}
		}

		return $aEntity;
	}

	/**
	 *
	 * @return array
	 */
	protected function parseEntity($oObject, $sType)
	{
		$oEntity = null;
		if (isset($oObject))
		{
			$oEntity = \Aurora\System\EAV\Entity::createInstance($sType);

			$aAttributes = $oEntity->getAttributes();
			foreach ($aAttributes as $oAttribute)
			{
				if (isset($oObject[$oAttribute->Name]))
				{
					$mValue = $oObject[$oAttribute->Name];
					if ($oAttribute->Type === 'datetime')
					{
						$mValue = $oObject[$oAttribute->Name]->toDateTime()->format('r');
					}
					$oEntity->{$oAttribute->Name} = $mValue;
				}
			}
			$oEntity->UUID = (string) $oObject['_id'];
			$oEntity->EntityId = $oObject['EntityId'];
		}

		return $oEntity;
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return bool
	 */
	public function createEntity($oEntity)
	{
		$aEntity = $this->prepareEntity($oEntity);
		$oEntity->EntityId = $this->getNextSequence($oEntity->getName());
		$aEntity['EntityId'] = $oEntity->EntityId;

		$sEntityType = str_replace('\\', '.', $oEntity->getName());
		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->{$sEntityType};
		$oResult = $oCollection->insertOne($aEntity);

		return $oResult->getInsertedCount() > 0 ? $aEntity['EntityId'] : false;
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return bool
	 */
	public function updateEntity($oEntity)
	{
		$aEntity = $this->prepareEntity($oEntity);

		$sEntityType = str_replace('\\', '.', $oEntity->getName());
		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->{$sEntityType};
		$oResult = $oCollection->updateOne(
			['_id' => new \MongoDB\BSON\ObjectId($oEntity->UUID)],
			['$set' => $aEntity]
		);

		return ($oResult instanceof \MongoDB\UpdateResult);
	}

	/**
	 *
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function getEntity($mIdOrUUID, $sType)
	{
		$oObject = null;

		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->{\str_replace('\\', '.', $sType)};
		if (\is_numeric($mIdOrUUID))
		{
			$oObject = $oCollection->findOne(
				['EntityId' => $mIdOrUUID]
			);
		}
		else
		{
			$sObjectId = null;
			try
			{
				$sObjectId = new \MongoDB\BSON\ObjectId($mIdOrUUID);
			}
			catch (\Exception $oEx) { }
			if (isset($sObjectId))
			{
				$oObject = $oCollection->findOne(
					['_id' => $sObjectId]
				);
			}
		}

		return $this->parseEntity($oObject, $sType);
	}

	public function getTypes()
	{
		return false;
	}

	protected function getOperator($sOperator)
	{
		$sResult = '$eq';
		switch ($sOperator)
		{
			case '=':
				$sResult = '$eq';
				break;
			case '<>':
			case '!=':
				$sResult = '$ne';
				break;
			case '>':
				$sResult = '$gt';
				break;
			case '>=':
				$sResult = '$gte';
				break;
			case '<':
				$sResult = '$lt';
				break;
			case '<=':
				$sResult = '$lte';
				break;
			default:
				if (strtolower($sOperator) === 'in')
				{
					$sResult = '$in';
				}
				if (strtolower($sOperator) === 'not in')
				{
					$sResult = '$nin';
				}
				break;
		}

		return $sResult;
	}

	protected function prepareFilter($aWhere, $oEntity, &$aFilter)
	{
		foreach ($aWhere as $sKey => $mValue)
		{
			if (strpos($sKey, '$') !== false)
			{
				$sKey = strtolower($sKey);
				$aFilter[] = [$sKey => []];
				$aSubFilter = &$aFilter[count($aFilter)-1][$sKey];

				$this->prepareFilter($mValue, $oEntity, $aSubFilter);
			}
			else
			{
				$mResultValue = null;
				$mResultOperator = '$eq';
				if (is_array($mValue))
				{
					if (0 < count($mValue))
					{
						$mResultValue = $mValue[0];
						$mResultOperator = $this->getOperator($mValue[1]);
					}
				}
				else
				{
					$mResultValue = $mValue;
				}
				if (isset($mResultValue))
				{
					if (strpos($sKey, '@') !== false)
					{
						list(,$sKey) = explode('@', $sKey);
					}
					if ($oEntity->isEncryptedAttribute($sKey))
					{
						$mResultValue = \Aurora\System\Utils::EncryptValue($mResultValue);
					}
					$bIsInOperator = false;
					if ($mResultOperator === '$in' || $mResultOperator === '$nin' && is_array($mResultValue))
					{
						$bIsInOperator = true;
						$mResultValue = array_map(
							function ($mValue) use ($oEntity, $sKey) {
								return $oEntity->isStringAttribute($sKey) ? "'".$mValue."'" : $mValue;
							},
							$mResultValue
						);
					}

					$sType =$oEntity->getType($sKey);
					if ($sType === 'datetime')
					{
						$mResultValue = (new \MongoDB\BSON\UTCDateTime(new \DateTime($mResultValue)));
					}

					$aFilter[] = ["'".$sKey."'" =>
							[$mResultOperator => $mResultValue]
					];
				}
			}
		}
	}

	public function getEntitiesUids($sType, $iOffset = 0, $iLimit = 20, $aSearchAttrs = [], $mSortAttributes = [], $iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $sCustomViewSql = '')
	{
		$aEntities = $this->getEntities(
			$sType,
			[],
			$iOffset,
			$iLimit,
			$aSearchAttrs,
			$mSortAttributes,
			$iSortOrder,
			[],
			false,
			$sCustomViewSql
		);
	}

	/**
	 *
	 * @param type $sType
	 * @param type $aWhere
	 * @param type $aIds
	 * @return type
	 */
	public function getEntitiesCount($sType, $aWhere = array(), $aIds = array())
	{
		$aOptions = [];
		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->{\str_replace('\\', '.', $sType)};
		return (int) $oCollection->count(
			[],
			$aOptions
		);
	}

	/**
	 *
	 * @param type $sType
	 * @param type $aViewAttrs
	 * @param type $iOffset
	 * @param type $iLimit
	 * @param type $aSearchAttrs
	 * @param type $mOrderBy
	 * @param type $iSortOrder
	 * @param type $aIdsOrUUIDs
	 * @return \Aurora\System\EAV\Entity
	 */
	public function getEntities($sType, $aViewAttrs = array(), $iOffset = 0, $iLimit = 20, $aSearchAttrs = array(), $mOrderBy = array(), $iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $aIdsOrUUIDs = array(),  $sCustomViewSql = '')
	{
		$aEntities = [];
		$aOptions = [
			'skip' => $iOffset,
			'limit' => $iLimit,
			'projection' => ['EntityId'=> 1]
		];
		if (!is_array($mOrderBy))
		{
			$mOrderBy = [$mOrderBy];
		}
		if (count($mOrderBy) > 0)
		{
			$aOptions['sort'] = [
				$mOrderBy[0] => $iSortOrder === \Aurora\System\Enums\SortOrder::ASC ? 1 : -1
			];
		}
		if (count($aViewAttrs) > 0)
		{
			foreach ($aViewAttrs as $sAttribute)
			{
				$aOptions['projection'][$sAttribute] = 1;
			}
		}

		$aFilter = [];
		$oEntity = \Aurora\System\EAV\Entity::createInstance($sType);

		if (count($aIdsOrUUIDs) > 0)
		{
			$aIdsOrUUIDs = array_map(
				function ($mIdOrUUID) {
					return \MongoDB\BSON\ObjectId($mIdOrUUID);
				},
				$aIdsOrUUIDs
			);

			$aFilter['_id'] = [
				'$in' => $aIdsOrUUIDs
			];
		}

		// $this->prepareFilter($aSearchAttrs, $oEntity, $aFilter['$and']);

		// print_r($aFilter); exit;

		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->{\str_replace('\\', '.', $sType)};
		$oObjects = $oCollection->find(
			$aFilter,
			$aOptions
		);
		foreach ($oObjects as $oObject)
		{
			$aEntities[] = $this->parseEntity($oObject, $sType);
		}
		return $aEntities;
	}

	/**
	 * @return bool
	 */
	public function deleteEntity($mIdOrUUID, $sType)
	{
		$oCollection = (new \MongoDB\Client())->{$this->sDBName}->{\str_replace('\\', '.', $sType)};
		if (\is_numeric($mIdOrUUID))
		{
			$oCollection->deleteOne(
				['EntityId' => $mIdOrUUID]
			);
		}
		else
		{
			$oCollection->deleteOne(
				['_id' => \MongoDB\BSON\ObjectId($mIdOrUUID)]
			);
		}

	}

	/**
	 * @return bool
	 */
	public function deleteEntities($aIdsOrUUIDs, $sType)
	{
		return false;
	}

	/**
	 */
	public function setAttributes($aEntitiesIds, $aAttributes)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function deleteAttribute($sType, $iEntityId, $sAttribute)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function getAttributesNamesByEntityType($sEntityTypes)
	{
		return false;
	}

	public function testConnection()
	{
		return isset((new \MongoDB\Client())->{$this->sDBName});
	}
}
