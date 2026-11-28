<?php
// 4P Always Two — what each screen receives (plan 2026-11-21-swusim-always-two-4p Task 5; spec "## 4P Always Two" → "Shared hands").
//  • A teammate's HAND reaches its partner's screen face-up — never resources, never the opponents' hands.
//  • Game variables are echoed to EVERY screen, so the vote's private records (SWU_PRIVATE_ keys: the pending proposal, the blind
//    start votes) must be stripped from every poll — or an opponent's browser receives which card the team is about to play.
// Controls: Team Suns shares no hands; a spectator sees what it saw before.
// HTTP (needs the web server): docker exec otmtcge-swusim-web-server-1 curl -s http://localhost/TCGEngine/SWUSim/DevTools/tests/alwaystwo4p_teammate_hand_test.php
// ⚠ The poll is GENERATED (SWUSim/GetNextTurn.php from zzGameCodeGenerator.php) — regenerate SWUSim if this fails after a pull.
header('Content-Type: text/plain');
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$B = 'http://localhost/TCGEngine/';
$jar = tempnam(sys_get_temp_dir(), 'a2h');
function hit($url, $params, $jar) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => http_build_query($params), CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 120, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    $r = curl_exec($ch); curl_close($ch);
    return (string)$r;
}
hit($B . 'AccountFiles/AttemptPasswordLogin.php', ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass'], $jar);
$GAMES = [];
register_shutdown_function(function () use (&$GAMES, $jar) {
    foreach ($GAMES as $g) {
        $dir = dirname(__DIR__, 2) . '/Games/' . preg_replace('/[^0-9A-Za-z_-]/', '', $g);
        if (is_dir($dir)) { array_map('unlink', glob($dir . '/*') ?: []); @rmdir($dir); }
    }
    @unlink($jar);
});
$seed = function (string $schema, array $steps = []) use ($B, $jar, &$GAMES) {
    $j = json_decode(hit($B . 'SWUSim/TestSchemaSetup.php', ['schema' => $schema], $jar), true);
    $g = strval($j['gameName'] ?? '');
    if ($g !== '') $GAMES[] = $g;
    foreach ($steps as $st) hit($B . 'SWUSim/TestSchemaStep.php', ['gameName' => $g, 'step' => $st], $jar);
    return $g;
};
$poll = function (string $g, $seat) use ($B) {
    $ch = curl_init($B . 'SWUSim/GetNextTurn.php?' . http_build_query(['gameName' => $g, 'playerID' => $seat, 'authKey' => 'testschema',
                                                                       'folderPath' => 'SWUSim', 'lastUpdate' => 0]));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60]);
    $r = curl_exec($ch); curl_close($ch);
    return (string)$r;
};
$faceUp = fn(string $view, string $zone, int $b) => preg_match_all('/"Location":"' . $zone . '","PlayerID":' . $b . '[,}]/', $view);

$given = fn(string $mode) => "# T\n## GIVEN\nCommonSetup4P: bbk/bbk/bbk/bbk\nSkipPreGame: true\n{$mode}\nWithActivePlayer: 1\nWithGamePhase: ActionPhase\n"
    . "WithP1Resources: 6\nWithP3Resources: 4\nWithP1Hand: [SOR_095 SOR_095]\nWithP2Hand: [SOR_095 SOR_095 SOR_095]\n"
    . "WithP3Hand: [SOR_098 SOR_128 SOR_225 SOR_046]\nWithP4Hand: [SOR_095]\n## WHEN\n## EXPECT\nSEATCOUNT:4\n";

echo "TeammateHandsFaceUp:\n";
$g = $seed($given('WithAlwaysTwo: 4p'));
$check($g !== '', "seeded a 4P Always Two game ($g)");
$v1 = $poll($g, 1); $v3 = $poll($g, 3); $v2 = $poll($g, 2);
$check($faceUp($v1, 'Hand', 3) === 4, "seat 1 sees teammate board 3's hand face-up (4 cards; got " . $faceUp($v1, 'Hand', 3) . ')');
$check($faceUp($v3, 'Hand', 1) === 2, "seat 3 sees teammate board 1's hand face-up (2; got " . $faceUp($v3, 'Hand', 1) . ')');
$check($faceUp($v1, 'Hand', 2) === 0 && $faceUp($v1, 'Hand', 4) === 0, 'seat 1 never sees the opponents\' hands');
$check($faceUp($v2, 'Hand', 1) === 0 && $faceUp($v2, 'Hand', 3) === 0, 'seat 2 never sees Red\'s hands');
$check($faceUp($v2, 'Hand', 4) === 1, 'seat 2 sees its own teammate board 4\'s hand (1)');
$check($faceUp($v1, 'Resources', 1) === 6, "control: seat 1 sees its OWN 6 resources face-up (got " . $faceUp($v1, 'Resources', 1) . ')');
$check($faceUp($v1, 'Resources', 3) === 0, "seat 1 does NOT see board 3's resources (got " . $faceUp($v1, 'Resources', 3) . ')');
$check($faceUp($v1, 'Hand', 1) === 2, 'seat 1 still sees its own hand');

echo "ProposalNeverReachesAnyScreen:\n";
$g = $seed($given('WithAlwaysTwo: 4p'), ['- P1>PlayHand:0']);
foreach ([1, 2, 3, 4] as $s) {
    $v = $poll($g, $s);
    $check($v !== '' && strpos($v, 'SWU_PRIVATE_') === false, "seat $s's poll carries no SWU_PRIVATE_ record");
}
$check(strpos($poll($g, 3), 'wants_to_play') !== false || strpos($poll($g, 3), 'wants to play') !== false, "the partner's poll does carry its own prompt");
$check(strpos($poll($g, 2), 'wants_to_play') === false && strpos($poll($g, 2), 'wants to play') === false, "an opponent's poll does not carry the proposal");

echo "Control_TeamSuns_NoSharedHands:\n";
$g = $seed($given('WithTeams: true'));
$check($faceUp($poll($g, 1), 'Hand', 3) === 0, 'Team Suns: seat 1 does not see teammate seat 3\'s hand');

echo "Control_Spectator:\n";
$g = $seed($given('WithAlwaysTwo: 4p'));
$vs = $poll($g, 'S');
$check($faceUp($vs, 'Hand', 1) === 0 && $faceUp($vs, 'Hand', 3) === 0, 'a spectator sees no hand face-up');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
