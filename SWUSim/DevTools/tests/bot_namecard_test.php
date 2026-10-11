<?php
// Part 21 'namecard' — "Name a card" names the OPPONENT'S cards. Owner request 2026-10-02: "fix how the bot names cards
// for stuff like Ryder Azadi. today it seems like it names cards from their own deck". Measured: Krennic Blue playing
// ASH_077 Ryder Azadi ("opponents can't play cards with that name") named "Chimaera" — the first title, alphabetically,
// of its OWN deck (the bridge's only candidates). Owner ruling: the bot may use what the opponent has SHOWN plus the
// META lists for their leader (BotDeckLabels.json) — never their hidden hand/deck. See SWUSim/Custom/BotNameCard.php.
// Expectations are DERIVED from the meta lists and card data, not written as literals.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_namecard_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-namecard') === ['namecard'], 'namecard is switchable');

$myDeck = ['LAW_044', 'ASH_097', 'JTL_033', 'LAW_038', 'ASH_052', 'LAW_159', 'SEC_087', 'ASH_048'];   // Krennic Blue cards
$ownTitles = array_values(array_unique(array_map(fn($c) => CardTitle($c), $myDeck)));
// Plays hand card 0 (a "name a card" card) on $board, answers any prompt before the NAMECARD, returns [options, pick].
$nameFor = function (callable $board, string $variant = '', string $style = 'normal') use (&$gameName, $act, $build) {
    $build($board);
    $act(1, 10002, 'myHand-0!FSM!');
    for ($i = 0; $i < 6; $i++) {
        $l = SWUBotLegalActions($gameName, 1);
        if (($l['kind'] ?? '') !== 'decision') return [[], null];
        if (($l['decisionType'] ?? '') === 'NAMECARD') {
            $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, $variant);
            return [array_map(fn($a) => strval($a['cardID']), (array)$l['actions']), strval($p['cardID'] ?? '')];
        }
        $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, $variant);
        $act(1, intval($p['mode'] ?? 10001), strval($p['cardID'] ?? 'PASS'));
    }
    return [[], null];
};
$withMine = function ($b, string $card) use ($myDeck) {
    $b->MyLeader('LAW_008'); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, $card);
    foreach ($myDeck as $c) $b->WithCardInDeckForPlayer(1, $c);
};

// The meta for the opponent's leader: Ahsoka Tano (ASH_009) on Otoh Gunga (HMW_033) — Ninin's Ahsoka Yellow, a pre-con.
$meta = SWUBotMetaCopiesByTitle('ASH_009', 'HMW_033');
$check(count($meta) >= 10, 'fixture: the Ahsoka Yellow meta list is known (' . count($meta) . ' titles)');
// The deny pick at R resources (owner 2026-10-02, "resource aware … at 6R+, the usual bombs are good to name"): the most
// impact (cost) × how close it is to castable. Derived through the bot's own proximity rule.
$denyBest = function (int $r) use ($meta) {
    $best = null; $bestV = -1;
    foreach ($meta as $t => $m) { $c = intval(CardCost($m['cid'])); $v = $c * SWUBotCastProximity($c, $r); if ($v > $bestV) { $bestV = $v; $best = $t; } }
    return $best;
};
$check(SWUBotCastProximity(5, 3) < 1.0 && SWUBotCastProximity(4, 3) === 1.0 && SWUBotCastProximity(8, 7) === 1.0, 'proximity: full at cost ≤ resources + 1, discounted beyond');
$oppAhsoka = function ($b, int $r = 3) { $b->TheirLeader('ASH_009'); $b->TheirBase('HMW_033'); $b->FillResourcesForPlayer(2, 'SOR_095', $r); };

