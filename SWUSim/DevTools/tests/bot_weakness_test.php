<?php
// Weakness tokens are hostile: a distribution puts them on enemy units, and a Weakness event with nothing to hit is
// held. FOUND 2026-09-15 in a live Bot Practice game (189011, owner report): "P2's Ravage gave a Weakness token to P2's
// 0-0-0" ×3, "P2's Ravage defeated P2's 0-0-0 — there was no reason to defeat their own unit. Weakness tokens are a
// disadvantage". HMW_071 Ravage: "Distribute up to 3 Weakness tokens among any number of units." (Weakness HMW_T02 =
// -1/-1, unpreventable.) Its MZSPLITASSIGN prompt does not say "damage", so the split scorer ('splits') never read it
// and the first enumerated split — all three on my own unit — was taken; the card is untagged, so the dud gate never
// saw it either.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_weakness_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$stack = function (string $style, int $seat = 1, string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, $seat);
    $p = SWUBotHeuristicChoose($style, (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][$seat] ?? [])];
};
$playScore = function (string $style) use ($botCtx) {
    $ctx = $botCtx($style);
    foreach ($ctx['actions'] as $i => $a) if (strval($a['cardID']) === 'myHand-0!FSM!') return SWUBotScoreAction($ctx, $a, $i);
    return null;
};
$check(_SWUBotIsEffectEvent('HMW_071'), 'a Weakness-token event is an effect event (the dud gate reads it)');

// The live shape: my 0-0-0 (4/4) and their Marine (3/3) + TIE/ln (2/1). Ravage's three tokens go on THEIR units.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'HMW_071');
    $b->WithGroundUnitForPlayer(1, 'LAW_174', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('normal');
$check($ctx['type'] === 'MZSPLITASSIGN', 'fixture: Ravage asks for a split; got ' . $ctx['type'] . ' ' . $ctx['tooltip']);
$pick = strval($stack('normal')[0]);
$mine = 0; $theirs = 0;
foreach (explode(',', $pick) as $pair) {
    [$mz, $n] = array_pad(explode(':', $pair), 2, 0);
    if (str_starts_with(trim($mz), 'my')) $mine += intval($n); elseif (str_starts_with(trim($mz), 'their')) $theirs += intval($n);
}
$check($mine === 0 && $theirs > 0, 'the tokens go on enemy units, none on mine; got ' . $pick);

// No enemy unit: Ravage can only hurt me — it is held.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'HMW_071');
    $b->WithGroundUnitForPlayer(1, 'LAW_174', true); });
$check($playScore('normal') === -0.5, 'with no enemy unit Ravage is held; scored ' . json_encode($playScore('normal')));

// HMW_100 Torrent: "Give a Weakness token to a unit. If you control a Naboo base, give 2 Weakness tokens to that unit
// instead." FOUND 2026-10-01 in Bot Practice game 1438045 (owner report): P1 attacked into the bot's shielded Droid Laser
// Turret, popping the Shield, and the bot then Torrented its OWN Turret (2 tokens off Otoh Gunga) and defeated it. Torrent
// resolves through its own continuation "HMW_100#0", not the shared GIVE_WEAKNESS (which is in the hostile list), so the
// class came from the tooltip "Give_a_Weakness_token_to_a_unit" — and "give" read as BENEFICIAL, putting the -1/-1s on
// the bot's most valuable friendly unit. The live board, seat-swapped: the bot is seat 1.
$build(function ($b) { $b->MyLeader('HMW_003', true); $b->MyBase('HMW_033', 15); $b->FillResourcesForPlayer(1, 'SOR_095', 5);
    $b->WithCardInHandForPlayer(1, 'HMW_100');
    $b->WithGroundUnitForPlayer(1, 'LAW_097', true); $b->WithGroundUnitForPlayer(1, 'LAW_118', true); $b->WithSpaceUnitForPlayer(1, 'JTL_033', true);
    $b->WithGroundUnitForPlayer(2, 'HMW_213', false); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 1), GameStateBuilder::Upgrade('HMW_T02', 1)]);
    $b->WithGroundUnitForPlayer(2, 'LOF_096', true); $b->WithSpaceUnitForPlayer(2, 'ASH_203', true); });
