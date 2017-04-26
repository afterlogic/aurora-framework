<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
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
 * @internal
 * 
 * @package EAV
 * @subpackage Storages
 */
class CApiEavCommandCreator extends \Aurora\System\Db\AbstractCommandCreator
{
	/**
	 * @return string
	 */
	public function isEntityExists($mIdOrUUID)
	{
		$sWhere = is_int($mIdOrUUID) ? 
				sprintf('id = %d', $mIdOrUUID) : 
					sprintf('uuid = %s', $this->escapeString($mIdOrUUID));

		return sprintf(
			'SELECT COUNT(id) as entities_count '
			. 'FROM %seav_entities WHERE %s', 
			$this->prefix(), $sWhere
		);
	}

	/**
	 * @return string
	 */
	public function createEntity($sModule, $sType, $sUUID = '')
	{
		return sprintf(
			'INSERT INTO %seav_entities ( %s, %s, %s ) '
			. 'VALUES ( %s, %s, %s )', 
			$this->prefix(),
			$this->escapeColumn('uuid'), 
			$this->escapeColumn('module_name'), 
			$this->escapeColumn('entity_type'), 
			empty($sUUID) ? 'UUID()' : $this->escapeString($sUUID), 
			$this->escapeString($sModule),
			$this->escapeString($sType)
		);
	}
	
	/**
	 * @param $mIdOrUUID
	 *
	 * @return string
	 */
	public function deleteEntity($mIdOrUUID)
	{
		$sWhere = is_int($mIdOrUUID) ? 
				sprintf('id = %d', $mIdOrUUID) : 
					sprintf('uuid = %s', $this->escapeString($mIdOrUUID));

		return sprintf(
			'DELETE FROM %seav_entities WHERE %s', 
			$this->prefix(), $sWhere);
	}	
	
	/**
	 * @param $aIdsOrUUIDs
	 *
	 * @return string
	 */
	public function deleteEntities($aIdsOrUUIDs)
	{
		$sResult = '';
		if (count($aIdsOrUUIDs) > 0)
		{
			$sIdOrUUID = 'id';
			if(!is_int($aIdsOrUUIDs[0]))
			{
				$sIdOrUUID = 'uuid';
				$aIdsOrUUIDs = array_map(
					function ($mValue) {
						return $this->escapeString($mValue);
					}, 
					$aIdsOrUUIDs
				);
			}
			$sResult = sprintf(
				'DELETE FROM %seav_entities WHERE %s IN (' . implode(',', $aIdsOrUUIDs) . ')', 
				$this->prefix(), $sIdOrUUID
			);
		}
		
		return $sResult;
	}	
	
