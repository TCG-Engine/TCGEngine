<?php
// Curve value (spec docs/superpowers/specs/2026-10-05-swusim-curve-value-design.md): a card in hand priced against its
// cost, in RESOURCES, from the owner's prices. Every assertion here was mutation-checked when written.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_curvevalue_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';

// IDs are resolved by TITLE, never typed from memory (a wrong ID once produced false failures in bot_cardvalue_test).
$idByTitle = function (string $title, string $type = ''): string {
    static $ids = null;
    if ($ids === null) {
        preg_match_all("/'([A-Z]{3}_\d{3})' =>/", (string)file_get_contents('./SWUSim/GeneratedCode/GeneratedCardDictionaries.php'), $m);
        $ids = array_values(array_unique($m[1]));
    }
    foreach ($ids as $id) if (CardTitle($id) === $title && ($type === '' || CardType($id) === $type) && !str_starts_with($id, 'IBH')) return $id;
    return '';
};
$static = fn(string $cid) => SWUBotCurveValue(0, $cid, false);
$near = fn(?array $r, float $want, float $tol) => $r !== null && abs($r['surplus'] - $want) <= $tol;

// ── Task 1: unit bodies and keywords — the owner's examples sit on curve (surplus within ±0.5) ──────────────────
$check(function_exists('SWUBotCurveValue') && function_exists('SWUBotCurveSurplus'), 'the pricer is loaded by BotHeuristic.php');
foreach (['Imperial Dark Trooper' => 'SEC_080', 'Fennec Shand' => 'ASH_192', 'Flanking TIE Interceptor' => 'ASH_215',
          'Eager Escort Fighter' => 'JTL_112', 'Shin Hati' => 'LOF_183', 'Unsanctioned Patrol' => 'ASH_222',
          'Forest Patroller' => 'ASH_096'] as $title => $id) {
    $check(CardTitle($id) === $title, "premise: $id is $title");
    $r = $static($id);
    $check($near($r, 0.0, 0.5), "$title is on curve; got " . json_encode($r === null ? null : [$r['surplus'], $r['parts']]));
}
// Exact arithmetic on two, so a price change cannot hide inside the ±0.5 band.
$r = $static('SEC_080');
$check($r !== null && abs($r['value'] - 3.0) < 1e-9 && abs($r['budget'] - 3.0) < 1e-9, 'Dark Trooper 2/3/3: value 3.0, budget 3.0; got ' . json_encode($r));
$r = $static('ASH_192');   // 3/3 = 3.0, Ambush 1.0, Saboteur (free) 0.25 → 4.25 vs 4
$check($r !== null && abs($r['value'] - 4.25) < 1e-9, 'Fennec: 3.0 body + 1.0 Ambush + 0.25 free = 4.25; got ' . json_encode($r));
$r = $static('ASH_215');   // 2/2 = 2.0, space 0.5, Support 0.5 → 3.0 — the space stat would hide inside the ±0.5 band
$check($r !== null && abs($r['value'] - 3.0) < 1e-9, 'Flanking TIE: 2.0 body + 0.5 space + 0.5 Support = 3.0; got ' . json_encode($r));
// Mae (HMW, Legendary) is above curve.
$check(CardTitle('HMW_055') === 'Mae', 'premise: HMW_055 is Mae');
$r = $static('HMW_055');
$check($r !== null && $r['surplus'] > 0.5, 'Mae (2/4 Ambush Shielded Grit for 3) reads above curve; got ' . json_encode($r));
// Allowances are REPORTED but never subtracted (owner 2026-10-05).
$check($r !== null && $r['allowances']['aspects'] > 0 && abs($r['surplus'] - ($r['value'] - $r['budget'])) < 1e-9,
    'Mae: 3-aspect allowance reported (> 0) and NOT taken out of the surplus');
// Unpriced: text the parser cannot read. Out of scope: leaders, bases (Review Focus 3).
$check($static('HMW_007') === null, 'a Leader card ID is out of scope (null)');
$check($static('HMW_032') === null, 'a Base card ID is out of scope (null)');
$check(SWUBotCurveSurplus(0, 'HMW_032', 6, false) === null, 'SWUBotCurveSurplus passes the null through');

// ── Task 2: abilities, priced with NO board ───────────────────────────────────────────────────────────────────────
$ev = function (string $title, float $want, float $tol, string $why) use ($idByTitle, $static, $near, $check) {
    $id = $idByTitle($title);
    $check($id !== '', "premise: '$title' resolves to a card ID");
    $r = $static($id);
    $check($near($r, $want, $tol), "$title: $why; got " . json_encode($r === null ? null : [$r['surplus'], $r['parts']]));
};
$ev('Strategic Analysis', -2.0, 0.01, 'draw 3 (=3) for 5 → −2, the one NOT on the owner\'s list');
$ev('Daring Raid',         0.0, 0.01, '2 to a unit or base = 1 for 1 — the owner\'s floor');
$ev('Open Fire',           0.0, 0.01, '4 damage = 3 for 3');
$ev('Maim',                0.5, 0.01, '1 damage (0.5) + exhaust (1) for 1');
$ev('Spark of Rebellion',  0.0, 0.01, 'targeted discard 2 for 2');
$ev('Rebel Assault',       1.0, 0.01, 'two attacks (0.5 each) at +1 (0.5 each) for 1');
$ev('Takedown',            1.0, 0.01, 'defeat ≤5 remaining HP (5 damage = 4) +1 because it can kill a softened leader (owner, rule C), for 4');
$ev('Force Choke',         1.0, 0.01, '5 damage (4) − their draw (1), for 2 (no Force unit, no board: no discount)');
$ev('Resupply',            0.0, 0.01, 'ramp at round 1, horizon 6 = 3, for 3');
$ev('Aggression',          0.0, 0.01, 'best two priced modes: 4 damage (3) + draw (1), for 4');
$ev('Cunning',             2.0, 0.01, 'best two: bounce ≤4 power (4, static) + exhaust 2 or +4/+0 (2), for 4');
$ev('Planetary Bombardment', -2.0, 0.01, '8 indirect = 4 with no board (no Capital Ship), for 6');
$ev('Academy Training',    0.0, 0.01, 'a +2/+2 upgrade = 2 for 2');
$r = $static('ASH_220');
$check(CardTitle('ASH_220') === 'Remnant Lookouts' && $near($r, 0.0, 0.01),
    'Remnant Lookouts: 3/3 (3) + discard (2) − their draw (1) = 4 for 4; got ' . json_encode($r));
