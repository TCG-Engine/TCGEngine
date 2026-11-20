<?php
// Limited booster slot pools, derived from the generated card dictionaries —
// docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.1–2.3, §4.2.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_slot_pools_test.php
chdir(dirname(__DIR__, 3));
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Limited/LimitedPacks.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$isBase   = fn($c) => CardType($c) === 'Base';
$isLeader = fn($c) => CardType($c) === 'Leader';
$isToken  = fn($c) => strpos((string)CardType($c), 'Token') !== false || !empty(DecomposeCardID($c)['isToken']);
$pools = [];
foreach (array_keys(SWUBoosterSets()) as $s) $pools[$s] = SWULimitedSlotPools($s);

echo "RareBasesFollowTheEraRule:\n";
foreach (['SOR', 'TWI', 'JTL', 'LOF', 'LAW'] as $s) {
    $check(count(array_filter($pools[$s]['rare'], $isBase)) >= 1, "$s opens rare bases in the rare slot");
}
foreach (['SHD', 'SEC', 'ASH', 'HMW'] as $s) {
    $check(count(array_filter($pools[$s]['rare'], $isBase)) === 0, "$s has no base in the rare slot");
}
foreach ($pools as $s => $p) {
    $check(count(array_filter($p['base'], fn($c) => CardRarity($c) === 'Special')) === 0, "$s base slot holds no Special base");
}
$check(!in_array('LAW_023', $pools['LAW']['base'], true) && !in_array('LAW_023', $pools['LAW']['rare'], true), 'LAW_023 Great Pit of Carkoon is in no pool');

echo "SpecialsOnlyInSpecialPool:\n";
$bad = [];
foreach ($pools as $s => $p) foreach ($p as $key => $ids) {
    if ($key === 'special') continue;
    foreach ($ids as $c) if (CardRarity($c) === 'Special') $bad[] = "$s/$key/$c";
}
$check($bad === [], 'no Special card outside the special pool' . ($bad ? ': ' . implode(' ', array_slice($bad, 0, 5)) : ''));

echo "NoNonBoosterSetCardEverPooled:\n";
$bad = [];
foreach ($pools as $s => $p) foreach ($p as $key => $ids) foreach ($ids as $c) if (CardSet($c) !== $s) $bad[] = "$s/$key/$c";
$check($bad === [], 'every pooled card belongs to its own booster set' . ($bad ? ': ' . implode(' ', array_slice($bad, 0, 5)) : ''));

echo "NoTokensNoLeadersWhereTheyDontBelong:\n";
$tok = []; $lead = []; $base = [];
foreach ($pools as $s => $p) foreach ($p as $key => $ids) foreach ($ids as $c) {
    if ($isToken($c)) $tok[] = "$s/$key/$c";
    if ($isLeader($c) && !in_array($key, ['leaderCommon', 'leaderRare'], true)) $lead[] = "$s/$key/$c";
    if ($isBase($c) && !in_array($key, ['base', 'rare'], true)) $base[] = "$s/$key/$c";
}
$check($tok === [], 'no token in any pool');
$check($lead === [], 'leaders only in the leader pools');
$check($base === [], 'bases only in the base and rare pools (never a foil pool)');

echo "EverySetCanFillAPack:\n";
foreach ($pools as $s => $p) {
    $counts = implode(' ', array_map(fn($k) => "$k=" . count($p[$k]), array_keys($p)));
    $ok = count($p['common']) >= 9 && count($p['uncommon']) >= 3
        && $p['rare'] && $p['legendary'] && $p['leaderCommon'] && $p['leaderRare'] && $p['base']
        && $p['foilCommon'] && $p['foilUncommon'] && $p['foilRare'] && $p['foilLegendary'];
    $check($ok, "$s fills every slot ($counts)");
    if (SWUBoosterSetEra($s) !== 'classic') $check(count($p['special']) > 0, "$s has S cards for the foil/hyperspace rolls");
}

echo "NonBoosterSetThrows:\n";
$threw = false;
try { SWULimitedSlotPools('IBH'); } catch (InvalidArgumentException $e) { $threw = true; }
$check($threw, 'IBH (no boosters) throws');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
