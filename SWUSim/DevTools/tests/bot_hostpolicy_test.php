<?php
// Feature 'hostpolicy' (group p17) — every attachment is called an "Upgrade", but some are DOWNGRADES. Owner rulings
// 2026-10-01 decide which side of the table each listed upgrade goes on (SWU_BOT_UPGRADE_HOST_POLICY, BotFallback.php):
//   enemy       — Bounty granters, Imprisoned-style ability/ready/stat suppressors, Nowhere to Hide, Traitorous …
//   own         — Preparation, Battle Fury, Death Star Plans, Sith Holocron, Han's Golden Dice
//   own-small   — Size Matters Not: only on my own unit whose printed stats it actually raises
//   entrenched  — Entrenched: an enemy unit WITHOUT Overwhelm, or one of my own Sentinels
// A listed upgrade with no allowed host is HELD; one with an allowed host is never treated as a gift ('nogift').
// FOUND 2026-10-01: extending 'nogift' to every upgrade (LAW_129 Mastery, game 1438045) made every 0/0 downgrade
// read as a gift on an enemy host — Wanted, Imprisoned, Shadow of Stygeon Prime … all scored -0.5.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_hostpolicy_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$play = 'myHand-0!FSM!';
$stack = function (string $style = 'normal', string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$playScore = function (array $disabled = []) use ($botCtx, $play) {
    SWUBotSetDisabledFeatures($disabled);
    $ctx = $botCtx('normal'); $s = null;
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === $play) { $s = SWUBotScoreAction($ctx, $a, $i); break; }
    SWUBotSetDisabledFeatures([]);
    return $s;
};
// The bot (seat 1) holds $upgrade with plenty of resources; $mine / $theirs are [cardID, ready] ground units.
// $attached: [seat => [groundIndex => [upgrade CardIDs already on that unit]]].
$board = function (string $upgrade, array $mine, array $theirs, array $attached = []) use ($build) {
    $build(function ($b) use ($upgrade, $mine, $theirs, $attached) {
        $b->MyLeader('SOR_014', false, false, true);
        $b->FillResourcesForPlayer(1, 'SOR_095', 10);
        $b->WithCardInHandForPlayer(1, $upgrade);
        foreach ($mine as [$c, $r]) $b->WithGroundUnitForPlayer(1, $c, $r, 0);
        foreach ($theirs as [$c, $r]) $b->WithGroundUnitForPlayer(2, $c, $r, 0);
        foreach ($attached as $seat => $units) foreach ($units as $i => $ups)
            $b->WithUpgradesOnGroundUnitForPlayer($seat, $i, array_map(fn($u) => GameStateBuilder::Upgrade($u, $seat), $ups));
    });
};
// Play the upgrade for real and return the host the stack picks at "Choose_upgrade_target".
$host = function (string $variant = '') use ($act, $botCtx, $stack, $play) {
    $act(1, 10002, $play);
    $ctx = $botCtx('normal');
    if ($ctx['tooltip'] !== 'Choose_upgrade_target') return 'NO-PROMPT:' . $ctx['tooltip'];
    return $stack('normal', $variant);
};
$pos = fn($s) => $s !== null && $s > 0.0;

$check(in_array('hostpolicy', SWUBotFeatureList(), true), 'hostpolicy is shipped behaviour (defaults on)');
$check(SWUBotFeatureGroups()['p17'] === ['hostpolicy'], 'group p17 is the stack before it');

// ── A) DOWNGRADES are played onto an ENEMY unit (the regression the Mastery fix caused) ─────────────────────────
foreach (['SHD_221' => 'Wanted (Bounty)', 'SHD_072' => 'Imprisoned', 'LAW_077' => 'Shadow of Stygeon Prime',
          'ASH_150' => 'Deadly Vulnerability', 'LAW_141' => 'Targeted For Removal'] as $cid => $name) {
    $board($cid, [], [['SOR_095', true]]);
    $s = $playScore();
    // Not HELD. (A 0/0 Bounty upgrade still scores 0 — the play-value model prices printed stats, not Bounties.)
    $check($s !== null && $s > -0.5, "A: $name with only an enemy host is not held; got " . json_encode($s));
}

// ── B) …and HELD when my own unit is the only host ──────────────────────────────────────────────────────────────
foreach (['SHD_221' => 'Wanted', 'SHD_072' => 'Imprisoned', 'ASH_198' => 'Nowhere to Hide', 'SOR_122' => 'Traitorous'] as $cid => $name) {
    $board($cid, [['SOR_095', true]], []);
    $s = $playScore();
    $check($s === -0.5, "B: $name with only my own host is held (-0.5); got " . json_encode($s));
}
$board('SHD_221', [['SOR_095', true]], []);
$offB = $playScore(['hostpolicy']);
$check($offB !== null && $offB !== -0.5, "B @no-hostpolicy: Wanted on my own unit was NOT held before (the gap); got " . json_encode($offB));

// ── C) …and with BOTH sides available, the host is the ENEMY unit ───────────────────────────────────────────────
foreach (['SHD_221' => 'Wanted', 'SHD_176' => 'Death Mark', 'JTL_192' => 'In Debt to Crimson Dawn', 'ASH_085' => 'Grav Charge'] as $cid => $name) {
    $board($cid, [['SOR_095', true]], [['SOR_095', true]]);
    $h = $host();
    $check($h === 'theirGroundArena-0', "C: $name goes on the enemy unit; got " . json_encode($h));
}

