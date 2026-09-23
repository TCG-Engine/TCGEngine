<?php
// The SHAPE half of the deck-style classifier: features, the 0-4 score, and the style + confidence it rounds to.
// (The label half and the accuracy bar are bot_deckstyle_test.php.)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_deckstyle_shape_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Rl/CardTags.php';
require_once './SWUSim/Custom/BotDeckStyle.php';
$fails = 0;
$check = function ($ok, $msg, $detail = '') use (&$fails) {
    echo ($ok ? 'PASS' : 'FAIL') . ": $msg" . (!$ok && $detail !== '' ? "  [got: $detail]" : '') . "\n";
    if (!$ok) $fails++;
};
$deckOf = fn(string $f) => SWUBotDeckFromFixtureText((string)file_get_contents("./SWUSim/Tests/BotFixtures/meta-2026-09/$f.txt"));

// ── features ────────────────────────────────────────────────────────────────────────────────────
$vader = $deckOf('vader_yellow');
$f = SWUBotDeckFeatures($vader);
$check($f['n'] >= 45, 'Vader Yellow: the main deck is counted', strval($f['n']));
$check($f['space'] > $f['units'] * 0.6, 'Vader Yellow: mostly space units', $f['space'] . '/' . $f['units']);
$check($f['avgCost'] < 3.5, 'Vader Yellow: a low curve', strval($f['avgCost']));
$krennic = $deckOf('krennic_splash');
$fc = SWUBotDeckFeatures($krennic);
$check($fc['removal'] + $fc['wipe'] >= 4, 'Krennic Splash: it carries answers', ($fc['removal'] + $fc['wipe']) . '');
$check($fc['big'] > $f['big'], 'Krennic Splash holds more 6+ drops than Vader Yellow', $fc['big'] . ' vs ' . $f['big']);
$check(SWUBotDeckFeatures(['leader' => '', 'base' => '', 'cards' => []])['n'] === 0, 'an empty deck has no cards');
$check(SWUBotDeckFeatures(['leader' => 'JTL_006', 'base' => 'JTL_020', 'cards' => ['ZZZ_999' => 3]])['n'] === 3,
    'an unknown card id still counts toward the deck size');

// ── the score and the style it rounds to ────────────────────────────────────────────────────────
$sv = SWUBotDeckShapeScore($vader); $sk = SWUBotDeckShapeScore($krennic);
$check($sv < $sk, 'Vader Yellow scores nearer aggro than Krennic Splash', "$sv vs $sk");
$check($sv >= 0.0 && $sk <= 4.0, 'the score stays on the 0-4 scale', "$sv / $sk");
$check(SWUBotStyleFromScore(0.0)['style'] === 'hyperaggro', 'score 0 is hyperaggro');
$check(SWUBotStyleFromScore(2.0)['style'] === 'midrange', 'score 2 is midrange');
$check(SWUBotStyleFromScore(4.0)['style'] === 'hardcontrol', 'score 4 is hardcontrol');
$check(SWUBotStyleFromScore(-3.0)['style'] === 'hyperaggro' && SWUBotStyleFromScore(9.0)['style'] === 'hardcontrol',
    'a score outside the scale is clamped');
$check(SWUBotStyleFromScore(2.0)['confidence'] === 'high', 'dead centre is high confidence');
$check(SWUBotStyleFromScore(2.45)['confidence'] === 'low', 'a score next to a boundary is low confidence');
$check(SWUBotStyleFromScore(2.2)['confidence'] === 'medium', '0.2 from centre is medium confidence');

// ── burn pulls a deck toward aggro ──────────────────────────────────────────────────────────────
// Boba Lake Country carries 18 burn cards, the most of any labelled deck. Swap them for a neutral 3-drop and the
// score must rise: this is the one assertion that notices the sign of the burn weight.
// Swapping the cards out would change the curve too, so the weight itself is switched off instead: same deck, same
// features, burn priced at nothing. The score must RISE, i.e. burn was pulling it toward aggro.
$boba = $deckOf('boba_lakecountry');
$check(SWUBotDeckFeatures($boba)['burn'] >= 10, 'fixture: Boba Lake Country is the burn deck', strval(SWUBotDeckFeatures($boba)['burn']));
$withBurn = SWUBotDeckShapeScore($boba);
$GLOBALS['SWUDeckStyleWeightOverride'] = ['burn' => 0.0];
$withoutBurn = SWUBotDeckShapeScore($boba);
unset($GLOBALS['SWUDeckStyleWeightOverride']);
$check($withoutBurn > $withBurn, 'burn pulls the score toward aggro', "$withBurn -> $withoutBurn");

// ── it must NOT apply the piloting shift ────────────────────────────────────────────────────────
// Maul (LOF_009) carries the 'tempo' flavour, which SWUBotRacingRank shifts one step toward control at PLAY time.
// The classifier reproduces the owner's LABEL (midrange). If it ALSO applied that shift, Maul would be shifted
// twice — the double-count that 'flavourcap' was written for — and would land in HARD control.
// ⚠ This is deliberately a two-step guard, not "Maul must come out midrange": how close the scan gets on any one
// deck is the accuracy bar's job (bot_deckstyle_test.php, leave-one-out over all 23). Maul reads soft control, one
// step high, and that is counted there.
$maul = $deckOf('maul_blueforce');
$maulStyle = SWUBotStyleFromScore(SWUBotDeckShapeScore($maul))['style'];
$check(array_search($maulStyle, SWU_DECKSTYLE_SCALE, true) <= 3, 'Maul Blue Force is not double-shifted into hard control',
    $maulStyle . ' (score ' . SWUBotDeckShapeScore($maul) . ')');
// And the real guarantee: the classifier never consults the piloting layer at all.
$src = (string)file_get_contents('./SWUSim/Custom/BotDeckStyle.php');
$check(!str_contains($src, 'SWUBotRacingRank') && !str_contains($src, 'FlavourRankShift') && !str_contains($src, 'SWUBotDeckFlavours'),
    'the classifier never calls the piloting rank or the flavour shift');

// ── every fixture classifies without error (smoke) ───────────────────────────────────────────────
$n = 0;
foreach (array_merge(glob('./SWUSim/Tests/BotFixtures/meta-2026-09/*.txt') ?: [],
                     glob('./SWUSim/Tests/BotFixtures/meta-2026-09-field/*.txt') ?: []) as $p) {
    $s = SWUBotDeckShapeScore(SWUBotDeckFromFixtureText((string)file_get_contents($p)));
    if (!is_float($s) || $s < 0.0 || $s > 4.0) { $check(false, 'smoke: ' . basename($p) . ' scores on the scale', strval($s)); break; }
    $n++;
}
$check($n >= 85, 'every fixture deck scores without error', strval($n));

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
