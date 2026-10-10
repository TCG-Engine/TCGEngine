<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_viewer_api.php
//
// The SWU-PGN replay viewer endpoint (SWUSim/SwuPgnViewer.php): open / info / seek, the viewer game it
// writes, refusals, and the 24-hour cleanup that must only ever remove VIEWER folders.
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';
$fails = 0;
$check = function (bool $ok, string $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$post = function (string $path, string $body, string $type = 'text/plain') {
    $ch = curl_init(SWUCHAT_BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 60, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => ["Content-Type: $type"]]);
    $r = (string)curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$code, json_decode($r, true), $r];
};
$fx = __DIR__ . '/fixtures/swupgn/';
$games = dirname(__DIR__, 2) . '/SWUSim/Games';

// Cleanup: an old viewer folder goes, an old normal game folder stays.
@mkdir("$games/990000001", 0777, true); file_put_contents("$games/990000001/Viewer.json", '{}'); touch("$games/990000001/Viewer.json", time() - 90000);
@mkdir("$games/990000002", 0777, true); file_put_contents("$games/990000002/Gamestate.txt", 'x'); touch("$games/990000002/Gamestate.txt", time() - 90000); touch("$games/990000002", time() - 90000);

@unlink("$games/SwuPgnViewers.sweep");   // the full folder sweep runs at most once a day; make this open run it
[$code, $open, $raw] = $post('SWUSim/SwuPgnViewer.php?action=open', file_get_contents($fx . 'viewer-game.swupgn'));
$check($code === 200 && ($open['success'] ?? false) && preg_match('/^\d+$/', (string)($open['gameName'] ?? '')) === 1, 'open: a valid file creates a viewer game' . ($code !== 200 ? " (HTTP $code: " . substr($raw, 0, 200) . ')' : ''));
$check(!is_dir("$games/990000001") && is_dir("$games/990000002"), 'open: cleanup removed the old VIEWER folder and kept the normal game');
@unlink("$games/990000002/Gamestate.txt"); @rmdir("$games/990000002");
$meta = $open['meta'] ?? [];
$check(count($meta['steps'] ?? []) > 10 && array_key_exists('perspective', $meta) && $meta['perspective'] === null && ($meta['view'] ?? '') === 'both', 'open: steps, all-seeing file, view both');
$check(str_contains((string)($open['viewUrl'] ?? ''), 'playerID=S') && str_contains((string)($open['viewUrl'] ?? ''), 'swupgnViewer=1'), 'open: viewUrl opens the board as a spectator in viewer mode');
$gn = (string)($open['gameName'] ?? ''); $key = (string)($open['key'] ?? '');
$check(isset((json_decode((string)@file_get_contents("$games/SwuPgnViewers.json"), true)['games'] ?? [])[$gn]), 'open: the viewer game is registered in the limits index');

$poll = fn(string $g, string $k, int $persp = 1) => swuchat_http('SWUSim/GetNextTurn.php?' . http_build_query(['gameName' => $g, 'playerID' => 'S', 'authKey' => $k, 'viewerPerspective' => $persp, 'lastUpdate' => 0, 'lastChatVersion' => 0, 'lastChatID' => 0]));
$last = max(0, count($meta['steps'] ?? []) - 1);
[$code, $seek] = $post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $gn, 'key' => $key, 'step' => $last]), 'application/json');
$check($code === 200 && ($seek['meta']['step'] ?? -1) === $last, 'seek: lands on the requested step');
$board = $poll($gn, $key);
$check(count(explode('<~>', $board)) > 31, 'poll: the spectator sees a full board');
$check(str_contains($board, 'JTL_095') && str_contains($board, 'SWUPGNX_'), 'poll: mapped cards and the placeholder are on the board');
$check(!preg_match('/Deprecated|Warning:|Notice:|Fatal error/', $board), 'poll: no PHP notice leaks into the payload (placeholder cards are tolerated)');
[$code, $past] = $post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $gn, 'key' => $key, 'step' => 99999]), 'application/json');
$check(($past['meta']['step'] ?? -1) === $last, 'seek: past the end clamps to the last step');
[$code] = $post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $gn, 'key' => 'wrong', 'step' => 0]), 'application/json');
$check($code === 403, 'seek: a wrong key is refused');
$actRaw = swuchat_http('ProcessInput.php?' . http_build_query(['gameName' => $gn, 'playerID' => '1', 'authKey' => $key, 'folderPath' => 'SWUSim', 'mode' => 10002, 'cardID' => 'myHand-0!FSM!', 'responseFormat' => 'json']));
$check(str_contains($actRaw, 'replay viewer'), 'ProcessInput: game input is refused in a viewer game (' . substr($actRaw, 0, 120) . ')');
$info = json_decode(swuchat_http('SWUSim/SwuPgnViewer.php?' . http_build_query(['action' => 'info', 'gameName' => $gn, 'key' => $key])), true);
$check(($info['meta']['step'] ?? -1) === $last && ($info['meta']['names'] ?? []) !== [], 'info: returns the current step and the placeholder names');