// ── D) OWN-ONLY upgrades: held with only an enemy host, mine when both are available ─────────────────────────────
foreach (['ASH_228' => 'Preparation', 'JTL_260' => 'Death Star Plans', 'LAW_225' => "Han's Golden Dice", 'LOF_139' => 'Battle Fury'] as $cid => $name) {
    $board($cid, [], [['SOR_095', true]]);
    $s = $playScore();
    $check($s === -0.5, "D: $name with only an enemy host is held (-0.5); got " . json_encode($s));
    $board($cid, [['SOR_095', true]], [['SOR_095', true]]);
    $h = $host();
    $check($h === 'myGroundArena-0', "D: $name goes on my own unit; got " . json_encode($h));
}
// Preparation ("When Played: Exhaust attached unit") prefers a unit that is ALREADY exhausted.
$board('ASH_228', [['SOR_095', true], ['SOR_095', false]], []);
$h = $host();
$check($h === 'myGroundArena-1', "D: Preparation picks my exhausted unit over my ready one; got " . json_encode($h));

// ── E) LOF_056 Size Matters Not — only my own SMALL unit (printed stats both 5 or less, not already 5/5) ─────────
$board('LOF_056', [['SOR_095', true]], []);                     // Marine 3/3 → 5/5
$check($pos($playScore()), 'E: Size Matters Not on my 3/3 is played');
$board('LOF_056', [['SOR_046', true]], []);                     // Consular 3/7 → 5/5 would SHRINK it
$check($playScore() === -0.5, 'E: Size Matters Not with only my 3/7 is held');
$board('LOF_056', [], [['SOR_095', true]]);
$check($playScore() === -0.5, 'E: Size Matters Not with only an enemy host is held');
$board('LOF_056', [['SOR_046', true], ['SOR_095', true]], [['SOR_095', true]]);
$h = $host();
$check($h === 'myGroundArena-1', "E: Size Matters Not picks my small unit; got " . json_encode($h));

// ── F) SOR_072 Entrenched — an enemy unit WITHOUT Overwhelm, or one of my own Sentinels ─────────────────────────
$board('SOR_072', [], [['SOR_046', true]]);                     // enemy Consular, no Overwhelm
$check($pos($playScore()), 'F: Entrenched on a big enemy unit without Overwhelm is played');
$board('SOR_072', [], [['SOR_164', true]]);                     // enemy Wampa HAS Overwhelm
$check($playScore() === -0.5, 'F: Entrenched with only an Overwhelm enemy is held');
$board('SOR_072', [['SOR_095', true]], []);                     // my non-Sentinel Marine
$check($playScore() === -0.5, 'F: Entrenched with only my non-Sentinel unit is held');
$board('SOR_072', [['SEC_057', true]], [['SOR_164', true]]);    // my Sentinel Lobot vs an Overwhelm Wampa
$h = $host();
$check($h === 'myGroundArena-0', "F: Entrenched goes on my Sentinel when the enemy has Overwhelm; got " . json_encode($h));
$board('SOR_072', [['SOR_095', true]], [['SOR_046', true]]);    // my plain Marine vs a plain big enemy
$h = $host();
$check($h === 'theirGroundArena-0', "F: Entrenched goes on the big enemy without Overwhelm; got " . json_encode($h));

// ── H) SEC_038 Condemn — on an enemy unit; on MY unit only to cancel a Condemn already on it ──────────────────────
// Owner 2026-10-01: "Condemn should only go on my own unit if I already have Condemn on it and I want to nullify their
// downsides." A second copy's "loses all other abilities" stops the first copy's ability being gained (the official
// Condemn reminder), so it cancels the defending player's -6/-0 disclose. The same rule makes a second Condemn on an
// ENEMY unit that already carries one pointless — it would cancel mine.
$board('SEC_038', [['SOR_095', true]], []);
$check($playScore() === -0.5, 'H: Condemn with only my own (un-Condemned) unit is held');
$board('SEC_038', [['SOR_095', true]], [], [1 => [0 => ['SEC_038']]]);
$check($pos($playScore()), 'H: Condemn on my own unit that already carries a Condemn is played (cancels it)');
$board('SEC_038', [], [['SOR_095', true]], [2 => [0 => ['SEC_038']]]);
$check($playScore() === -0.5, 'H: Condemn with only an enemy unit that already carries a Condemn is held');
$board('SEC_038', [['SOR_095', true]], [['SOR_095', true]]);
$h = $host();
$check($h === 'theirGroundArena-0', "H: with a plain unit on each side, Condemn goes on the enemy; got " . json_encode($h));

// ── G) CONTROL — an unlisted upgrade keeps its old behaviour (Mastery, the nogift case) ─────────────────────────
$board('LAW_129', [], [['SOR_095', true]]);
$check($playScore() === -0.5, 'G: Mastery with only an enemy host is still held by nogift');
$board('LAW_129', [['SOR_095', true]], [['SOR_095', true]]);
$check($host() === 'myGroundArena-0', 'G: Mastery still goes on my own unit');

bot_test_finish();
