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
	public function existObject($iObjectId)
	{
		$sSql = 'SELECT COUNT(id) as objects_count FROM %seav_objects WHERE %s = %d';

		return sprintf($sSql, $this->prefix(),
			$this->escapeColumn('id'), $this->escapeString(strtolower($iObjectId))
		);
	}

	/**
	 * @return string
	 */
	public function createObject($sModule, $sType)
	{
		$sSql = 'INSERT INTO %seav_objects ( %s, %s ) VALUES ( %s, %s )';
		
		return sprintf($sSql, $this->prefix(),
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
		$sSql = 'DELETE FROM %seav_objects WHERE id = %d';

		return sprintf($sSql, $this->prefix(), $iId);
	}	
	
	public function getObjectById($iId)
	{
		$sSql = "
			SELECT 	   
				objects.id as obj_id, 
				objects.object_type as obj_type, 
				objects.module_name as obj_module,
				props.id, props.key as prop_key, props.value as prop_value
			FROM %seav_properties as props
				  RIGHT JOIN %seav_objects as objects
					ON objects.id = props.id_object

			WHERE objects.id = %d;";
		
		return sprintf($sSql, $this->prefix(), $this->prefix(), $iId);
	}
	
	public function getObjects($sObjectType, $aViewProperties = array(), 
			$iPage = 0, $iPerPage = 20, $aSearchProperties = array(), 
			$sSortProperty = "", $iSortOrder = \ESortOrder::ASC)
	{
		$mResult = false;
		
		$sViewPoperties = "";
		$sJoinPoperties = "";
		$sResultSearchProperties = "";
		$sResultSort = "";
		
		$aResultViewProperties = array();
		$aJoinProperties = array();
		$aResultSearchProperties = array();
		
		if (!empty($sSortProperty))
		{
			$aViewProperties[] = $sSortProperty;
			$sResultSort = sprintf(" ORDER BY prop_%s %s", $sSortProperty, $iSortOrder === \ESortOrder::ASC ? "ASC" : "DESC");
		}
		$aViewProperties = array_unique(array_merge($aViewProperties, array_keys($aSearchProperties)));

		foreach ($aViewProperties as $sProperty)
		{
			$aResultViewProperties[$sProperty] = sprintf("props_%s.value as prop_%s", $sProperty, $sProperty);
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
		foreach ($aSearchProperties as $sKey => $sValue)
		{
			$aResultSearchProperties[] = sprintf("props_%s.value = %s", 
					$sKey, $this->escapeString($sValue));
		}
		if (0 < count($aSearchProperties))
		{
			$sResultSearchProperties = ' AND ' . implode(' AND ', $aResultSearchProperties);
		}
		
		$sSql = sprintf("SELECT 	   
			objects.id as obj_id, 
			objects.object_type as obj_type, 
			objects.module_name as obj_module
			# fields
			%s #1
			# ------
			FROM eav_properties as props

			  RIGHT JOIN eav_objects as objects
				ON objects.id = props.id_object
			# fields
			%s #2
			# ------
			WHERE objects.object_type = %s #3
			%s #4
			GROUP BY obj_id 
			%s #5
			LIMIT %s
			OFFSET %s", 
			$sViewPoperties, 
			$sJoinPoperties, 
			$this->escapeString($sObjectType), 
			$sResultSearchProperties,
			$sResultSort,
			$iPerPage,
			($iPage > 0) ? ($iPage - 1) * $iPerPage : 0
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
		$aResults = api_AContainer::DbInsertArrays($oProperty, $this->oHelper);

		if ($aResults[0] && $aResults[1])
		{
			$sSql = 'INSERT INTO %seav_properties ( %s ) VALUES ( %s )';
			return sprintf($sSql, $this->prefix(), implode(', ', $aResults[0]), implode(', ', $aResults[1]));
		}

		return '';
	}	
	
	/**
	 * @param CProperty $oProperty
	 *
	 * @return string
	 */
	public function updateProperty(CProperty $oProperty)
	{
		$sSql = 'UPDATE %seav_properties SET %s = %s WHERE %s = %d AND %s = %s';
		return sprintf($sSql, $this->prefix(), 
			$this->escapeColumn('value'), $this->escapeString($oProperty->Value),
			$this->escapeColumn('id_object'), $oProperty->ObjectId,
			$this->escapeColumn('key'), $this->escapeString($oProperty->Name)
		);
	}	
	
	/**
	 * @param $oProperty
	 *
	 * @return string
	 */
	public function deleteProperty(CProperty $oProperty)
	{
		$sSql = 'DELETE FROM %seav_properties WHERE id = %d';

		return sprintf($sSql, $this->prefix(), $oProperty->Id);
	}
	
	/**
	 * @param $oProperty
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
		return sprintf('SELECT COUNT(id) as properties_count FROM %seav_properties WHERE %s = %s and %s = %s', 
				$this->prefix(),
				$this->escapeColumn('id_object'), $this->escapeString(strtolower($iObjectId)),
				$this->escapeColumn('key'), $this->escapeString(strtolower($sPropertyName))
		);
	}
	
	/**
	 * @param string $sWhere
	 *
	 * @return string
	 */
	protected function getPropertyByWhere($sWhere)
	{
		$aMap = api_AContainer::DbReadKeys(CProperty::getStaticMap());
		$aMap = array_map(array($this, 'escapeColumn'), $aMap);

		$sSql = 'SELECT %s FROM %seav_properties WHERE %s';

		return sprintf($sSql, implode(', ', $aMap), $this->prefix(), $sWhere);
	}
	
	/**
	 * @param int $iDomainId
	 *
	 * @return string
	 */
	public function getProperty($iObjectId, $sPropertyName)
	{
		return $this->gePropertyByWhere(sprintf('%s = %d AND %s = %s', 
				$this->escapeColumn('id_object'), $iObjectId,
				$this->escapeColumn('key'), $sPropertyName)
		);
	}
	
	/**
	 * @param int $iObjectId
	 *
	 * @return string
	 */
	public function getProperties($iObjectId, $sPropertyValue = '')
	{
		$sFilter = '1 = 1';
		if (!empty($sPropertyValue))
		{
			$sFilter = sprintf('%s LIKE %s', 
				$this->escapeColumn('value'), '%' . $sPropertyValue . '%');
		}
		
		return $this->gePropertyByWhere(sprintf('%s = %d AND %s', 
				$this->escapeColumn('id_object'), $iObjectId, $sFilter)
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
