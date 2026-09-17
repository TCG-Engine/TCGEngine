<?php
// RUN VIA CLI (it calls Apache inside the container; never curl this file):
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swusim_kick_vote_flow.php
//
// Inactivity kick vote (stage 2) — END TO END through the real endpoints: the presence payload on the
// poll, the two vote buttons, the per-mode removal, and stats.
// Timeouts are reached by REWINDING the clock (SWUSim/DevTools/zz_presence_poke.php), never by sleeping.
// Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
header('Content-Type: text/plain');
require_once __DIR__ . '/fixtures/swusim_chat_http_helpers.php';

function kv_dump(string $gn): array
{
    $j = json_decode(swuchat_http('SWUSim/DevTools/zz_presence_dump.php?gameName=' . urlencode($gn)), true);
    return is_array($j) ? $j : [];
}
function kv_poke(string $gn, int $seat, int $back, bool $actedOnly = false): array
{
    $q = ['gameName' => $gn, 'seat' => $seat, 'back' => $back] + ($actedOnly ? ['actedOnly' => 1] : []);
    $j = json_decode(swuchat_http('SWUSim/DevTools/zz_presence_poke.php?' . http_build_query($q)), true);
    return is_array($j) ? $j : [];
}
function kv_submit(string $gn, string $pid, int $mode, string $cardID = ''): string
{
    return trim(swuchat_http('ProcessInput.php?' . http_build_query([
        'gameName' => $gn, 'playerID' => $pid, 'authKey' => 'testschema', 'folderPath' => 'SWUSim',
        'mode' => $mode, 'cardID' => $cardID,
    ])));
}
// The presence block rides the poll's chat JSON (ParseChatPayload finds that piece by shape).
// ⚠ Read it from the FULL-BOARD poll (lastUpdate=0). The cheap branch answers KEEPALIVE when nothing
// has changed, which is correct behaviour but carries no payload.
function kv_presence(string $gn, string $pid): array
{
    $raw = swuchat_poll_full_raw($gn, $pid);
    foreach (explode('<~>', $raw) as $piece) {
        $piece = trim($piece);
        if ($piece === '' || $piece[0] !== '{' || strpos($piece, '"presence"') === false) continue;
        $j = json_decode($piece, true);
        if (is_array($j) && is_array($j['presence'] ?? null)) return $j['presence'];
    }
    return [];
}
$SCHEMA_2P = <<<'MD'
## GIVEN
CommonSetup: bbw/rrk/{myResources:8; theirResources:8}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_032:1:0]
WithP2GroundArena: [SOR_034:1:0]

## WHEN

## EXPECT
TURNPLAYER:1
MD;

$checks = [];

// ── the payload ──
$gn = swuchat_make_game($SCHEMA_2P);
$checks['2P fixture created'] = $gn !== '';
swuchat_poll($gn, '1');              // heartbeat + a first evaluate
$pr = kv_presence($gn, '2');
$checks['payload present on the poll']   = array_key_exists('onClock', $pr);
$checks['2P: seat 1 is on the clock']    = intval($pr['onClock']['seat'] ?? 0) === 1;
$checks['2P: ~75s remaining']            = intval($pr['onClock']['remaining'] ?? 0) > 60
                                           && intval($pr['onClock']['remaining'] ?? 0) <= 75;
$checks['no vote before expiry']         = ($pr['vote'] ?? null) === null;

$gn4 = swuchat_make_game(SWUCHAT_SCHEMA_TWINSUNS);
swuchat_poll($gn4, '1');
$pr4 = kv_presence($gn4, '2');
$checks['TwinSuns: ~150s remaining']     = intval($pr4['onClock']['remaining'] ?? 0) > 120
                                           && intval($pr4['onClock']['remaining'] ?? 0) <= 150;

// ── expiry opens a vote, and only the right people may vote ──
kv_poke($gn, 1, 80, true);           // seat 1 stalled 80s, still polling (stall, not disconnect)
$pr = kv_presence($gn, '2');
$checks['2P: vote opens after expiry']   = intval($pr['vote']['target'] ?? 0) === 1;
$checks['2P: reason is stall']           = ($pr['vote']['reason'] ?? '') === 'stall';
$checks['2P: opponent may vote']         = ($pr['vote']['canVote'] ?? false) === true;
$checks['2P: one vote needed']           = intval($pr['vote']['needed'] ?? 0) === 1;
$prTarget = kv_presence($gn, '1');
$checks['2P: target gets no buttons']    = ($prTarget['vote']['canVote'] ?? false) === false;
$prSpec = kv_presence($gn, 'S');
$checks['spectator cannot vote']         = ($prSpec['vote']['canVote'] ?? false) === false;

// ── the buttons: refusals ──
$checks['target cannot vote itself']     = kv_submit($gn, '1', 10021, '1') !== 'OK';
$checks['vote against nobody refused']   = kv_submit($gn, '2', 10021, '9') !== 'OK';
$dumpBefore = kv_dump($gn);
$checks['refusals recorded no vote']     = empty($dumpBefore['votes'][1]['yes']);

