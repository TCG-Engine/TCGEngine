<?php
// Part 29 (2026-10-03) — a card DISCARDED FROM MY OWN HAND is a real cost. Found from a human pilot's 53 Arenabot games
// with Darth Vader, Unstoppable (LAW_011, "Action [Exhaust, discard a card from your hand]: Deal 1 damage to a unit or
// base"; BotData bundle swusim-botdata-20261004-025711): the human pinged ~1.8 times a game, 80 of 82 at UNITS (64% kills),
// pitching dead cards (Rey 32, Ki-Adi-Mundi 16). The bot on the same list pinged ~4.5 times a game, 715 of 907 at the BASE,
// and pitched hand index 0 every time — Chimaera 95 times, Anakin 79, No Glory 76 — because no own-hand discard prompt had
// any scoring (enumeration-order tiebreak) and the Action was a flat W['ability'] with the cost unpriced.
//   discardpick — an own-hand discard takes the card worth least to KEEP (play value less its aspect penalty);
//   heropitch   — owner 2026-10-03: "those off-aspect Heroism cards are worth more in the discard to activate Anakin fully"
//                 (LOF_070: "If there is a Heroism card in your discard pile, you may give a unit -3/-3");
//   pingvalue   — an Action whose cost discards a card is worth its effect less that card.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_discardcost_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
foreach (['discardpick', 'heropitch', 'pingvalue', 'dumpdamage'] as $f) {
    $check(SWUBotVariantDisabled('no-' . $f) === [$f], "$f is switchable; got " . json_encode(SWUBotVariantDisabled('no-' . $f)));
    $check(in_array($f, SWUBotFeatureGroups()['p29'] ?? [], true) && !in_array($f, SWU_BOT_PROPOSALS, true), "$f is in group p29");
}

// A Vader board: LAW_011 ready on Nightsister Lair, $res ready resources, $hand in order, plus $more($b).
$vader = function (array $hand, int $res = 0, ?callable $more = null) {
    return function ($b) use ($hand, $res, $more) {
        $b->MyLeader('LAW_011'); $b->MyBase('LOF_020');
        if ($res > 0) $b->FillResourcesForPlayer(1, 'LOF_059', $res);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        if ($more !== null) $more($b);
    };
};
$legal = fn() => SWUBotLegalActions($GLOBALS['gameName'], 1);
$pick = fn(array $l, string $variant) => SWUBotHeuristicChoose('midrange', (array)$l['actions'], $l, $variant);
// Use the leader Action, then return the bot's pick at the discard prompt.
$discardPick = function (callable $board, string $variant) use ($build, $act, $legal, $pick) {
    $build($board);
    $l = $legal(); $ping = null;
    foreach ((array)$l['actions'] as $a) if (str_contains(strval($a['cardID'] ?? ''), 'LeaderAbility')) $ping = $a;
    if ($ping === null) return 'NO-PING-ACTION';
    $act(1, intval($ping['mode'] ?? 10001), strval($ping['cardID']));
    $l = $legal();
    if (($l['kind'] ?? '') !== 'decision') return 'NO-DECISION';
    return strval($pick($l, $variant)['cardID'] ?? '');
};
// The bot's first free-play choice on the board.
$first = function (callable $board, string $variant) use ($build, $legal, $pick) {
    $build($board);
    return strval($pick($legal(), $variant)['cardID'] ?? '');
};
// Play the bot's turn out (the opponent passes); return [actions it took at free play, the discards it paid].
$turn = function (callable $board, string $variant) use ($build, $act, $legal, $pick) {
    $build($board);
    $took = [];
    for ($i = 0; $i < 10; $i++) {
        $l = $legal();
        if (($l['kind'] ?? '') === 'waiting-on-other-seat') { $act(2, 10001, 'myHealth-0!CustomInput!Pass'); continue; }
        $p = $pick($l, $variant); $id = strval($p['cardID'] ?? '');
        if (($l['kind'] ?? '') !== 'decision') {
            if ($id === '' || str_contains($id, '!Pass') || str_contains($id, 'Initiative')) { $took[] = 'pass'; break; }
            $took[] = $id;
        }
        $act(1, intval($p['mode'] ?? 10001), $id);
    }
    return $took;
};

