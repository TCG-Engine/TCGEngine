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
$deckOf = fn(string $f) => SWUBotDeckFromFixtureText((string)file_get_contents("./SWUSim/Tests/BotFixtures/ash-meta-2026-09/$f.txt"));

// ── features ────────────────────────────────────────────────────────────────────────────────────
$vader = $deckOf('darth-vader_jtl_yellow');
$f = SWUBotDeckFeatures($vader);
$check($f['n'] >= 45, 'Vader Yellow: the main deck is counted', strval($f['n']));
$check($f['space'] > $f['units'] * 0.6, 'Vader Yellow: mostly space units', $f['space'] . '/' . $f['units']);
$check($f['avgCost'] < 3.5, 'Vader Yellow: a low curve', strval($f['avgCost']));
$krennic = $deckOf('director-krennic_law_blue-splash');
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
$boba = $deckOf('boba-fett_jtl_lake-country');
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
$maul = $deckOf('darth-maul_lof_blue-force');
$maulStyle = SWUBotStyleFromScore(SWUBotDeckShapeScore($maul))['style'];
$check(array_search($maulStyle, SWU_DECKSTYLE_SCALE, true) <= 3, 'Maul Blue Force is not double-shifted into hard control',
    $maulStyle . ' (score ' . SWUBotDeckShapeScore($maul) . ')');
// And the real guarantee: the classifier never consults the piloting layer at all.
$src = (string)file_get_contents('./SWUSim/Custom/BotDeckStyle.php');
$check(!str_contains($src, 'SWUBotRacingRank') && !str_contains($src, 'FlavourRankShift') && !str_contains($src, 'SWUBotDeckFlavours'),
    'the classifier never calls the piloting rank or the flavour shift');

// ── every fixture classifies without error (smoke) ───────────────────────────────────────────────
// ⚠ Counted from the GLOB, not against a hardcoded floor. It was `>= 85`, which went red the moment the
// 2026-09-29 rename merged greef_datavault away and moved krennic_ninin to force-fam/ (86 -> 84) — a number
// that has to be edited every time a fixture is added or removed tells you nothing about the code. What this
// smoke test actually means is "every fixture scored", so it asserts exactly that.
// meta-2026-09-field/ was deleted 2026-09-29, so this is the one fixture dir the classifier covers.
// weak-2026-09/ is deliberately absent: those decks are built to be bad and are not the field.
$paths = glob('./SWUSim/Tests/BotFixtures/ash-meta-2026-09/*.txt') ?: [];
$n = 0;
foreach ($paths as $p) {
    $s = SWUBotDeckShapeScore(SWUBotDeckFromFixtureText((string)file_get_contents($p)));
    if (!is_float($s) || $s < 0.0 || $s > 4.0) { $check(false, 'smoke: ' . basename($p) . ' scores on the scale', strval($s)); break; }
    $n++;
}
$check(count($paths) >= 20, 'the fixture dirs are not empty (the glob resolved)', strval(count($paths)));
$check($n === count($paths), 'every fixture deck scores without error', $n . '/' . count($paths));

// ── THE 2026-10-01 RE-FIT: the scale's ENDS are reachable ─────────────────────────────────────────
// Before it, the shape scan could not say hyper aggro or hard control at all: the even raw cuts put hyper below
// -1.5, which no labelled hyper aggro list reached, and hard control overlaps soft control on the score. Fixed by
// fitted cut points (SWU_DECKSTYLE_WEIGHTS['cuts']) and the event-share rule for hard control. Pinned by LABEL, read
// from the fixtures, and on the SHAPE scan alone (no 75% label match can supply the answer here).
$shapeStyle = fn(string $p) => SWUBotStyleFromScore(SWUBotDeckShapeScore(SWUBotDeckFromFixtureText((string)file_get_contents($p))))['style'];
$labelOf = function (string $p): string {
    $t = (string)file_get_contents($p);
    preg_match('/^# DeckStyle:\s*(\S+)/m', $t, $dm); preg_match('/^# Style:\s*(\S+)/m', $t, $m);
    return strval($dm[1] ?? $m[1] ?? '');
};
foreach (['hyperaggro', 'hardcontrol'] as $end) {
    $miss = []; $k = 0;
    foreach ($paths as $p) {
        if ($labelOf($p) !== $end) continue;
        $k++;
        if (($got = $shapeStyle($p)) !== $end) $miss[] = basename($p, '.txt') . " -> $got";
    }
    $check($k >= 3 && empty($miss), "every $end-labelled ash-meta deck reads $end on shape ($k decks)", implode('; ', $miss));
}
// …and hard control comes ONLY from the event share: a high shape score alone stays soft control (the 3.49 cap).
// Without the cap the two Data Vault soft control lists (Thrawn, Aurra — the highest scores in the set) read hard.
$tooHard = [];
foreach ($paths as $p) if ($labelOf($p) === 'softcontrol' && $shapeStyle($p) === 'hardcontrol') $tooHard[] = basename($p, '.txt');
$check(empty($tooHard), 'no softcontrol-labelled deck reads hardcontrol on shape (hard control needs the event share)', implode('; ', $tooHard));
// Burn counts linearly: Boba Fett (JTL) Lake Country — 18 burn cards — is hyper aggro (owner 2026-10-01, relabelled
// from soft aggro: "Boba LC with 18 burn cards should be hyper aggro"). A burn cap would pull it back to soft aggro.
$lc = './SWUSim/Tests/BotFixtures/ash-meta-2026-09/boba-fett_jtl_lake-country.txt';
$check($shapeStyle($lc) === 'hyperaggro', 'Boba Fett (JTL) Lake Country (18 burn) reads hyperaggro on shape', $shapeStyle($lc));
// The two Boba Fett (JTL) lists split by plan, not leader (owner 2026-10-01): Blue — midrange "with slight burn",
// 66% ships — read HYPER aggro through the leader nudge (Boba was on the aggro-leader list) and the space term, both
// now gone from the classifier (SWU_DECKSTYLE_NOT_AGGRO_LEADERS; no space term). Ezra Yellow is soft aggro.
$fx = fn(string $n) => "./SWUSim/Tests/BotFixtures/ash-meta-2026-09/$n.txt";
$check($shapeStyle($fx('boba-fett_jtl_blue')) === 'midrange', 'Boba Fett (JTL) Blue reads midrange on shape', $shapeStyle($fx('boba-fett_jtl_blue')));
$check($shapeStyle($fx('ezra-bridger_ash_yellow')) === 'softaggro', 'Ezra Bridger (ASH) Yellow reads softaggro on shape', $shapeStyle($fx('ezra-bridger_ash_yellow')));
$check(in_array('JTL_009', SWU_BOT_AGGRO_LEADERS, true), 'the BOT\'s aggro-leader list still has Boba Fett (JTL) — the exclusion is classifier-only');
// Out of sample: the owner-labelled HMW predictions were never used to place the cuts. Both hyper aggro lists —
// Ninin's Ahsoka (the deck that prompted the re-fit) and Wicket — must read hyper aggro.
foreach (['ahsoka-tano_ash_yellow', 'wicket_hmw_green-splash'] as $hmw) {
    $p = "./SWUSim/Tests/BotFixtures/force-fam-HMW-predictions/$hmw.txt";
    $check($shapeStyle($p) === 'hyperaggro', "HMW holdout: $hmw reads hyperaggro on shape", $shapeStyle($p));
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