// A) THE REPORT: Ryder Azadi early (3 resources), nothing shown yet. Names one of THEIR meta cards — the most impactful one
// they can cast soon — never my own.
[$opts, $pick] = $nameFor(function ($b) use ($withMine, $oppAhsoka) { $withMine($b, 'ASH_077'); $oppAhsoka($b); });
$check(isset($meta[$pick]), "A: Ryder names a card from the opponent's meta list; got \"$pick\"");
$check($pick === $denyBest(3), "A: …the most impact they can cast soon at 3 resources (" . $denyBest(3) . "); got \"$pick\"");
// A2) At 6+ resources their bombs are close: Ryder names the costliest card in their list. ⚠ With Ahsoka DEPLOYED: an
// undeployed Ahsoka at 6 is on her deploy turn, where the Plot rule (P below) names a Plot card instead — this list's
// "bombs" cost only 5, so Naboo Royal Starship (4 × 1.5) outranks them then. A 7-8 cost bomb would still win.
$metaByCost = $meta; uasort($metaByCost, fn($x, $y) => intval(CardCost($y['cid'])) <=> intval(CardCost($x['cid'])));
$topCost = intval(CardCost(reset($metaByCost)['cid']));
[, $pick6] = $nameFor(function ($b) use ($withMine) { $withMine($b, 'ASH_077'); $b->TheirLeader('ASH_009', true, true, true, 'unit');
    $b->TheirBase('HMW_033'); $b->FillResourcesForPlayer(2, 'SOR_095', 6); });
$check(isset($meta[$pick6]) && intval(CardCost($meta[$pick6]['cid'])) === $topCost, "A2: at 6 resources Ryder names a bomb (cost $topCost); got \"$pick6\"");
$check($denyBest(3) !== $pick6 || $topCost <= 4, 'A2: fixture — the 3- and 6-resource picks differ, so the proximity rule is what decides');
$check(!in_array($pick, $ownTitles, true) || isset($meta[$pick]), "A: never a card only in MY deck; got \"$pick\"");
[, $pickOff] = $nameFor(function ($b) use ($withMine, $oppAhsoka) { $withMine($b, 'ASH_077'); $oppAhsoka($b); }, 'no-namecard');
$check(in_array($pickOff, $ownTitles, true), "A @no-namecard: named a card from its OWN deck (the report); got \"$pickOff\"");

// B) An opponent with NO meta list (leader SOR_014 on SOR_020): only what they have shown. Their discard holds a Battlefield
// Marine (cost 2) and a Consular Security Force (cost 4) is in play: Regional Governor names the costlier, still-live one.
$shownBoard = function ($b) use ($withMine) { $withMine($b, 'SOR_062'); $b->TheirLeader('SOR_014'); $b->TheirBase('SOR_020');
    $b->WithCardInDiscardForPlayer(2, 'SOR_095'); $b->WithGroundUnitForPlayer(2, 'SOR_046', true); $b->FillResourcesForPlayer(2, 'SOR_095', 3); };
[$opts, $pick] = $nameFor($shownBoard);
$check($pick === CardTitle('SOR_046'), 'B: with no meta, Regional Governor names the costliest SHOWN card; got "' . $pick . '"');

// C) Nothing known at all (no meta, nothing shown): declines rather than naming at random.
[$opts, $pick] = $nameFor(function ($b) use ($withMine) { $withMine($b, 'ASH_077'); $b->TheirLeader('SOR_014'); $b->TheirBase('SOR_020'); $b->FillResourcesForPlayer(2, 'SOR_095', 3); });
$check($pick === '-', "C: nothing known about the opponent → decline; got \"$pick\"");

// D) Transmission Jamming ("can't be played THIS PHASE") names a card they can AFFORD now: 3 resources → cost ≤ 3, the
// costliest such card in their meta.
$affordable = array_filter($meta, fn($m) => intval(CardCost($m['cid'])) <= 3);
$affordTop = max(array_map(fn($m) => intval(CardCost($m['cid'])), $affordable));
[$opts, $pick] = $nameFor(function ($b) use ($withMine, $oppAhsoka) { $withMine($b, 'LAW_243'); $oppAhsoka($b); });
$check(isset($affordable[$pick]) && intval(CardCost($meta[$pick]['cid'])) === $affordTop, "D: Transmission Jamming names an affordable card (cost $affordTop of ≤ 3); got \"$pick\"");