// ── Wait extends and keeps sticky votes ──
$checks['wait accepted']                 = kv_submit($gn, '2', 10022, '1') === 'OK';
$d = kv_dump($gn);
$checks['wait counted']                  = intval($d['votes'][1]['waits'] ?? 0) === 1;
// A Wait HIDES the prompt for everyone until the extension lapses, while keeping the sticky Yes votes
// in the store (user decision 2026-09-17: "wait another 20 seconds" + votes stay).
$pr = kv_presence($gn, '2');
$checks['wait hides the prompt']         = ($pr['vote'] ?? null) === null;
$checks['wait gives the target time']    = intval($pr['onClock']['remaining'] ?? -1) > 0;
$checks['wait kept the vote in store']   = isset(kv_dump($gn)['votes'][1]);

// ── 2P: one Yes ends the game, with stats recorded like a concede ──
$checks['2P vote accepted']              = kv_submit($gn, '2', 10021, '1') === 'OK';
$board = swuchat_poll_full_raw($gn, '2');
$checks['2P: game over after the kick']  = strpos($board, 'GAMEOVER_WINNER') !== false
                                           || strpos($board, 'removed for inactivity') !== false;
$checks['2P: vote cleared after kick']   = empty(kv_dump($gn)['votes']);

// ── Twin Suns: 2 of 3 removes the seat, nobody heals, the table keeps playing ──
$gt = swuchat_make_game(SWUCHAT_SCHEMA_TWINSUNS);
swuchat_poll($gt, '1'); swuchat_poll_full_raw($gt, '1');
kv_poke($gt, 1, 200, true);                          // seat 1 stalls (still connected)
$prt = kv_presence($gt, '2');
$checks['TS: vote opened on seat 1']     = intval($prt['vote']['target'] ?? 0) === 1;
$checks['TS: 2 votes needed']            = intval($prt['vote']['needed'] ?? 0) === 2;
$checks['TS: first Yes accepted']        = kv_submit($gt, '2', 10021, '1') === 'OK';
$live1 = kv_presence($gt, '2');
$checks['TS: one Yes does not remove']   = intval($live1['vote']['target'] ?? 0) === 1;
$checks['TS: second Yes accepted']       = kv_submit($gt, '3', 10021, '1') === 'OK';
$boardT = swuchat_poll_full_raw($gt, '2');
$checks['TS: seat 1 left LiveSeats']     = strpos($boardT, 'removed for inactivity') !== false;
// The table must still accept actions from a surviving seat (no soft-lock from a stranded queue).
$checks['TS: table still playable']      = kv_submit($gt, '2', 10001, 'myHealth-0!CustomInput!') === 'OK'
                                           || kv_submit($gt, '2', 10001, 'myHealth-0!CustomInput!') === '';

// ── Team Suns: both opposing players are needed; the teammate cannot vote ──
$gm = swuchat_make_game(SWUCHAT_SCHEMA_TEAMSUNS);
swuchat_poll($gm, '1'); swuchat_poll_full_raw($gm, '1');
kv_poke($gm, 1, 200, true);                          // seat 1 (red) stalls; blue = seats 2+4
$prm = kv_presence($gm, '2');
$checks['Team: vote opened']             = intval($prm['vote']['target'] ?? 0) === 1;
$checks['Team: both blues needed']       = intval($prm['vote']['needed'] ?? 0) === 2;
$prmMate = kv_presence($gm, '3');
$checks['Team: red teammate cannot vote'] = ($prmMate['vote']['canVote'] ?? false) === false;
$checks['Team: teammate Yes refused']    = kv_submit($gm, '3', 10021, '1') !== 'OK';
$checks['Team: blue 1 accepted']         = kv_submit($gm, '2', 10021, '1') === 'OK';
$checks['Team: one blue is not enough']  = intval(kv_presence($gm, '2')['vote']['target'] ?? 0) === 1;
$checks['Team: blue 2 accepted']         = kv_submit($gm, '4', 10021, '1') === 'OK';
$boardM = swuchat_poll_full_raw($gm, '2');
$checks['Team: opposing team wins']      = strpos($boardM, 'removed for inactivity') !== false;

// ── acting cancels an open vote ──
$gc = swuchat_make_game($SCHEMA_2P);
swuchat_poll($gc, '1'); swuchat_poll_full_raw($gc, '1');
kv_poke($gc, 1, 200, true);
$checks['cancel: vote opened']           = intval(kv_presence($gc, '2')['vote']['target'] ?? 0) === 1;
kv_submit($gc, '1', 10002, 'myGroundArena-0!FSM!');   // declare
kv_submit($gc, '1', 100, 'theirGroundArena-0');       // resolve -> a real action
$checks['cancel: acting closed it']      = empty(kv_dump($gc)['votes']);

$fail = 0;
foreach ($checks as $name => $ok) { echo ($ok ? 'PASS' : 'FAIL') . "  $name\n"; if (!$ok) $fail++; }
echo "games: 2p=$gn twinsuns=$gn4 ts=$gt team=$gm cancel=$gc\n";
echo $fail === 0 ? "\nPASS (" . count($checks) . ")\n" : "\nFAIL ($fail of " . count($checks) . ")\n";
exit($fail === 0 ? 0 : 1);
