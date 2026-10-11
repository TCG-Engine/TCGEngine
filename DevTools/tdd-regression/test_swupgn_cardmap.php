<?php
// SWU-PGN card ids → SWUSim CardIDs (SWU-PGN/1.0 spec §6.1).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_cardmap.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
restore_error_handler();   // engine files are not warning-clean at include time; the checks below are what matter
chdir(__DIR__ . '/../..');
include_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Custom/SwuPgnCardMap.php';

SwuPgnTestEq(SwuPgnMapCardId('SOR#108'), 'SOR_108', 'SET#NUM maps to SET_NNN');
SwuPgnTestEq(SwuPgnMapCardId('SOR#108:3'), 'SOR_108', 'the :N copy suffix is stripped first');
SwuPgnTestEq(SwuPgnMapCardId('SOR#8'), BuildCardID('SOR', 8), 'an unpadded number is padded the dictionary way');
SwuPgnTestEq(SwuPgnMapCardId('TS26#1'), 'TS26_01', 'TS26 uses two-digit numbers');
SwuPgnTestEq(SwuPgnMapCardId('TS26#001'), 'TS26_01', 'a zero-padded TS26 number still maps');
$ic27 = null;
foreach (array_keys($GLOBALS['titleData']) as $id) if (str_starts_with($id, 'IC27_') && !str_contains($id, '_T')) { $ic27 = $id; break; }
SwuPgnTestCheck($ic27 !== null, 'the dictionary has an IC27 card to test with');
[$set, $num] = explode('_', (string)$ic27) + [1 => '0'];
SwuPgnTestEq(SwuPgnMapCardId("$set#$num"), $ic27, 'a set code containing digits (IC27) maps');
SwuPgnTestEq(SwuPgnMapCardId('ZZZ#999'), null, 'an unknown card is null');
SwuPgnTestEq(SwuPgnMapCardId('not an id'), null, 'garbage is null');
SwuPgnTestEq(SwuPgnMapCardId(42), null, 'a non-string is null');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:shield#' . GetCardUUID('SOR_T02')), 'SOR_T02', 'a token maps by its official id');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:shield#' . GetCardUUID('SOR_T02') . ':2'), 'SOR_T02', 'a token copy suffix is stripped');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:experience'), 'SOR_T01', 'a degraded token (no id) maps by name');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:advantage#not-a-number'), 'ASH_T02', 'a non-numeric token id falls back to the name');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:weakness#weakness-id'), 'HMW_T02', 'weakness by name');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:credit#12345'), 'LAW_T01', 'reserved name `credit` (§6.1) wins over any id');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:the-force#12345'), 'LOF_T03', 'reserved name `the-force` (§6.1)');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:mystery#1'), null, 'an unknown token is null');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:palpatine#' . ltrim(GetCardUUID('TWI_017'), '0')), 'TWI_017', 'an official id written without its leading zeros matches');
SwuPgnTestEq(SwuPgnMapCardId('TOKEN:palpatine#' . GetCardUUID('TWI_017')), 'TWI_017', 'an official id written WITH its leading zeros matches');

SwuPgnTestFinish();