// A) DISCARD PICK: Chimaera sits at hand index 0. Today the pick is index 0; the bot must pay with the card worth least —
// Nightsister Warrior (2-cost 2/2) — not its 7-cost bomb.
$boardA = $vader(['ASH_052', 'LOF_031', 'LOF_059']);
$check($discardPick($boardA, 'no-discardpick') === 'myHand-0', 'A fixture: today the discard is hand index 0 (Chimaera); got ' . $discardPick($boardA, 'no-discardpick'));
$check($discardPick($boardA, '') === 'myHand-2', 'A: the discard is the Nightsister Warrior; got ' . $discardPick($boardA, ''));
// A2) The aspect penalty counts: an off-aspect card is worth less to keep than its printed cost says. Ki-Adi-Mundi
// (Aggression/Heroism, 4) costs this seat 6; Talzin's Assassin (Vigilance/Villainy, 4) costs 4. No Anakin, so no heropitch.
$boardA2 = $vader(['LOF_035', 'LOF_146']);
$check($discardPick($boardA2, '') === 'myHand-1', 'A2: with equal printed cost the off-aspect Ki-Adi-Mundi goes; got ' . $discardPick($boardA2, ''));

// A3) …and it can FLIP the pick: One Way Out (SEC_157, Aggression/Heroism, 1) plays at 3 here, so it is worth less to keep
// than a Nightsister Warrior (on-aspect 2) although its printed cost says otherwise. No Anakin, so no heropitch.
$boardA3 = $vader(['LOF_059', 'SEC_157']);
$check($discardPick($boardA3, '') === 'myHand-1', 'A3: the off-aspect One Way Out goes before the Nightsister Warrior; got ' . $discardPick($boardA3, ''));
// A4) An OPTIONAL discard — SEC_197 Furtive Handmaiden's On Attack loot ("Choose_a_card_to_discard", with PASS). Today it
// loots away the Chimaera (hand index 0). It must still LOOT (whether to discard is unchanged), but pay with the filler.
$boardA4 = function ($b) {
    $b->MyLeader('LAW_011'); $b->MyBase('LOF_020');
    $b->WithGroundUnitForPlayer(1, 'SEC_197');
    foreach (['ASH_052', 'LOF_059'] as $c) $b->WithCardInHandForPlayer(1, $c);
};
$lootPick = function (string $variant) use ($build, $act, $legal, $pick, $boardA4) {
    $build($boardA4);
    $att = null;
    foreach ((array)$legal()['actions'] as $a) if (strval($a['cardID'] ?? '') === 'myGroundArena-0!FSM!') $att = $a;
    if ($att === null) return 'NO-ATTACK';
    $act(1, intval($att['mode'] ?? 10001), strval($att['cardID']));
    $l = $legal();
    if (($l['kind'] ?? '') !== 'decision' || strval($l['decisionTooltip'] ?? '') !== 'Choose_a_card_to_discard') return 'NO-LOOT-PROMPT';
    return strval($pick($l, $variant)['cardID'] ?? '');
};
$check($lootPick('no-discardpick') === 'myHand-0', 'A4 fixture: today the loot discards Chimaera (index 0); got ' . $lootPick('no-discardpick'));
$check($lootPick('') === 'myHand-1', 'A4: the loot is still taken, paying with the Nightsister Warrior; got ' . $lootPick(''));

