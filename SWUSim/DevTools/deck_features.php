<?php
// Print deck features as JSON for style auto-derivation: reads a fixture-format deck on stdin.
chdir('/var/www/html/TCGEngine');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/Rl/CardTags.php';
$sec = ''; $leader = ''; $base = ''; $cards = [];
foreach (explode("\n", stream_get_contents(STDIN)) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#') continue;
    // 'Sideboard' must be recognised or its cards fall into the else-branch below as main deck.
    if ($l === 'Leader' || $l === 'Base' || $l === 'Deck' || $l === 'Sideboard') { $sec = $l; continue; }
    if (preg_match('/^(\d+)\s+(\S+)/', $l, $m)) {
        if ($sec === 'Leader') $leader = $m[2];
        elseif ($sec === 'Base') $base = $m[2];
        elseif ($sec !== 'Sideboard') $cards[] = [intval($m[1]), $m[2]];
    }
}
$f = ['leader' => $leader, 'base' => $base, 'baseHp' => intval(CardHp($base)), 'baseAspect' => strval(CardAspect($base)),
      'n' => 0, 'units' => 0, 'events' => 0, 'upgrades' => 0, 'cheap' => 0, 'big' => 0, 'costSum' => 0,
      'removal' => 0, 'wipe' => 0, 'burn' => 0, 'draw' => 0, 'space' => 0];
foreach ($cards as [$n, $id]) {
    $type = strval(CardType($id)); $cost = intval(CardCost($id)); $tags = SWUBotCardTags($id);
    $f['n'] += $n; $f['costSum'] += $n * $cost;
    if (str_contains($type, 'Unit')) {
        $f['units'] += $n;
        if ($cost <= 2) $f['cheap'] += $n;
        if ($cost >= 6) $f['big'] += $n;
        if (str_contains(strval(CardArena($id) ?? ''), 'Space')) $f['space'] += $n;
    } elseif (str_contains($type, 'Event')) $f['events'] += $n;
    elseif (str_contains($type, 'Upgrade')) $f['upgrades'] += $n;
    foreach (['removal', 'wipe', 'burn', 'draw'] as $t) if (in_array($t === 'burn' ? 'damage-enemy-base' : $t, $tags, true)) $f[$t] += $n;   // 'burn' feature <- damage-enemy-base tag (2026-10-01)
}
$f['avgCost'] = $f['n'] ? round($f['costSum'] / $f['n'], 2) : 0;
echo json_encode($f), "\n";