$r = $static('ASH_030');
$check(CardTitle('ASH_030') === 'Marrok' && $r !== null && $r['surplus'] > 0.5,
    'Marrok: 2/6 Sentinel for 3 reads above curve (his upgraded clause is a 0 trade); got ' . json_encode($r));
$r = $static('LOF_096');
$check(CardTitle('LOF_096') === 'Obi-Wan Kenobi' && $near($r, 0.0, 0.5),
    'Obi-Wan (LOF) 3/3/5 (3.9) + conditional Sentinel (1 stat = 0.5) = 4.4 vs 4 → on curve; got ' . json_encode($r));
$r = $static('HMW_202');
$check(CardTitle('HMW_202') === 'Inferno Squad' && $r !== null && abs($r['value'] - (1.5 + 0.9 * (4.35 + 0.75))) < 1e-9,
    'Inferno Squad (cost 5): When Played ping+Weakness 1.5 + lasting (3/6 4.35 + When Defeated copy at 50% 0.75) × 0.9 (rules A, B); got ' . json_encode($r));
$r = $static('JTL_252');
$tok = $r === null ? [] : array_filter($r['parts'], fn($v, $k) => str_contains($k, 'x-wing'), ARRAY_FILTER_USE_BOTH);
$check(CardTitle('JTL_252') === 'Tantive IV' && count($tok) === 1 && array_values($tok)[0] > 1.0,
    'Tantive IV: "Create an X-Wing token" is priced as the token\'s body; got ' . json_encode($r));
// Unpriced stays unpriced: a sentence the parser cannot read makes the whole card null.
$check($static($idByTitle('Overwhelming Barrage')) === null, 'Overwhelming Barrage ("divided as you choose") is unpriced');
$check($static('HMW_104') === null, 'Garnac (conditional Hidden + When Attack Ends) is unpriced');

// ── Task 3: priced ON A BOARD ─────────────────────────────────────────────────────────────────────────────────────
// The reference board: mine = Shin Hati (a Force unit), Consular Security Force (3/7), two small space fighters (more
// space units than them); theirs = Battlefield Marine (2-cost), Consular Security Force, Coastal Catamarans (8/8
// Vehicle), Rogue-class Starfighter (space Vehicle). Round 2, I have the initiative and fewer resources (3 vs 6),
// and six fillers in hand besides the card under test.
foreach (['LOF_183' => 'Force', 'HMW_093' => 'Vehicle', 'JTL_241' => 'Vehicle', 'JTL_069' => 'Capital Ship'] as $id => $trait) {
    $check(_SWUBotCurveHasTrait($id, $trait), "premise: $id " . CardTitle($id) . " has trait $trait");
}
$board = function (string $kind, string $cardInHand) use ($build) {
    $build(function ($b) use ($kind, $cardInHand) {
        $b->FillResourcesForPlayer(1, 'SOR_095', 3);
        $b->FillResourcesForPlayer(2, 'SOR_095', 6);
        $b->WithCurrentRoundBeing(2);
        $b->WithInitiativePlayerBeing(1);
        if ($kind === 'default') {
            foreach (['LOF_183', 'SOR_046'] as $u) $b->WithGroundUnitForPlayer(1, $u, true, 0);
            foreach (['JTL_212', 'ASH_201'] as $u) $b->WithSpaceUnitForPlayer(1, $u, true, 0);
        }
        if ($kind === 'empty-enemy') $b->WithSpaceUnitForPlayer(1, 'JTL_069', true, 0);   // a Capital Ship, no enemies
        if ($kind !== 'empty-enemy') {
            foreach (['SOR_095', 'SOR_046', 'HMW_093'] as $u) $b->WithGroundUnitForPlayer(2, $u, true, 0);
            $b->WithSpaceUnitForPlayer(2, 'JTL_241', true, 0);
        }
        for ($i = 0; $i < 6; $i++) $b->WithCardInHandForPlayer(1, 'SOR_095');
        $b->WithCardInHandForPlayer(1, $cardInHand);
    });
};
// The owner's good events: each reads ≥ −0.5 (aspect penalty added back, since the test seat's aspects are arbitrary).
// Ramp is priced at horizon 8 (soft control: Resupply is a control/ramp card).
$special = ['Planetary Bombardment' => 'empty-enemy', 'Merciless Contest' => 'no-friendly'];
$reference = ['Daring Raid', 'Maim', 'Rebel Assault', 'Punch It', 'Commence the Festivities', 'Force Choke', 'Air Superiority',
              'No Good to Me Dead', 'Surprise Strike', 'Spark of Rebellion', 'Open Fire', "Let's Call It War", 'Crushing Blow',
              'Takedown', 'Aggression', 'Protect the Pod', 'Fell the Dragon', "Dragon's Might", 'Direct Hit', 'No Glory, Only Results',
              "Rival's Fall", 'Planetary Bombardment', 'Resupply', 'Vigilance', 'Command', 'Cunning', 'Hold For Questioning',
              'Power of the Dark Side', 'Merciless Contest', 'Single Reactor Ignition', 'Aggressive Negotiations'];
$unpricedRef = [];
foreach ($reference as $title) {
    $id = $idByTitle($title, 'Event');
    $check($id !== '', "premise: '$title' resolves to an Event");
    $board($special[$title] ?? 'default', $id);
    $r = SWUBotCurveValue(1, $id, true, 8);
    if ($r === null) { $unpricedRef[] = $title; continue; }
    $s = $r['surplus'] + intval(SWUAspectPenalty(1, $id));
    $check($s >= -0.5, "$title reads at least −0.5 on its board; got " . json_encode([round($s, 2), $r['parts']]));
}
$check(count($unpricedRef) <= 2, 'at most 2 reference events unpriced (owner review lists them): ' . json_encode($unpricedRef));
echo "INFO: reference events unpriced (go to the owner's review): " . json_encode($unpricedRef) . "\n";

