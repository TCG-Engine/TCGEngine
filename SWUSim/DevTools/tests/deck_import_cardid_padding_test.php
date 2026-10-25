<?php
// A deck whose CardIDs are zero-padded differently from the dictionary must still import as REAL cards.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/deck_import_cardid_padding_test.php
//
// ─── WHY THIS TEST EXISTS (game 1647080, 2026-10-19) ─────────────────────────────────────────────
// TS26 is the one set whose dictionary keys are TWO-digit (TS26_30; see CardIDDoubleDigitSets in
// zzCardCodeGenerator.php). A deck source that pads every number to three digits sent TS26_030, and
// SWUIsAcceptableCardID()'s shape fallback let it through, so the game was built with ids that exist in
// no dictionary: blank card art in hand (TS26_032.webp; TS26_32.webp is the real file), a log line of
// [[TS26_067|TS26_067]] because the title lookup missed, and no ability, cost or aspects at all.
// swudb and SWUStats both send TS26_30, so a fixture built from either one could never catch this.
// The ids are folded at SWUDeckSuccess(), the step every import path goes through (JSON paste, free
// text, swudb, SWUStats, the shared deck-link sources).
error_reporting(E_ALL & ~E_DEPRECATED);

$FAILS = 0;
function check($cond, $msg) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n";
    if (!$cond) $FAILS++;
}

include_once './SWUSim/Custom/DeckImport.php';

echo "── the dictionary really is two-digit for TS26 (else this test proves nothing) ──\n";
check(IsSWUCardID('TS26_30') && !IsSWUCardID('TS26_030'), 'TS26_30 is a card; TS26_030 is not');
check(IsSWUCardID('SOR_014') && !IsSWUCardID('SOR_14'), 'SOR_014 is a card; SOR_14 is not');

echo "── a pasted JSON deck with 3-digit TS26 ids imports the real cards ──\n";
$json = json_encode([
    'metadata'     => ['name' => 'padding'],
    'leader'       => ['id' => 'TS26_001', 'count' => 1],
    'secondleader' => ['id' => 'LAW_008', 'count' => 1],
    'base'         => ['id' => 'ASH_023', 'count' => 1],
    'deck'         => [
        ['id' => 'TS26_030', 'count' => 2],   // padded TS26
        ['id' => 'TS26_32',  'count' => 1],   // already canonical: untouched
        ['id' => 'SOR_14',   'count' => 1],   // UNDER-padded 3-digit set: folded the other way
        ['id' => 'SOR_095',  'count' => 1],   // ordinary id: untouched
    ],
    'sideboard'    => [['id' => 'TS26_067', 'count' => 1]],
]);
$r = SWUResolveDeckInput($json);
check(!empty($r['success']), 'the deck resolves');
$leaders = is_array($r['leader']) ? $r['leader'] : [$r['leader']];
check(in_array('TS26_01', $leaders, true), 'leader TS26_001 -> TS26_01 (got ' . implode(',', $leaders) . ')');
$main = $r['mainDeck'] ?? [];
check(count(array_keys($main, 'TS26_30', true)) === 2, 'both copies of TS26_030 -> TS26_30');
check(in_array('TS26_32', $main, true), 'TS26_32 is kept as is');
check(in_array('SOR_014', $main, true), 'SOR_14 -> SOR_014');
check(in_array('SOR_095', $main, true), 'SOR_095 is kept as is');
check(in_array('TS26_67', $r['sideboard'] ?? [], true), 'sideboard TS26_067 -> TS26_67');
$bad = array_values(array_filter(array_merge($leaders, [$r['base']], $main, $r['sideboard'] ?? []),
    fn($id) => !IsSWUCardID($id)));
check(empty($bad), 'every imported id is a dictionary card (not: ' . implode(',', $bad) . ')');

echo "── the free-text path folds too ──\n";
$t = SWUResolveDeckInput("Leader\nTS26_001\nBase\nASH_023\nDeck\n2 TS26_030\n");
check(count(array_keys($t['mainDeck'] ?? [], 'TS26_30', true)) === 2, 'free-text "2 TS26_030" -> two TS26_30');

echo "── an id with no real card behind it is left alone, not guessed at ──\n";
// TS26_099 re-pads to TS26_99, which is no card either (the set has 88). Re-padding must only be kept
// when it lands on a real card; otherwise the id the source sent is kept as is.
check(!IsSWUCardID('TS26_99'), 'TS26_99 is not a card (else this case proves nothing)');
$u = SWUResolveDeckInput(json_encode(['leader' => ['id' => 'TS26_01', 'count' => 1], 'base' => ['id' => 'ASH_023', 'count' => 1],
    'deck' => [['id' => 'TS26_099', 'count' => 1]]]));
check(($u['mainDeck'] ?? []) === ['TS26_099'], 'TS26_099 passes through unchanged (got ' . implode(',', $u['mainDeck'] ?? []) . ')');

echo $FAILS === 0 ? "\nALL PASS\n" : "\n$FAILS FAILED\n";
exit($FAILS === 0 ? 0 : 1);
