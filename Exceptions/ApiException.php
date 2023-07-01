<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Exceptions;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @category Core
 * @package Exceptions
 */
class ApiException extends Exception
{
    /**
     * @var array
     */
    protected $aObjectParams;


    /**
     * @var \Aurora\System\Module\AbstractModule
     */
    protected $oModule;

    /**
     * ApiException constructor.
     *
     * @param null|int $iCode
     * @param null|\Throwable $oPrevious
     * @param null|string $sMessage
     * @param null|array $aObjectParams
     * @psalm-param null|array<string, string|int|mixed> $aObjectParams
     * @param null|\Aurora\System\Module\AbstractModule $oModule
     */
    public function __construct($iCode, $oPrevious = null, $sMessage = '', $aObjectParams = array(), $oModule = null)
    {
        $this->aObjectParams = $aObjectParams;
        $this->oModule = $oModule;
        $mCode = is_int($iCode) ? $iCode : 0;
        parent::__construct('' === $sMessage ? 'ApiException' : $sMessage, $mCode, $oPrevious);
    }

    /**
     * @return array
     */
    public function GetObjectParams()
    {
        return $this->aObjectParams;
    }

    /**
     * @return \Aurora\System\Module\AbstractModule
     */
    public function GetModule()
    {
        return $this->oModule;
    }
}
