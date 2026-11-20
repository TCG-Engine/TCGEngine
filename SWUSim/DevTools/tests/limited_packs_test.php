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

echo "IconsPackShape:\n";   // owner 2026-11-05: IC27 Icons packs, 9 cards — 3 C, a slot that is a Leader 25% of the time
// (otherwise a C), a hyperspace C, 2 U, 1 R/L, a foil of any rarity. No base slot. Reprints collate by their IC27 rarity.
$printed = (require './AppCore/SWU/PrintingRarities.php')['IC27'] ?? [];
$canonical = [];   // canonical card => its IC27 printed rarity
foreach ($printed as $printing => $r) $canonical[CardIDOverride($printing)] = $r;
$icRarity = function (string $c) use ($canonical) { return CardSet($c) === 'IC27' ? CardRarity($c) : ($canonical[$c] ?? null); };
$ip = SWULimitedIconsSlotPools('IC27');
$bad = []; $leaders = 0; $n = 2000;
for ($i = 0; $i < $n; $i++) {
    $pack = SWULimitedOpenPack('IC27', $rng);
    $slots = array_count_values(array_column($pack, 'slot'));
    $isLeader = ($slots['leader'] ?? 0) === 1;
    $leaders += $isLeader ? 1 : 0;
    if (count($pack) !== 9 || ($slots['common'] ?? 0) !== ($isLeader ? 3 : 4) || ($slots['hyperspace'] ?? 0) !== 1 || ($slots['uncommon'] ?? 0) !== 2
        || ($slots['rare'] ?? 0) !== 1 || ($slots['foil'] ?? 0) !== 1 || isset($slots['base'])) { $bad[] = 'shape ' . json_encode($slots); continue; }
    $plain = array_column(array_filter($pack, fn($e) => $e['slot'] !== 'foil'), 'id');
    if (count($plain) !== count(array_unique($plain))) $bad[] = 'a repeat outside the foil';
    foreach ($pack as $e) {
        $r = $icRarity($e['id']); $t = CardType($e['id']);
        if ($r === null) { $bad[] = "{$e['id']} is not an IC27 card with a known IC27 rarity"; continue; }
        if (strpos($e['id'], 'IC27_') === 0 && CardIDOverride($e['id']) !== $e['id']) $bad[] = "{$e['id']} is a reprint printing, not its card";
        if ($r === 'Special' || $t === 'Base') $bad[] = "{$e['id']} ($r $t) can't be in a pack";
        $want = ['leader' => ['Common', 'Rare'], 'common' => ['Common'], 'hyperspace' => ['Common'], 'uncommon' => ['Uncommon'],
                 'rare' => ['Rare', 'Legendary'], 'foil' => ['Common', 'Uncommon', 'Rare', 'Legendary']][$e['slot']];
        if (!in_array($r, $want, true) || ($e['slot'] === 'leader') !== ($t === 'Leader')) $bad[] = "{$e['id']} ($r $t) in the {$e['slot']} slot";
    }
}
$check($bad === [], "IC27: $n packs are 9 cards in the right slots, by IC27 rarity" . ($bad ? ': ' . implode('; ', array_slice(array_unique($bad), 0, 3)) : ''));
$check(abs($leaders / $n - 0.25) < 0.035, 'a leader in about 1 pack in 4: ' . round($leaders / $n, 3));
$grogu = CardIDOverride('IC27_154');
$check(isset($printed['IC27_154']) && $printed['IC27_154'] === 'Uncommon' && in_array($grogu, $ip['uncommon'], true) && CardRarity($grogu) === 'Special',
       'Grogu (SHD Special) opens in the IC27 Uncommon slot — the IC27 printing\'s rarity, under its own card ID');
$missing = array_values(array_filter(array_keys(SWUReprintsPrintedIn('IC27')), fn($p) => !isset($printed[$p])));
$check($missing === [] || array_filter($missing, fn($p) => in_array(CardIDOverride($p), array_merge(...array_values($ip)), true)) === [],
       'a reprint with no known IC27 rarity is never opened (' . count($missing) . ' waiting for the API)');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
