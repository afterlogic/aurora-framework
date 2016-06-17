<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @internal
 * 
 * @package EAV
 * @subpackage Storages
 */
class CApiEavCommandCreator extends api_CommandCreator
{
	/**
	 * @return string
	 */
	public function isEntityExists($iId)
	{
		return sprintf(
			'SELECT COUNT(id) as entities_count '
			. 'FROM %seav_entities WHERE %s = %d', 
			$this->prefix(), $this->escapeColumn('id'), $iId
		);
	}

	/**
	 * @return string
	 */
	public function createEntity($sModule, $sType)
	{
		return sprintf(
			'INSERT INTO %seav_entities ( %s, %s ) '
			. 'VALUES ( %s, %s )', 
			$this->prefix(),
			$this->escapeColumn('module_name'), 
			$this->escapeColumn('entity_type'), 
			$this->escapeString($sModule),
			$this->escapeString($sType)
		);
	}
	
	/**
	 * @param $iId
	 *
	 * @return string
	 */
	public function deleteEntity($iId)
	{
		return sprintf(
			'DELETE FROM %seav_entities WHERE id = %d', 
			$this->prefix(), $iId);
	}	
	
	public function getEntityById($iId)
	{
		$sSubSql = "
(SELECT 	   
	entities.id as entity_id, 
	entities.entity_type, 
	entities.module_name as entity_module,
	attrs.name as attr_name,
    attrs.value as attr_value,
	%s as attr_type
FROM %seav_entities as entities
	  INNER JOIN %seav_attributes_%s as attrs ON entities.id = attrs.id_entity
WHERE entities.id = %d)
";
		
		foreach (\AEntity::getTypes() as $sSqlType)
		{
			$aSql[] = sprintf($sSubSql, $this->escapeString($sSqlType), $this->prefix(), $this->prefix(), $sSqlType, $iId);
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
SELECT DISTINCT entity_type '
			. 'FROM %seav_entities', 
			$this->prefix()
		);
	}
	public function getEntitiesCount($sType, $aWhere = array())
	{
		return $this->getEntities($sType, array(), 0, 0, $aWhere, "", \ESortOrder::ASC, true);
	}
			
	
	public function getEntities($sEntityType, $aViewAttributes = array(), 
			$iOffset = 0, $iLimit = 0, $aWhere = array(), 
			$sSortAttribute = "", $iSortOrder = \ESortOrder::ASC, $bCount = false)
	{
		$sCount = "";
		$sViewAttributes = "";
		$sJoinAttrbutes = "";
		$sResultWhere = "";
		$sResultSort = "";
		$sGroupByField = "entity_id";
		$sLimit = "";
		$sOffset = "";
		
		$oEntity = call_user_func($sEntityType . '::createInstance');
		if ($oEntity instanceof $sEntityType)
		{
			$aResultViewAttributes = array();
			$aJoinAttributes = array();
			$aResultSearchAttributes = array();
			
			if ($bCount)
			{
				$sGroupByField = "entity_type";
				$sCount = "COUNT(DISTINCT entities.id) as entities_count,";
			}			
			else
			{
				if ($aViewAttributes === null)
				{
					$aViewAttributes = array();
				}
				else if (count($aViewAttributes) === 0)
				{
					$aMap = $oEntity->GetMap();
					$aViewAttributes = array_keys($aMap);
				}
			}

			if (!empty($sSortAttribute))
			{
				array_push($aViewAttributes, $sSortAttribute);
				$sResultSort = sprintf(" ORDER BY `attr_%s` %s", $sSortAttribute, $iSortOrder === \ESortOrder::ASC ? "ASC" : "DESC");
			}
			$aViewAttributes = array_unique(array_merge($aViewAttributes, array_keys($aWhere)));

			foreach ($aViewAttributes as $sAttribute)
			{
				$sType = $oEntity->getAttributeType($sAttribute);

				$aResultViewAttributes[$sAttribute] = sprintf(
						"
	`attrs_%s`.`value` as `attr_%s`", 
						$sAttribute, 
						$sAttribute
				);
				$aJoinAttributes[$sAttribute] = sprintf(
						"
	LEFT JOIN eav_attributes_%s as `attrs_%s` 
		ON `attrs_%s`.name = %s AND `attrs_%s`.id_entity = entities.id",
				$sType, $sAttribute, $sAttribute, $this->escapeString($sAttribute), $sAttribute);
			}
			if (0 < count($aViewAttributes))
			{
				$sViewAttributes = ', ' . implode(', ', $aResultViewAttributes);
				$sJoinAttrbutes = implode(' ', $aJoinAttributes);
			}
			foreach ($aWhere as $sKey => $mValue)
			{
				$sAttributeValue = $mValue;
				$sAction = '=';
				if (is_array($mValue) && count($mValue) > 1)
				{
					$sAction = $mValue[0];
					$sAttributeValue = $mValue[1];
				}
				else
				{
					if (strpos($sAttributeValue, "%") !== false)
					{
						$sAction = 'LIKE';
					}
				}
				$sType = $oEntity->getAttributeType($sKey);
				if ($oEntity->isEncryptedAttribute($sKey))
				{
					$sAttributeValue = \api_Utils::EncryptValue($sAttributeValue);
				}
				$sValueFormat = $oEntity->isStringAttribute($sKey) ? "%s" : "%d";
				$aResultSearchAttributes[] = sprintf(
						"`attrs_%s`.`value` %s " . $sValueFormat, 
						$sKey, 
						$sAction, 
						$oEntity->isStringAttribute($sKey) ? $this->escapeString($sAttributeValue) : $sAttributeValue
				);
			}
			if (0 < count($aWhere))
			{
				$sResultWhere = ' AND ' . implode(' AND ', $aResultSearchAttributes);
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
	entities.entity_type, 
	entities.module_name as entity_module
	# fields
	%s #2
	# ------
FROM eav_entities as entities
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
			$sJoinAttrbutes, 
			$this->escapeString($sEntityType), 
			$sResultWhere,
			$sGroupByField,
			$sResultSort,
			$sLimit,
			$sOffset
		);		
		return $sSql;
	}	
	
	/**
	 * @param CAttribute $oAttribute
	 *
	 * @return string
	 */
	public function createAttribute(CAttribute $oAttribute)
	{
		return $this->setAttributes(
				array($oAttribute->EntityId),
				array($oAttribute)
		);
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
				if ($oAttribute instanceof \CAttribute)
				{
					$mValue = $oAttribute->Value;
					if ($oAttribute->Encrypt)
					{
						$mValue = \api_Utils::EncryptValue($mValue);
					}
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
	
	/**
	 * @param $oAttribute
	 *
	 * @return string
	 */
	public function deleteAttribute(CAttribute $oAttribute)
	{
		return sprintf(
				'DELETE FROM %seav_attributes_%s WHERE id = %d', 
				$this->prefix(), $oAttribute->Type, $oAttribute->Id);
	}
	
	/**
	 * @param $iEntityId
	 *
	 * @return string
	 */
	public function deleteAttributes($iEntityId)
	{
		return sprintf(
				'DELETE FROM %seav_attributes WHERE id_entity = %d', 
				$this->prefix(), $iEntityId
		);
	}

	/**
	 * @return string
	 */
	public function isAttributeExists($iEntityId, $sAttributeName, $sAttributeType)
	{
		return sprintf(
				'SELECT COUNT(id) as attrs_count '
				. 'FROM %seav_attributes_%s WHERE %s = %d and %s = %s', 
				$this->prefix(),
				$sAttributeType,
				$this->escapeColumn('id_entity'), $iEntityId,
				$this->escapeColumn('name'), $this->escapeString($sAttributeName)
		);
	}
	
	public function getAttributesNamesByEntityType($sEntityType)
	{
		$sSubSql = "
(SELECT DISTINCT name FROM %seav_attributes_%s as attrs, %seav_entities as entities
	WHERE entity_type = %s AND entities.id = attrs.id_entity)
";
		
		foreach (\AEntity::getTypes() as $sSqlType)
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