// E) Garindan ("look at an opponent's hand and discard a card with that name") names the card with the MOST copies still
// hidden. Two of their most-played card are already in their discard, so a card with all its copies hidden outranks it.
// ⚠ Garindan skips the whole ability when the opponent's hand is EMPTY (nothing to look at), so they hold two cards here —
// both OUTSIDE their meta list, which also shows the bot is not reading the hand: it never names what is really in it.
$oppHand = function ($b) { $b->WithCardInHandForPlayer(2, 'SOR_237'); $b->WithCardInHandForPlayer(2, 'SOR_237'); };
$check(!isset($meta[CardTitle('SOR_237')]), 'fixture: the opponent\'s real hand card is not in their meta list');
$byCopies = $meta; uasort($byCopies, fn($x, $y) => $y['copies'] <=> $x['copies']);
$commonest = array_key_first($byCopies);
[$opts, $pick] = $nameFor(function ($b) use ($withMine, $oppAhsoka, $oppHand) { $withMine($b, 'SEC_186'); $oppAhsoka($b); $oppHand($b); });
$check($pick !== CardTitle('SOR_237'), "E: the bot does not name the card really in their hand (it cannot see it); got \"$pick\"");
$check(isset($meta[$pick]) && $meta[$pick]['copies'] == $byCopies[$commonest]['copies'], "E: Garindan names a card with the most copies ({$byCopies[$commonest]['copies']}); got \"$pick\"");
$shownTwice = $meta[$pick]['cid'] ?? '';
[$opts, $pick2] = $nameFor(function ($b) use ($withMine, $oppAhsoka, $oppHand, $shownTwice) { $withMine($b, 'SEC_186'); $oppAhsoka($b); $oppHand($b);
    $b->WithCardInDiscardForPlayer(2, $shownTwice); $b->WithCardInDiscardForPlayer(2, $shownTwice); });
$check($pick2 !== $pick, "E: with two copies of \"$pick\" already discarded, Garindan names another card; got \"$pick2\"");

// A3) A UNIQUE unit of that name already on their board (owner 2026-10-03: "they most likely will not play another unique
// unit of the same kind … weigh those less to name"). At 3 resources Ryder's pick is Captain Typho (unique); with Typho
// in play it names another card of the same castable cost instead — and a Galen still names nothing worse for it.
$typho = CardTitle('SEC_098');
$check($denyBest(3) === $typho && CardUnique('SEC_098'), 'A3: fixture — Captain Typho (unique) is the 3-resource pick');
[, $pickU] = $nameFor(function ($b) use ($withMine, $oppAhsoka) { $withMine($b, 'ASH_077'); $oppAhsoka($b); $b->WithGroundUnitForPlayer(2, 'SEC_098', true); });
$check($pickU !== $typho && isset($meta[$pickU]) && intval(CardCost($meta[$pickU]['cid'])) === intval(CardCost('SEC_098')),
    "A3: with their unique Typho in play, Ryder names another castable cost-" . CardCost('SEC_098') . " card; got \"$pickU\"");
