<?php
// Curve-value review report (spec docs/superpowers/specs/2026-10-05-swusim-curve-value-design.md §5, §6.2–6.4).
// Prices EVERY card with no board, writes the owner's review table, coverage per set and per bot deck, the worklist of
// unpriced cards in the bot decks, and a keyword cross-check against what the designers charge.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/curve-value-report.php
chdir(dirname(__DIR__, 2));
error_reporting(E_ALL & ~E_DEPRECATED);
require __DIR__ . '/tests/fixtures/bot_test_bootstrap.php';   // loads the engine the same way the bot tests do
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotDeckStyle.php';              // SWUBotDeckFromFixtureText (not loaded by BotHeuristic.php)

preg_match_all("/'([A-Z]{3}_\d{3})' =>/", (string)file_get_contents('./SWUSim/GeneratedCode/GeneratedCardDictionaries.php'), $m);
$rows = []; $perSet = []; $boardOnly = [];
foreach (array_unique($m[1]) as $id) {
    if (str_starts_with($id, 'IBH') || !in_array(CardType($id), ['Unit', 'Event', 'Upgrade'], true)) continue;
    $r = SWUBotCurveValue(0, $id, false);
    $set = substr($id, 0, 3);
    $perSet[$set]['n'] = ($perSet[$set]['n'] ?? 0) + 1;
    // PRICED = the parser can read it. A board-only card (a wipe, Power of the Dark Side) has no static value but is
    // priced in every real decision, since the bot always has a board — it is listed as "board-only", not unpriced.
    if (_SWUBotCurveParse($id) !== null) $perSet[$set]['priced'] = ($perSet[$set]['priced'] ?? 0) + 1;
    if ($r === null && _SWUBotCurveParse($id) !== null) $boardOnly[] = $id;
    $rows[$id] = $r;
}
// Coverage of the bot decks: share of main-deck COPIES that are priced.
$decks = array_merge(glob('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/*.txt') ?: [], glob('./SWUSim/Tests/BotFixtures/force-fam-HMW-predictions/*.txt') ?: []);
$copies = 0; $pricedCopies = 0; $work = [];
foreach ($decks as $path) {
    $deck = SWUBotDeckFromFixtureText((string)file_get_contents($path));
    foreach ($deck['cards'] as $cid => $n) {
        $copies += $n;
        if (_SWUBotCurveParse($cid) !== null) $pricedCopies += $n; else $work[$cid] = ($work[$cid] ?? 0) + $n;
    }
}
arsort($work);
// Keyword cross-check: priced units whose parts are ONLY body/space/keywords; mean surplus per keyword.
$kwMean = [];
foreach ($rows as $id => $r) {
    if ($r === null || CardType($id) !== 'Unit') continue;
    $labels = array_keys($r['parts']);
    if (array_filter($labels, fn($l) => !preg_match('/^(body |space$|Ambush$|Sentinel$|Shielded$|Support$|Raid \d|Restore \d|free keywords)/', $l))) continue;
    foreach ($labels as $l) { $k = preg_replace('/ x\d+$/', '', $l); if (str_starts_with($k, 'body ')) $k = 'vanilla body'; $kwMean[$k][] = $r['surplus']; }
}
$out = "# Curve value — review table (" . date('Y-m-d') . ")\n\nNo board: conditions unmet, damage uncapped, defeat at its static anchors. Surplus is in RESOURCES.\n\n";
$out .= "## Coverage\n\n- Bot decks (" . count($decks) . " fixtures): **" . round(100 * $pricedCopies / max(1, $copies), 1) . "%** of main-deck copies priced (gate: 80%).\n";
foreach ($perSet as $s => $c) $out .= "- $s: " . intval($c['priced'] ?? 0) . " / {$c['n']} priced\n";
$out .= "\n## Board-only (priced only against a live board)\n\n" . implode(', ', array_map(fn($c) => "$c " . CardTitle($c), $boardOnly)) . "\n";
$out .= "\n## Worklist — unpriced cards in the bot decks, by copies\n\n| card | copies | text |\n|---|---|---|\n";
foreach (array_slice($work, 0, 60, true) as $cid => $n) $out .= "| $cid " . CardTitle($cid) . " | $n | " . str_replace(['|', "\n"], ['/', ' '], strval(CardText($cid))) . " |\n";
$out .= "\n## Keyword cross-check (mean surplus of keyword-only units carrying it; |mean| > 1 is flagged)\n\n| keyword | units | mean surplus | flag |\n|---|---|---|---|\n";
foreach ($kwMean as $k => $v) { $mean = array_sum($v) / count($v); $out .= "| $k | " . count($v) . ' | ' . round($mean, 2) . ' | ' . (abs($mean) > 1 ? '**FLAG**' : '') . " |\n"; }
$out .= "\n## Every priced card\n\n| card | type | cost | value | surplus | parts | allowances (aspects / unique) |\n|---|---|---|---|---|---|---|\n";
foreach ($rows as $id => $r) {
    if ($r === null) continue;
    $parts = implode(', ', array_map(fn($k, $v) => "$k " . round($v, 2), array_keys($r['parts']), $r['parts']));
    $out .= "| $id " . CardTitle($id) . ' | ' . CardType($id) . ' | ' . CardCost($id) . ' | ' . round($r['value'], 2) . ' | ' . sprintf('%+.2f', $r['surplus'])
          . " | $parts | " . round($r['allowances']['aspects'], 2) . ' / ' . round($r['allowances']['unique'], 2) . " |\n";
}
@mkdir('./docs/superpowers/research/curve-value', 0777, true);
$file = './docs/superpowers/research/curve-value/' . date('Y-m-d') . '-table.md';
file_put_contents($file, $out);
echo "wrote $file — bot-deck coverage " . round(100 * $pricedCopies / max(1, $copies), 1) . "%\n";
bot_test_finish();