	/**
	 * 
	 * @param int|string $mIdOrUUID
	 * @return string
	 */
	public function getEntity($mIdOrUUID)
	{
		$sWhere = is_int($mIdOrUUID) ? 
				sprintf('entities.id = %d', $mIdOrUUID) : 
					sprintf('entities.uuid = %s', $this->escapeString($mIdOrUUID));
		
		$sSubSql = "
(SELECT 	   
	entities.id as entity_id, 
	entities.uuid as entity_uuid, 
	entities.entity_type, 
	entities.module_name as entity_module,
	attrs.name as attr_name,
    attrs.value as attr_value,
	%s as attr_type
FROM %seav_entities as entities
	  INNER JOIN %seav_attributes_%s as attrs ON entities.id = attrs.id_entity
WHERE %s)
";
		
		foreach (\Aurora\System\EAV\Entity::getTypes() as $sSqlType)
		{
			$aSql[] = sprintf($sSubSql, $this->escapeString($sSqlType), $this->prefix(), $this->prefix(), $sSqlType, $sWhere);
		}
		$sSql = implode("UNION
", $aSql);

		return $sSql;
	}

	/**
	 * @return string
	 */
	public function getTypes()
	{
		return sprintf('
SELECT DISTINCT entity_type FROM %seav_entities', 
			$this->prefix()
		);
	}
			
	public function prepareWhere($aWhere, $oEntity, &$aWhereAttributes, $sOperator = 'AND')
	{
		$aResultOperations = array();
		foreach ($aWhere as $sKey => $mValue)
		{
			if (strpos($sKey, '$') !== false)
			{
				list(,$sKey) = explode('$', $sKey);
				$aResultOperations[] = $this->prepareWhere($mValue, $oEntity, $aWhereAttributes, $sKey);
			}
			else
			{
				$mResultValue = null;
				$mResultOperator = '=';
				if (is_array($mValue))
				{
					if (0 < count($mValue))
					{
						$mResultValue = $mValue[0];
						$mResultOperator = $mValue[1];
					}
				}
				else
				{
					$mResultValue = $mValue;
				}
				if (isset($mResultValue))
				{
					if (!in_array($sKey, $aWhereAttributes))
					{
						$aWhereAttributes[] = $sKey;
					}
					if ($oEntity->isEncryptedAttribute($sKey))
					{
						$mResultValue = \Aurora\System\Utils::EncryptValue($mResultValue);
					}
					$bIsInOperator = false;
					if (strtolower($mResultOperator) === 'in' || strtolower($mResultOperator) === 'not in'  
						&& is_array($mResultValue))
					{
						$bIsInOperator = true;
						$mResultValue = array_map(
							function ($mValue) use ($oEntity, $sKey) {
								return $oEntity->isStringAttribute($sKey) ? $this->escapeString($mValue) : $mValue;
							}, 
							$mResultValue
						);
						$mResultValue = '(' . implode(', ', $mResultValue)  . ')';
					}
					
					$sValueFormat = $oEntity->isStringAttribute($sKey) ? "%s" : "%d";
					$aResultOperations[] = sprintf(
						"`attrs_%s`.`value` %s " . $sValueFormat, 
						$sKey, 
						$mResultOperator, 
						($oEntity->isStringAttribute($sKey) && !$bIsInOperator) ? $this->escapeString($mResultValue) : $mResultValue
					);
				}
			}
		}
		return sprintf(
			count($aResultOperations) > 1 ? '(%s)' : '%s', 
			implode(' ' . $sOperator . ' ', $aResultOperations)
		);
	}
	
	public function getEntitiesCount($sType, $aWhere = array(), $aIdsOrUUIDs = array())
	{
		return $this->getEntities($sType, array(), 0, 0, $aWhere, "", \ESortOrder::ASC, $aIdsOrUUIDs, true);
	}

	/**
	 * 
	 * @param type $sEntityType
	 * @param type $aViewAttributes
	 * @param type $iOffset
	 * @param type $iLimit
	 * @param type $aWhere
	 * @param string|array $mSortAttributes
	 * @param type $iSortOrder
	 * @param type $aIdsOrUUIDs
	 * @param type $bCount
	 * @return type
	 * 
		$aWhere = [
		   '$OR' => [
			   '$AND' => [
				   'IdUser' => [
					   1,
					   '='
				   ],
				   'Storage' => [
					   'personal',
					   '='
				   ]
			   ],
			   'Storage' => [
				   'global',
				   '='
			   ]
		   ]
	   ];
	 */	
	public function getEntities($sEntityType, $aViewAttributes = array(), 
			$iOffset = 0, $iLimit = 0, $aWhere = array(), $mSortAttributes = array(), 
			$iSortOrder = \ESortOrder::ASC, $aIdsOrUUIDs = array(), $bCount = false)
	{
		$sCount = "";
		$sViewAttributes = "";
		$sJoinAttrbutes = "";
		$sResultWhere = "";
		$sResultSort = "";
		$sGroupByFields = "entity_id";
		$sLimit = "";
		$sOffset = "";
		
		$oEntity = \Aurora\System\EAV\Entity::createInstance($sEntityType);
		if ($oEntity instanceof $sEntityType)
		{
			$aResultViewAttributes = array();
			$aJoinAttributes = array();
			
			if ($bCount)
			{
				$sGroupByFields .= ", entity_type";
				$sCount = "COUNT(DISTINCT entities.id) as entities_count,";
			}			
			else
			{
				if ($aViewAttributes === null)
				{
					$aViewAttributes = array();
				}
				if (count($aViewAttributes) === 0)
				{
					$aViewAttributes = $oEntity->getAttributesKeys();
				}
			}

			if (!is_array($mSortAttributes))
			{
				if (!empty($mSortAttributes))
				{
					$mSortAttributes = array($mSortAttributes);
				}
				else 
				{
					$mSortAttributes = array();
				}
			}
			
			$aViewAttributes = array_merge($aViewAttributes, $mSortAttributes);

			$mSortAttributes = array_map(function($sValue){
				return $this->escapeColumn(
					sprintf("attr_%s", $sValue)
				);
			}, $mSortAttributes);
			$mSortAttributes[] = 'entity_id';
			
			$mSortAttributes = array_map(function ($sSortField) use ($iSortOrder) {
				return $sSortField . ' ' . ($iSortOrder === \ESortOrder::ASC ? "ASC" : "DESC");
			}, $mSortAttributes);

			$sResultSort = " ORDER BY " . implode(',', $mSortAttributes) . "";
			
			$aWhereAttrs = array();
			if (0 < count($aWhere))
			{
				$sResultWhere = ' AND ' . $this->prepareWhere($aWhere, $oEntity, $aWhereAttrs);
			}
			$aViewAttributes = array_unique(array_merge($aViewAttributes, $aWhereAttrs));

			foreach ($aViewAttributes as $sAttribute)
			{
				$sType = $oEntity->getType($sAttribute);

				$aResultViewAttributes[$sAttribute] = sprintf(
						"
	`attrs_%s`.`value` as `attr_%s`", 
						$sAttribute, 
						$sAttribute
				);
				if (!$bCount)
				{
					$sGroupByFields .= ', ' . sprintf("`attr_%s`", $sAttribute);
				}
				$aJoinAttributes[$sAttribute] = sprintf(
						"
	LEFT JOIN %seav_attributes_%s as `attrs_%s` 
		ON `attrs_%s`.name = %s AND `attrs_%s`.id_entity = entities.id",
					$this->prefix(),
					$sType, 
					$sAttribute, 
					$sAttribute, 
					$this->escapeString($sAttribute), 
					$sAttribute
				);
			}
			if (0 < count($aViewAttributes))
			{
				if (!$bCount)
				{
					$sViewAttributes = ', ' . implode(', ', $aResultViewAttributes);
				}
				$sJoinAttrbutes = implode(' ', $aJoinAttributes);
			}
			if (0 < count($aIdsOrUUIDs))
			{
				$bUUID = !is_numeric($aIdsOrUUIDs[0]);
				if ($bUUID)
				{
					$aIdsOrUUIDs = array_map(
						function ($mValue) use ($bUUID) {
							return $bUUID ? $this->escapeString($mValue) : $mValue;
						}, 
						$aIdsOrUUIDs
					);
				}
				$sResultWhere .= sprintf(
					' AND entities.%s IN (%s)', 
					$bUUID ? 'uuid' : 'id',
					implode(',', $aIdsOrUUIDs)
				);
			}
			
			if ($iLimit > 0)
			{
				$sLimit = sprintf("LIMIT %d", $iLimit);
				$sOffset = sprintf("OFFSET %d", $iOffset);
			}
		}		
		$sSql = sprintf("
SELECT 
	%s #1 COUNT
	entities.id as entity_id, 
	entities.uuid as entity_uuid, 
	entities.entity_type, 
	entities.module_name as entity_module
	# fields
	%s #2
	# ------
FROM %seav_entities as entities
	# fields
	%s #3
	# ------

WHERE entities.entity_type = %s #4 ENTITY TYPE
	%s #5 WHERE
GROUP BY %s #6 
%s #7 SORT
%s #8 LIMIT
%s #9 OFFSET", 
			$sCount,
			$sViewAttributes, 
			$this->prefix(),
			$sJoinAttrbutes, 
			$this->escapeString($sEntityType), 
			$sResultWhere,
			$sGroupByFields,
			$sResultSort,
			$sLimit,
			$sOffset
		);		
		return $sSql;
	}	
	
	/**
	 * @param array $aEntityIds
	 * @param array $aAttributes
	 *
	 * @return string
	 */
	public function setAttributes($aEntityIds, $aAttributes, $sType)
	{
		$sSql = '';
		$aValues = array();
		foreach ($aEntityIds as $iEntityId)
		{
			foreach ($aAttributes as $oAttribute)
			{
				if ($oAttribute instanceof \Aurora\System\EAV\Attribute)
				{
					if ($oAttribute->IsEncrypt && !$oAttribute->Encrypted)
					{
						$oAttribute->Encrypt();
					}
					$mValue = $oAttribute->Value;
					$sSqlValue = $oAttribute->needToEscape() ? $this->escapeString($mValue) : $mValue;
					$sSqlValueType = $oAttribute->getValueFormat();
					
					$aValues[] = sprintf('	(%d, %s, ' . $sSqlValueType . ')',
						$iEntityId,
						$this->escapeString($oAttribute->Name),
						$sSqlValue
					);
				}
			}
		}
		if (count($aValues) > 0)
		{
			$sValues = implode(",\r\n", $aValues);

			$sSql = $sSql . sprintf('
INSERT INTO %seav_attributes_%s 
	(%s, %s, %s)
VALUES 
%s
ON DUPLICATE KEY UPDATE 
	%s=VALUES(%s),
	%s=VALUES(%s),
	%s=VALUES(%s);
', 
				$this->prefix(), 
				$sType, 
				$this->escapeColumn('id_entity'), 
				$this->escapeColumn('name'),
				$this->escapeColumn('value'),
				$sValues,
				$this->escapeColumn('id_entity'), $this->escapeColumn('id_entity'), 
				$this->escapeColumn('name'), $this->escapeColumn('name'),
				$this->escapeColumn('value'), $this->escapeColumn('value')
			);
		}
		return $sSql;
	}	
	
	public function getAttributesNamesByEntityType($sEntityType)
	{
		$sSubSql = "
(SELECT DISTINCT name FROM %seav_attributes_%s as attrs, %seav_entities as entities
	WHERE entity_type = %s AND entities.id = attrs.id_entity)
";
		
		foreach (\Aurora\System\EAV\Entity::getTypes() as $sSqlType)
		{
			$aSql[] = sprintf($sSubSql, $this->prefix(), $sSqlType, $this->prefix(), $this->escapeString($sEntityType));
		}
		$sSql = implode("UNION
", $aSql);

		return $sSql;
	}
}

/**
 * @internal
 * 
 * @subpackage Storages
 */
class CApiEavCommandCreatorMySQL extends CApiEavCommandCreator
{
}

/**
 * @todo make it
 * 
 * @internal
 * 
 * @subpackage Storages
 */
class CApiEavCommandCreatorPostgreSQL  extends CApiEavCommandCreatorMySQL
{
	// TODO
}
