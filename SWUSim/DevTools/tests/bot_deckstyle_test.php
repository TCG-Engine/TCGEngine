<?php
// The deck-style classifier end to end: the 75% instant label, the shape fallback, and the ACCURACY BAR.
// Spec: docs/superpowers/specs/2026-09-22-swusim-deck-style-classifier-design.md.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_deckstyle_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Rl/CardTags.php';
require_once './SWUSim/Custom/BotDeckStyle.php';
$fails = 0;
$check = function ($ok, $msg, $detail = '') use (&$fails) {
    echo ($ok ? 'PASS' : 'FAIL') . ": $msg" . (!$ok && $detail !== '' ? "  [got: $detail]" : '') . "\n";
    if (!$ok) $fails++;
};
$deckOf = fn(string $f) => SWUBotDeckFromFixtureText((string)file_get_contents("./SWUSim/Tests/BotFixtures/meta-2026-09/$f.txt"));
$reg = SWUBotDeckLabelRegistry();
$check(count($reg) === 23, 'the registry holds the 23 labelled decks', strval(count($reg)));

// ── overlap ─────────────────────────────────────────────────────────────────────────────────────
$vader = $deckOf('vader_yellow');
$check(abs(SWUBotDeckOverlap($vader['cards'], $vader['cards']) - 1.0) < 1e-9, 'a deck overlaps itself fully');
$check(SWUBotDeckOverlap($vader['cards'], $deckOf('krennic_splash')['cards']) < 0.3,
    'Vader Yellow and Krennic Splash barely overlap');
$half = array_slice($vader['cards'], 0, (int)floor(count($vader['cards']) / 2), true);
$o = SWUBotDeckOverlap($vader['cards'], $half);
$check($o > 0.2 && $o < 0.85, 'a half list overlaps partially', strval(round($o, 2)));

// ── the 75% instant label ───────────────────────────────────────────────────────────────────────
$r = SWUBotDeckStyle($vader);
$check($r['source'] === 'label' && $r['style'] === 'hyperaggro', "the stock Vader list takes its label", $r['source'] . '/' . strval($r['style']));
$check($r['confidence'] === 'high', 'an instant label is high confidence');
$check(!empty($r['reasons']) && str_contains($r['reasons'][0], 'vader_yellow'), 'the reason names the matched deck');

// ── an off-meta list of the same leader falls through to the scan (owner, 2026-09-22) ───────────
// Vader's leader, but a midrange shell: the labelled Vader list must no longer reach 75%.
$offmeta = ['leader' => 'JTL_006', 'base' => 'JTL_020', 'cards' => $deckOf('lukeash_datavault')['cards']];
$r2 = SWUBotDeckStyle($offmeta);
$check($r2['source'] === 'shape', 'an off-meta Vader list is scanned, not labelled', $r2['source']);
$check($r2['style'] !== 'hyperaggro', 'an off-meta Vader list is not mislabelled Hyper Aggro', strval($r2['style']));

// ── no labelled deck for this leader, and unreadable decks ──────────────────────────────────────
$unknown = ['leader' => 'SOR_001', 'base' => 'SOR_020', 'cards' => $deckOf('dedra_colossus')['cards']];
$check(SWUBotDeckStyle($unknown)['source'] === 'shape', 'an unknown leader is scanned');
$none = SWUBotDeckStyle(['leader' => '', 'base' => '', 'cards' => []]);
$check($none['style'] === null && $none['source'] === 'none', 'an empty deck returns no style');

// ── THE ACCURACY BAR: leave-one-out over the 23 labelled decks ──────────────────────────────────
// Each deck is classified with ITS OWN entry removed from the registry, so the 75% rule cannot see the answer.
$exact = 0; $within = 0; $rows = [];
foreach ($reg as $d) {
    $others = array_values(array_filter($reg, fn($x) => $x['file'] !== $d['file']));
    $got = SWUBotDeckStyle(['leader' => $d['leader'], 'base' => $d['base'], 'cards' => $d['cards']], $others);
    $i = array_search($got['style'], SWU_DECKSTYLE_SCALE, true);
    $j = array_search($d['style'], SWU_DECKSTYLE_SCALE, true);
    $off = ($i === false) ? 9 : abs($i - $j);
    $exact += ($off === 0) ? 1 : 0;
    $within += ($off <= 1) ? 1 : 0;
    $rows[] = sprintf('  %-34s want %-11s got %-11s (%s, %s)', $d['file'], $d['style'], strval($got['style']), $got['source'], $got['confidence']);
}
$n = count($reg);
echo "\nleave-one-out over $n owner-labelled decks:\n" . implode("\n", $rows) . "\n";
printf("  exact %d/%d (%.0f%%)   within one step %d/%d (%.0f%%)\n\n", $exact, $n, 100 * $exact / $n, $within, $n, 100 * $within / $n);
$check($exact / $n >= 0.65, 'exact accuracy is at least 65%', sprintf('%.0f%%', 100 * $exact / $n));
$check($within / $n >= 0.95, 'within-one-step accuracy is at least 95%', sprintf('%.0f%%', 100 * $within / $n));

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
