<?php
// GET → the signed-in player's hearted SWUStats decks for the deck-source toggle
// (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2).
// {status: ok|unlinked|relink|unavailable, decks: [SWUSetupSavedDecks-shaped rows], swustatsUrl}
ob_start();   // a stray notice from the include chain must not corrupt the JSON
require_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';
require_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';   // GLOBAL scope: SWUStatsCardToSimId reads $cardUUIDData
require_once __DIR__ . '/Custom/SetupPanels.php';                        // SWUSetupCardLabel, for unnamed decks
require_once __DIR__ . '/SWUStatsDeckList.php';

CheckSession();
$uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
session_write_close();   // a slow SWUStats must not hold this player's session lock
$res = SWUStatsHeartedDecks($uid, !empty($_GET['refresh']));
$res['swustatsUrl'] = SWUStatsPublicBase() . '/TCGEngine/SharedUI/MainMenu.php';
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/json');
header('Cache-Control: no-store');
echo json_encode($res);
