<?php
// The 2026-09-24 KILL-WEIGHT screen's arms (both default OFF). See SWUSim/Custom/BotFeatures.php.
//
// WHY THESE ARMS EXIST. Block 2 of the owner's 100-game human-vs-bot run (2026-09-23) put a
// `heuristic-hardcontrol` Luke ASH_005 against an Ahsoka ASH_009 go-wide deck, 24 games, and it went
// 0W/24L. Against block 1's midrange arm on the SAME matchup:
//
//     attacks sent at BASE      79% (154/196)  ->  57% (94/164)
//     damage DEALT by the bot   14.4 mean      ->   8.2 mean   (permutation p = 0.0008)
//     damage TAKEN by R5, cum.  28.9           ->  30.2
//
// The extra trading bought NOTHING: incoming damage was unchanged-to-worse while the bot's own output
// nearly halved. The suspected cause is the weight table itself — hardcontrol prices `kill` at 1.50
// against a FLAT `base` of 0.60, so a trade outscores a swing 2.5:1 before any board state is read.
// These arms move that ratio and let the strength test say whether it was ever paying for itself.
//
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
//     php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_killweight_arms_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';

foreach (['kill-down', 'kill-base'] as $p) {
    $check(SWUBotVariantDisabled("w-$p") === ["w:$p"], "probe @w-$p is registered");
}

// Weights under a variant, for one style.
$w = function (string $variant, string $style) {
    SWUBotSetDisabledFeatures(SWUBotVariantDisabled($variant) ?? []);
    $o = SWUBotWeights($style, 1);
    SWUBotSetDisabledFeatures([]);
    return $o;
};

// ── the SHIPPED ratio is the thing under test: hardcontrol wants a kill 2.5x more than a swing ──
$hc = $w('', 'hardcontrol');
$check(abs($hc['kill'] - 1.50) < 1e-9 && abs($hc['base'] - 0.60) < 1e-9,
    'shipped hardcontrol is kill 1.50 / base 0.60');
$check(abs($hc['kill'] / $hc['base'] - 2.5) < 1e-9, 'shipped hardcontrol kill:base ratio is 2.5');

// ── kill-down: scale the kill term only ──
$kd = $w('w-kill-down', 'hardcontrol');
$check(abs($kd['kill'] - 0.6 * $hc['kill']) < 1e-9, 'kill-down scales kill by 0.6');
$check(count(array_diff_assoc($kd, $hc)) === 1, 'kill-down changes exactly one weight');
$check(abs($kd['kill'] / $kd['base'] - 1.5) < 1e-9, 'kill-down brings hardcontrol kill:base to 1.5');

// ── kill-base: move both sides of the ratio, a stronger version of the same hypothesis ──
$kb = $w('w-kill-base', 'hardcontrol');
$check(abs($kb['kill'] - 0.6 * $hc['kill']) < 1e-9 && abs($kb['base'] - 1.3 * $hc['base']) < 1e-9,
    'kill-base scales kill 0.6 and base 1.3');
$check(count(array_diff_assoc($kb, $hc)) === 2, 'kill-base changes exactly two weights');
$check($kb['kill'] / $kb['base'] < $kd['kill'] / $kd['base'], 'kill-base shifts the ratio further than kill-down');

// ── ⚠ A PROBE IS GLOBAL. It is applied in SWUBotWeights() for EVERY style, so this screen also moves
// aggro and midrange. That is deliberate for a first screen — strength_report.py breaks the result down
// per style, so "helps control, hurts aggro" is visible and would FAIL the owner's "raise the weak,
// never lower the strong" ruling. Pin the blast radius so nobody reads these arms as control-only.
foreach (['hyperaggro', 'softaggro', 'midrange', 'softcontrol'] as $style) {
    $b = $w('', $style);
    $a = $w('w-kill-down', $style);
    $check(abs($a['kill'] - 0.6 * $b['kill']) < 1e-9, "kill-down also scales $style (probes are global)");
}

bot_test_finish();
