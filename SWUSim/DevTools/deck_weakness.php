<?php
// Score decks for STRUCTURAL weakness, for mining "bad deck" fixtures (owner 2026-09-15). Reads one JSON deck per
// line on stdin ({"id","leader","base","deck":[[count,cardID],…]}) and writes one JSON line of features per deck.
// Weakness is composition, never tournament record: the worst records in the corpus belong to the BEST archetypes
// (Vader, Krennic, Ahsoka), i.e. a bad day, not a bad deck.
//
// Aspect penalty (CR 4.2): leader + base supply aspect icons; each of a card's icons is matched against them at most
// once, and every UNMATCHED icon costs +2. A deck that pays a lot of penalty is playing off-aspect cards.
//   docker exec -i -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/deck_weakness.php < lists.jsonl
chdir('/var/www/html/TCGEngine');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './SWUSim/Rl/CardTags.php';

$aspects = function (string $id): array {
    $a = strval(CardAspect($id) ?? '');
    return $a === '' ? [] : array_map('trim', explode(',', $a));
};
while (($line = fgets(STDIN)) !== false) {
    $d = json_decode(trim($line), true);
    if (!is_array($d) || empty($d['leader']) || empty($d['base']) || empty($d['deck'])) continue;
    $pool = array_merge($aspects($d['leader']), $aspects($d['base']));   // the icons this deck supplies
    $f = ['id' => $d['id'], 'leader' => $d['leader'], 'base' => $d['base'], 'n' => 0, 'units' => 0, 'cheap' => 0,
          'big' => 0, 'events' => 0, 'upgrades' => 0, 'removal' => 0, 'wipe' => 0, 'draw' => 0, 'vanilla' => 0,
          'penaltyPips' => 0, 'offAspectCards' => 0, 'costSum' => 0, 'aspects' => count(array_unique($pool))];
    foreach ($d['deck'] as [$n, $id]) {
        $type = strval(CardType($id));
        $f['n'] += $n;
        $f['costSum'] += $n * intval(CardCost($id));
        if (str_contains($type, 'Unit')) {
            $f['units'] += $n;
            if (intval(CardCost($id)) <= 2) $f['cheap'] += $n;
            if (intval(CardCost($id)) >= 6) $f['big'] += $n;
            if (trim(strval(CardText($id) ?? '')) === '') $f['vanilla'] += $n;   // no printed ability
        } elseif (str_contains($type, 'Event')) $f['events'] += $n;
        elseif (str_contains($type, 'Upgrade')) $f['upgrades'] += $n;
        $tags = SWUBotCardTags($id);
        foreach (['removal', 'wipe', 'draw'] as $t) if (in_array($t, $tags, true)) $f[$t] += $n;
        $avail = $pool;
        $unmatched = 0;
        foreach ($aspects($id) as $pip) {
            $k = array_search($pip, $avail, true);
            if ($k === false) $unmatched++; else unset($avail[$k]);
        }
        if ($unmatched > 0) { $f['penaltyPips'] += $n * $unmatched; $f['offAspectCards'] += $n; }
    }
    $f['avgCost'] = $f['n'] ? round($f['costSum'] / $f['n'], 2) : 0;
    echo json_encode($f), "\n";
}
