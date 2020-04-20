<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers\Db\CommandCreator;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class MySQL extends \Aurora\System\Db\AbstractCommandCreator
{
    public function columnExists($sTable, $sColumn)
    {
        $sTable = $this->prefix() . $sTable;
        return sprintf("SELECT count(*) as cnt
        FROM information_schema.columns
        WHERE table_schema = database()
            AND COLUMN_NAME = %s AND table_name = %s", $this->escapeString($sColumn), $this->escapeString($sTable));
    }

}
