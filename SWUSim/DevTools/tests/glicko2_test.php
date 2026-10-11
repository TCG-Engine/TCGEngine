<?php
// Glicko-2 core math — spec docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §3.2.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/glicko2_test.php
chdir(dirname(__DIR__, 3));
require_once './AppCore/SWU/Glicko2.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$near = fn($a, $b, $eps) => abs($a - $b) <= $eps;

// Glickman's worked example (glicko2.pdf, "Example calculation"), tau = 0.5.
$r = Glicko2Rate(['rating'=>1500,'rd'=>200,'volatility'=>0.06], [
    ['rating'=>1400,'rd'=>30,'score'=>1],
    ['rating'=>1550,'rd'=>100,'score'=>0],
    ['rating'=>1700,'rd'=>300,'score'=>0],
]);
// The paper prints 1464.06 / 151.52 / 0.05999 from rounded intermediates; full-precision implementations
// land on 1464.0507 / 151.5165 / 0.0599960. Volatility is checked to 1e-6 because a wrong tau term in the
// volatility solver moves it by only ~4e-6 here.
$check($near($r['rating'], 1464.0507, 0.001), 'paper example rating 1464.05 (got ' . round($r['rating'], 4) . ')');
$check($near($r['rd'], 151.5165, 0.001), 'paper example RD 151.52 (got ' . round($r['rd'], 4) . ')');
$check($near($r['volatility'], 0.0599960, 0.000001), 'paper example volatility 0.059996 (got ' . round($r['volatility'], 7) . ')');
// Losing three times to much weaker players is surprising, so volatility must rise.
$up = Glicko2Rate(['rating'=>1500,'rd'=>50,'volatility'=>0.06], array_fill(0, 3, ['rating'=>1000,'rd'=>30,'score'=>0]));
$check($near($up['volatility'], 0.0601034, 0.000001), 'upset streak raises volatility to 0.0601034 (got ' . round($up['volatility'], 7) . ')');

// One game, equal players: winner gains exactly what the loser loses (symmetric RD/vol).
$a = ['rating'=>1500,'rd'=>350,'volatility'=>0.06];
$w = Glicko2Rate($a, [['rating'=>1500,'rd'=>350,'score'=>1]]);
$l = Glicko2Rate($a, [['rating'=>1500,'rd'=>350,'score'=>0]]);
$check($w['rating'] > 1500 && $l['rating'] < 1500, 'a win raises, a loss lowers');
$check($near($w['rating'] - 1500, 1500 - $l['rating'], 1e-6), 'equal players: symmetric change');
$check($w['rd'] < 350, 'playing a game lowers RD');

// Upset pays more than an expected win.
$fav = Glicko2Rate(['rating'=>1800,'rd'=>80,'volatility'=>0.06], [['rating'=>1400,'rd'=>80,'score'=>1]]);
$dog = Glicko2Rate(['rating'=>1400,'rd'=>80,'volatility'=>0.06], [['rating'=>1800,'rd'=>80,'score'=>1]]);
$check(($dog['rating'] - 1400) > ($fav['rating'] - 1800) * 3, 'beating a much stronger player pays far more');

// Inactivity inflation.
$check($near(Glicko2InflateRd(50, 0.06, 0), 50, 1e-9), 'no idle time: RD unchanged');
$check(Glicko2InflateRd(50, 0.06, 52) > 50, 'a year idle: RD grows');
$check($near(Glicko2InflateRd(340, 0.06, 1e6), 350, 1e-9), 'RD capped at 350');
$idle  = Glicko2Rate(['rating'=>1600,'rd'=>50,'volatility'=>0.06], [['rating'=>1600,'rd'=>50,'score'=>1]], 52);
$fresh = Glicko2Rate(['rating'=>1600,'rd'=>50,'volatility'=>0.06], [['rating'=>1600,'rd'=>50,'score'=>1]], 0);
$check($idle['rating'] - 1600 > $fresh['rating'] - 1600, 'a returning player moves more than an active one');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
