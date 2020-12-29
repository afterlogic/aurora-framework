<?php

include_once '../autoload.php';

\Aurora\System\Api::Init();

if (PHP_SAPI !== 'cli')
{
	\Aurora\System\Api::requireAdminAuth();
}

$aAuthIds = [];
$iAuthTokenExpiryPeriodDays = 30;
$aTokens = \Aurora\System\Api::UserSession()->GetExpiredAuthTokens($iAuthTokenExpiryPeriodDays);

if (is_array($aTokens) && count($aTokens) > 0)
{
    echo 'Number of found outdated auth tokens: ' . count($aTokens) . '<br />';
    echo 'Remove outdated auth tokens: <br />';
    foreach ($aTokens as $oToken)
    {
        echo $oToken->Token . '<br />';
        $aAuthIds[] = $oToken->EntityId;
    }
}
else
{
    echo 'Outdated tokens not found<br />';
}

\Aurora\System\Managers\Eav::getInstance()->deleteEntities($aAuthIds);
