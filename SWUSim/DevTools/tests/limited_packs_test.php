<?php
// Limited pack generator — docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.1, §2.3.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_packs_test.php
chdir(dirname(__DIR__, 3));
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Limited/LimitedPacks.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$rng = SWULimitedRng(20261104);

echo "PackShape:\n";
foreach (array_keys(SWUBoosterSets()) as $set) {
    $pools = SWULimitedSlotPools($set);
    $slotPool = ['leader' => array_merge($pools['leaderCommon'], $pools['leaderRare']), 'base' => $pools['base'],
                 'common' => $pools['common'], 'hyperspace' => $pools['special'], 'uncommon' => $pools['uncommon'],
                 'rare' => array_merge($pools['rare'], $pools['legendary']),
                 'foil' => array_merge($pools['foilCommon'], $pools['foilUncommon'], $pools['foilRare'], $pools['foilLegendary'], $pools['special'])];
    $bad = [];
    for ($i = 0; $i < 500; $i++) {
        $pack = SWULimitedOpenPack($set, $rng);
        $slots = array_count_values(array_column($pack, 'slot'));
        $shapeOk = count($pack) === 16 && ($slots['leader'] ?? 0) === 1 && ($slots['base'] ?? 0) === 1
            && (($slots['common'] ?? 0) + ($slots['hyperspace'] ?? 0)) === 9 && ($slots['hyperspace'] ?? 0) <= 1
            && ($slots['uncommon'] ?? 0) === 3 && ($slots['rare'] ?? 0) === 1 && ($slots['foil'] ?? 0) === 1;
        if (!$shapeOk) { $bad[] = "shape " . json_encode($slots); continue; }
        $nonFoil = array_column(array_filter($pack, fn($e) => in_array($e['slot'], ['common', 'hyperspace', 'uncommon', 'rare'], true)), 'id');
        if (count($nonFoil) !== count(array_unique($nonFoil))) $bad[] = 'duplicate in C/U/R';
        foreach ($pack as $e) if (!in_array($e['id'], $slotPool[$e['slot']], true)) $bad[] = "{$e['id']} not in {$e['slot']} pool";
        $foil = array_values(array_filter($pack, fn($e) => $e['slot'] === 'foil'))[0]['id'];
        if (in_array(CardType($foil), ['Leader', 'Base'], true)) $bad[] = "foil $foil is a " . CardType($foil);
    }
    $check($bad === [], "$set: 500 packs are 16 cards in the right slots" . ($bad ? ': ' . implode('; ', array_slice(array_unique($bad), 0, 3)) : ''));
}

echo "HyperspaceOnlyFromJTL:\n";
$hs = 0; $foilS = 0;
foreach (['SOR', 'SHD', 'TWI'] as $set) for ($i = 0; $i < 500; $i++) foreach (SWULimitedOpenPack($set, $rng) as $e) {
    if ($e['slot'] === 'hyperspace') $hs++;
    if ($e['slot'] === 'foil' && CardRarity($e['id']) === 'Special') $foilS++;
}
$check($hs === 0 && $foilS === 0, "classic packs never hold an S card (hyperspace=$hs, foil S=$foilS)");

echo "RatesMatchEraOdds:\n";
$within = fn($got, $want) => abs($got - $want) <= max(0.003, 0.25 * $want);
$N = 20000;
foreach (['SHD', 'SEC', 'HMW'] as $set) {
    $odds = SWULimitedEraOdds(SWUBoosterSetEra($set));
    $pools = SWULimitedSlotPools($set);
    $n = ['rareLeader' => 0, 'legendary' => 0, 'foilRare' => 0, 'foilLegendary' => 0, 'foilSpecial' => 0, 'hyperSpecial' => 0];
    for ($i = 0; $i < $N; $i++) foreach (SWULimitedOpenPack($set, $rng) as $e) {
        if ($e['slot'] === 'leader' && in_array($e['id'], $pools['leaderRare'], true)) $n['rareLeader']++;
        if ($e['slot'] === 'rare' && CardRarity($e['id']) === 'Legendary') $n['legendary']++;
        if ($e['slot'] === 'hyperspace') $n['hyperSpecial']++;
        if ($e['slot'] === 'foil') {
            $r = CardRarity($e['id']);
            if ($r === 'Rare') $n['foilRare']++;
            if ($r === 'Legendary') $n['foilLegendary']++;
            if ($r === 'Special') $n['foilSpecial']++;
        }
    }
    foreach ($n as $k => $count) {
        $got = $count / $N;
        $check($within($got, $odds[$k]), sprintf('%s %s %.4f ≈ %.4f', $set, $k, $got, $odds[$k]));
    }
}

echo "RareBaseCanBeOpened:\n";
$rareBases = ['SOR' => 0, 'HMW' => 0];
foreach (array_keys($rareBases) as $set) for ($i = 0; $i < $N; $i++) foreach (SWULimitedOpenPack($set, $rng) as $e) {
    if ($e['slot'] === 'rare' && CardType($e['id']) === 'Base') $rareBases[$set]++;
}
$check($rareBases['SOR'] > 0, "SOR rare slot opened {$rareBases['SOR']} rare bases in $N packs");
$check($rareBases['HMW'] === 0, 'HMW rare slot never opens a base');

echo "SameSeedSamePacks:\n";
$run = function (int $seed) { $r = SWULimitedRng($seed); $out = []; for ($i = 0; $i < 50; $i++) $out[] = SWULimitedOpenPack('JTL', $r); return $out; };
$check($run(7) === $run(7), 'seed 7 twice gives identical packs');
$check($run(7) !== $run(8), 'seed 8 gives different packs');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
