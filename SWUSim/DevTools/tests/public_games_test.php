<?php
// Games in Progress (owner, 2026-09-21) — the main menu's left panel.
//   A. SWUPublicGamesList() (SWUSim/PublicGames.php): which games are listed, and what each carries. Pure, so it is
//      driven with synthetic index/ref/match data — every exclusion rule has its own decoy.
//   B. Guests may spectate PUBLIC SWUSim games (Core/GameAuth.php SimGameValidateSpectatorAuth), tested with dev mode
//      OFF, because local dev lets every viewer spectate and so cannot tell an open gate from a closed one. Game auth
//      records live in APCu, so B registers two throwaway games (public + private) in the CLI's OWN APCu — hence
//      apc.enable_cli=1. (Flipping DEVENV inside a web request instead would leak into that worker's later requests.)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 SWUSim/DevTools/tests/public_games_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/PublicGames.php';
$fails = 0;
$check = function ($ok, $msg, $detail = '') use (&$fails) {
    echo ($ok ? 'PASS' : 'FAIL') . ": $msg" . (!$ok && $detail !== '' ? "  [got: $detail]" : '') . "\n";
    if (!$ok) $fails++;
};

// ── A. The list ──────────────────────────────────────────────────────────────────────────────────────
$now = 1000000;
$idx = function ($game, $ago, $private = false, $root = 'SWUSim') use ($now) {
    return ['rootName' => $root, 'gameName' => $game, 'isPrivate' => $private, 'lastUpdatedAt' => $now - $ago, 'createdAt' => $now - 5000];
};
$deck = fn($leader, $base) => ['originalDeck' => ['leader' => $leader, 'base' => $base]];
$index = [
    'a' => $idx('g_premier', 10),                 // listed
    'b' => $idx('g_twin', 5),                     // listed (newest)
    'c' => $idx('g_team', 20),                    // listed
    'd' => $idx('g_idx_private', 1, true),        // private in the index
    'e' => $idx('g_auth_private', 1),             // private by its auth record
    'f' => $idx('g_solo', 1),                     // no match (Goldfish / Hotseat / Arenabot)
    'g' => $idx('g_match_private', 1),            // private match
    'h' => $idx('g_over', 1),                     // match already decided
    'i' => $idx('g_old_game', 1),                 // game 1 of a Bo3 that has moved on to game 2
    'j' => $idx('g_stale', 1801),                 // not touched in 30 minutes
    'k' => $idx('g_other_sim', 1, false, 'GrandArchiveSim'),
];
$refs = [
    'g_premier' => ['matchId' => 'M1', 'gameNumber' => 1], 'g_twin' => ['matchId' => 'M2', 'gameNumber' => 1],
    'g_team' => ['matchId' => 'M3', 'gameNumber' => 1], 'g_idx_private' => ['matchId' => 'M1', 'gameNumber' => 1],
    'g_auth_private' => ['matchId' => 'M1', 'gameNumber' => 1], 'g_match_private' => ['matchId' => 'M4', 'gameNumber' => 1],
    'g_over' => ['matchId' => 'M5', 'gameNumber' => 1], 'g_old_game' => ['matchId' => 'M6', 'gameNumber' => 1],
    'g_stale' => ['matchId' => 'M1', 'gameNumber' => 1], 'g_other_sim' => ['matchId' => 'M1', 'gameNumber' => 1],
];
$matches = [
    'M1' => ['format' => 'premier', 'isPrivate' => false, 'state' => 'in_progress', 'currentGameNumber' => 1,
             'players' => ['2' => $deck('LAW_004', 'JTL_026'), '1' => $deck('SOR_005', 'SOR_019')]],
    'M2' => ['format' => 'twinsuns', 'isPrivate' => false, 'state' => 'in_progress', 'currentGameNumber' => 1,
             'players' => ['1' => $deck(['SEC_002', 'SHD_002'], 'SHD_026'), '2' => $deck(['TWI_004', 'SOR_014'], 'TWI_027'),
                           '3' => $deck(['LOF_012', 'SOR_005'], 'LOF_019')]],
    'M3' => ['format' => 'teamsuns', 'isPrivate' => false, 'state' => 'in_progress', 'currentGameNumber' => 1,
             'players' => ['1' => $deck(['SEC_002', 'SHD_002'], 'SHD_026'), '2' => $deck(['TWI_004', 'SOR_014'], 'TWI_027'),
                           '3' => $deck(['LOF_012', 'SOR_005'], 'LOF_019'), '4' => $deck(['LAW_004', 'JTL_005'], 'JTL_026')]],
    'M4' => ['format' => 'premier', 'isPrivate' => true, 'state' => 'in_progress', 'currentGameNumber' => 1, 'players' => ['1' => $deck('SOR_005', 'SOR_019'), '2' => $deck('LAW_004', 'JTL_026')]],
    'M5' => ['format' => 'premier', 'isPrivate' => false, 'state' => 'complete', 'currentGameNumber' => 1, 'players' => ['1' => $deck('SOR_005', 'SOR_019'), '2' => $deck('LAW_004', 'JTL_026')]],
    'M6' => ['format' => 'premier', 'isPrivate' => false, 'state' => 'in_progress', 'currentGameNumber' => 2, 'players' => ['1' => $deck('SOR_005', 'SOR_019'), '2' => $deck('LAW_004', 'JTL_026')]],
];
$out = SWUPublicGamesList($index, $now,
    fn($g) => $refs[$g] ?? null,
    fn($id) => $matches[$id] ?? null,
    fn($g) => $g === 'g_auth_private',
    fn($cid) => "Name of $cid");