// Perspective file: fixed view; P2's hand stays backs although the file names those cards.
[, $p1] = $post('SWUSim/SwuPgnViewer.php?action=open', file_get_contents($fx . 'viewer-game-p1.swupgn'));
$check(($p1['meta']['perspective'] ?? null) === 'P1' && ($p1['meta']['view'] ?? '') === 'p1', 'open: a P1 perspective file is fixed to P1\'s view');
[, $p1seek] = $post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $p1['gameName'] ?? '', 'key' => $p1['key'] ?? '', 'step' => 3, 'view' => 'both']), 'application/json');
$check(($p1seek['meta']['view'] ?? '') === 'p1', 'seek: a perspective file ignores a view change');
// Captions follow the view: in a seat's view the other seat's resources and draws name no card.
$captions = fn($m) => json_encode(array_map(fn($s) => [$s['caption'], $s['lines']], $m['steps'] ?? []), JSON_UNESCAPED_UNICODE);
$check(str_contains($captions($meta), 'Player 2 resources Yoda'), 'captions: the all-seeing view names P2\'s resources (so the checks below are not vacuous)');
$check(($p1['meta']['steps'] ?? []) !== [] && !str_contains($captions($p1['meta']), 'Player 2 resources Yoda') && !str_contains($captions($p1seek['meta'] ?? []), 'Player 2 resources Yoda'), 'captions: a P1 perspective file never names P2\'s resources');
[, $asP1] = $post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $gn, 'key' => $key, 'step' => 2, 'view' => 'p1']), 'application/json');
$check(($asP1['meta']['steps'] ?? []) !== [] && !str_contains($captions($asP1['meta']), 'Player 2 resources Yoda') && str_contains($captions($asP1['meta']), 'Player 1 resources Vanguard'), 'captions: "P1\'s view" of an all-seeing file hides P2\'s resources, keeps P1\'s');
[, $asBoth] = $post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $gn, 'key' => $key, 'step' => $last, 'view' => 'both']), 'application/json');
$check(str_contains($captions($asBoth['meta'] ?? []), 'Player 2 resources Yoda'), 'captions: back to "Both hands" names them again');
// Seat blocks: field 1+31*(seat-1) is Deck, the next is Hand (zones in schema order: Deck, Hand, …).
// Caster mode sends real CardIDs, so P1's hand is face up and P2's is only the converter's CardBacks.
$pp = explode('<~>', $poll((string)($p1['gameName'] ?? ''), (string)($p1['key'] ?? '')));
$hand = fn(int $seat) => array_values(array_filter(explode('<|>', $pp[2 + 31 * ($seat - 1)] ?? ''), 'strlen'));
$check(count($hand(2)) > 0 && !array_filter($hand(2), fn($t) => !str_starts_with($t, 'CardBack')), 'perspective file: P2\'s hand is all card backs, though the file names those cards');
$check((bool)array_filter($hand(1), fn($t) => !str_starts_with($t, 'CardBack')), 'perspective file: P1\'s own hand is face up');

// The file is untrusted: no name or keyword it carries may reach the poll payload, whose fields are split on
// <~> / <|> / <NL>, and one of which the game log renders as HTML.
$hostileText = str_replace(['Mystery <b>Card</b>', '"keywords":["restore 1"]'], ['x<~>y<|>z<NL>w\" onmouseover=\"q', '"keywords":["restore 1","x<~>y<|>z"]'], file_get_contents($fx . 'viewer-game.swupgn'));
[, $hostile] = $post('SWUSim/SwuPgnViewer.php?action=open', $hostileText);
$hg = (string)($hostile['gameName'] ?? ''); $hk = (string)($hostile['key'] ?? '');
$post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $hg, 'key' => $hk, 'step' => $last]), 'application/json');
$hBoard = $poll($hg, $hk);
$check(($hostile['success'] ?? false) && count(explode('<~>', $hBoard)) === count(explode('<~>', $board)), 'poll: hostile names/keywords leave the payload\'s field count unchanged (' . count(explode('<~>', $hBoard)) . ' vs ' . count(explode('<~>', $board)) . ')');
$check(($hostile['success'] ?? false) && !str_contains($hBoard, 'x<') && !str_contains($hBoard, 'onmouseover'), 'poll: no text from the file reaches the payload');
$check(in_array('x<~>y<|>z<NL>w" onmouseover="q', $hostile['meta']['names'] ?? [], true), 'meta: the client still gets the name (drawn with textContent)');

