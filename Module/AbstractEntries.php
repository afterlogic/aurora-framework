<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
abstract class AbstractEntries
{
    protected AbstractModule $module;

    protected $entries = [];

    public function __construct(AbstractModule $module)
    {
        $this->module = $module;
    }

    final public function init()
    {
        if ($this->entries) {
            \Aurora\System\Router::getInstance()->registerArray(
                $this->module->getModuleName(),
                $this->entries
            );
        }
    }
}
