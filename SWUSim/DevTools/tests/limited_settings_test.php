<?php
// Limited host settings, per-seat pack lineups, bases in play, Sealed pool —
// docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.3.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_settings_test.php
chdir(dirname(__DIR__, 3));
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Limited/LimitedSettings.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$valid = fn(array $s) => SWULimitedValidateSettings($s) === [];
$with  = fn(string $mode, array $over) => array_merge(SWULimitedDefaultSettings($mode), $over);

echo "DefaultsAreValid:\n";
foreach (['draft', 'sealed'] as $m) $check($valid(SWULimitedDefaultSettings($m)), "$m defaults validate: " . implode('; ', SWULimitedValidateSettings(SWULimitedDefaultSettings($m))));
$check(SWULimitedDefaultSettings('draft')['packCount'] === 3 && SWULimitedDefaultSettings('sealed')['packCount'] === 6, 'default pack counts: Draft 3, Sealed 6');
$check(SWULimitedDefaultSettings('draft')['singleSet'] === 'HMW', 'default set is the latest main set');
$check(SWULimitedDefaultSettings('sealed')['lineup'] === array_fill(0, 6, 'HMW'), 'default lineup = latest set × packCount');
$check(SWULimitedPackRange('draft') === [3, 6] && SWULimitedPackRange('sealed') === [6, 16], 'pack ranges');

echo "PackCountRanges:\n";
foreach ([['draft', 3, true], ['draft', 6, true], ['draft', 2, false], ['draft', 7, false],
          ['sealed', 6, true], ['sealed', 16, true], ['sealed', 5, false], ['sealed', 17, false]] as [$m, $n, $ok]) {
    $s = $with($m, ['packCount' => $n, 'lineup' => array_fill(0, $n, 'HMW')]);
    $check($valid($s) === $ok, "$m with $n packs is " . ($ok ? 'allowed' : 'rejected'));
}
$check(!$valid($with('draft', ['packCount' => '3x'])), 'a non-integer packCount is rejected');
$msg = SWULimitedValidateSettings($with('sealed', ['packCount' => 5, 'lineup' => array_fill(0, 5, 'HMW')]));
$check($msg === ['Sealed uses 6–16 packs per player.'], 'the range message names the range: ' . json_encode($msg, JSON_UNESCAPED_UNICODE));

echo "ChaosMinimumSets:\n";
$check(!$valid($with('draft', ['packMode' => 'chaos', 'chaosSets' => ['SOR', 'TWI']])), 'Draft chaos with 2 sets is rejected');
$check($valid($with('draft', ['packMode' => 'chaos', 'chaosSets' => ['SOR', 'TWI', 'HMW']])), 'Draft chaos with 3 sets is allowed');
$check($valid($with('sealed', ['packMode' => 'chaos', 'chaosSets' => ['SOR', 'TWI']])), 'Sealed chaos with 2 sets is allowed');
$check(!$valid($with('sealed', ['packMode' => 'chaos', 'chaosSets' => ['SOR']])), 'Sealed chaos with 1 set is rejected');

echo "RejectsCraftedSettings:\n";
$crafted = [
    'unknown mode'           => $with('draft', ['mode' => 'cube']),
    'bestOf 2'               => $with('draft', ['bestOf' => 2]),
    'unknown packMode'       => $with('draft', ['packMode' => 'mystery']),
    'TS26 in a lineup'       => $with('draft', ['packMode' => 'hostchoice', 'lineup' => ['SOR', 'TS26', 'HMW']]),
    'IC27 in a lineup'       => $with('draft', ['packMode' => 'hostchoice', 'lineup' => ['SOR', 'IC27', 'HMW']]),
    'lineup length ≠ packs'  => $with('draft', ['packMode' => 'hostchoice', 'lineup' => ['SOR', 'HMW']]),
    'duplicate chaos sets'   => $with('draft', ['packMode' => 'chaos', 'chaosSets' => ['SOR', 'SOR', 'TWI']]),
    'IBH as the single set'  => $with('draft', ['singleSet' => 'IBH']),
    'IC27 in chaos'          => $with('sealed', ['packMode' => 'chaos', 'chaosSets' => ['SOR', 'IC27']]),
];
foreach ($crafted as $label => $s) $check(!$valid($s), "rejected: $label");
$multi = SWULimitedValidateSettings($with('draft', ['bestOf' => 2, 'packCount' => 9]));
$check(count($multi) >= 2, 'every error is reported, not just the first (' . count($multi) . ')');
$check($valid($with('draft', ['packMode' => 'hostchoice', 'lineup' => ['SOR', 'LAW', 'HMW']])), 'a real hostchoice lineup is allowed');

