<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\EAV;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class Query
{
    protected $sType =  null;

    protected $aViewAttributes = [];

    protected $aWhere = [];

    protected $aIdOrUuids = [];

    protected $iLimit = 0;

    protected $iOffset = 0;

    protected $mOrderAttributes = [];

    protected $iSortOrder = \Aurora\System\Enums\SortOrder::ASC;

    protected $bCount = false;

    public function select($aViewAttributes = [])
    {
        $this->aViewAttributes = $aViewAttributes;

        return $this;
    }

    public function whereType($sType)
    {
        $this->sType = $sType;

        return $this;
    }

    public function where($aWhere)
    {
        $this->aWhere = $aWhere;

        return $this;
    }

    public function whereIdOrUuidIn($aIdOrUuids)
    {
        $this->aIdOrUuids = $aIdOrUuids;

        return $this;
    }

    public function offset($iLimit)
    {
        $this->iLimit = $iLimit;

        return $this;
    }

    public function limit($iOffset)
    {
        $this->iOffset = $iOffset;

        return $this;
    }

    public function orderBy($mOrderAttributes)
    {
        $this->mOrderAttributes = $mOrderAttributes;

        return $this;
    }    

    public function sortOrder($iSortOrder)
    {
        $this->iSortOrder = $iSortOrder;

        return $this;
    }    

    public function asc()
    {
        $this->iSortOrder = \Aurora\System\Enums\SortOrder::ASC;

        return $this;
    }

    public function desc()
    {
        $this->iSortOrder = \Aurora\System\Enums\SortOrder::DESC;

        return $this;
    }

    public function count()
    {
        $this->bCount = true;

        return $this;
    }

    public function exec()
    {
        if (!$this->bCount)
        {
            return \Aurora\System\Managers\Eav::getInstance()->getEntities(
                $this->sType, 
                $this->aViewAttributes,
                $this->iOffset, 
                $this->iLimit, 
                $this->aWhere,
                $this->mOrderAttributes, 
                $this->iSortOrder,
                $this->aIdOrUuids
            );
        }
        else
        {
            return \Aurora\System\Managers\Eav::getInstance()->getEntitiesCount(
                $this->sType, 
                $this->aWhere,
                $this->aIdOrUuids
            );
        }
    }

}