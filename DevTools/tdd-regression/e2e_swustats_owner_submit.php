<?php
// RUN VIA CLI. Live owner-stats check (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §3): the
// PRODUCTION per-game submit, a REAL token from claudebot1's link, the REAL local SubmitGameResult.
// Needs: claudebot1 (usersId 6) linked (DevTools/ui-harness/swusim-swustats-link-e2e.mjs with KEEP_LINK=1), and the
// sentinel deck 990001 owned by Drixx with TEAM visibility (1001) → SubmitGameResult disables meta stats and skips
// completedgame for it, so nothing but sentinel-keyed rows is written. See the plan, Task 8 Step 4, for setup + cleanup.
// Run: docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php DevTools/tdd-regression/e2e_swustats_owner_submit.php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
chdir(dirname(__DIR__, 2));
include './SWUSim/StatsSubmit.php';
$url = SWUStatsServerBase() . '/TCGEngine/APIs/SubmitGameResult.php';
$link = SWUStatsDeckLinkBase() . '/TCGEngine/NextTurn.php?gameName=990001&folderPath=SWUDeck';
$game = ['gameName' => 'E2E-OWNER-' . time(), 'gameNumber' => 1, 'winner' => 1,
    'detail' => ['firstPlayer' => 1, 'turns' => 6, 'leader' => ['1' => 'SOR_005', '2' => 'SOR_010'],
        'base' => ['1' => 'SOR_027', '2' => 'SOR_027'], 'baseHpLeft' => ['1' => 10, '2' => 0],
        'telemetry' => ['cards' => [], 'turns' => []]]];
$m = fn($uid) => ['format' => 'premier', 'players' => ['1' => ['userId' => $uid, 'deckLink' => $link], '2' => ['userId' => null, 'deckLink' => '']]];
$apiKey = $GLOBALS['petranakiAPIKey'] ?? ($GLOBALS['karabastAPIKey'] ?? '');
echo 'linked seat (claudebot1): ' . SWUSubmitOneGame($m(6), $game, $url, $apiKey) . "\n";
$game['gameName'] .= '-B';
echo 'unlinked seat (claudebot2): ' . SWUSubmitOneGame($m(7), $game, $url, $apiKey) . "\n";
