<?php

// Cron entry point for hosted Card Code workspaces. Example (UTC midnight):
// 0 0 * * * php /var/www/html/TCGEngine/DevTools/CardCodeDailyCheckpoint.php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../CardEditor/Database/CardCodeServiceDB.php';

$requestedRoots = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--root=')) $requestedRoots[] = substr($arg, 7);
}
$conn = GetLocalMySQLConnection();
$service = new CardCodeServiceDB($conn);
if (!$requestedRoots) {
    $result = mysqli_query($conn, 'SELECT DISTINCT root_name FROM card_abilities ORDER BY root_name ASC');
    while ($result && ($row = mysqli_fetch_assoc($result))) $requestedRoots[] = $row['root_name'];
    if ($result) mysqli_free_result($result);
}
$failed = 0;
foreach (array_unique($requestedRoots) as $root) {
    try {
        $checkpoint = $service->checkpoint($root, 'daily-cron');
        $verb = $checkpoint['created'] ? 'created' : ($checkpoint['unchanged'] ? 'unchanged' : 'already exists');
        echo $root . ': ' . $verb . ' (' . $checkpoint['abilityCount'] . " abilities)\n";
    } catch (Throwable $error) {
        ++$failed;
        fwrite(STDERR, $root . ': ' . $error->getMessage() . "\n");
    }
}
mysqli_close($conn);
exit($failed === 0 ? 0 : 1);
