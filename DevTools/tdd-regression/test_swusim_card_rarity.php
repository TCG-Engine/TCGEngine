<?php
// RUN VIA CLI:
//   docker exec otmtcge-swusim-web-server-1 php -d xdebug.mode=off /var/www/html/TCGEngine/DevTools/tdd-regression/test_swusim_card_rarity.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/../../AppCore/SWU/DeckValidation.php';

$checks = [];
$ETERNAL = ['SOR','SHD','TWI','JTL','LOF','SEC','IBH','LAW','TS26','ASH'];
$C = ['Common'];

// 1. Vocabulary normalization: both sites' vocabularies collapse to the same distinct codes.
$checks['normalizes full word']   = _SWURarityCode('Common') === 'C';
$checks['normalizes letter code'] = _SWURarityCode('C') === 'C';
$checks['Special is S not C']     = _SWURarityCode('Special') === 'S';
$checks['Uncommon is U']          = _SWURarityCode('Uncommon') === 'U';
$checks['Rare is R']              = _SWURarityCode('Rare') === 'R';
$checks['Legendary is L']         = _SWURarityCode('Legendary') === 'L';
$checks['empty stays empty']      = _SWURarityCode('') === '';
// The whole normalization scheme rests on these five being distinct.
$codes = array_map('_SWURarityCode', ['Common','Uncommon','Rare','Legendary','Special']);
$checks['five rarities have five distinct codes'] = count(array_unique($codes)) === 5;

// 2. Raw accessor reads SWUSim's SET_NNN-keyed dictionary.
$checks['reads a common']  = _SWURarityCode(SWUCardRarity('SOR_033')) === 'C';
$checks['reads a special'] = _SWURarityCode(SWUCardRarity('SOR_236')) === 'S';
$checks['unknown id null'] = SWUCardRarity('ZZZ_999') === null;

// 3. Group-wide check: the plain Common case.
$checks['plain common passes'] = SWUCardHasLegalRarityPrint('SOR_033', $ETERNAL, $C) === true;
$checks['special fails']       = SWUCardHasLegalRarityPrint('SOR_236', $ETERNAL, $C) === false;
$checks['rare fails']          = SWUCardHasLegalRarityPrint('JTL_140', $ETERNAL, $C) === false;
$checks['uncommon fails']      = SWUCardHasLegalRarityPrint('JTL_170', $ETERNAL, $C) === false;

// 4. REPRINT GROUP, upward: SHD_030 Death Trooper is Special, but SOR_033 is Common -> legal.
$checks['special reprint of a common passes'] = SWUCardHasLegalRarityPrint('SHD_030', $ETERNAL, $C) === true;

// 5. REPRINT GROUP, downshift: SOR_125 Prepare for Takeoff is Uncommon, JTL_128 is Common.
//    BOTH printings must pass — a deck may list either.
$checks['downshifted reprint passes from common printing']   = SWUCardHasLegalRarityPrint('JTL_128', $ETERNAL, $C) === true;
$checks['downshifted reprint passes from uncommon printing'] = SWUCardHasLegalRarityPrint('SOR_125', $ETERNAL, $C) === true;

// 6. SET GATE: a Common is only legal if the COMMON printing is in a legal set. Restricting the
//    legal sets to SOR alone must reject JTL_128 (its only Common printing is in JTL).
$checks['set gate rejects out-of-pool common'] = SWUCardHasLegalRarityPrint('JTL_128', ['SOR'], $C) === false;

// 7. FAIL CLOSED: no rarity data -> never matches.
$checks['unknown card fails closed'] = SWUCardHasLegalRarityPrint('ZZZ_999', $ETERNAL, $C) === false;

// 8. Multi-rarity allowance works (nothing uses it today; guards the generalization).
$checks['multi-rarity allowance'] = SWUCardHasLegalRarityPrint('JTL_170', $ETERNAL, ['Common','Uncommon']) === true;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
