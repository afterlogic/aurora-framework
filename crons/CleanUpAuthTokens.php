<?php

include_once __DIR__ . '/../autoload.php';

\Aurora\System\Api::Init();

if (PHP_SAPI !== 'cli') {
    \Aurora\System\Api::requireAdminAuth();
}

$iAuthTokenExpiryPeriodDays = \Aurora\Api::GetSettings()->GetValue('AuthTokenExpirationLifetimeDays', 0);
if ($iAuthTokenExpiryPeriodDays > 0) {
    $tokens = \Aurora\System\Api::UserSession()->GetExpiredAuthTokens($iAuthTokenExpiryPeriodDays);
    if ($tokens->count() > 0) {
        echo 'Number of found outdated auth tokens: ' . $tokens->count() . '<br />';
        echo 'Remove outdated auth tokens: <br />';
        \Aurora\System\Api::UserSession()->DeleteExpiredAuthTokens($iAuthTokenExpiryPeriodDays);
    } else {
        echo 'Outdated tokens not found<br />';
    }
}