// Cost actually paid: an off-aspect card's budget carries the aspect penalty.
$vig = $idByTitle('Vigilance', 'Event');
$board('default', $vig);
$pen = intval(SWUAspectPenalty(1, $vig));
$check($pen > 0, "premise: Vigilance is off-aspect for the test seat (penalty $pen)");
$r = SWUBotCurveValue(1, $vig, true, 8);
$check($r !== null && abs($r['budget'] - (intval(CardCost($vig)) + $pen)) < 1e-9, 'an off-aspect Vigilance must earn its printed cost + the penalty; got ' . json_encode($r));

// A discount whose condition holds lowers the cost: Force Choke with vs without a Force unit differs by exactly 1.
$fc = $idByTitle('Force Choke', 'Event');
$board('default', $fc);                      $with = SWUBotCurveSurplus(1, $fc);
$board('no-friendly', $fc);                  $without = SWUBotCurveSurplus(1, $fc);
$check($with !== null && $without !== null && abs(($with - $without) - 1.0) < 1e-9,
    'Force Choke: a Force unit in play is worth exactly its 1-resource discount; got ' . json_encode([$with, $without]));

// Wasted damage: Open Fire into a board of 2-HP units prices as 2 damage (1), not 4 (3).
$of = $idByTitle('Open Fire', 'Event');
$check(intval(CardHp('TWI_241')) === 2, 'premise: TWI_241 has 2 HP');
$build(function ($b) use ($of) { $b->WithGroundUnitForPlayer(2, 'TWI_241', true, 0); $b->WithCardInHandForPlayer(1, $of); });
$r = SWUBotCurveValue(1, $of);
$check($r !== null && abs(array_sum($r['parts']) - 1.0) < 1e-9, 'Open Fire into a 2-HP board is worth 1; got ' . json_encode($r));

// Removal with no target is worth 0; leaders and tokens (Review Focus 1, 2).
$rf = $idByTitle("Rival's Fall", 'Event'); $ng = $idByTitle('No Glory, Only Results', 'Event');
$build(function ($b) use ($rf) { $b->WithCardInHandForPlayer(1, $rf); });
$check(abs(SWUBotCurveValue(1, $rf)['value']) < 1e-9, "Rival's Fall into an empty board is worth 0");
$build(function ($b) use ($rf) { $b->WithLeaderForSeat(2, 'HMW_007', true, true); $b->WithCardInHandForPlayer(1, $rf); });
$enemy = SWUBotUnits(2);
$check(count($enemy) === 1 && $enemy[0]['isLeader'], 'premise: the only enemy unit is a deployed leader; got ' . json_encode(array_column($enemy, 'cardID')));
$check(abs(SWUBotCurveValue(1, $rf)['value'] - 6.0) < 1e-9, "Rival's Fall vs a deployed leader alone = the any-unit anchor 6");
$check(abs(SWUBotCurveValue(1, $ng)['value']) < 1e-9, 'No Glory (non-leader) vs a deployed leader alone = 0');
$build(function ($b) use ($rf) { $b->WithGroundUnitForPlayer(2, 'TWI_T01', true, 0); $b->WithCardInHandForPlayer(1, $rf); });
$tv = SWUBotCurveValue(1, $rf)['value'] ?? -1;
$check($tv > 0 && $tv <= 2.0, "Rival's Fall vs a token prices it by its fallback worth (cost 0 + 1); got $tv");

// Duplicate unique (Review Focus 5): Shin Hati in play makes the Shin Hati in hand surplus 0. Same title, different
// subtitle (another printing of the character) must NOT match.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'LOF_183', true, 0); $b->WithCardInHandForPlayer(1, 'LOF_183'); });
$check(abs(SWUBotCurveSurplus(1, 'LOF_183')) < 1e-9, 'a second Shin Hati while one is in play: surplus 0');
$otherObi = '';
preg_match_all("/'([A-Z]{3}_\d{3})' => 'Obi-Wan Kenobi'/", (string)file_get_contents('./SWUSim/GeneratedCode/GeneratedCardDictionaries.php'), $mm);
foreach (array_unique($mm[1]) as $cand) {
    if (CardType($cand) === 'Unit' && CardSubtitle($cand) !== CardSubtitle('LOF_096') && !str_starts_with($cand, 'IBH')) { $otherObi = $cand; break; }
}
$check($otherObi !== '', 'premise: another Obi-Wan Kenobi unit printing exists');
$build(function ($b) use ($otherObi) { $b->WithGroundUnitForPlayer(1, $otherObi, true, 0); $b->WithCardInHandForPlayer(1, 'LOF_096'); });
$check(_SWUBotCurveUniqueInPlay(1, 'LOF_096') === false, 'a different Obi-Wan printing in play does not block LOF_096');

