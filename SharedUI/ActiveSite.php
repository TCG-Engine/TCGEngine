<?php
// The active site that root SharedUI/ pages render — the site follows the database.
//
// Each app (dev container or prod deployment) connects to its own DB via MYSQL_DATABASE_NAME
// (see Database/ConnectionManager.php), so we resolve the site from that same var. Keying on
// the connection's own var means the rendered site and the connected DB can never disagree,
// and it works everywhere: browser, curl, CLI, and the regression runner alike.
//
// Locally this mirrors the per-port containers (:3100 SWUDeck, :3200 GA, :3300 Azuki,
// :3400 SWUSim). There is no fallback on purpose: an unset or unmapped DB is a
// misconfiguration, and serving the wrong site silently is worse than failing loudly.
$dbToSite = [
    'swudeck'         => 'SWUDeck',
    'grandarchivesim' => 'GrandArchiveSim',
    'azukisim'        => 'AzukiSim',
    'swusim'          => 'SWUSim',
];
$db = getenv('MYSQL_DATABASE_NAME');
if ($db === false || $db === '') {
    throw new RuntimeException('ActiveSite: MYSQL_DATABASE_NAME is not set; cannot resolve the active site.');
}
if (!isset($dbToSite[$db])) {
    throw new RuntimeException("ActiveSite: no site mapped for MYSQL_DATABASE_NAME '$db'; add it to the map in SharedUI/ActiveSite.php.");
}
return $dbToSite[$db];