// Isolated: Typho IN PLAY vs Typho in the DISCARD — the same number of copies shown, so the only difference is the unique
// on their board. (The pick above is a near-tie the shown copy alone can tip; this is the rule itself.)
$build(function ($b) use ($oppAhsoka) { $b->MyLeader('LAW_008'); $oppAhsoka($b); $b->WithCardInDiscardForPlayer(2, 'SEC_098'); });
$ryderDiscard = SWUBotNameCardScore(1, $typho, ['ASH_077#0']);
// (Transmission Jamming needs Typho AFFORDABLE now, so its pair is measured at 4 resources.)
$build(function ($b) use ($oppAhsoka) { $b->MyLeader('LAW_008'); $oppAhsoka($b, 4); $b->WithCardInDiscardForPlayer(2, 'SEC_098'); });
$jamDiscard = SWUBotNameCardScore(1, $typho, ['LAW_243#0']);
$build(function ($b) use ($oppAhsoka) { $b->MyLeader('LAW_008'); $oppAhsoka($b, 4); $b->WithGroundUnitForPlayer(2, 'SEC_098', true); });
$jamInPlay = SWUBotNameCardScore(1, $typho, ['LAW_243#0']);
$build(function ($b) use ($oppAhsoka) { $b->MyLeader('LAW_008'); $oppAhsoka($b); $b->WithGroundUnitForPlayer(2, 'SEC_098', true); });
$ryderInPlay = SWUBotNameCardScore(1, $typho, ['ASH_077#0']);
$check($ryderInPlay < $ryderDiscard - 1.0, "A3: Ryder weighs Typho far less with the unique on their board than with one in the discard ($ryderDiscard → $ryderInPlay)");
$check($jamInPlay < $jamDiscard - 1.0, "A3: …and so does Transmission Jamming ($jamDiscard → $jamInPlay)");
$galenIn = SWUBotNameCardScore(1, $typho, ['SEC_046#0']);
$build(function ($b) use ($oppAhsoka) { $b->MyLeader('LAW_008'); $oppAhsoka($b); });
$galenOut = SWUBotNameCardScore(1, $typho, ['SEC_046#0']);
$check($galenIn >= $galenOut, "A3: Galen is not discouraged by the unique in play (its copy loses abilities too): $galenOut → $galenIn");

// P) PLOT cards near the opponent's DEPLOY (owner 2026-10-03: "Plot cards should be named closer to the human's deploy
// turn"). Ahsoka deploys at 6 resources; her list's Plot cards come out of resources at that deploy. At 5 resources (deploy
// next round) Ryder names a Plot card; the same board with Ahsoka already deployed names a non-Plot card; at 3 (two rounds
// away) the Plot window is not near and the pick is unchanged (A).
$isPlotTitle = fn($t) => isset($meta[$t]) && in_array('plot', SWUBotCardTags($meta[$t]['cid']), true);
$check(count(array_filter(array_keys($meta), $isPlotTitle)) >= 1, 'P: fixture — their list has Plot cards');
[, $pickP] = $nameFor(function ($b) use ($withMine, $oppAhsoka) { $withMine($b, 'ASH_077'); $oppAhsoka($b, 5); });
$check(SWUBotOpponentRoundsToDeploy(1) === 1, 'P: fixture — at 5 resources Ahsoka deploys next round; got ' . json_encode(SWUBotOpponentRoundsToDeploy(1)));
$check($isPlotTitle($pickP), "P: the round before their deploy, Ryder names a PLOT card; got \"$pickP\"");
[, $pickD] = $nameFor(function ($b) use ($withMine) { $withMine($b, 'ASH_077'); $b->TheirLeader('ASH_009', true, true, true, 'unit');
    $b->TheirBase('HMW_033'); $b->FillResourcesForPlayer(2, 'SOR_095', 5); });
$check(SWUBotOpponentRoundsToDeploy(1) === null && !$isPlotTitle($pickD), "P: after their deploy the Plot window is gone — a non-Plot card; got \"$pickD\"");
$check(!$isPlotTitle($denyBest(3)), 'P: at 3 resources (deploy two rounds away) the pick is not a Plot card (' . $denyBest(3) . ')');

