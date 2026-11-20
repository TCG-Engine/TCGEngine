<?php
// Limited booster-set list — docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.2, §4.2.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_booster_sets_test.php
chdir(dirname(__DIR__, 3));
require_once './AppCore/SWU/BoosterSets.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$sets = SWUBoosterSets();
$check(array_keys($sets) === ['SOR', 'SHD', 'TWI', 'JTL', 'LOF', 'SEC', 'LAW', 'ASH', 'HMW'], 'booster sets in release order: ' . implode(',', array_keys($sets)));
$check(SWULatestBoosterSet() === 'HMW', 'latest main set is the last entry (HMW)');

foreach (['IBH', 'TS26', 'IC27'] as $s) $check(!SWUIsBoosterSet($s), "$s has no Limited boosters");
$preview = require './AppCore/SWU/PreviewSets.php';
foreach ((array)$preview as $s) $check(!SWUIsBoosterSet($s), "preview set $s is not a booster set");

$eras = ['SOR' => 'classic', 'SHD' => 'classic', 'TWI' => 'classic', 'JTL' => 'jtl', 'LOF' => 'jtl', 'SEC' => 'jtl',
         'LAW' => 'law', 'ASH' => 'law', 'HMW' => 'law'];
foreach ($eras as $s => $era) $check(SWUBoosterSetEra($s) === $era, "$s era is $era");
$check(SWUBoosterSetEra('IBH') === null, 'a non-booster set has no era');

foreach (['SOR', 'SHD', 'TWI', 'JTL', 'LOF', 'SEC', 'LAW'] as $s) $check(SWUBoosterSetRareSlotBases($s) === true, "$s puts rare bases in the rare slot");
foreach (['ASH', 'HMW'] as $s) $check(SWUBoosterSetRareSlotBases($s) === false, "$s puts every base in the base slot");

// Supplemental packs (owner 2026-11-05): IC27 Icons packs may be picked in Host's Choice only — never Single Set or Chaos,
// and never the "latest" default (that stays the last MAIN set).
$check(array_keys(SWUSupplementalBoosterSets()) === ['IC27'], 'supplemental booster sets: IC27');
$check(SWUSupplementalBoosterSets()['IC27']['collation'] === 'icons', 'IC27 packs use the Icons collation');
$check(SWUIsHostChoiceSet('IC27') && SWUIsHostChoiceSet('HMW') && SWUIsHostChoiceSet('SOR') && !SWUIsHostChoiceSet('IBH') && !SWUIsHostChoiceSet('TS26'),
       'Host\'s Choice offers every main booster set plus IC27, and nothing else');
$check(SWUHostChoiceSets() === array_merge(array_keys($sets), ['IC27']), 'Host\'s Choice lists the main sets, then the supplemental ones');

$all = require './AppCore/SWU/AllSets.php';
foreach (array_keys($sets) as $s) $check(isset($all[$s]), "$s is a registered set code in AllSets.php");

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