$names = array_map(fn($g) => $g['gameName'], $out['games']);
$check($names === ['g_twin', 'g_premier', 'g_team'], 'lists exactly the public, current, in-progress SWUSim match games, newest first', implode(',', $names));
$check($out['count'] === 3, 'count matches the list');
$check(array_map(fn($f) => $f['id'], $out['formats']) === ['premier', 'teamsuns', 'twinsuns'], 'formats: one per format present, sorted by name',
    json_encode($out['formats']));
$p = $out['games'][1];
$check(array_map(fn($s) => $s['seat'], $p['seats']) === [1, 2], '1v1: seats in seat order');
$check($p['seats'][0]['leaders'][0]['id'] === 'SOR_005' && count($p['seats'][0]['leaders']) === 1 && $p['seats'][0]['base']['id'] === 'SOR_019',
    '1v1: one leader + the base per seat', json_encode($p['seats'][0]));
$check($p['seats'][0]['leaders'][0]['name'] === 'Name of SOR_005', 'cards carry their display name');
$check(str_ends_with($p['seats'][0]['leaders'][0]['url'], '/WebpImages/SOR_005.webp'), 'cards carry their art URL', $p['seats'][0]['leaders'][0]['url']);
$check($p['formatName'] === 'Premier' && $p['isTeam'] === false, '1v1: format display name, not a team game');
$check($p['spectateUrl'] === '/TCGEngine/NextTurn.php?playerID=S&gameName=g_premier&folderPath=SWUSim', 'spectate URL', $p['spectateUrl']);
$t = $out['games'][0];
$check(array_map(fn($s) => count($s['leaders']), $t['seats']) === [2, 2, 2], 'Twin Suns: two leaders per seat, three seats');
$check(array_map(fn($s) => $s['team'], $t['seats']) === [0, 0, 0], 'Twin Suns free-for-all: no teams');
$m = $out['games'][2];
$check($m['isTeam'] === true && array_map(fn($s) => $s['team'], $m['seats']) === [1, 2, 1, 2], 'Team Suns: seats 1+3 vs 2+4',
    json_encode(array_map(fn($s) => $s['team'], $m['seats'])));

// ── B. Guests may spectate a public SWUSim game ──────────────────────────────────────────────────────
require_once './Core/NetworkingLibraries.php';
$check(function_exists('apcu_enabled') && apcu_enabled(), 'setup: APCu is on in this CLI (run with -d apc.enable_cli=1)');
$tag = 'pgtest' . bin2hex(random_bytes(4));
$made = [];
foreach (['pub' => false, 'priv' => true] as $kind => $private) {
    $name = $tag . $kind;
    @mkdir("./SWUSim/Games/$name", 0777, true);   // SimGameHasAuthKeys also wants the game directory
    $made[] = $name;
    SimGameWriteAuthKeys('SWUSim', $name, ['p1' => 'k1', 'p2' => 'k2', 'spectator' => $private ? 'spec-secret' : '', 'isPrivate' => $private]);
}
putenv('DEVENV=false');
$_SERVER['HTTP_HOST'] = 'swustats.net';
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
unset($_SESSION['useruid'], $_SESSION['userid']);
try {
    [$pub, $priv] = $made;
    $check(!SimGameIsDevelopmentEnvironment(), 'setup: dev mode is OFF for the auth checks');
    $check(SimGameHasAuthKeys('SWUSim', $pub) && !SimGameIsPrivateGame('SWUSim', $pub), 'setup: a managed PUBLIC game');
    $check(SimGameValidateSpectatorAuth('SWUSim', $pub, '') === true, 'a logged-out spectator may watch a public SWUSim game');
    $check(SimGameValidateViewerAuth('SWUSim', $pub, ['isSpectator' => true, 'viewerSeat' => 0], '') === true,
        'the viewer gate NextTurn/GetNextTurn use agrees');
    $check(SimGameIsPrivateGame('SWUSim', $priv), 'setup: a managed PRIVATE game');
    $check(SimGameValidateSpectatorAuth('SWUSim', $priv, '') === false, 'a private game still refuses a spectator without its key');
    $check(SimGameValidateSpectatorAuth('SWUSim', $priv, 'spec-secret') === true, 'a private game admits its spectator key');
} finally {
    putenv('DEVENV=true');
    foreach ($made as $name) { SimGameDeleteAuthKeys('SWUSim', $name); @rmdir("./SWUSim/Games/$name"); }
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails ? 1 : 0);
