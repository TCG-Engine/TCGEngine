<?php
// Meta-baseline CONVERT (skill `swusim-meta-bot-run`, owner 2026-10-11): every real match of the 53-event ASH Premier set, both players' actual melee lists (main + the
// 10-card sideboard, folded in at play time by SUPERSET=1), one fixture per list. Style = the curated ash-meta fixture's '# Style:' when the
// deck's archetype (canonical leader + base bucket) has one, else SWUBotDeckStyle (label overlap, then shape score).
chdir('/var/www/html/TCGEngine');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
foreach (['./SWUSim/Rl/CardTags.php', './SWUSim/Custom/BotDeckStyle.php'] as $f) include_once $f;
// usage (SWUSim container): php convert.php <export.json> <lists dir> <work dir> <curated fixture dir>
$EXPORT = $argv[1]; $LISTS = $argv[2]; $O = $argv[3]; $FIXDIR = $argv[4] ?? 'SWUSim/Tests/BotFixtures/ash-meta-2026-09';
@mkdir("$O/fx", 0777, true);
$e = json_decode(file_get_contents($EXPORT), true);
$styleByKey = [];
foreach ($e['fixtures'] as $file => $key) {
    if (!$key) continue;
    preg_match('/^# Style:\s*(\S+)/m', file_get_contents("$FIXDIR/$file.txt"), $m);
    if (!empty($m[1])) $styleByKey[$key] = $m[1];
}
$minFor = fn($base) => $base === 'JTL_024' ? 60 : ($base === 'JTL_025' ? 45 : 50);
$ok = []; $why = []; $srcStat = []; $labels = [];
foreach ($e['decks'] as $deckID => $d) {
    $src = strval($d['src'] ?? '');
    if ($src === '') { $why['no list id']++; continue; }
    $path = "$LISTS/$src.json";
    if (!is_file($path)) { $why['list missing']++; continue; }
    $j = json_decode(file_get_contents($path), true);
    $L = $j['leader']['id'] ?? ''; $B = $j['base']['id'] ?? '';
    $main = []; foreach ((array)($j['deck'] ?? []) as $c) $main[$c['id']] = ($main[$c['id']] ?? 0) + intval($c['count']);
    $side = []; foreach ((array)($j['sideboard'] ?? []) as $c) $side[$c['id']] = ($side[$c['id']] ?? 0) + intval($c['count']);
    if ($L === '' || $B === '') { $why['no leader/base']++; continue; }
    if (array_sum($main) < $minFor($B)) { $why['main below base minimum (dropped card?)']++; continue; }
    $unknown = array_filter(array_keys($main + $side), fn($id) => strval(CardType($id)) === '');
    if ($unknown) { $why['card unknown to SWUSim']++; continue; }
    // ARCHETYPE = the fixture-style name (leader + base type + Homeworlds trait read off main + sideboard; owner 2026-10-11), not the DB's
    // leader + base-bucket key, which cannot see the trait (it merged maul_hmw_blue_naboo with maul_hmw_blue). The frozen ASH baseline
    // (built before this) kept the DB keys.
    $named = $main; foreach ($side as $id => $n) $named[$id] = ($named[$id] ?? 0) + $n;
    $fname = SWUBotFixtureFileName($L, $B, $named);
    $key = $fname; $labels[$fname] = SWUBotDeckDisplayName($L, $B, $named);
    // The curated fixture of this EXACT archetype first — leader + base + trait, named by SWUBotFixtureFileName over main + sideboard —
    // so a trait deck takes its own fixture's label (maul_hmw_blue_naboo vs maul_hmw_blue); then the leader + base-bucket key; then the classifier.
    if (is_file("$FIXDIR/$fname.txt") && preg_match('/^# Style:\s*(\S+)/m', file_get_contents("$FIXDIR/$fname.txt"), $fm)) { $style = $fm[1]; $ss = 'fixture'; }
    elseif ($d['k'] !== null && isset($styleByKey[$d['k']])) { $style = $styleByKey[$d['k']]; $ss = 'fixture-key'; }
    else { $r = SWUBotDeckStyle(['leader' => $L, 'base' => $B, 'cards' => $main]); $style = $r['style'] ?? 'midrange'; $ss = $r['style'] ? $r['source'] : 'default'; }
    $srcStat[$ss] = ($srcStat[$ss] ?? 0) + 1;
    $txt = "# Tournament baseline list — melee tournament {$d['t']}, player {$d['p']}, decklist $src\n# Archetype key: $key\n# Style: $style\n# StyleSource: $ss\n\nLeader\n1 $L\n\nBase\n1 $B\n\nDeck\n";
    foreach ($main as $id => $n) $txt .= "$n $id\n";
    $txt .= "\nSideboard\n"; foreach ($side as $id => $n) $txt .= "$n $id\n";
    file_put_contents("$O/fx/$src.txt", $txt);
    $ok[$deckID] = ['src' => $src, 'k' => $key, 'style' => $style, 'side' => array_sum($side)];
}
$pairs = []; $matches = []; $dropped = 0;
foreach ($e['matchups'] as [$p, $o, $w, $l, $dr]) {
    if ($o === null || $p >= $o) continue;
    if (!isset($ok[$p], $ok[$o])) { $dropped++; continue; }
    $pairs[] = $ok[$p]['src'] . ' ' . $ok[$o]['src'];
    $matches[] = implode("\t", [$ok[$p]['src'], $ok[$o]['src'], $w, $l, $dr, $ok[$p]['k'], $ok[$o]['k'], $ok[$p]['style'], $ok[$o]['style']]);
}
file_put_contents("$O/pairs.txt", implode("\n", $pairs) . "\n");
file_put_contents("$O/matches.tsv", implode("\n", $matches) . "\n");
file_put_contents("$O/labels.json", json_encode($labels, JSON_UNESCAPED_UNICODE));
$sideHist = array_count_values(array_map(fn($x) => $x['side'], $ok)); ksort($sideHist);
echo "decks written: " . count($ok) . " of " . count($e['decks']) . "\nexcluded: " . json_encode($why) . "\nstyle source: " . json_encode($srcStat) . "\nsideboard sizes: " . json_encode($sideHist) . "\nmatches kept: " . count($matches) . " (dropped for an excluded deck: $dropped)\n";