// G) SEEN IN HAND (owner 2026-10-02: "the bot should also be aware of cards that might be in hand after it 'sees' them
// with cards like Beguile or Garindan"). The bot plays SEC_233 Beguile ("Look at an opponent's hand…"), which shows an
// X-Wing (SOR_237) — a card in no Ahsoka meta list — then plays Ryder: it names the X-Wing it KNOWS they hold.
$check(!isset($meta[CardTitle('SOR_237')]), 'fixture: the X-Wing is not in their meta list');
$lookThenName = function (string $namer, ?callable $between = null, string $variant = '') use (&$gameName, $act, $build, $withMine, $oppAhsoka) {
    $build(function ($b) use ($withMine, $oppAhsoka, $namer) { $withMine($b, 'SEC_233'); $b->WithCardInHandForPlayer(1, $namer); $oppAhsoka($b);
        $b->WithCardInHandForPlayer(2, 'SOR_237'); $b->FillResourcesForPlayer(1, 'SOR_095', 10); });
    $drain = function () use (&$gameName, $act, $variant) {
        for ($i = 0; $i < 8; $i++) {
            $l = SWUBotLegalActions($gameName, 1);
            if (($l['kind'] ?? '') !== 'decision' || ($l['decisionType'] ?? '') === 'NAMECARD') return $l;
            $p = SWUBotHeuristicChoose('normal', (array)$l['actions'], $l, $variant);
            $act(1, intval($p['mode'] ?? 10001), strval($p['cardID'] ?? 'PASS'));
        }
        return SWUBotLegalActions($gameName, 1);
    };
    $act(1, 10002, 'myHand-0!FSM!');   // Beguile: the look
    $drain();
    if ($between !== null) $between();
    if (SWUBotLegalActions($gameName, 1)['kind'] === 'waiting-on-other-seat') $act(2, 10001, 'myHealth-0!CustomInput!Pass');
    foreach (GetHand(1) as $i => $o) if (strval($o->CardID) === $namer) { $act(1, 10002, "myHand-$i!FSM!"); break; }
    $l = $drain();
    if (($l['decisionType'] ?? '') !== 'NAMECARD') return null;
    return strval(SWUBotHeuristicChoose('normal', (array)$l['actions'], $l, $variant)['cardID'] ?? '');
};
$pick = $lookThenName('ASH_077');
$check(SWUHandSeenSnapshot(1, 2) !== null, 'G: fixture — Beguile\'s look was recorded for the bot');
$check($pick === CardTitle('SOR_237'), 'G: after Beguile showed an X-Wing, Ryder names the X-Wing it knows they hold; got "' . $pick . '"');
// H) …but once that X-Wing has been PLAYED (it went public after the look), it is no longer counted as held.
$pick = $lookThenName('ASH_077', function () use ($act) { $act(2, 10002, 'myHand-0!FSM!'); });
$check(count(GetUnitsInArena(2, 'Space')) === 1, 'H: fixture — the opponent played the X-Wing after the look');
$check($pick !== CardTitle('SOR_237'), 'H: a seen card that has since been played is not named as held; got "' . $pick . '"');
// I) Garindan after a Beguile: names the card it saw, though Garindan itself only looks AFTER naming.
$pick = $lookThenName('SEC_186');
$check($pick === CardTitle('SOR_237'), 'I: Garindan, after a Beguile look, names the card seen in their hand; got "' . $pick . '"');
// J) Zuckuss names the top of their DECK: a copy seen in their HAND is not there, so seeing it LOWERS the deck-top score
// (a seen card off-meta is assumed run at 2 copies, so the other may still be in the deck) — and raises a hand-hitter's.
$t = CardTitle(SWUBotMetaCopiesByTitle('ASH_009', 'HMW_033') ? array_values($meta)[0]['cid'] : 'SOR_237');
$build(function ($b) use ($oppAhsoka, $meta, $t) { $b->MyLeader('LAW_008'); $oppAhsoka($b); $b->WithCardInHandForPlayer(2, $meta[$t]['cid']);
    for ($i = 0; $i < 20; $i++) $b->WithCardInDeckForPlayer(2, 'SOR_095'); });
$deckBefore = SWUBotNameCardScore(1, $t, ['LOF_204#0']); $handBefore = SWUBotNameCardScore(1, $t, ['SEC_186#0']);
SWURecordHandSeen(2, [1]);
$deckAfter = SWUBotNameCardScore(1, $t, ['LOF_204#0']); $handAfter = SWUBotNameCardScore(1, $t, ['SEC_186#0']);
$check($deckAfter < $deckBefore, "J: seeing \"$t\" in their hand lowers Zuckuss's deck-top score ($deckBefore → $deckAfter)");
$check($handAfter > $handBefore, "J: …and raises a hand-hitter's ($handBefore → $handAfter)");

