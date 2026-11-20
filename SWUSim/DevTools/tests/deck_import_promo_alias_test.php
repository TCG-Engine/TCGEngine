<?php
// A promo printing must import as the card it reprints, never as its own id.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/deck_import_promo_alias_test.php
//
// ─── WHY THIS TEST EXISTS (P-34, game 7162, 2026-11-18) ──────────────────────────────────────────
// Promo sets (P25, P26, …) are not in the card DB, so a promo id has no dictionary entry, no art and no
// handler. The only thing that turns one into a real card is its case in CardIDOverride
// (AppCore/SWU/Overrides.php). P26_277 (Hera Syndulla - Against the Odds, a promo of IC27_117) had none:
// SWUResolveToImplementedPrint fell back to the unchanged id, SWUIsAcceptableCardID's shape fallback let
// it through, and the game was built with three cards that showed placeholder art and did nothing when
// played. swudb sends the promo id as-is, so any deck built with the promo printing hit it.
error_reporting(E_ALL & ~E_DEPRECATED);

$FAILS = 0;
function check($cond, $msg) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . "\n";
    if (!$cond) $FAILS++;
}

include_once './SWUSim/Custom/DeckImport.php';

echo "── the promo really has no card behind it (else this test proves nothing) ──\n";
check(!IsSWUCardID('P26_277') && IsSWUCardID('IC27_117'), 'P26_277 is not a card; IC27_117 is');

echo "── a deck with the P26 Hera promo imports the IC27 card ──\n";
$json = json_encode([
    'metadata' => ['name' => 'promo alias'],
    'leader'   => ['id' => 'LAW_009', 'count' => 1],
    'base'     => ['id' => 'LAW_027', 'count' => 1],
    'deck'     => [
        ['id' => 'P26_277', 'count' => 3],   // Hera Syndulla - Against the Odds (promo)
        ['id' => 'P25_042', 'count' => 1],   // an existing promo alias, as a control: -> SOR_196
        ['id' => 'IC27_113', 'count' => 1],  // ordinary id: untouched
    ],
    'sideboard' => [['id' => 'P26_277', 'count' => 1]],
]);
$r = SWUResolveDeckInput($json);
check(!empty($r['success']), 'the deck resolves');
$main = $r['mainDeck'] ?? [];
check(count(array_keys($main, 'IC27_117', true)) === 3, 'all three P26_277 -> IC27_117 (got ' . implode(',', $main) . ')');
check(!in_array('P26_277', $main, true), 'no P26_277 is left in the main deck');
check(in_array('SOR_196', $main, true), 'P25_042 -> SOR_196 (control)');
check(in_array('IC27_113', $main, true), 'IC27_113 is kept as is');
check(($r['sideboard'] ?? []) === ['IC27_117'], 'the sideboard copy -> IC27_117 too');

echo "── the generated promo block: both prerelease spellings, other promo sets ──\n";
foreach ([
    'P26_014' => 'ASH_031',    // Hera Syndulla - Renegade General
    'JTLP_003' => 'JTL_045',   // Hera Syndulla - We've Lost Enough (official code) …
    'JTLW_003' => 'JTL_045',   // … and the spelling swudb has sent
    'LOFW_001' => 'LOF_068',
    'C26_001' => CardIDOverride('C26_001'),
] as $promo => $want) {
    check($want !== $promo && SWUResolveToImplementedPrint($promo) === $want, "$promo -> $want (got " . SWUResolveToImplementedPrint($promo) . ')');
}

echo "── two hand-written aliases that pointed at the WRONG card ──\n";
check(CardIDOverride('JTLW_011') === 'JTL_149', 'JTLW_011 Red Squadron Y-Wing -> JTL_149, not JTL_051 Red Squadron X-Wing');
check(CardIDOverride('J25_008') === 'JTL_201', 'J25_008 Ahsoka Tano - Chasing Whispers -> JTL_201, not TWI_194 Always Ready For Trouble');

echo "── every alias in Overrides.php lands on a real card that is its own canonical ──\n";
preg_match_all('/case "([^"]+)": return "([^"]+)";/', file_get_contents('./AppCore/SWU/Overrides.php'), $cases, PREG_SET_ORDER);
check(count($cases) > 1000, 'the case list was read (' . count($cases) . ' cases)');
$dupes = array_keys(array_filter(array_count_values(array_column($cases, 1)), fn($n) => $n > 1));
check($dupes === [], 'no printing is listed twice (' . implode(',', $dupes) . ')');
$bad = [];
foreach ($cases as [, $from, $to]) {
    if (!IsSWUCardID($to) || CardIDOverride($to) !== $to) $bad[] = "$from->$to";
}
check($bad === [], 'every target is a canonical card (' . implode(', ', array_slice($bad, 0, 5)) . ')');

echo $FAILS === 0 ? "PASS\n" : "FAIL ($FAILS)\n";
exit($FAILS === 0 ? 0 : 1);