// Keyframes that disagree with the events: the file still plays and its warnings say so.
$check(!array_filter($meta['warnings'] ?? [], fn($w) => str_contains($w, 'keyframe')), 'warnings: a consistent file has no keyframe warning');
[$code, $kf] = $post('SWUSim/SwuPgnViewer.php?action=open', preg_replace('/"baseHp":25,"baseMaxHp":25/', '"baseHp":24,"baseMaxHp":25', file_get_contents($fx . 'viewer-game.swupgn'), 1));
$check($code === 200 && ($kf['success'] ?? false), 'keyframe mismatch: the file still opens');
$check((bool)array_filter($kf['meta']['warnings'] ?? [], fn($w) => str_contains($w, 'keyframe') && str_contains($w, 'R1.end')), 'keyframe mismatch: a warning names the keyframe (' . json_encode($kf['meta']['warnings'] ?? null) . ')');

// No keyframes, Colossus (35 HP) vs Lake Country (34): the board starts from card data, not the reader's 30-HP
// placeholder — no phantom base damage, and the deck counts down from the DECKS total.
$nkLines = [];
foreach (explode("\n", str_replace(['SOR#020', 'SOR#028'], ['JTL#021', 'JTL#031'], file_get_contents($fx . 'viewer-game.swupgn'))) as $l) {
    $j = json_decode($l, true);
    if (is_array($j) && isset($j['keyframe'])) { unset($j['keyframe']); $l = json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); }
    $nkLines[] = $l;
}
[, $nko] = $post('SWUSim/SwuPgnViewer.php?action=open', implode("\n", $nkLines));
$nkPoll = function (int $step) use ($post, $poll, $nko) {
    $post('SWUSim/SwuPgnViewer.php?action=seek', json_encode(['gameName' => $nko['gameName'] ?? '', 'key' => $nko['key'] ?? '', 'step' => $step]), 'application/json');
    return explode('<~>', $poll((string)($nko['gameName'] ?? ''), (string)($nko['key'] ?? '')));
};
$baseDamage = function (array $segs, string $cardID) {
    foreach ($segs as $s) if (str_contains($s, '"CardID":"' . $cardID . '"') && str_contains($s, '"Location":"Base"') && preg_match('/"Damage":(\d+)/', $s, $m)) return intval($m[1]);
    return null;
};
$deckCount = fn(array $segs) => preg_match('/^CardBack (\d+)/', $segs[1] ?? '', $m) ? intval($m[1]) : 0;   // field 1 = P1's Deck, sent as "CardBack <count>"
$nk2 = $nkPoll(2);
$check($baseDamage($nk2, 'JTL_021') === 0 && $baseDamage($nk2, 'JTL_031') === 0, 'no keyframes: 35- and 34-HP bases show no phantom damage before they are hit');
$check($deckCount($nk2) === 2, 'no keyframes: after a unit is played the deck counts 8 − 6 drawn = 2 (cards in play are not deck cards) — got ' . $deckCount($nk2));
$nkLast = $nkPoll(count($nko['meta']['steps'] ?? []) - 1);
require_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
$hitHp = preg_match('/"tgt":"base@2"[^\n]*"hp":(\d+)/', implode("\n", $nkLines), $m) ? intval($m[1]) : null;
$check($hitHp !== null && $baseDamage($nkLast, 'JTL_031') === CardHp('JTL_031') - $hitHp, 'no keyframes: the hit 34-HP base shows printed − remaining (' . $baseDamage($nkLast, 'JTL_031') . ' = ' . CardHp('JTL_031') . ' − ' . $hitHp . '), not 30 − remaining');
// Refusals.
[$code, $bad] = $post('SWUSim/SwuPgnViewer.php?action=open', "[Game \"SWU-PGN/1.0\"]\n%%% EVENTS\n{not json}\n");
$check($code === 400 && is_string($bad['message'] ?? null) && $bad['message'] !== '', 'open: an invalid file is refused with a reason');
[$code, $bad2] = $post('SWUSim/SwuPgnViewer.php?action=open', str_replace('"t":"DAMAGE","src":"SOR#108","tgt":"base@2","amt":3', '"t":"DAMAGE","src":"SOR#108","tgt":"base@2","amt":"x"', file_get_contents($fx . 'viewer-game.swupgn')));
$check($code === 400 && str_contains((string)($bad2['message'] ?? ''), 'line '), 'open: a schema error is refused with its line number');
[$code] = $post('SWUSim/SwuPgnViewer.php?action=open', str_repeat('x', 2097153));
$check($code === 413, 'open: a file over 2 MB is refused');

echo $fails === 0 ? "PASS\n" : "FAIL: $fails check(s)\n";
exit($fails === 0 ? 0 : 1);