// K) Each reveal path records on its own (Beguile goes through BOTH helpers, which would mask either one breaking).
$kBoard = function ($b) use ($oppAhsoka) { $b->MyLeader('LAW_008'); $oppAhsoka($b); $b->WithCardInHandForPlayer(2, 'SOR_237'); };
$build($kBoard); SWULookAtOpponentHand(1, null, 2);
$check(array_column((array)SWUHandSeenSnapshot(1, 2), 0) === ['SOR_237'], 'K: SWULookAtOpponentHand records what the viewer saw');
$build($kBoard); SWUQueueShowOpponentHand(1, 2);
$check(array_column((array)SWUHandSeenSnapshot(1, 2), 0) === ['SOR_237'], 'K: SWUQueueShowOpponentHand records what the viewer saw');
$check(SWUHandSeenSnapshot(2, 1) === null, 'K: …for the viewer only — the owner\'s own record is untouched');
// L) A PUBLIC reveal a card logs itself — SOR_185 Chimaera's On Attack: "Name a card. An opponent reveals their hand…".
$build(function ($b) use ($oppAhsoka) { $b->MyLeader('LAW_008'); $oppAhsoka($b); $b->WithSpaceUnitForPlayer(1, 'SOR_185', true);
    $b->WithCardInHandForPlayer(2, 'SOR_237'); $b->WithCardInHandForPlayer(2, 'SOR_095'); });
$atk = null;
foreach ((array)SWUBotLegalActions($gameName, 1)['actions'] as $a) if (SWUBotActionKind($a) === 'attack') { $atk = $a; break; }
if ($atk !== null) $act(1, intval($atk['mode'] ?? 10001), strval($atk['cardID']));
for ($i = 0; $i < 8; $i++) {
    $l = SWUBotLegalActions($gameName, 1);
    if (($l['kind'] ?? '') !== 'decision') break;
    $p = SWUBotHeuristicChoose('normal', (array)$l['actions'], $l, '');
    $act(1, intval($p['mode'] ?? 10001), strval($p['cardID'] ?? 'PASS'));
}
$snap = SWUHandSeenSnapshot(1, 2);
$check($snap !== null, 'L: Chimaera\'s public hand reveal is recorded for the attacker; got ' . json_encode($snap));
// L2) …and a PUBLIC reveal is seen by EVERY other seat: at 3 seats the bystander (seat 3) knows the hand too. (At 2 seats
// the attacker's record also comes from the show-hand popup, so only a third seat can tell this hook apart.)
$build(function ($b) { $b->WithSeatOrder('123'); $b->WithLiveSeats('123'); $b->MyLeader('LAW_008');
    $b->WithLeaderForSeat(2, 'ASH_009'); $b->WithLeaderForSeat(3, 'SOR_014'); $b->WithCardInHandForPlayer(2, 'SOR_237'); });
global $customDQHandlers;
$customDQHandlers['SOR_185#0'](1, ['2'], CardTitle('SOR_095'));
$check(SeatCountForGame() === 3 && SWUHandSeenSnapshot(3, 2) !== null, 'L2: at 3 seats Chimaera\'s reveal is recorded for the bystander seat too; got ' . json_encode(SWUHandSeenSnapshot(3, 2)));

// F) Foresight (TWI_068, regroup: "name a card, then look at the top card of your deck") is the one that names MY deck:
// the title with the most copies left in it. Scored directly on its continuation.
$build(function ($b) { $b->MyLeader('LAW_008'); foreach (['ASH_052', 'SEC_087', 'SEC_087', 'SEC_087', 'LAW_044'] as $c) $b->WithCardInDeckForPlayer(1, $c); });
$scores = [];
foreach ([CardTitle('ASH_052'), CardTitle('SEC_087'), CardTitle('LAW_044')] as $t) $scores[$t] = SWUBotNameCardScore(1, $t, ['TWI_068#0']);
arsort($scores);
$check(array_key_first($scores) === CardTitle('SEC_087'), 'F: Foresight names the title with the most copies left in my deck; got ' . json_encode($scores));