// ── Task 4 coverage growth: the worklist's cards priced from the owner's prices (static). Body = (1.1P + 0.9H) / 2.
// "(derived)" assumptions, shown in the review table: engines expect 2 kills; "each enemy unit" expects 2 units; an
// attached host has 3 power; Pre Vizsla's static kill is 1; Chimaera's friendly sacrifice is a 3-worth 2-drop.
$cov = function (string $id, string $title, float $want, string $why) use ($static, $near, $check) {
    $r = $static($id);
    $check(CardTitle($id) === $title && $near($r, $want, 0.01), "$title: $why; got " . json_encode($r === null ? null : [$r['surplus'], $r['parts']]));
};
$cov('LAW_097', 'Imperial Door Technician', 2.0 + 1 / 3 - 2.0, '2/2 (2.0) + When Defeated heal 2 from your base (2/3) at 50% (rule A), budget 2');
$cov('LAW_133', 'Lost and Forgotten', 5.0 + 1.0 - 6.0, 'defeat a non-leader (5) + heal 3 (1), for 6');
$cov('LOF_091', 'Craving Power', 2.0 + 4.0 - 5.0, '+2/+2 (2) + damage = host power 3 + 2 → 5 damage (4) (derived host), for 5');
$cov('LAW_039', 'Latts Razzi', 1.55 + 1.0 + 1.0 - 4.0, '2/1 (1.55) + Shield-or-Experience (1) + her power 2 as damage (1), budget 4');
$cov('LAW_132', 'The Tree Remembers', 0.0 + 4.0 - 4.0, 'loses abilities (0) + defeat if it costs ≤3 (a 3-cost unit = 4), for 4');
$cov('SEC_163', 'Outer Rim Constable', 2.1 + 1.0 - 3.0, '3/1 (2.1) + defeat an upgrade (1, derived), budget 3');
$cov('LAW_037', 'Han Solo', 1.0 + 1.0 + 1.0 - 2.0, '1/1 (1) + Shielded (1) + On Attack Experience (1), budget 2');
$cov('JTL_140', 'IG-2000', 3.45 + 0.5 + 0.25 + 1.5 - 5.0, '3/4 space Overwhelm (4.2) + 1 damage to each of up to 3 (1.5), budget 5');
$cov('SEC_204', 'Blue Ace', 4.45 + 0.5 + 1.0 - 1.0 - 5.0, '4/5 space Ambush (5.95) − On Attack readies an enemy (−1, derived), budget 5');
$cov('SEC_075', 'Knowledge and Defense', 1.0 + 1.0 - 3.0, '-2/-2 for the phase (= 2 damage, 1, derived) + draw (1), for 3');
$cov('JTL_102', 'Resistance Blue Squadron', 3.45 + 0.5 + 1.0 - 5.0, '3/4 space (3.95) + damage = friendly space units (static 2 → 1, derived), budget 5');
$cov('JTL_237', 'TIE Bomber', 1.8 + 0.5 + 1.5 - 3.0, '0/4 space (2.3) + On Attack 3 indirect to the defending player (1.5), budget 3');
$cov('LOF_213', 'The Legacy Run', 0.9 * (3.5 + 2.5) - 6.0, 'cost 5: lasting (3/3 space 3.5 + When Defeated 6 divided 5 at 50%) × 0.9 (rules A, B), budget 6');
$cov('ASH_097', 'Moff Gideon', 3.35 + 1.0 + 0.5 - 4.0, '2/5 Sentinel (4.35) + When Defeated return an Imperial from discard (1, derived) at 50%, budget 4');
$cov('ASH_053', 'Pre Vizsla', 0.6 * 6.0 + 5.0 + 3.0 - 9.0, 'cost 8: body 6 × 0.6 (rule B) + When Played defeat ≤6 total HP (5) + a Mandalorian token per kill (1 kill, 3), budget 9');
$cov('ASH_052', 'Chimaera', 0.7 * (6.5 + 4.0) + 4.0 - 8.0, 'cost 7: lasting (6/6 space 6.5 + heal 2 per kill × 6 kills = 4, rule D) × 0.7 + paired defeat 5 − cheapest unit 1 (rule D), budget 8');
$cov('ASH_079', 'Koska Reeves', 4.0 + 0.5 - 5.0, '4/4 (4) + conditional Sentinel (0.5) + token only if a friendly unit died this phase (static 0), budget 5');
$cov('SEC_051', 'Bo-Katan Kryze', 0.5 * (8.0 + 6.0) + 4.0 - 10.0, 'cost 9: lasting (8/8 + Experience per kill × 6, rule D) × 0.5 + When Played -3/-3 to each enemy (2 units × 2, derived), budget 10');
$cov('LOF_130', 'HK-47', 2.9 + 1.5 - 3.0, '2/4 (2.9) + 1 base damage per enemy kill × 3 (rounds left 6, capped at cost + 1 = 3, rule D), budget 3');
// Board checks for the board-only and condition cases.
$hsd = 'SEC_078';
$check(CardTitle($hsd) === 'Hyperspace Disaster' && $static($hsd) === null, 'Hyperspace Disaster is board-only (null with no board)');
$board('no-friendly', $hsd);
$want = _SWUBotCurveWorth(SWUBotUnits(2)[array_search('JTL_241', array_column(SWUBotUnits(2), 'cardID'))]);
$r = SWUBotCurveValue(1, $hsd);
$check($r !== null && abs($r['value'] - $want) < 1e-9, "Hyperspace Disaster vs only their space unit = that unit's worth ($want), ground units untouched; got " . json_encode($r));
$build(function ($b) { $b->WithCardInHandForPlayer(1, 'ASH_079'); });
$noDeath = SWUBotCurveSurplus(1, 'ASH_079', 6);
$build(function ($b) { $b->WithGlobalEffectForPlayer(1, 'SWU_FRIENDLY_DEFEATED'); $b->WithCardInHandForPlayer(1, 'ASH_079'); });
$death = SWUBotCurveSurplus(1, 'ASH_079', 6);
$check($noDeath !== null && $death !== null && abs(($death - $noDeath) - 3.0) < 1e-9,
    'Koska Reeves: a friendly unit defeated this phase adds the Mandalorian token (3); got ' . json_encode([$noDeath, $death]));
