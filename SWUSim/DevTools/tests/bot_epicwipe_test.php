<?php
// Feature 'epicwipe' (p37) — rule 5 ('control-wipe') also weighs a wipe that is castable only THROUGH the base's aspect waiver: the base
// Epic Action ("Play a card from your hand, ignoring 1 of its … aspect penalties") is the first step of that wipe's line. Owner, Krennic
// Splash questionnaire 2026-10-06: Single Reactor Ignition "should be early against aggro decks. at the 5R turn + 3 credits or 6R + 2C"
// (8 = its cost 10 less the Daimyo's Palace waiver), and the Palace Epic is for "SRI, always". Traced 20 Krennic vs Ahsoka Blue games
// with 'wipekeepaggro' on: SRI held by round 5 in 12, castable-as-offered in 1 — at 8 capacity it costs 10, so rule 5 never saw it,
// and the Epic's own value (what it unlocks, a plain play value) lost to an Expendable Mercenary.
// The rule's own gate decides as for any wipe (2+ defeated, stabilises, net value): the Epic only opens the line.
// Fixtures (dictionary-checked): LAW_008 Director Krennic · LAW_020 Daimyo's Palace (27 HP, "Epic Action: Play a card from your hand,
//   ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties") · LAW_044 Single Reactor Ignition (8; Aggression
//   off-aspect here, so 10) · LOF_059 Nightsister Warrior · ASH_009 Ahsoka Tano · LAW_149 Rey (8, Heroism) · LAW_159 Expendable Mercenary · ASH_048 Imperial Armored Commando · SOR_095 Battlefield Marine 3/3 · JTL_043 · ASH_079 · ASH_052 · ASH_097 · SEC_T01 Spy · ASH_062 The
//   Mandalorian 5/4 · ASH_077 Ryder Azadi 2/5 · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_epicwipe_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-epicwipe') === ['epicwipe'], 'epicwipe is switchable');
$check(in_array('epicwipe', SWUBotFeatureGroups()['p37'] ?? [], true), 'epicwipe is in group p37');
$epic = 'myBase-0!CustomInput!EpicAction';
// Krennic Splash on $res resources and 20 HP left (not facing lethal: rule 4 'break-lethal' already takes the Epic at 9 HP), SRI + a Nightsister in hand, one Nightsister on board; their Ahsoka with $n Marines.
// $flipped: their Ahsoka has deployed (her flip turn) — owner: "wipe on HER flip turn only".
$board = function (int $res, int $n, bool $epicUsed = false, bool $mine = true, bool $flipped = true) use ($build) {
    $build(function ($b) use ($res, $n, $epicUsed, $mine, $flipped) {
        $b->MyLeader('LAW_008', false); $b->MyBase('LAW_020', 7, $epicUsed); $b->TheirLeader('ASH_009', true, $flipped, $flipped, $flipped ? 'unit' : '');
        $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        // SRI plus the plays the traced bot took instead at round 5 (Expendable Mercenary, Imperial Armored Commando).
        foreach (['LAW_044', 'LAW_159', 'ASH_048'] as $c) $b->WithCardInHandForPlayer(1, $c);
        if ($mine) $b->WithGroundUnitForPlayer(1, 'LOF_059', false);
        for ($i = 0; $i < $n; $i++) $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    });
};
$stack = function (string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][1] ?? [])];
};
// A) 8 resources, SRI costs 10 (Aggression off-aspect), their three Marines threaten 9 a round into my 20 HP (a 3-round clock): wiping them
// stabilises, and the Epic opens the wipe.
$board(8, 3);
$check(!empty(array_filter(SWUBotUnits(2), fn($v) => $v['isLeader'])), 'A fixture: Ahsoka is deployed as a unit (her flip)');
$sri = null; foreach (GetHand(1) as $o) if (strval($o->CardID ?? '') === 'LAW_044') $sri = $o;
$check(intval(SWUComputePlayCost(1, $sri)) === 10 && SWUTotalPaymentCapacity(1) === 8, 'A fixture: SRI costs 10 against 8 capacity');
[$on, $cov] = $stack();
$check($on === $epic && in_array('rule:control-wipe', $cov, true), 'A: rule 5 opens the wipe with the Epic; got ' . var_export($on, true) . ' ' . json_encode($cov));
// A1) The same board BEFORE her flip (Ahsoka undeployed): owner, follow-up 2026-10-06 — "wipe on HER flip turn only". No Epic.
$board(8, 3, false, true, false);
[$a1] = $stack();
$check($a1 !== $epic, 'A1: Ahsoka has not flipped — the wipe waits; got ' . var_export($a1, true));
// A3) Her deployed leader counts among the dead: one Marine + flipped Ahsoka — SRI defeats both (2), which passes; she is a unit now.
$board(8, 1);
[$a3, $a3cov] = $stack();
$check($a3 === $epic && in_array('rule:control-wipe', $a3cov, true), 'A3: one Marine + her deployed Ahsoka are the 2 the wipe defeats — rule 5 opens it; got ' . var_export($a3, true) . ' ' . json_encode($a3cov));
// A2) THE TRACED BOARD (kb002, Ahsoka Blue, R5) — and the same once she has FLIPPED: 6 resources + 2 Credits (8 here as resources), 20 HP; their The Mandalorian (5/4, 3
// damage) and Ryder Azadi; my Spy token. Traced, the bot played an Expendable Mercenary ('blocker-first'). The wipe defeats both, costs
// me a 0/2 token, and takes their 7-a-round clock to nothing: rule 5 — which runs before 'blocker-first' — opens it with the Epic.
$a2board = function (bool $flipped) use ($build) {
    $build(function ($b) use ($flipped) {
        $b->MyLeader('LAW_008', false); $b->MyBase('LAW_020', 7); $b->TheirLeader('ASH_009', true, $flipped, $flipped, $flipped ? 'unit' : '');
        $b->FillResourcesForPlayer(1, 'LAW_097', 8);
        foreach (['JTL_043', 'LAW_044', 'LAW_159', 'ASH_079', 'ASH_052', 'ASH_097'] as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(1, 'SEC_T01', false);
        $b->WithGroundUnitForPlayer(2, 'ASH_062', true, 3); $b->WithGroundUnitForPlayer(2, 'ASH_077', true);
    });
};
$a2board(false);
[$a2pre] = $stack();
$check($a2pre !== $epic, 'A2 (as traced, before her flip): the wipe waits; got ' . var_export($a2pre, true));
$a2board(true);
[$a2off, $a2offCov] = $stack('no-epicwipe');
[$a2on, $a2cov] = $stack();
$check($a2off !== $epic, 'A2 fixture: today the traced play is not the Epic; got ' . var_export($a2off, true) . ' ' . json_encode($a2offCov));
$check($a2on === $epic && in_array('rule:control-wipe', $a2cov, true), 'A2: rule 5 opens the wipe with the Epic; got ' . var_export($a2on, true) . ' ' . json_encode($a2cov));
// B) …and the line goes through: Epic, then SRI from the prompt, and their board is gone.
$board(8, 3);
for ($i = 0; $i < 6; $i++) {
    $l = SWUBotLegalActions($gameName, 1);
    if (empty($l['actions'])) break;
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, '');
    if ($p === null || str_contains(strval($p['cardID']), 'Pass')) break;
    $act(1, intval($p['mode'] ?? 100), strval($p['cardID']));
    if (count(SWUBotUnits(2)) === 0) break;
}
$check(count(SWUBotUnits(2)) === 0, 'B: Epic -> SRI resolves, their Marines are defeated; left ' . count(SWUBotUnits(2)));
// B2) Rey (8, Heroism: off-aspect too) in hand as well: the waiver unlocks both, and the prompt must still play the wipe it was taken for.
$build(function ($b) {
    $b->MyLeader('LAW_008', false); $b->MyBase('LAW_020', 7); $b->TheirLeader('ASH_009', true, true, true, 'unit');
    $b->FillResourcesForPlayer(1, 'LAW_097', 8);
    foreach (['LAW_149', 'LAW_044', 'LAW_159'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->WithGroundUnitForPlayer(1, 'LOF_059', false);
    for ($i = 0; $i < 3; $i++) $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
for ($i = 0; $i < 6; $i++) {
    $l = SWUBotLegalActions($gameName, 1);
    if (empty($l['actions'])) break;
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, '');
    if ($p === null || str_contains(strval($p['cardID']), 'Pass')) break;
    $act(1, intval($p['mode'] ?? 100), strval($p['cardID']));
    if (count(SWUBotUnits(2)) === 0) break;
}
$check(count(SWUBotUnits(2)) === 0, 'B2: with Rey also unlocked, the Epic still plays SRI; their units left ' . count(SWUBotUnits(2)));
// C) 7 resources: even with the waiver SRI costs 8 > 7 — no Epic.
$board(7, 3);
[$c] = $stack();
$check($c !== $epic, 'C: not castable even through the waiver — no Epic; got ' . var_export($c, true));
// (A one-unit wipe is left to the shipped 'aspectwaiver' fallback pricing, which values any genuine unlock — bot_aspectwaiver_test B.)

bot_test_finish();