// B) HEROPITCH: Anakin in hand and no Heroism card in my discard → the off-aspect Heroism card (Ki-Adi-Mundi) is pitched
// before even the cheapest filler, because in the discard it switches on Anakin's Heroism clause.
$boardB = $vader(['ASH_052', 'LOF_059', 'LOF_146', 'LOF_070']);
// Owner, 2026-10-06 (curve value, p38): "Ki-Adi goes (curve is right)" — an off-aspect 4-drop that plays at 6 is worth less
// to keep than an on-curve 2/2, so Ki-Adi-Mundi goes even WITHOUT heropitch (B), and when nothing is gained by pitching (B2, B3).
$check($discardPick($boardB, 'no-heropitch') === 'myHand-2', 'B fixture: without heropitch, curve value already pitches the off-aspect Ki-Adi; got ' . $discardPick($boardB, 'no-heropitch'));
$check($discardPick($boardB, '') === 'myHand-2', 'B: the off-aspect Heroism Ki-Adi-Mundi is pitched for Anakin; got ' . $discardPick($boardB, ''));
// B2) A Heroism card is already in the discard: Anakin's clause is on — nothing more to gain, the filler goes.
$boardB2 = $vader(['ASH_052', 'LOF_059', 'LOF_146', 'LOF_070'], 0, fn($b) => $b->WithCardInDiscardForPlayer(1, 'SEC_157'));
$check($discardPick($boardB2, '') === 'myHand-2', 'B2: a Heroism card already in the discard — Ki-Adi goes (curve value, owner 2026-10-06); got ' . $discardPick($boardB2, ''));
// B3) No Anakin anywhere (hand or deck): the Heroism card is just a card.
$boardB3 = $vader(['ASH_052', 'LOF_059', 'LOF_146']);
$check($discardPick($boardB3, '') === 'myHand-2', 'B3: no Anakin — Ki-Adi goes (curve value, owner 2026-10-06); got ' . $discardPick($boardB3, ''));
// B4) Anakin still in the DECK counts: it is the card the pitch is for.
$boardB4 = $vader(['ASH_052', 'LOF_059', 'LOF_146'], 0, fn($b) => $b->WithCardInDeckForPlayer(1, 'LOF_070'));
$check($discardPick($boardB4, '') === 'myHand-2', 'B4: Anakin in the deck — the Heroism card is pitched; got ' . $discardPick($boardB4, ''));
// B5) Only a card off-aspect BECAUSE OF Heroism is pitched: the owner's rule is about a Villainy deck's Heroism splash.
// Under Luke (SOR_005, Vigilance/Heroism) One Way Out (SEC_157, Aggression/Heroism) is still off-aspect — by Aggression —
// but its Heroism pip is paid, so it is just a card.
$boardB5 = function ($b) {
    $b->MyLeader('SOR_005'); $b->MyBase('LOF_020');
    foreach (['ASH_052', 'LOF_059', 'SEC_157', 'LOF_070'] as $c) $b->WithCardInHandForPlayer(1, $c);
};
$build($boardB5);
$keepFiller = SWUBotHandKeepValue(1, GetHand(1)[1], SWUBotWeights('midrange', 1));
$keepHero   = SWUBotHandKeepValue(1, GetHand(1)[2], SWUBotWeights('midrange', 1));
$check($keepHero >= 0.0, 'B5: under a Heroism leader the Heroism card is on-aspect — no pitch bonus; keep ' . round($keepHero, 3) . ' (filler ' . round($keepFiller, 3) . ')');