// Second batch: conditions on my own state, optional payments, granted keywords.
$cov('LOF_070', 'Anakin Skywalker', 0.8 * 5.9 - 7.0, 'cost 6: 5/7 (5.9) × 0.8 (rule B); both -3/-3 triggers need a discard-pile aspect (unmet with no board), budget 7');
$cov('SEC_233', 'Beguile', 0.0 + 4.0 - 3.0, 'look (0) + bounce a ≤6-cost non-leader (static: a typical 4-cost target, derived), for 3');
$cov('JTL_096', 'Blue Leader', 3.0 + 0.5 + 1.0 + (-2.0 + 0.0 + 2.0) - 4.0, '3/3 space Ambush (4.5) + pay 2 for 2 Experience (net 0), budget 4');
$cov('LOF_215', 'Ascension Cable', 1.9 + 0.25 - 2.0, '+1/+3 (1.9) + grants Saboteur (a free keyword, 0.25), for 2');
$cov('JTL_143', 'Devastator', 0.6 * 8.15 + 2.0 - 9.0, 'cost 8: 9/6 space (8.15) × 0.6 (rule B) + "you assign" (0, derived) + When Played 4 indirect (static 2), budget 9');
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0); $b->WithCardInHandForPlayer(1, 'LOF_070'); });
$dry = SWUBotCurveSurplus(1, 'LOF_070', 6);
$check(CardAspect('SOR_095') === 'Command,Heroism', 'premise: Battlefield Marine is a Heroism card');
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0); $b->WithCardInDiscardForPlayer(1, 'SOR_095'); $b->WithCardInHandForPlayer(1, 'LOF_070'); });
$wet = SWUBotCurveSurplus(1, 'LOF_070', 6);
$check($dry !== null && $wet !== null && abs(($wet - $dry) - 2.0) < 1e-9,   // When Played: immediate, not discounted
    'Anakin: a Heroism card in my discard turns on one -3/-3 (3 damage = 2); got ' . json_encode([$dry, $wet]));