$act(1, 10002, 'myHand-0!FSM!');
$ctx = $botCtx('normal');
$check($ctx['type'] === 'MZCHOOSE' && $ctx['tooltip'] === 'Give_2_Weakness_tokens_to_a_unit',
    'fixture: Torrent off a Naboo base asks for a unit and NAMES its 2 tokens; got ' . $ctx['type'] . ' ' . $ctx['tooltip']);
foreach (['normal', 'aggro', 'control'] as $style) {
    $pick = strval($stack($style)[0]);
    $check(str_starts_with($pick, 'their'), "[$style] Torrent's Weakness goes on an ENEMY unit, never mine; got $pick");
}
// …and on the live board the 2 tokens CLEAN UP the Bongo Sub (1/3, already Weakened to 0/2): a kill through its Shield.
$check(strval($stack('normal')[0]) === 'theirGroundArena-0', 'the live board: Torrent defeats the Weakened Bongo Sub through its Shield');

// ══ Feature 'weakness' (p19, owner request 2026-10-01): Weakness tokens are DOWNGRADES, and a Weakness give / spread
// looks for the kill it makes ("clean up") before the strongest body it shrinks ("soften up"). Weakness is -1/-1 HP
// REDUCTION: unpreventable, so a Shield does not stop it, and permanent, so the -1 power matters for the rest of the game.
$torrentPick = function (callable $board, string $base = '') use ($build, $act, $botCtx, $stack) {
    $build(function ($b) use ($board, $base) { $b->MyLeader('SOR_014', false); if ($base !== '') $b->MyBase($base);
        $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCardInHandForPlayer(1, 'HMW_100'); $board($b); });
    $act(1, 10002, 'myHand-0!FSM!');
    return [strval($botCtx('normal')['tooltip']), strval($stack('normal')[0])];
};