// C) PINGVALUE — the traced round-1 shape: 2 resources, Karis castable, no enemy unit the ping could kill. Today the bot
// pings the base and pays with Karis (hand index 0). It should develop instead.
$boardC = $vader(['LOF_031', 'LOF_035', 'JTL_043', 'LOF_070'], 2, fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_035'));
$check($first($boardC, 'no-pingvalue') === 'myLeader-0!CustomInput!LeaderAbility', 'C fixture: today the bot pings first; got ' . $first($boardC, 'no-pingvalue'));
$check($first($boardC, '') !== 'myLeader-0!CustomInput!LeaderAbility', 'C: with Karis castable and nothing to kill, the bot does not ping; got ' . $first($boardC, ''));

// D) THE PING AS REMOVAL: an enemy Outer Rim Constable (3/1) — one damage kills it. Nothing castable (0 resources). The bot
// pings, pays with the filler, and the Constable dies.
$boardD = $vader(['ASH_052', 'LOF_059'], 0, fn($b) => $b->WithGroundUnitForPlayer(2, 'SEC_163'));
$tookD = $turn($boardD, '');
$theirs = array_map(fn($v) => $v['cardID'], SWUBotUnits(2));
$check(in_array('myLeader-0!CustomInput!LeaderAbility', $tookD, true) && !in_array('SEC_163', $theirs, true),
       'D: the bot pings the 1-HP Constable dead; took ' . json_encode($tookD) . ', their units ' . json_encode($theirs));
$check(in_array('ASH_052', array_map(fn($o) => $o->CardID, array_values(GetHand(1))), true), 'D: …and kept Chimaera');

// E) NOTHING TO KILL, NOTHING CASTABLE, only a bomb in hand: a 1-damage base ping is not worth Chimaera.
$boardE = $vader(['ASH_052'], 0, fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_035'));
$check($first($boardE, 'no-pingvalue') === 'myLeader-0!CustomInput!LeaderAbility', 'E fixture: today the bot pings its Chimaera away; got ' . $first($boardE, 'no-pingvalue'));
$check($first($boardE, '') !== 'myLeader-0!CustomInput!LeaderAbility', 'E: it keeps Chimaera; got ' . $first($boardE, ''));

// E2) A Shield absorbs the 1 damage: a Shielded 1-HP Constable is no kill. Only cheap filler in hand — the unshielded
// Constable WOULD be worth that card (D) — so the Shield alone decides it.
$boardE2 = $vader(['LOF_059'], 0, function ($b) {
    $b->WithGroundUnitForPlayer(2, 'SEC_163');
    $b->WithUpgradesOnGroundUnitForPlayer(2, 0, [GameStateBuilder::Upgrade('SOR_T02', 2)]);
});
$check($first($boardE2, '') !== 'myLeader-0!CustomInput!LeaderAbility', 'E2: a Shielded 1-HP unit is not a kill — no ping; got ' . $first($boardE2, ''));
// E3) A card off-aspect by two pips is worth NOTHING to keep, never less: Jaxxon (HMW_219, Cunning/Cunning, a vanilla 1-cost
// 3/3) plays at 5 here, and below zero its pitch would read as profit — a base ping "for free".
$boardE3 = $vader(['HMW_219'], 0, fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_035'));
$check($first($boardE3, '') !== 'myLeader-0!CustomInput!LeaderAbility', 'E3: no free base ping off a dead card; got ' . $first($boardE3, ''));

// F) LETHAL: their base has 1 HP left — the ping wins the game, whatever it costs.
$boardF = $vader(['ASH_052'], 0, function ($b) { $b->TheirBase('LOF_020', 27); $b->WithGroundUnitForPlayer(2, 'LOF_035'); });
$tookF = $turn($boardF, '');
$check(SWUBaseRemainingHp(2) <= 0, 'F: the lethal ping is taken; took ' . json_encode($tookF) . ', their base ' . SWUBaseRemainingHp(2));

// A5) A MULTI-select discard — deployed Vader's "On Attack: Discard any number of cards… deal that much damage" — is a
// choice of HOW MANY, which discardpick does not judge: its single-card candidates must not be lifted above '-'.
$boardA5 = function ($b) {
    $b->MyLeader('LAW_011', true, true, false, 'unit'); $b->MyBase('LOF_020');
    foreach (['ASH_052', 'LOF_059', 'LOF_031'] as $c) $b->WithCardInHandForPlayer(1, $c);
};
$dumpPick = function (string $variant) use ($build, $act, $legal, $pick, $boardA5) {
    $build($boardA5);
    $att = null;
    foreach ((array)$legal()['actions'] as $a) if (preg_match('/^myGroundArena-\d+!FSM!$/', strval($a['cardID'] ?? ''))) $att = $a;
    if ($att === null) return 'NO-ATTACK';
    $act(1, intval($att['mode'] ?? 10001), strval($att['cardID']));
    for ($i = 0; $i < 3; $i++) {
        $l = $legal();
        if (($l['kind'] ?? '') !== 'decision') return 'NO-DUMP-PROMPT';
        if (strval($l['decisionTooltip'] ?? '') === 'Discard_any_number_of_cards_from_your_hand') return strval($pick($l, $variant)['cardID'] ?? '');
        $p = $pick($l, $variant); $act(1, intval($p['mode'] ?? 10001), strval($p['cardID'] ?? ''));
    }
    return 'NO-DUMP-PROMPT';
};
$check($dumpPick('') !== 'NO-DUMP-PROMPT' && $dumpPick('') !== 'NO-ATTACK', 'A5 fixture: the dump prompt is reached; got ' . $dumpPick(''));
$check($dumpPick('') === $dumpPick('no-discardpick'), 'A5: discardpick leaves the multi-select dump alone; got ' . $dumpPick('') . ' vs ' . $dumpPick('no-discardpick'));

// H) DUMPDAMAGE — deployed Vader's "On Attack: Discard any number of cards from your hand. Deal damage to a unit or base
// equal to the number discarded". The human pilot used it on 41 of 81 attacks, usually one card (26), sometimes 3-5;
// targets: 17 unit kills, 18 base hits. Today the bot dumps nothing (4 uses in 107 deploys).
$dumpBoard = function (array $hand, ?callable $more = null) {
    return function ($b) use ($hand, $more) {
        $b->MyLeader('LAW_011', true, true, false, 'unit'); $b->MyBase('LOF_020');
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        if ($more !== null) $more($b);
    };
};
$dumpOn = function (callable $board, string $variant) use ($build, $act, $legal, $pick) {
    $build($board);
    $att = null;
    foreach ((array)$legal()['actions'] as $a) if (preg_match('/^myGroundArena-\d+!FSM!$/', strval($a['cardID'] ?? ''))) $att = $a;
    if ($att === null) return 'NO-ATTACK';
    $act(1, intval($att['mode'] ?? 10001), strval($att['cardID']));
    for ($i = 0; $i < 3; $i++) {
        $l = $legal();
        if (($l['kind'] ?? '') !== 'decision') return 'NO-DUMP-PROMPT';
        if (strval($l['decisionTooltip'] ?? '') === 'Discard_any_number_of_cards_from_your_hand') return strval($pick($l, $variant)['cardID'] ?? '');
        $p = $pick($l, $variant); $act(1, intval($p['mode'] ?? 10001), strval($p['cardID'] ?? ''));
    }
    return 'NO-DUMP-PROMPT';
};
// H1) Filler for base damage: a Nightsister Warrior (worth ~nothing to keep) buys 1 damage — dump it, keep Chimaera.
$boardH1 = $dumpBoard(['ASH_052', 'LOF_059']);
$check($dumpOn($boardH1, 'no-dumpdamage') === '-', 'H1 fixture: today deployed Vader dumps nothing; got ' . $dumpOn($boardH1, 'no-dumpdamage'));
$check($dumpOn($boardH1, '') === 'myHand-1', 'H1: the filler is dumped for 1 damage, Chimaera kept; got ' . $dumpOn($boardH1, ''));
// H2) Only a bomb in hand: 1 damage is not worth Chimaera.
$check($dumpOn($dumpBoard(['ASH_052']), '') === '-', 'H2: Chimaera is never dumped for 1 chip; got ' . $dumpOn($dumpBoard(['ASH_052']), ''));
// H3) A KILL is worth more than chip: Karis (a real 2-drop) is not dumped for 1 base damage, but is dumped to finish an
// enemy Talzin's Assassin (4/4) on 1 HP.
$boardH3 = $dumpBoard(['ASH_052', 'LOF_031']);
$check($dumpOn($boardH3, '') === '-', 'H3: Karis is not dumped for 1 chip; got ' . $dumpOn($boardH3, ''));
$boardH3k = $dumpBoard(['ASH_052', 'LOF_031'], fn($b) => $b->WithGroundUnitForPlayer(2, 'LOF_035', true, 3));
$check($dumpOn($boardH3k, '') === 'myHand-1', 'H3k: Karis is dumped to finish a 1-HP Talzin\'s Assassin; got ' . $dumpOn($boardH3k, ''));
// H4) Owner 2026-10-03: "hold cards until you can use Aggressive Negotiations for a double buffed attack" (SEC_179: +1/+0
// per card in hand). While AN is in hand every card is ALSO a point of a future AN swing, so the filler is held.
$boardH4 = $dumpBoard(['ASH_052', 'LOF_059', 'SEC_179']);
$check($dumpOn($boardH4, '') === '-', 'H4: with Aggressive Negotiations in hand the filler is held; got ' . $dumpOn($boardH4, ''));

// G) OTHER DISCARD-COST ACTIONS ARE LEFT ALONE: HMW_010 Tarfful ("Action [2 resources, Exhaust, discard a card from your
// hand]: Create a Beast token") has no price for its effect here, so pingvalue must not touch it.
$boardG = function ($b) {
    $b->MyLeader('HMW_010'); $b->MyBase('LOF_020');
    $b->FillResourcesForPlayer(1, 'LOF_059', 2);
    foreach (['ASH_052', 'JTL_043'] as $c) $b->WithCardInHandForPlayer(1, $c);
    $b->WithGroundUnitForPlayer(2, 'LOF_035');
};
$build($boardG);
$tarf = null;
foreach ((array)$legal()['actions'] as $a) if (str_contains(strval($a['cardID'] ?? ''), 'LeaderAbility')) $tarf = $a;
$check($tarf !== null, 'G fixture: Tarfful\'s Action is offered');
if ($tarf !== null) {
    $score = function (string $variant) use ($tarf, $botCtx) {
        $GLOBALS['SWUBotDisabledFeatures'] = SWUBotVariantDisabled($variant) ?? [];
        $ctx = $botCtx('midrange');
        $s = _SWUBotAbilityValue($ctx, $tarf, SWUBotWeights('midrange', 1));
        $GLOBALS['SWUBotDisabledFeatures'] = [];
        return $s;
    };
    $check(abs($score('') - $score('no-pingvalue')) < 1e-9, 'G: Tarfful\'s Beast Action is scored as before; got ' . $score('') . ' vs ' . $score('no-pingvalue'));
}

bot_test_finish();