// ── Owner review 2026-10-06: rules A–D ────────────────────────────────────────────────────────────────────────────
$cov('SOR_078', 'Vanquish', 5.0 - 5.0, 'defeat a non-leader unit = the 5 anchor, for 5 — "pretty fair" (owner)');
$cov('JTL_040', 'Fleet Interdictor', 0.7 * (7.5 + 1.5) - 8.0, 'cost 7: lasting (6/6 space Sentinel 7.5 + When Defeated space kill ≤3-cost 3 at 50%) × 0.7, budget 8 — overvalued before (owner)');
$cov('TWI_247', 'AT-TE Vanguard', 0.6 * (7.35 + 1.0 + 2.0) - 9.0, 'cost 8: lasting (6/9 7.35 + Restore 3 1 + When Defeated 2 Clone Troopers 4 at 50%) × 0.6, budget 9 — "way overvalued" (owner)');
$cov('LOF_071', 'Grappling Guardian', 0.7 * 6.2 + 4.0 - 8.0, 'cost 7: 3/9 space 6.2 × 0.7 + When Played space kill ≤6 HP (5 damage = 5, +1 leaders, capped 5, −1 one arena = 4), budget 8');
$cov('SEC_121', 'Shadow Crawler', 0.7 * 6.45 + 1.0 - 8.0, 'cost 7: 6/7 body 6.45 × 0.7 + Ambush 1 NOT discounted (it acts the turn it lands), budget 8');
$cov('HMW_203', 'Victor Squadron', 0.8 * (5.0 + 0.5) + 1.5 - 7.0, 'cost 6: 5/5 space 5.5 × 0.8 + enters ready 1.5 NOT discounted, budget 7');
$cov('HMW_093', 'Coastal Catamarans', 0.7 * 8.0 - 8.0, 'a vanilla 7-cost 8/8: no board impact, 8 × 0.7 — the 5-cost conundrum (owner)');
$r = $static($idByTitle('Vigilance', 'Event'));
$check($near($r, 3.0 + 5 / 3 - 4.0, 0.01), 'Vigilance: best two modes = defeat ≤3 HP (2, +1 leaders = 3) + heal 5 (5/3), for 4; got ' . json_encode($r));
$cP = $static('ASH_052'); $pP = $static('ASH_053');
$check($cP !== null && $pP !== null && $cP['surplus'] > $pP['surplus'], 'Chimaera reads above Pre Vizsla (owner); got ' . json_encode([$cP['surplus'] ?? null, $pP['surplus'] ?? null]));
$r = $static('LOF_071');
$disc = $r === null ? [] : array_filter(array_keys($r['parts']), fn($k) => str_starts_with($k, 'conundrum'));
$check($r !== null && count($disc) === 1 && abs(array_sum($r['parts']) - $r['value']) < 1e-9, 'the conundrum discount is its own part, and the parts still sum to the value; got ' . json_encode($r));
// ── Owner category prices (2026-10-06): Plot +1, search-and-draw 1 / search-and-play 1 + discount, Disclose (the reveal
// −0.5, derived; cost AND effect only when the hand can reveal), cost reducer +2 (restricted 1, derived), Use the Force
// −1 / gain +1 (cost and effect only with the Force), Advantage 0.5, Piloting = unit mode + 0.5, auras × 2 units,
// bodyguard 2, Name a card 1.5.
$cov('SEC_111', 'Jar Jar Binks', 1.55 + 1.5 + 1.0 - 3.0, '2/1 (1.55) + When Played +2/+2 to another (1.5) + Plot (1), budget 3');
$cov('SEC_176', 'Sudden Ferocity', 1.65 + 1.0 - 3.0, '+3/+0 (1.65) + Plot (1), for 3');
$cov('SEC_172', 'Cinta Kaz', 0.8 * 5.0 + 0.5 + 1.0 - 7.0, 'cost 6: 5/5 × 0.8 + When Played attack (0.5) + Plot (1), budget 7');
$cov('LOF_100', 'Kelleran Beq', 0.7 * 7.0 + 1.0 + 3.0 - 8.0, 'cost 7: 7/7 × 0.7 + search-and-play (1) + 3 less (3), budget 8');
$cov('LOF_057', 'Owen Lars', 1.35 + 0.5 + 0.5 - 2.0, '0/3 (1.35) + Restore 2 (0.5) + When Defeated search-and-draw (1 at 50%), budget 2');
$cov('SEC_148', 'Karis Nemik', 2.55 + 0.25 - 3.0, '3/2 Hidden (2.8); When Defeated disclose: no hand with no board → neither cost nor effect, budget 3');
$cov('SEC_110', 'GNK Power Droid', 1.9 + 2.0 - 3.0, '1/3 (1.9) + On Attack next unit costs 1 less (2), budget 3');
$cov('JTL_032', 'Director Krennic', 2.0 + 1.0 + 1.0 - 3.0, '2/2 Shielded (3) + reducer restricted to When Defeated units (1, derived), budget 3');
$cov('ASH_248', 'Neel', 2.35 + 1.0 + 1.0 - 2.0, '1/4 (2.35) + "next unit with ≤1 power enters ready" When Played and On Attack (restricted 1 each), budget 2');
$cov('LAW_058', 'Honor-Bound Partisan', 2.0 + 0.5 + 1.0 - 3.0, '2/2 (2) + 1 base damage (0.5) + When Defeated reducer (2 at 50%), budget 3');
$cov('LOF_035', "Talzin's Assassin", 4.0 - 5.0, '4/4 (4); use the Force → -3/-3: no Force with no board → 0, budget 5');
$cov('LOF_129', 'Acolyte of the Beyond', 2.45 + 1.0 + 0.5 - 3.0, '2/3 (2.45) + On Attack gain the Force (1) + When Defeated gain the Force (1 at 50%), budget 3');
$cov('LOF_193', 'Youngling Padawan', 2.45 + 1.0 - 3.0, '2/3 (2.45) + When Played gain the Force (1), budget 3');
$cov('LOF_231', 'Darth Tyranus', 3.55 + 1.0 - 5.0, '4/3 Shielded (4.55); Ambush only while the Force is with you (unmet), budget 5');
$cov('LOF_031', 'Karis', 2.9 - 3.0, '2/4 (2.9); When Defeated use the Force → -2/-2 (no Force: 0), budget 3');
$cov('ASH_157', 'Danger Squadron Wingmen', 4.45 + 0.5 + 0.5 - 5.0, '4/5 space (4.95) + On Attack an Advantage token (0.5), budget 5');
$cov('ASH_191', "Shin Hati's Fiend Fighter", 2.1 + 0.5 + 0.5 - 3.0, '3/1 space (2.6) + When Defeated 2 Advantage (1 at 50%; the 3-instead clause 0, derived), budget 3');
$cov('ASH_146', 'Justifier', 0.9 * (4.95 + 0.75) + 0.75 - 6.0, 'cost 5: lasting (4/5 space 4.95 + On Attack ping 0.5 + Advantage-if-it-kills 0.25) × 0.9 + When Played copy 0.75, budget 6');
$cov('ASH_167', 'Flarestar Attack Shuttle', 2.05 + 0.5 + 0.25 - 3.0, '2/1 space (2.05) + When Played Advantage (0.5) + When Defeated Advantage (0.25), budget 3');
$cov('JTL_203', 'Han Solo', 0.9 * 4.45 + 1.0 + 0.5 - 6.0, 'cost 5: 4/5 × 0.9 + Ambush (1) + Piloting choice (0.5), budget 6');
$cov('JTL_103', 'Chewbacca', 0.9 * (5.45 + 2.0) + 0.5 - 6.0, 'cost 5: lasting (5/6 + immune to enemy defeat/bounce 2, derived as bodyguard) × 0.9 + Piloting (0.5), budget 6');
$cov('SEC_099', 'Naboo Royal Starship', 3.35 + 0.5 + 1.5 + 1.0 - 5.0, '2/5 space (3.85) + leader aura Raid 2 + Overwhelm (1.5 stats × 2 units = 1.5) + Plot (1), budget 5');
$cov('LOF_045', 'Yaddle', 2.9 + 0.25 + 0.5 - 3.0, '2/4 Restore 1 (3.15) + On Attack Restore 1 to other Jedi (0.5 stats × 2 units = 0.5), budget 3');
$cov('LOF_191', 'BD-1', 1.9 + 0.25 + 0.8 - 2.0, '1/3 Hidden (2.15) + chosen unit +1/+0 and Saboteur (1.6 stats = 0.8), budget 2');
$cov('SEC_101', 'Queen Amidala', 0.9 * (4.1 + 2.0) + 2.8 - 6.0, 'cost 5: lasting (5/3 + bodyguard 2) × 0.9 + When Played 2 Spy tokens (2.8), budget 6');
$cov('ASH_062', 'The Mandalorian', 4.55 + 1.0 + 2.0 - 5.0, '5/4 Shielded (5.55) + bodyguard (2), budget 5');
$cov('ASH_077', 'Ryder Azadi', 3.35 + 0.25 + 1.5 - 4.0, '2/5 Restore 1 (3.6) + Name a card (1.5), budget 4');
$cov('SEC_046', 'Galen Erso', 3.9 + 1.5 + 1.0 - 5.0, '3/5 (3.9) + Name a card (1.5) + Plot (1), budget 5');
$cov('SEC_186', 'Garindan', 1.9 + 1.5 + 1.0 - 3.0, '1/3 (1.9) + Name a card (1.5, includes its discard) + Plot (1), budget 3');
// On a board: the Force and Disclose conditions turn their cost AND effect on together.
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0); $b->WithCardInHandForPlayer(1, 'LOF_035'); });
$noF = SWUBotCurveSurplus(1, 'LOF_035', 6);
$build(function ($b) { $b->WithForceForPlayer(1); $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0); $b->WithCardInHandForPlayer(1, 'LOF_035'); });
$check(PlayerHasTheForce(1), 'premise: the Force is with seat 1');
$withF = SWUBotCurveSurplus(1, 'LOF_035', 6);
$check($noF !== null && $withF !== null && abs(($withF - $noF) - 1.0) < 1e-9, "Talzin's Assassin: with the Force, −1 + a -3/-3 (2) = +1; got " . json_encode([$noF, $withF]));
$cwt = $idByTitle('Charged with Treason', 'Event');
$build(function ($b) use ($cwt) { $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0); $b->WithCardInHandForPlayer(1, 'SOR_095'); $b->WithCardInHandForPlayer(1, $cwt); });
$r0 = SWUBotCurveValue(1, $cwt);
$check(CardAspect('SOR_172') === 'Aggression', 'premise: Open Fire is an Aggression card');
$build(function ($b) use ($cwt) { $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0); $b->WithCardInHandForPlayer(1, 'SOR_172'); $b->WithCardInHandForPlayer(1, 'SOR_172'); $b->WithCardInHandForPlayer(1, $cwt); });
$r1 = SWUBotCurveValue(1, $cwt);
$check($r0 !== null && $r1 !== null && abs($r0['value']) < 1e-9 && abs($r1['value'] - 3.5) < 1e-9,
    'Charged with Treason: no Aggression pair in hand = 0; with two Aggression cards = −0.5 reveal + 5 damage (4) = 3.5; got ' . json_encode([$r0['value'] ?? null, $r1['value'] ?? null]));
