<?php
// The THREE-LAYER shape scan (2026-10-11; SWU_DECKSTYLE_MODEL in BotDeckStyle.php): no leader term, the accuracy bar over every
// owner-labelled list, the style = the most probable class, and the features the layers read.
// (Per-deck pins: bot_deckstyle_shape_test.php. The label half: bot_deckstyle_test.php.)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off SWUSim/DevTools/tests/bot_deckstyle_layers_test.php
chdir(dirname(__DIR__, 3));
require_once './SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once './SWUSim/Rl/CardTags.php';
require_once './SWUSim/Custom/BotDeckStyle.php';
$fails = 0;
$check = function ($ok, $msg, $detail = '') use (&$fails) {
    echo ($ok ? 'PASS' : 'FAIL') . ": $msg" . (!$ok && $detail !== '' ? "  [got: $detail]" : '') . "\n";
    if (!$ok) $fails++;
};
$S = array_flip(SWU_DECKSTYLE_SCALE);
$shape = fn(array $d) => SWUBotStyleFromScore(SWUBotDeckShapeScore($d))['style'];
$labelled = [];
foreach (['ash-meta-2026-09', 'hmw-meta-2026-09', 'force-fam-HMW-predictions'] as $g) foreach (glob("./SWUSim/Tests/BotFixtures/$g/*.txt") ?: [] as $p) {
    $t = (string)file_get_contents($p); preg_match('/^# DeckStyle:\s*(\S+)/m', $t, $dm); preg_match('/^# Style:\s*(\S+)/m', $t, $m);
    $l = strtolower(strval($dm[1] ?? $m[1] ?? ''));
    if (isset($S[$l])) $labelled[] = ["$g/" . basename($p, '.txt'), SWUBotDeckFromFixtureText($t), $l];
}
$check(count($labelled) >= 50, 'the owner-labelled fixture groups resolved', strval(count($labelled)));

// ── No leader term: the owner's requirement ("classify the deck's shape rather than interpret by leader") ──────────────────────────
$src = (string)file_get_contents('./SWUSim/Custom/BotDeckStyle.php');
$code = preg_replace('~//[^\n]*~', '', $src);   // comments may NAME the old list; code may not READ it
$check(!preg_match('/SWU_BOT_AGGRO_LEADERS|SWUBotDeckFlavours/', $code),
    'the classifier code never reads the aggro-leader list or the deck flavours');
// Same cards under any leader → same score. Every labelled list is re-scored with its leader swapped to one that is on no list.
$moved = [];
foreach ($labelled as [$name, $d, $l]) { $e = $d; $e['leader'] = 'SOR_998'; if (abs(SWUBotDeckShapeScore($e) - SWUBotDeckShapeScore($d)) > 1e-9) $moved[] = $name; }
$check($moved === [], 'swapping the leader never moves the score', implode(', ', array_slice($moved, 0, 3)));

// ── The style is the most probable class (or hard control by event share) ──────────────────────────────────────────────────────────
$bad = [];
foreach ($labelled as [$name, $d, $l]) {
    $L = SWUBotDeckLayerProbs(SWUBotDeckFeatures($d));
    $want = $L['hard'] ? 'hardcontrol' : SWU_DECKSTYLE_SCALE[array_keys($L['p'], max($L['p']))[0]];
    if ($shape($d) !== $want) $bad[] = "$name ($want vs " . $shape($d) . ')';
}
$check($bad === [], 'every style is the argmax of the layer probabilities', implode('; ', array_slice($bad, 0, 3)));
// Probabilities are probabilities.
$L = SWUBotDeckLayerProbs(SWUBotDeckFeatures($labelled[0][1]));
$check(abs(array_sum($L['p']) - 1.0) < 1e-9 && min($L['p']) >= 0.0, 'the four class probabilities sum to 1');

// ── THE ACCURACY BAR (in-sample over every owner list; leave-one-archetype-out it was 89% / 100% — fit_deckstyle.py) ──────────────────
$ex = 0; $w1 = 0; $byStyle = [];
foreach ($labelled as [$name, $d, $l]) {
    $p = $shape($d); $ex += $p === $l ? 1 : 0; $w1 += abs($S[$p] - $S[$l]) <= 1 ? 1 : 0;
    $byStyle[$l][] = $p === $l;
}
$n = count($labelled);
$check($ex / $n >= 0.85, 'at least 85% of owner-labelled lists read their exact style on shape', "$ex/$n");
$check($w1 === $n, 'every owner-labelled list reads within one step on shape', "$w1/$n");
// Each END of the middle split holds on its own (the midrange | soft control layer has a job).
foreach (['midrange', 'softcontrol'] as $st) {
    $v = $byStyle[$st] ?? []; $ok = array_sum($v);
    $check(count($v) >= 5 && $ok / count($v) >= 0.75, "at least 75% of $st-labelled lists read $st", $ok . '/' . count($v));
}

// ── The features the layers read are counted from the cards ────────────────────────────────────────────────────────────────────────
// Expected counts are derived from SWUBotCardTags / card data here, never pinned.
// Two lists from opposite ends, so a feature that is 0 in one (Krennic has no attack/buff cards) is exercised by the other.
foreach (['director-krennic_law_blue-splash', 'ahsoka-tano_ash_blue'] as $fx) {
    $deck = SWUBotDeckFromFixtureText((string)file_get_contents("./SWUSim/Tests/BotFixtures/ash-meta-2026-09/$fx.txt"));
    $want = ['heal' => 0, 'aggroTags' => 0, 'units3' => 0, 'events3' => 0];
    foreach ($deck['cards'] as $id => $c) {
        $tags = array_unique(SWUBotCardTags(strval($id)));
        if (in_array('heal', $tags, true)) $want['heal'] += $c;
        $want['aggroTags'] += $c * count(array_intersect($tags, SWU_DECKSTYLE_AGGRO_TAGS));
        if (str_contains(strval(CardType($id)), 'Unit') && intval(CardCost($id)) <= 3) $want['units3'] += $c;
        if (str_contains(strval(CardType($id)), 'Event') && intval(CardCost($id)) <= 3) $want['events3'] += $c;
    }
    $f = SWUBotDeckFeatures($deck);
    foreach ($want as $k => $v) $check($f[$k] === $v, "feature '$k' counts the cards ($fx: $v)", strval($f[$k]));
    $check($f['eventsBig'] === $f['events'] - $f['events3'], "$fx: 'eventsBig' is the events above cost 3");
    $nonzero[$fx] = $want;
}
$check($nonzero['director-krennic_law_blue-splash']['heal'] > 0 && $nonzero['ahsoka-tano_ash_blue']['aggroTags'] > 0,
    'premise: Krennic carries healers and Ahsoka Blue carries attack/buff cards (each feature is non-zero somewhere)');

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
