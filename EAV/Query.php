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

    protected $sCustomViewSql = '';

    protected $aWhere = [];

    protected $aIdOrUuids = [];

    protected $iLimit = 0;

    protected $iOffset = 0;

    protected $mOrderAttributes = [];

    protected $iSortOrder = \Aurora\System\Enums\SortOrder::ASC;

    protected $bCount = false;

    protected $bOnlyUUIDs = false;

    protected $bOne = false;

    protected $bAsArray = false;

    public function __construct($sType = null)
    {
        $this->sType = $sType;
    }

    public function select($aViewAttributes = [])
    {
        $this->aViewAttributes = $aViewAttributes;

        return $this;
    }

    public function customSelect($sSql)
    {
        $this->sCustomViewSql = $sSql;

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

    public function limit($iLimit)
    {
        $this->iLimit = $iLimit;

        return $this;
    }

    public function offset($iOffset)
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

    public function onlyUUIDs()
    {
        $this->bOnlyUUIDs = true;

        return $this;
    }

    public function one()
    {
        $this->bOne = true;

        return $this;
    }

    public function asArray()
    {
        $this->bAsArray = true;

        return $this;
    }

    public function exec()
    {
        $mResult = false;

        if (!$this->bCount)
        {
            if ($this->bOnlyUUIDs)
            {
                $mResult = \Aurora\System\Managers\Eav::getInstance()->getEntitiesUids(
                    $this->sType,
                    $this->iOffset,
                    $this->iLimit,
                    $this->aWhere,
                    $this->mOrderAttributes,
                    $this->iSortOrder,
                    $this->sCustomViewSql
                );
            }
            else
            {
                if (!$this->bAsArray)
                {
                    $mResult = \Aurora\System\Managers\Eav::getInstance()->getEntities(
                        $this->sType,
                        $this->aViewAttributes,
                        $this->iOffset,
                        $this->iLimit,
                        $this->aWhere,
                        $this->mOrderAttributes,
                        $this->iSortOrder,
                        $this->aIdOrUuids,
                        $this->sCustomViewSql
                    );
                }
                else
                {
                    $mResult = \Aurora\System\Managers\Eav::getInstance()->getEntitiesAsArray(
                        $this->sType,
                        $this->aViewAttributes,
                        $this->iOffset,
                        $this->iLimit,
                        $this->aWhere,
                        $this->mOrderAttributes,
                        $this->iSortOrder,
                        $this->aIdOrUuids,
                        $this->sCustomViewSql
                    );
                }
            }

            if ($this->bOne && is_array($mResult) && count($mResult) > 0)
            {
                $mResult = $mResult[0];
            }
        }
        else
        {
            $mResult = \Aurora\System\Managers\Eav::getInstance()->getEntitiesCount(
                $this->sType,
                $this->aWhere,
                $this->aIdOrUuids
            );
        }

        return $mResult;
    }

    public static function prepareWhere($aRawWhere)
    {
        $aWhere = [];

        if (is_array($aRawWhere) && count($aRawWhere) > 0)
        {
            $iAndIndex = 1;
            $iOrIndex = 1;
            foreach ($aRawWhere as $aSubWhere)
            {
                if (is_array($aSubWhere))
                {
                    foreach ($aSubWhere as $sKey => $a2ndSubWhere)
                    {
                        if (is_array($a2ndSubWhere))
                        {
                            $sNewKey = $sKey;
                            if ($sKey === '$AND')
                            {
                                $sNewKey = $iAndIndex.'$AND';
                                $iAndIndex++;
                            }
                            if ($sKey === '$OR')
                            {
                                $sNewKey = $iOrIndex.'$OR';
                                $iOrIndex++;
                            }
                            $aWhere[$sNewKey] = $a2ndSubWhere;
                        }
                    }
                }
            }
        }
        else
        {
            // It's forbidden to request contacts without any filters because in that case all contacts of all users will be returned.
            // If filters are empty, there is no subscribers from modules that describe behaviour of contacts storages.
            throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
        }

        return $aWhere;
    }


}