// ── One-off batch toward the 80% gate (2026-10-06). Every "(derived)" price shows in the review table. ────────────
$cov('SEC_215', "Emissary's Sheathipede", 2.9 + 0.5 - 0.5 - 3.0, '2/4 space (3.4) + When Defeated opponent readies a resource (−1, derived, at 50%), budget 3');
$cov('ASH_253', 'Yellow Aces Bomber', 2.9 + 0.5 + 0.5 - 4.0, '2/4 space Support (3.9); On Attack base damage only if upgraded (never at play), budget 4');
$cov('LAW_101', 'Lawbringer', 0.6 * (7.5 + 1.0) + 1.0 - 9.0, 'cost 8: lasting (7/7 space 7.5 + On Attack -2/-2 to one aspect, 1 unit derived = 1) × 0.6 + When Played copy 1, budget 9');
$cov('SEC_098', 'Captain Typho', 4.45 + 1.0 - 5.0, '4/5 Sentinel (5.45); when attacked: disclose → heal 1 (no hand, no board: 0), budget 5');
$cov('JTL_147', 'Black One', 2.45 + 0.5 - 3.0, '2/3 space (2.95); +1/+0 while upgraded and the Poe ping are conditional (unmet), budget 3');
$cov('LAW_174', '0-0-0', 4.0 - 4.0, '4/4 (4); On Attack needs an Aggression card in discard (unmet), budget 4');
$cov('ASH_056', 'Huyang', 2.9 - 3.0, '2/4 (2.9); On Attack -4/-0 needs an upgraded enemy (none with no board), budget 3');
$cov('ASH_133', 'Trask Walker', 0.6 * (6.8 + 1.0) + 1.0 - 9.0, 'cost 8: lasting (5/9 6.8 + On Attack recursion-or-heal 1, derived) × 0.6 + When Played copy 1, budget 9');
$cov('ASH_148', 'Ninth Sister', 0.7 * 7.8 + 0.5 + 2.0 - 8.0, 'cost 7: 8/7 Overwhelm 7.8 × 0.7 + their-choice discard (0.5, derived) + divided damage of its cost (static 3 → 2, derived), budget 8');
$cov('SEC_037', 'Cantwell Arrestor Cruiser', 0.7 * 6.95 - 8.0, 'cost 7: 6/7 space 6.95 × 0.7; disclose-gated exhaust + lock (no hand: 0), budget 8');
$cov('JTL_089', 'The Invisible Hand', 0.8 * (6.5 + 2.0) + 2.0 - 7.0, 'cost 6: lasting (6/6 space + after-attack search 1 + free cheap play 1, derived) × 0.8 + When Played copy 2, budget 7');
$cov('ASH_203', "Mando's N-1 Starfighter", 1.9 + 0.5 + 0.5 - 0.5 + 1.0 - 3.0, '1/3 space Support (2.9) + On Attack exhaust a friendly leader (−0.5, derived) for +2 (1), budget 3');
$cov('JTL_041', 'Annihilator', 0.5 * (12.5 + 3.5) + 7.0 - 12.0, 'cost 11: lasting (12/12 space + When Defeated copy 7 at 50%) × 0.5 + When Played defeat an enemy unit (6) + discard copies (1, derived), budget 12');
$cov('JTL_240', "Fett's Firespray", 4.0 + 0.5 + 0.5 + 0.5 - 5.0, '4/4 space (4.5) + 1 indirect When Played and On Attack (0.5 each); the Boba Fett upgrade is unmet, budget 5');
$cov('SEC_183', 'Topple the Summit', 2.0 + 1.0 - 5.0, '3 to each damaged unit (static: one unit, 2, derived) + Plot (1), for 5');
$cov('ASH_031', 'Hera Syndulla', 3.45 + 0.5 - 4.0, '3/4 (3.45) + after a base hit, heal that much (half of 3/3 = 0.5, derived), budget 4');
$cov('LAW_129', 'Mastery', 3.0 + 0.5 - 4.0, '+3/+3 (3) + 1 less on a unique host (0.5, derived), for 4');
$cov('SEC_082', 'Chancellor Palpatine', 2.0 + 1.0 - 4.0, '2/2 (2) + Plot (1); tokens need a deployed leader (unmet), budget 4');
$cov('JTL_081', 'First Order TIE Fighter', 1.55 + 0.5 - 2.0, '2/1 space (2.05); Raid 1 only with a token unit (unmet), budget 2');
$cov('LAW_078', 'Sabine Wren', 3.0 + 1.0 + 1.0 - 4.0, '3/3 Ambush (4) + defeat a non-unique upgrade (1); the any-upgrade upgrade is 0 (derived), budget 4');
$cov('ASH_040', 'Poe Dameron', 3.0 - 3.0, '3/3 (3) + "all units lose Sentinel" (0, derived: symmetric), budget 3');
$cov('LOF_261', 'Constructed Lightsaber', 2.45 + 0.25 + 0.25 + 0.5 - 3.0, '+2/+3 (2.45) + each host-dependent grant at 50% (Restore 2 0.25, Raid 2 0.25, Sentinel 0.5; derived), for 3');
$cov('ASH_114', "Sabine's Lightsaber", 2.0 + 0.25 - 2.0, '+2/+2 (2) + Restore 2 on a Force/Sabine host at 50% (0.25, derived), for 2');
$cov('SEC_069', 'Nimble Prowess', 1.0 + 1.0 - 1.0, '+1/+1 (1) + exhaust a unit in its arena (1), for 1');
$cov('LOF_132', 'Grand Inquisitor', 3.45 + 0.5 + 0.5 - 4.0, '3/4 Hidden Raid 1 (3.95) + other Inquisitors gain Hidden (aura 0.5 stats × 2 = 0.5), budget 4');
$cov('LOF_160', 'Merrin', 3.35 - 1.0 + 1.0 - 4.0, '2/5 (3.35) + On Attack discard a card (−1, derived) for 2 damage (1), budget 4');
$cov('LOF_046', 'Ezra Bridger', 3.9 + 1.0 - 4.0, '3/5 (3.9) + On Attack Experience to a creature or spectre (1), budget 4');
$cov('HMW_151', 'Overgrowth', 3.0 - 5.0, 'the Kashyyyk-gated hit is unmet with no board; "resource this card" ramp (3) — the owner\'s Tarfful line, for 5');
$cov('ASH_099', 'Gozanti Assault Carrier', 0.9 * (4.9 + 0.5 + 0.5) + 0.5 - 6.0, 'cost 5: lasting (4/6 body 4.9 + space 0.5 + On Attack Sentinel for the phase 0.5, derived) × 0.9 + Support 0.5, budget 6');
$cov('JTL_131', 'Turbolaser Salvo', 6.0 - 7.0, 'a space unit\'s power to each enemy in an arena (static: power 4 × 2 units = 6, derived), for 7');
$cov('JTL_175', 'System Shock', 1.0 + 0.5 - 1.0, 'defeat an upgrade (1, derived) + 1 damage to that unit (0.5), for 1');
$cov('ASH_208', 'Sabine Wren', 0.9 * (4.45 + 1.0 + 0.5) - 6.0, 'cost 5: lasting (4/5 Shielded 5.45 + exhaust a ground unit when an upgrade attaches, 1 at 50% derived) × 0.9, budget 6');
$cov('LAW_149', 'Rey', 0.6 * (9.0 + 2.0 + 0.5) - 9.0, 'cost 8: lasting (9/9 + immune to enemy defeat 2 + immune to steal 0.5, derived) × 0.6, budget 9');
// The card being played is not part of its own reveal: ONE Aggression card + Charged with Treason (itself Aggression)
// cannot disclose Aggression×2.
$build(function ($b) use ($cwt) { $b->WithGroundUnitForPlayer(2, 'SOR_046', true, 0); $b->WithCardInHandForPlayer(1, 'SOR_172'); $b->WithCardInHandForPlayer(1, $cwt); });
$r2 = SWUBotCurveValue(1, $cwt);
$check($r2 !== null && abs($r2['value']) < 1e-9, 'Charged with Treason does not count its own Aggression icon toward the reveal; got ' . json_encode($r2['value'] ?? null));
// A constant read through the sentence fallback is LASTING (rule B discounts it on a 5+ cost unit). Synthetic text:
// no real card in the test set needs the default.
$fb = _SWUBotCurveConstants('deal 1 damage to a base.', '');
$check(is_array($fb) && count($fb) === 1 && ($fb[0][2] ?? '') === 'last', 'a fallback-parsed constant is lasting; got ' . json_encode($fb === null ? null : array_map(fn($t) => [$t[0], $t[2] ?? null], $fb)));
// ROUTE (owner, 2026-10-06, fix 1): a route that WAIVES the aspect penalty (Daimyo's Palace Epic, the LAW_020 waiver
// prompt) is charged the printed cost; a FREE play (Ackbar's search "play each of them for free") has a budget of 0,
// so the surplus is the card's full value. 'paid' (the default) is unchanged.
$board('default', $vig);
$paid = SWUBotCurveValue(1, $vig, true, 8); $waived = SWUBotCurveValue(1, $vig, true, 8, 'waived'); $free = SWUBotCurveValue(1, $vig, true, 8, 'free');
$check($paid !== null && $waived !== null && abs($waived['budget'] - intval(CardCost($vig))) < 1e-9 && $waived['budget'] < $paid['budget'],
    "route 'waived': budget = printed cost, without the penalty; got " . json_encode([$paid['budget'] ?? null, $waived['budget'] ?? null]));
