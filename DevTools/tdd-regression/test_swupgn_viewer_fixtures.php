<?php
// Our own viewer fixtures are valid, self-consistent and cover what the converter must draw.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_viewer_fixtures.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

foreach (['viewer-game.swupgn' => null, 'viewer-game-p1.swupgn' => 'P1'] as $file => $perspective) {
    $text = @file_get_contents(__DIR__ . "/fixtures/swupgn/$file");
    SwuPgnTestCheck(is_string($text) && $text !== '', "$file exists");
    $doc = SwuPgnParse((string)$text);
    $v = SwuPgnValidate($doc);
    SwuPgnTestEq($v['errors'], [], "$file validates with no errors");
    SwuPgnTestEq(SwuPgnCheckKeyframes($doc['events'])['mismatches'], [], "$file: keyframes agree with the events (§14)");
    SwuPgnTestEq($doc['headers']['Perspective'] ?? null, $perspective, "$file: Perspective header");
}
$doc = SwuPgnParse(file_get_contents(__DIR__ . '/fixtures/swupgn/viewer-game.swupgn'));
$final = SwuPgnFold($doc['events']);
$cards = array_merge($final['players'][1]['cards'], $final['players'][2]['cards']);
$byId = array_column($cards, null, 'id');
SwuPgnTestCheck(isset($byId['SOR#108']) && in_array('LOF#091', $byId['SOR#108']['upgrades'], true), 'covers a printed upgrade on a ground unit');
SwuPgnTestCheck(isset($byId['JTL#095']) && $byId['JTL#095']['zone'] === 'space' && in_array('JTL#058', $byId['JTL#095']['upgrades'], true), 'covers a pilot on a space unit');
SwuPgnTestCheck(isset($byId['SOR#010']) && in_array('JTL#032', $byId['SOR#010']['captured'] ?? [], true), 'covers a deployed leader holding a captive');
SwuPgnTestCheck(($byId['SOR#108']['experience'] ?? 0) + ($byId['SOR#045']['experience'] ?? 0) >= 1, 'covers an Experience token');
SwuPgnTestCheck(($byId['SOR#108']['shields'] ?? 0) >= 1, 'covers a Shield token');
SwuPgnTestCheck(count((array)($byId['SOR#108']['statusTokens'] ?? [])) >= 1, 'covers a status token (Advantage)');
SwuPgnTestCheck(isset($byId['ZZZ#999']), 'covers an unknown card in play');
SwuPgnTestCheck($final['players'][1]['credits'] >= 1 && $final['players'][2]['hasForce'] === true, 'covers a Credit token and the Force');
SwuPgnTestCheck(($final['players'][1]['leader']['deployed'] ?? false) === true, 'covers a deployed leader');
SwuPgnTestCheck($final['players'][2]['baseHp'] < $final['players'][2]['baseMaxHp'], 'covers base damage');
SwuPgnTestCheck(count(SwuPgnSteps($doc)['rounds']) === 3, 'covers setup + two rounds');
$p1 = SwuPgnParse(file_get_contents(__DIR__ . '/fixtures/swupgn/viewer-game-p1.swupgn'));
$p2Draws = array_filter($p1['events'], fn($e) => ($e['t'] ?? '') === 'DRAW' && ($e['p'] ?? 0) === 2);
SwuPgnTestCheck($p2Draws && !array_filter($p2Draws, fn($e) => $e['cards'] !== []), 'the P1 perspective file blanks P2\'s DRAW cards');

SwuPgnTestFinish();
