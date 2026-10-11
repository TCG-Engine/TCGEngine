<?php
// GetPopupContent.php must never show a zone's cards to someone the board itself would not show them to.
// Bug (2026-10-11, measured on a Bot Practice game): it masked a hidden zone only when asked by its "their…"
// name, so seat 1 read the bot's hand as p2Hand, and Deck was not on its hidden list at all — every deck,
// the viewer's own included, came back face up IN DRAW ORDER.
// The popup now follows each zone's schema visibility (Schemas/<sim>/GameSchema.txt), by OWNING SEAT, the
// same rules the board poll uses: Public → everyone; Self → the owner (hands also a caster-mode spectator);
// Private / None → nobody; the undo stack (Versions) → nobody, it holds whole-game snapshots.
// RUN VIA CLI (it calls Apache inside the container; logs in as claudebot1):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_popup_hidden_zones_http.php
error_reporting(E_ALL & ~E_DEPRECATED);
chdir('/var/www/html/TCGEngine');
$B = 'http://localhost/TCGEngine/';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
function phz_req(string $url, string $jar, ?array $post = null, ?string $raw = null): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_TIMEOUT => 60, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($post !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post)); }
    if ($raw !== null) { curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, $raw); }
    $r = curl_exec($ch); curl_close($ch); return (string)$r;
}
$jar = tempnam(sys_get_temp_dir(), 'phz');
phz_req($B . 'AccountFiles/AttemptPasswordLogin.php', $jar, ['submit' => '1', 'userID' => 'claudebot1', 'password' => 'pass']);
$deck = trim(implode("\n", array_filter(explode("\n", file_get_contents('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/darth-vader_jtl_yellow.txt')), fn($l) => !str_starts_with($l, '#'))));
$join = json_decode(phz_req($B . 'APIs/Lobbies/JoinQueue.php', $jar, ['rootName' => 'SWUSim', 'format' => 'botpractice', 'deckLink' => $deck]), true);
$check(!empty($join['gameName']), 'a Bot Practice game was created (seat 2 is a real bot with a real hand and deck)');
if (empty($join['gameName'])) { echo "\nCANNOT CONTINUE\n"; exit(1); }

// The cards a popup shows: [faceUp => [ids], backs => n].
$popup = function (string $game, string $pid, string $key, string $zone) use ($B, $jar) {
    $r = phz_req($B . 'GetPopupContent.php?' . http_build_query(['gameName' => $game, 'playerID' => $pid, 'authKey' => $key, 'folderPath' => 'SWUSim', 'popupType' => $zone]), $jar);
    $body = explode('</>', $r, 2)[1] ?? $r;
    preg_match_all('/\b([A-Z0-9]{2,5}_T?\d{2,3})\b/', $body, $m);
    return ['faceUp' => array_values(array_unique($m[1])), 'backs' => substr_count($body, 'CardBack'), 'raw' => substr($r, 0, 80)];
};
$g = (string)$join['gameName']; $k = (string)$join['authKey'];
$show = fn($p) => count($p['faceUp']) . ' face up, ' . $p['backs'] . ' backs';

// Seat 1 looking at seat 2 — every name for the bot's hand and deck.
foreach (['theirHand', 'p2Hand', 'theirDeck', 'p2Deck'] as $z) {
    $p = $popup($g, '1', $k, $z);
    $check($p['faceUp'] === [] && $p['backs'] > 0, "seat 1 asking $z sees only card backs ({$show($p)})");
}
// Seat 1 looking at its own zones.
foreach (['myHand', 'p1Hand'] as $z) {
    $p = $popup($g, '1', $k, $z);
    $check(count($p['faceUp']) > 0, "seat 1 sees its own hand as $z ({$show($p)})");
}
foreach (['myDeck', 'p1Deck'] as $z) {
    $p = $popup($g, '1', $k, $z);
    $check($p['faceUp'] === [] && $p['backs'] > 0, "seat 1 does not see its OWN deck order as $z — a deck is private to everyone ({$show($p)})");
}
foreach (['myVersions', 'p1Versions', 'theirVersions'] as $z) {
    $p = $popup($g, '1', $k, $z);
    $check($p['faceUp'] === [] && !str_contains($p['raw'], 'Version'), "the undo stack ($z) shows nothing ({$show($p)})");
}
// Public zones stay public.
$p = $popup($g, '1', $k, 'p2Leader');
$check(count($p['faceUp']) > 0, "seat 2's leader (Public) is still shown ({$show($p)})");

// A caster-mode spectator (the .swupgn viewer's seat) still sees hands. (Its decks are card backs by
// construction, so deck order is covered by the Bot Practice checks above.)
$v = json_decode(phz_req($B . 'SWUSim/SwuPgnViewer.php?action=open', $jar, null, file_get_contents('./DevTools/tdd-regression/fixtures/swupgn/viewer-game.swupgn')), true);
$vg = (string)($v['gameName'] ?? ''); $vk = (string)($v['key'] ?? '');
$check($vg !== '', 'a caster-mode spectator game is open');
$p = $popup($vg, 'S', $vk, 'p2Hand');
$check(count($p['faceUp']) > 0, "a caster-mode spectator sees p2Hand ({$show($p)})");

@unlink($jar);
echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