// CLEAN UP (1 token, no Naboo base): the 2/1 TIE dies to it; Obi-Wan (3/5, the pricier body) only shrinks.
[$tip, $pick] = $torrentPick(function ($b) { $b->WithGroundUnitForPlayer(2, 'LOF_096', true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
$check($tip === 'Give_a_Weakness_token_to_a_unit', 'fixture: without a Naboo base Torrent names ONE token; got ' . $tip);
$check($pick === 'theirSpaceArena-0', 'clean up: 1 Weakness defeats the 1-HP TIE rather than shrinking Obi-Wan; got ' . $pick);

// THROUGH A SHIELD (2 tokens): a Shielded 2/2 dies to two Weakness tokens — a Shield stops damage, not HP reduction.
[, $pick] = $torrentPick(function ($b) { $b->WithGroundUnitForPlayer(2, 'LOF_096', true);
    $b->WithGroundUnitForPlayer(2, 'LAW_097', true); $b->WithUpgradesOnGroundUnitForPlayer(2, 1, [GameStateBuilder::Upgrade('SOR_T02', 1)]); }, 'HMW_033');
$check($pick === 'theirGroundArena-1', 'kill through a Shield: 2 Weakness defeat the Shielded 2/2; got ' . $pick);

// SOFTEN UP (2 tokens, no kill on the board): the STRONGER body takes them. The Max Rebo Band (1/5) can lose only 1 power,
// Obi-Wan (3/5) loses 2 — same cost, same HP, so only the power read can tell them apart.
[, $pick] = $torrentPick(function ($b) { $b->WithGroundUnitForPlayer(2, 'LAW_071', true); $b->WithGroundUnitForPlayer(2, 'LOF_096', true); }, 'HMW_033');
$check($pick === 'theirGroundArena-1', 'soften up: 2 Weakness go on Obi-Wan (3/5), not the 1-power Max Rebo Band; got ' . $pick);

// RAVAGE SPREAD (3 tokens): exactly 1 cleans up the TIE, the other 2 soften Obi-Wan — none wasted on the Band, none on mine.
// Both ground orders: the old scorer priced every non-lethal token the same, so the enumeration order picked the body.
foreach ([['LAW_071', 'LOF_096', 1, 0], ['LOF_096', 'LAW_071', 0, 1]] as [$g0, $g1, $obi, $band]) {
    $build(function ($b) use ($g0, $g1) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'HMW_071');
        $b->WithGroundUnitForPlayer(1, 'LAW_174', true);
        $b->WithGroundUnitForPlayer(2, $g0, true); $b->WithGroundUnitForPlayer(2, $g1, true); $b->WithSpaceUnitForPlayer(2, 'SOR_225', true); });
    $act(1, 10002, 'myHand-0!FSM!');
    $split = [];
    foreach (explode(',', strval($stack('normal')[0])) as $pair) { [$mz, $n] = array_pad(explode(':', $pair), 2, 0); $split[trim($mz)] = intval($n); }
    $check(($split['theirSpaceArena-0'] ?? 0) === 1 && ($split["theirGroundArena-$obi"] ?? 0) === 2
        && ($split["theirGroundArena-$band"] ?? 0) === 0 && ($split['myGroundArena-0'] ?? 0) === 0,
        "Ravage (Obi-Wan at ground $obi): TIE 1 (kill, no overkill) + Obi-Wan 2 (soften) + Band 0 + mine 0; got " . json_encode($split));
}

// DOWNGRADE VALUE: a Weakness on a unit is not worth MORE when that unit dies. The value model counted every subcard as an
// upgrade premium, so a Weakened Door Technician (1/1) priced above a healthy one (2/2).
$build(function ($b) { $b->MyLeader('SOR_014', false);
    $b->WithGroundUnitForPlayer(2, 'LAW_097', true); $b->WithGroundUnitForPlayer(2, 'LAW_097', true);
    $b->WithUpgradesOnGroundUnitForPlayer(2, 1, [GameStateBuilder::Upgrade('HMW_T02', 1)]); });
[$plain, $weak] = SWUBotUnits(2);
$check(intval($weak['downgrades'] ?? -1) === 1 && intval($plain['downgrades'] ?? -1) === 0, 'the unit view counts downgrades; got ' . json_encode([$plain['downgrades'] ?? null, $weak['downgrades'] ?? null]));
// (The default value model prices by printed COST, so the two tie; it is the premium on top that was wrong.)
$check(SWUBotUnitValue($weak) <= SWUBotUnitValue($plain), 'a Weakened unit is worth no MORE than a healthy copy; got ' . SWUBotUnitValue($weak) . ' vs ' . SWUBotUnitValue($plain));

// DEFEAT AN UPGRADE (SEC_163 Outer Rim Constable, "you may"): the Weakness to remove is MINE — removing theirs helps them.
$constable = function (bool $mineWeak) use ($build, $act, $stack) {
    $build(function ($b) use ($mineWeak) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 5); $b->WithCardInHandForPlayer(1, 'SEC_163');
        $b->WithGroundUnitForPlayer(1, 'LAW_174', true);
        if ($mineWeak) $b->WithUpgradesOnGroundUnitForPlayer(1, 0, [GameStateBuilder::Upgrade('HMW_T02', 1)]);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('HMW_T02', 1)]); });
    $act(1, 10002, 'myHand-0!FSM!');
    return strval($stack('normal')[0]);
};
$pick = $constable(true);
$check(str_starts_with($pick, 'myGroundArena-0'), 'Constable defeats the Weakness on MY unit, not the enemy\'s; got ' . $pick);
$pick = $constable(false);
$check($pick === 'PASS', 'with only an ENEMY Weakness in play the Constable declines; got ' . $pick);

bot_test_finish();
