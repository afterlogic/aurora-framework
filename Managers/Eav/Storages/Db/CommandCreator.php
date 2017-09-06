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

namespace Aurora\System\Managers\Eav\Storages\Db;

class CommandCreator extends \Aurora\System\Db\AbstractCommandCreator
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
					if (strpos($sKey, '@') !== false)
					{
						list(,$sKey) = explode('@', $sKey);
					}
					
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
						$sValueFormat = "%s";
					}
					else
					{
						$sValueFormat = $oEntity->isStringAttribute($sKey) ? "%s" : "%d";
					}
					$aResultOperations[] = sprintf(
						"`attr_%s` %s " . $sValueFormat, 
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
		return $this->getEntities($sType, array(), 0, 0, $aWhere, "", \Aurora\System\Enums\SortOrder::ASC, $aIdsOrUUIDs, true);
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
			$iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $aIdsOrUUIDs = array(), $bCount = false)
	{
		$sViewAttributes = "";
		$sMaxViewAttributes = "";
		$sJoinAttrbutes = "";
		$sResultWhere = "";
		$sResultSort = "";
		$sLimit = "";
		$sOffset = "";
		
		$oEntity = \Aurora\System\EAV\Entity::createInstance($sEntityType);
		if ($oEntity instanceof $sEntityType)
		{
			$aResultViewAttributes = array();
			$aResultMaxAttributes = array();
			$aJoinAttributes = array();
			
			if ($aViewAttributes === null)
			{
				$aViewAttributes = array();
			}
			if (count($aViewAttributes) === 0)
			{
				$aViewAttributes = $oEntity->getAttributesKeys();
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
				return $sSortField . ' ' . ($iSortOrder === \Aurora\System\Enums\SortOrder::ASC ? "ASC" : "DESC");
			}, $mSortAttributes);

			$sResultSort = " ORDER BY " . implode(',', $mSortAttributes) . "";
			
			$aWhereAttrs = array();
			if (0 < count($aWhere))
			{
				$sResultWhere = ' AND ' . $this->prepareWhere($aWhere, $oEntity, $aWhereAttrs);
			}
			$aViewAttributes = array_unique(array_merge($aViewAttributes, $aWhereAttrs));

			$aViewAttributesByTypes = [];
			foreach ($aViewAttributes as $sAttribute)
			{
				$sType = $oEntity->getType($sAttribute);
				$aViewAttributesByTypes[$sType][] = $sAttribute;
			}
			
			foreach ($aViewAttributesByTypes as $sType => $aAttributes)
			{
				$aJoinAttributesTmp = array();
				foreach ($aAttributes as $sAttribute)
				{
					$aResultViewAttributes[$sAttribute] = sprintf(
							"				
			CASE WHEN %seav_attributes_%s.name = '%s'
				THEN %seav_attributes_%s.`value` 
			END as `attr_%s`", 
							$this->prefix(),
							$sType,
							$sAttribute,
							$this->prefix(),
							$sType,
							$sAttribute
					);
					$aResultMaxAttributes[$sAttribute] = sprintf(
							"MAX(`attr_%s`) as `attr_%s`
	", 
							$sAttribute,
							$sAttribute
					);
					
					$aJoinAttributesTmp[$sAttribute] = sprintf(
							"%seav_attributes_%s.name = '%s'",
							$this->prefix(),
							$sType,
							$sAttribute
					);
					
				}
				
				$sJoinAttributesTmp = implode(' OR ', $aJoinAttributesTmp);
				
				$aJoinAttributes[$sType] = sprintf(
						"
			LEFT JOIN %seav_attributes_%s
			  ON (%s)
				AND %seav_attributes_%s.id_entity = entities.id",
						$this->prefix(),
						$sType,
						$sJoinAttributesTmp,
						$this->prefix(),
						$sType
				);
			}
			if (0 < count($aViewAttributes))
			{
				$sViewAttributes = ', ' . implode(', ', $aResultViewAttributes);

				$sMaxViewAttributes = ', ' . implode(', ', $aResultMaxAttributes);
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
					' AND S2.%s IN (%s)', 
					$bUUID ? 'entity_uuid' : 'entity_id',
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
SELECT * FROM (SELECT entity_id, entity_uuid, entity_type, entity_module
	%s #1
	FROM (SELECT 
			entities.id as entity_id, 
			entities.uuid as entity_uuid, 
			entities.entity_type, 
			entities.module_name as entity_module
			# fields
			%s #2
			# ------
		FROM %seav_entities as entities #3
			# fields
			%s #4
			# ------

		WHERE entities.entity_type = %s #5 ENTITY TYPE
		) AS S1
		GROUP BY entity_id 
    ) as S2
    WHERE 			
		1 = 1 %s #6 WHERE
	%s #7 SORT
	%s #8 LIMIT
	%s #9 OFFSET", 
			$sMaxViewAttributes,
			$sViewAttributes, 
			$this->prefix(),
			$sJoinAttrbutes, 
			$this->escapeString($sEntityType), 
			$sResultWhere,
			$sResultSort,
			$sLimit,
			$sOffset
		);
		
		if ($bCount)
		{
			$sSql = sprintf("
SELECT count(entity_id) AS entities_count FROM (
%s
) as tmp", $sSql);
		}
		
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
				if ($oAttribute instanceof \Aurora\System\EAV\Attribute && !in_array(strtolower($oAttribute->Name), \Aurora\System\EAV\Entity::$aReadOnlyAttributes))
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