echo "LineupsPerMode:\n";
$rng = SWULimitedRng(1);
$single = SWULimitedRollLineups($with('draft', ['singleSet' => 'TWI']), 5, $rng);
$check(array_keys($single) === [1, 2, 3, 4, 5], 'seats are keyed 1..N');
$check(array_unique(array_merge(...array_values($single))) === ['TWI'] && count($single[3]) === 3, 'single: every pack is the single set');
$host = $with('draft', ['packMode' => 'hostchoice', 'lineup' => ['SOR', 'LAW', 'HMW']]);
$hl = SWULimitedRollLineups($host, 4, $rng);
$check(count(array_filter($hl, fn($l) => $l === ['SOR', 'LAW', 'HMW'])) === 4, 'hostchoice: every seat opens the host lineup');
$chaos = $with('draft', ['packMode' => 'chaos', 'packCount' => 6, 'lineup' => array_fill(0, 6, 'HMW'), 'chaosSets' => ['SOR', 'JTL', 'HMW']]);
$cl = SWULimitedRollLineups($chaos, 8, $rng);
$flat = array_merge(...array_values($cl));
$check(count($flat) === 48 && array_diff($flat, ['SOR', 'JTL', 'HMW']) === [], 'chaos: 8 seats × 6 packs, all from the chosen sets');
$check(count(array_unique(array_map('json_encode', $cl))) > 1, 'chaos: seats roll their own packs');

echo "MinimalValidSettingsRoll:\n";
// A client sends only the active mode's fields; if that validates, rolling it must not fail.
foreach ([['mode' => 'draft', 'bestOf' => 1, 'packCount' => 3, 'packMode' => 'single', 'singleSet' => 'HMW'],
          ['mode' => 'draft', 'bestOf' => 3, 'packCount' => 3, 'packMode' => 'hostchoice', 'lineup' => ['SOR', 'LAW', 'HMW']],
          ['mode' => 'sealed', 'bestOf' => 1, 'packCount' => 6, 'packMode' => 'chaos', 'chaosSets' => ['SOR', 'HMW']]] as $min) {
    $label = $min['packMode'];
    $check($valid($min), "minimal $label settings validate");
    try { $rolled = SWULimitedRollLineups($min, 3, SWULimitedRng(9)); $ok = count($rolled) === 3 && count($rolled[1]) === $min['packCount']; }
    catch (Throwable $e) { $ok = false; }
    $check($ok, "minimal $label settings roll lineups without error");
}

echo "SameSeedSameLineups:\n";
$check(SWULimitedRollLineups($chaos, 8, SWULimitedRng(42)) === SWULimitedRollLineups($chaos, 8, SWULimitedRng(42)), 'same seed, same chaos lineups');

echo "BasesInPlay:\n";
$sor = SWULimitedCommonBasesInPlay(['SOR']);
$check($sor !== [] && array_filter($sor, fn($b) => CardSet($b) !== 'SOR' || CardType($b) !== 'Base' || CardRarity($b) !== 'Common') === [], 'SOR in play → only SOR Common bases (' . count($sor) . ')');
$sorRare = array_values(array_filter(GetAllCardIds(), fn($c) => CardSet($c) === 'SOR' && CardType($c) === 'Base' && CardRarity($c) === 'Rare'));
$check($sorRare !== [] && array_intersect($sorRare, $sor) === [], 'no SOR Rare base is offered as a base in play');
$both = SWULimitedCommonBasesInPlay(['SOR', 'HMW']);
$check(count($both) === count($sor) + count(SWULimitedCommonBasesInPlay(['HMW'])), 'two sets in play → the union of both');
$check(SWULimitedSetsInPlay($host) === ['SOR', 'LAW', 'HMW'] && SWULimitedSetsInPlay(SWULimitedDefaultSettings('draft')) === ['HMW'], 'sets in play per mode');
$check(SWULimitedSetsInPlay($with('draft', ['packMode' => 'hostchoice', 'lineup' => ['HMW', 'SOR', 'HMW']])) === ['SOR', 'HMW'], 'sets in play are distinct, in release order');

echo "SealedPoolIs16PerPack:\n";
$pool = SWULimitedOpenSealedPool(array_fill(0, 6, 'SOR'), SWULimitedRng(5));
$check(count($pool) === 96, '6 packs open into 96 cards');
$check(count(array_filter($pool, fn($c) => CardType($c) === 'Leader')) === 6, 'the pool holds exactly 6 leaders');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
