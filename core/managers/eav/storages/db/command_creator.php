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
	protected $aViewProperties;
	
	protected $aSearchProperties;
	
	protected $aSortProperties;
	
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
			'DELETE FROM %seav_objects '
			. 'WHERE id = %d', 
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
				
				props.type as prop_type
			FROM %seav_properties as props
				  RIGHT JOIN %seav_objects as objects
					ON objects.id = props.id_object
			WHERE objects.id = %d;",				
			$this->prefix(), $this->prefix(), $iId);
	}
	
	public function getObjectsCount($sObjectType, $aSearchProperties = array())
	{
		return $this->getObjects($sObjectType, array(), 0, 0, $aSearchProperties, "", \ESortOrder::ASC, true);
	}
			
	public function getObjects($sObjectType, $aViewProperties = array(), 
			$iPage = 0, $iPerPage = 0, $aSearchProperties = array(), 
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
		
		$oObject = call_user_func($sObjectType . '::createInstance');
		if ($oObject instanceof $sObjectType)
		{
			$aResultViewProperties = array();
			$aJoinProperties = array();
			$aResultSearchProperties = array();
			
			if ($bCount)
			{
				$sGroupByField = "obj_type";
				$sCount = "COUNT(DISTINCT objects.id) as objects_count,";
			}			
			else
			{
				if (count($aViewProperties) === 0)
				{
					$aMap = $oObject->GetMap();
					$aViewProperties = array_keys($aMap);
				}
			}

			if (!empty($sSortProperty))
			{
				array_push($aViewProperties, $sSortProperty);
				$sResultSort = sprintf(" ORDER BY prop_%s %s", $sSortProperty, $iSortOrder === \ESortOrder::ASC ? "ASC" : "DESC");
			}
			$aViewProperties = array_unique(array_merge($aViewProperties, array_keys($aSearchProperties)));

			foreach ($aViewProperties as $sProperty)
			{
				$sType = $oObject->getPropertyType($sProperty);

				$aResultViewProperties[$sProperty] = sprintf(
						"props_%s.value_%s as prop_%s", 
						$sProperty, 
						$sType, 
						$sProperty
				);
				$aJoinProperties[$sProperty] = sprintf(
						"LEFT JOIN eav_properties as props_%s 
							ON props_%s.key = %s 
								AND props_%s.id_object = objects.id",
				$sProperty, $sProperty, $this->escapeString($sProperty), $sProperty);
			}
			if (0 < count($aViewProperties))
			{
				$sViewPoperties = ', ' . implode(', ', $aResultViewProperties);
				$sJoinPoperties = implode(' ', $aJoinProperties);
			}
			foreach ($aSearchProperties as $sKey => $mValue)
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
				$sValueFormat = ($sType === 'int' || $sType === 'bool') ? "%d" : "%s";
				$aResultSearchProperties[] = sprintf(
						"props_%s.value_%s %s " . $sValueFormat, 
						$sKey, 
						$sType, 
						$sPropertyAction, 
						($sType !== 'int' && $sType !== 'bool') ? $this->escapeString($mValue) : $mValue
				);
			}
			if (0 < count($aSearchProperties))
			{
				$sResultWhere = ' AND ' . implode(' AND ', $aResultSearchProperties);
			}
			
			if ($iPerPage > 0)
			{
				$sLimit = sprintf("LIMIT %d", $iPerPage);
				$sOffset = sprintf("OFFSET %d", ($iPage > 0) ? ($iPage - 1) * $iPerPage : 0);
			}
		}		
		$sSql = sprintf(
			"SELECT 
			%s #1 COUNT
			objects.id as obj_id, objects.object_type as obj_type, objects.module_name as obj_module
			# fields
			%s #2
			# ------
			FROM eav_properties as props

			  RIGHT JOIN eav_objects as objects
				ON objects.id = props.id_object
			
			# fields
			%s #3
			# ------
			
			WHERE objects.object_type = %s #4 OBJECT TYPE
			%s #5 WHERE
			GROUP BY %s #6 
			%s #7 SORT
			%s #8 LIMIT
			%s #9 OFFSET", 
			$sCount,
			$sViewPoperties, 
			$sJoinPoperties, 
			$this->escapeString($sObjectType), 
			$sResultWhere,
			$sGroupByField,
			$sResultSort,
			$sLimit,
			$sOffset
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
					$aValues[] = sprintf('(%d, %s, %s, %s, %s, %d, %d)',
						$iObjectId,
						$this->escapeString($oProperty->Name),
						$this->escapeString($oProperty->Type),
						$oProperty->Type === "string" ? $this->escapeString($oProperty->Value) : 'null',
						$oProperty->Type === "text" ? $this->escapeString($oProperty->Value) : 'null',
						$oProperty->Type === "int" ? $oProperty->Value : 'null',
						$oProperty->Type === "bool" ? $oProperty->Value : 'null'
					);
				}
			}
		}
		if (count($aValues) > 0)
		{
			$sValues = implode(",\r\n", $aValues);
			$sSql = sprintf(
			'INSERT INTO %seav_properties 
				(%s, %s, %s, %s, %s, %s, %s)
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
				$sValues,
				$this->escapeColumn('id_object'), $this->escapeColumn('id_object'), 
				$this->escapeColumn('key'), $this->escapeColumn('key'),
				$this->escapeColumn('type'), $this->escapeColumn('type'),
				$this->escapeColumn('value_string'), $this->escapeColumn('value_string'),
				$this->escapeColumn('value_text'), $this->escapeColumn('value_text'),
				$this->escapeColumn('value_int'), $this->escapeColumn('value_int'),
				$this->escapeColumn('value_bool'), $this->escapeColumn('value_bool')
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
				'DELETE FROM %seav_properties '
				. 'WHERE id_object = %d', 
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
