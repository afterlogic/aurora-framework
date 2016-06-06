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
	public function isObjectExists($iObjectId)
	{
		return sprintf(
			'SELECT COUNT(id) as objects_count '
			. 'FROM %seav_objects WHERE %s = %d', 
			$this->prefix(), $this->escapeColumn('id'), $iObjectId
		);
	}

	/**
	 * @return string
	 */
	public function createObject($sModule, $sType)
	{
		return sprintf(
			'INSERT INTO %seav_objects ( %s, %s ) '
			. 'VALUES ( %s, %s )', 
			$this->prefix(),
			$this->escapeColumn('module_name'), 
			$this->escapeColumn('object_type'), 
			$this->escapeString($sModule),
			$this->escapeString($sType)
		);
	}
	
	/**
	 * @param $iId
	 *
	 * @return string
	 */
	public function deleteObject($iId)
	{
		return sprintf(
			'DELETE FROM %seav_objects WHERE id = %d', 
			$this->prefix(), $iId);
	}	
	
	public function getObjectById($iId)
	{
		return sprintf(
			"SELECT 	   
				objects.id as obj_id, 
				objects.object_type as obj_type, 
				objects.module_name as obj_module,
				props.id, props.key as prop_key, 
				
				props.value_int as prop_value_int,
				props.value_bool as prop_value_bool,
				props.value_string as prop_value_string,
				props.value_text as prop_value_text,
				props.value_datetime as prop_value_datetime,
				
				props.type as prop_type
			FROM %seav_properties as props
				  RIGHT JOIN %seav_objects as objects
					ON objects.id = props.id_object
			WHERE objects.id = %d;",				
			$this->prefix(), $this->prefix(), $iId);
	}
	
	/**
	 * @return string
	 */
	public function getTypes()
	{
		return sprintf(
			'SELECT DISTINCT object_type '
			. 'FROM %seav_objects', 
			$this->prefix()
		);
	}
	public function getObjectsCount($sObjectType, $aWhere = array())
	{
		return $this->getObjects($sObjectType, array(), 0, 0, $aWhere, "", \ESortOrder::ASC, true);
	}
			
	public function getObjects($sObjectType, $aViewProperties = array(), 
			$iOffset = 0, $iLimit = 0, $aWhere = array(), 
			$sSortProperty = "", $iSortOrder = \ESortOrder::ASC, $bCount = false)
	{
		$sCount = "";
		$sViewPoperties = "";
		$sJoinPoperties = "";
		$sResultWhere = "";
		$sResultSort = "";
		$sGroupByField = "obj_id";
		$sLimit = "";
		$sOffset = "";
		
		if (class_exists($sObjectType))
		{
			$oObject = call_user_func($sObjectType . '::createInstance');
		}
		else
		{
			$oObject = new \api_APropertyBag($sObjectType);
		}
		
		$aResultViewProperties = array();
		$aJoinProperties = array();
		$aResultSearchProperties = array();

		if ($bCount)
		{
			$sGroupByField = "obj_type";
			$sCount = "COUNT(DISTINCT objects.id) as objects_count,";
		}			

		if (!empty($sSortProperty) && $oObject->IsProperty($sSortProperty))
		{
			array_push($aViewProperties, $sSortProperty);
			$sResultSort = sprintf(" ORDER BY `prop_%s` %s", $sSortProperty, $iSortOrder === \ESortOrder::ASC ? "ASC" : "DESC");
		}
		$aViewProperties = array_unique(array_merge($aViewProperties, array_keys($aWhere)));

		foreach ($aViewProperties as $sProperty)
		{
			$sType = $oObject->getPropertyType($sProperty);

#			$aResultViewProperties[$sProperty] = sprintf(
#					"
#(SELECT props.value_%s 
#	FROM %seav_properties as props 
#		WHERE props.id_object = objects.id AND props.`key` = %s ORDER BY 1 LIMIT 1) AS `prop_%s`",
#			$sType, $this->prefix(), $this->escapeString($sProperty), $sProperty);
			
			
			$aResultViewProperties[$sProperty] = sprintf(
"
	MAX(IF(props.`key` = %s, props.value_%s, NULL))as `prop_%s`",
					$this->escapeString($sProperty), $sType, $sProperty);
		}
		if (0 < count($aViewProperties))
		{
			$sViewPoperties = ', ' . implode(', ', $aResultViewProperties);
			$sJoinPoperties = implode(' ', $aJoinProperties);
		}
		foreach ($aWhere as $sKey => $mValue)
		{
			$sPrpertyValue = $mValue;
			$sPropertyAction = '=';
			if (is_array($mValue) && count($mValue) > 1)
			{
				$sPropertyAction = $mValue[0];
				$sPrpertyValue = $mValue[1];
			}
			else
			{
				if (strpos($sPrpertyValue, "%") !== false)
				{
					$sPropertyAction = 'LIKE';
				}
			}
			$sType = $oObject->getPropertyType($sKey);
			if ($oObject->isEncryptedProperty($sKey))
			{
				$sPrpertyValue = \api_Utils::EncryptValue($sPrpertyValue);
			}
			$sValueFormat = $oObject->isStringProperty($sKey) ? "%s" : "%d";
			$aResultSearchProperties[] = sprintf(
					"`prop_%s` %s " . $sValueFormat, 
					$sKey, 
					$sPropertyAction, 
					$oObject->isStringProperty($sKey) ? $this->escapeString($sPrpertyValue) : $sPrpertyValue
			);
		}
		if (0 < count($aWhere))
		{
			$sResultWhere = ' AND ' . implode(' AND ', $aResultSearchProperties);
		}

		if ($iLimit > 0)
		{
			$sLimit = sprintf("LIMIT %d", $iLimit);
			$sOffset = sprintf("OFFSET %d", $iOffset);
		}
		$sSql = sprintf(
			"SELECT 
	%s #1 COUNT
	objects.id as obj_id, 
	objects.object_type as obj_type, 
	objects.module_name as obj_module
	# fields
	%s #2
	# ------
	FROM %seav_properties as props

	  RIGHT JOIN %seav_objects as objects
		ON objects.id = props.id_object

	GROUP BY %s #5 
	HAVING objects.object_type = %s #6 OBJECT TYPE
	%s #7 WHERE
	%s #8 SORT
	%s #9 LIMIT
	%s #10 OFFSET", 
			$sCount,							// #1
			$sViewPoperties,					// #2
			$this->prefix(),					// #3
			$this->prefix(),					// #4
			$sGroupByField,						// #5
			$this->escapeString($sObjectType),	// #6
			$sResultWhere,						// #7
			$sResultSort,						// #8
			$sLimit,							// #9
			$sOffset							// #10
		);	
		
		return $sSql;
	}
	
	/**
	 * @param CProperty $oProperty
	 *
	 * @return string
	 */
	public function createProperty(CProperty $oProperty)
	{
		return $this->setProperties(
				array($oProperty->ObjectId),
				array($oProperty)
		);
	}	
	
	/**
	 * @param array $aObjectIds
	 * @param array $aProperties
	 *
	 * @return string
	 */
	public function setProperties($aObjectIds, $aProperties)
	{
		$sSql = '';
		$aValues = array();
		foreach ($aObjectIds as $iObjectId)
		{
			foreach ($aProperties as $oProperty)
			{
				if ($oProperty instanceof \CProperty)
				{
					$mValue = $oProperty->Value;
					if ($oProperty->Encrypt)
					{
						$mValue = \api_Utils::EncryptValue($mValue);
					}
					$aValues[] = sprintf('(%d, %s, %s, %s, %s, %d, %d, %s)',
						$iObjectId,
						$this->escapeString($oProperty->Name),
						$this->escapeString($oProperty->Type),
						$oProperty->Type === "string" ? $this->escapeString($mValue) : 'null',
						$oProperty->Type === "text" ? $this->escapeString($mValue) : 'null',
						$oProperty->Type === "int" ? $mValue : 'null',
						$oProperty->Type === "bool" ? $mValue : 'null',
						$oProperty->Type === "datetime" ? $this->escapeString($mValue) : 'null'
					);
				}
			}
		}
		if (count($aValues) > 0)
		{
			$sValues = implode(",\r\n", $aValues);
			$sSql = sprintf(
			'INSERT INTO %seav_properties 
				(%s, %s, %s, %s, %s, %s, %s, %s)
			VALUES 
				%s
			ON DUPLICATE KEY UPDATE 
				%s=VALUES(%s),
				%s=VALUES(%s),
				%s=VALUES(%s),
				%s=VALUES(%s),
				%s=VALUES(%s),
				%s=VALUES(%s),
				%s=VALUES(%s)', 
				$this->prefix(), 
				$this->escapeColumn('id_object'), 
				$this->escapeColumn('key'),
				$this->escapeColumn('type'),
				$this->escapeColumn('value_string'),
				$this->escapeColumn('value_text'),
				$this->escapeColumn('value_int'),
				$this->escapeColumn('value_bool'),
				$this->escapeColumn('value_datetime'),
				$sValues,
				$this->escapeColumn('id_object'), $this->escapeColumn('id_object'), 
				$this->escapeColumn('key'), $this->escapeColumn('key'),
				$this->escapeColumn('type'), $this->escapeColumn('type'),
				$this->escapeColumn('value_string'), $this->escapeColumn('value_string'),
				$this->escapeColumn('value_text'), $this->escapeColumn('value_text'),
				$this->escapeColumn('value_int'), $this->escapeColumn('value_int'),
				$this->escapeColumn('value_bool'), $this->escapeColumn('value_bool'),
				$this->escapeColumn('value_datetime'), $this->escapeColumn('value_datetime')
			);
		}
		
		return $sSql;
	}	
	
	/**
	 * @param $oProperty
	 *
	 * @return string
	 */
	public function deleteProperty(CProperty $oProperty)
	{
		return sprintf(
				'DELETE FROM %seav_properties WHERE id = %d', 
				$this->prefix(), $oProperty->Id);
	}
	
	/**
	 * @param $iObjectId
	 *
	 * @return string
	 */
	public function deleteProperties($iObjectId)
	{
		return sprintf(
				'DELETE FROM %seav_properties WHERE id_object = %d', 
				$this->prefix(), $iObjectId
		);
	}

	/**
	 * @param string $sDomainName
	 *
	 * @return string
	 */
	public function isPropertyExists($iObjectId, $sPropertyName)
	{
		return sprintf(
				'SELECT COUNT(id) as properties_count '
				. 'FROM %seav_properties WHERE %s = %d and %s = %s', 
				$this->prefix(),
				$this->escapeColumn('id_object'), $iObjectId,
				$this->escapeColumn('key'), $this->escapeString($sPropertyName)
		);
	}
	
	/**
	 * @param int $iDomainId
	 *
	 * @return string
	 */
	public function getProperty($iObjectId, $sPropertyName)
	{
		return $this->gePropertyByWhere(
			sprintf(
				'%s = %d AND %s = %s', 
				$this->escapeColumn('id_object'), 
				$iObjectId,
				$this->escapeColumn('key'), 
				$this->escapeString($sPropertyName)
			)
		);
	}
	
	public function getPropertiesNamesByObjectType($sObjectType)
	{
		return sprintf(
				'SELECT DISTINCT(`key`) as property_name '
				. 'FROM %seav_properties as props, %seav_objects as objects
				WHERE object_type = %s AND objects.id = props.id_object', 
				$this->prefix(), $this->prefix(),
				$this->escapeString($sObjectType)
		);	
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