$check($free !== null && abs($free['budget']) < 1e-9 && abs($free['surplus'] - $free['value']) < 1e-9, "route 'free': budget 0, surplus = value; got " . json_encode($free));
// Review #1 (2026-10-06): a condition the pricer cannot read must make the card UNPRICED (null), never silently false;
// aspects, arenas and "damaged" are read; an "X or Y unit" is two aspects; a bare name is a title only if one exists.
foreach (['you control a unit with 4 or more power', 'you control a tusken unit or a tatooine base', 'you control that unit'] as $cond) {
    $check(_SWUBotCurveCondition(null, $cond) === null, "unreadable condition → null: '$cond'");
}
$check(_SWUBotCurveCondition(null, 'you control boba fett') === false, 'a real title stays readable (false with no board)');
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_095', true, 2); });   // Battlefield Marine: Command/Heroism, ground, 2 damage
$check(CardAspect('SOR_095') === 'Command,Heroism', 'premise: Battlefield Marine is Command/Heroism');
foreach (['you control a vigilance or command unit' => true, 'you control a command unit' => true, 'you control a ground unit' => true,
          'you control a space unit' => false, 'you control a damaged unit' => true, 'you control an aggression unit' => false] as $cond => $want) {
    $check(_SWUBotCurveCondition(1, $cond) === $want, "board condition '$cond' → " . json_encode($want) . '; got ' . json_encode(_SWUBotCurveCondition(1, $cond)));
}
// A unit's worth AS A REMOVAL TARGET is undiscounted: the conundrum is its owner's risk, not a smaller threat.
$build(function ($b) { $b->WithGroundUnitForPlayer(2, 'HMW_093', true, 0); });
$cat = SWUBotUnits(2)[0] ?? null;
$check($cat !== null && abs(_SWUBotCurveWorth($cat) - 8.0) < 1e-9, 'Coastal Catamarans is worth its full 8 to the player removing it; got ' . json_encode($cat === null ? null : _SWUBotCurveWorth($cat)));

bot_test_finish();
