<?php
// Twin Suns "Fill Seat with Bot", step 3 — the bot's JUDGEMENT at 3-4 seats (SWUSim/docs/todo-twinsuns-fill-bot.md,
// "Bot policy" + Deep research A/B/C). Every choice goes through the PRODUCTION chooser (SWUBotHeuristicChoose, the
// one the live controller calls) on a prompt raised by the real enumerator. Each rule has its near-miss on the board.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/twinsuns_bot_judgement_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
require_once './SWUSim/BotController.php';


// A Twin Suns board. $hp = [seat => remaining base HP]; $units = [seat => [[cardID, ready], ...]]; $seats = 3 or 4.
$ts = function (array $hp, array $units, int $seats = 3, bool $teams = false, string $live = '', array $hands = []) use ($build) {
    $build(function ($b) use ($hp, $units, $seats, $teams, $live, $hands) {
        foreach ($hands as $s => $n) for ($i = 0; $i < $n; $i++) $b->WithCardInHandForPlayer($s, 'SOR_095');
        for ($s = 3; $s <= $seats; $s++) CommonSetupFarSeat($b, $s, 'bbk');
        $order = implode('', range(1, $seats));
        $b->WithSeatOrder($order)->WithLiveSeats($live !== '' ? $live : $order);
        if ($teams) $b->WithGlobalEffectForPlayer(1, 'SWU_MODE_TEAMS');
        foreach ($units as $s => $list) foreach ($list as $i => $u) {
            $b->WithGroundUnitForPlayer($s, $u[0], $u[1]);
            if (!empty($u[2])) $b->WithUpgradesOnGroundUnitForPlayer($s, $i, array_map(fn($c) => GameStateBuilder::Upgrade($c, $s), $u[2]));   // ['SOR_T02'] = a Shield
        }
        $b->WithActivePlayer(1);
    });
    // Remaining base HP, set on the bases the fixture already placed (WithBaseForPlayer would ADD a second base at
    // seats 1-2, which CommonSetup already dressed).
    foreach ($hp as $s => $remaining) { $b = &GetBase($s); $b[0]->Damage = max(0, intval(CardHp($b[0]->CardID)) - $remaining); unset($b); }
};
// What the production chooser picks for $seat right now.
$choose = function (int $seat = 1, string $style = 'normal') {
    global $gameName;
    $legal = SWUBotLegalActions($gameName, $seat);
    $a = SWUBotHeuristicChoose($style, (array)($legal['actions'] ?? []), $legal);
    return strval($a['cardID'] ?? '');
};
$targets = function (int $seat = 1) {
    global $gameName;
    return implode(' ', array_map(fn($a) => strval($a['cardID'] ?? ''), (array)(SWUBotLegalActions($gameName, $seat)['actions'] ?? [])));
};

// ── "the opponent" ──────────────────────────────────────────────────────────────────────────────────────────
$ts([1 => 30, 2 => 20, 3 => 25], []);
$check(SWUBotOpponent(1) === 3, 'FFA: "the opponent" is the live enemy with the most base HP (P3 25 > P2 20); got ' . SWUBotOpponent(1));
$check(SWUBotOpponent(3) === 2 || SWUBotOpponent(3) === 1, 'a seat-3 bot never reads itself as its opponent; got ' . SWUBotOpponent(3));
$ts([1 => 30, 2 => 20, 3 => 25], [], 3, false, '13');
$check(SWUBotOpponent(1) === 3 && SWUBotOpponents(1) === [3], 'an ELIMINATED seat is never an opponent; got ' . json_encode(SWUBotOpponents(1)));
$ts([1 => 30, 2 => 20, 3 => 25, 4 => 28], [], 4, true);
$check(SWUBotOpponents(3) === [2, 4] && in_array(SWUBotOpponent(3), [2, 4], true), 'Team Suns: seat 3\'s opponents are 2 and 4, never its teammate 1; got ' . json_encode(SWUBotOpponents(3)));
// 2-seat: unchanged — the other seat.
$build(function ($b) { $b->WithActivePlayer(1); });
$check(SWUBotOpponent(1) === 2 && SWUBotOpponent(2) === 1, '2 seats: the opponent is the other seat, as before');

// ── enemy tests read the OWNER, not a "their" prefix ─────────────────────────────────────────────────────────
$ts([], [3 => [['SOR_095', true]]]);
$check(SWUBotIsEnemyMz(1, 'p3GroundArena-0') && !SWUBotIsEnemyMz(1, 'myGroundArena-0') && SWUBotIsEnemyMz(1, 'theirBase-0'),
    'p{n} zones of an enemy seat are enemy; my zones are not');
$ts([], [3 => [['SOR_095', true]]], 4, true);
$check(!SWUBotIsEnemyMz(1, 'p3GroundArena-0') && SWUBotIsEnemyMz(1, 'p2GroundArena-0'), 'Team Suns: a teammate\'s p{n} zone is NOT an enemy');

// A hostile "deal 2 damage to a unit" offering my unit and an enemy (p3) unit: the enemy's. Before, every p{n}
// candidate missed the ^(my|their) on-board test and the first legal option — my own unit — was taken.
$ts([], [1 => [['SOR_095', true]], 3 => [['SOR_095', true]]]);
DecisionQueueController::AddDecision(1, 'MZCHOOSE', 'myGroundArena-0&p3GroundArena-0', 1, tooltip: 'Deal_2_damage_to_a_unit');
DecisionQueueController::AddDecision(1, 'CUSTOM', 'DEAL_UNIT_DAMAGE|2', 1);
$pick = $choose();
$check($pick === 'p3GroundArena-0', "hostile damage goes on the ENEMY (p3) unit, not mine; got $pick");
// Discriminating twin: both candidates are enemies (p3 Consular 3/7 listed FIRST, p2 Death Star Stormtrooper 3/1).
// 2 damage KILLS the Stormtrooper and only chips the Consular. Before the owner-seat test, no p{n} candidate was
// "on board", both scored the same flat 'take it' value, and the first — the chip — was taken.
$ts([], [2 => [['SOR_128', true]], 3 => [['SOR_046', true]]]);
DecisionQueueController::AddDecision(1, 'MZCHOOSE', 'p3GroundArena-0&p2GroundArena-0', 1, tooltip: 'Deal_2_damage_to_a_unit');
DecisionQueueController::AddDecision(1, 'CUSTOM', 'DEAL_UNIT_DAMAGE|2', 1);
$pick = $choose();
$check($pick === 'p2GroundArena-0', "between two enemies, the damage goes where it KILLS (p2 Stormtrooper), not the first listed; got $pick");

// ── attack target: the healthiest enemy base ────────────────────────────────────────────────────────────────
$ts([1 => 30, 2 => 20, 3 => 25], [1 => [['SOR_095', true]]]);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check($pick === 'p3Base-0', "attacks the healthiest enemy base (P3 25 > P2 20); got $pick [" . $targets() . ']');
$ts([1 => 30, 2 => 25, 3 => 20], [1 => [['SOR_095', true]]]);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check($pick === 'p2Base-0', "near-miss: swap the HP and it attacks P2; got $pick");

// ── take the kill only when it wins (FFA): my HP + 5 >= every OTHER live seat ───────────────────────────────
// Battlefield Marine (3 power) can finish P2 at 2.
$ts([1 => 25, 2 => 2, 3 => 20], [1 => [['SOR_095', true]]]);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check($pick === 'p2Base-0', "takes the kill when it wins (me 25+5 >= P3 20); got $pick");
$ts([1 => 10, 2 => 2, 3 => 25], [1 => [['SOR_095', true]]]);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check($pick === 'p3Base-0', "SKIPS a kill that would hand P3 the game (me 10+5 < P3 25); got $pick");

// The other base is unreachable: P3's Cell Block Guard (Sentinel) guards it. A 2-seat style would take the open
// base — here that is the LOSING kill on P2 — so the filter must drop it and the Sentinel is attacked instead.
$ts([1 => 10, 2 => 2, 3 => 25], [1 => [['SOR_095', true]], 3 => [['SOR_229', true]]]);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check($pick === 'p3GroundArena-0', "with P3's base guarded, it attacks the Sentinel rather than make the losing kill; got $pick [" . $targets() . ']');
// The same board for an AGGRO bot, whose weights prefer any base: its style keeps no unit target at all, so the filter's
// "never empty" fallback is what decides — and it must still leave the losing kill out.
$ts([1 => 10, 2 => 2, 3 => 25], [1 => [['SOR_095', true]], 3 => [['SOR_229', true]]]);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose(1, 'hyperaggro');
$check($pick === 'p3GroundArena-0', "an aggro bot also refuses the losing kill when the only other target is a unit; got $pick");

// ── Team Suns: an enemy kill is always taken (no HP scoring; the game ends on a team wipe) ───────────────────
$ts([1 => 5, 2 => 2, 3 => 30, 4 => 30], [1 => [['SOR_095', true]]], 4, true);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check($pick === 'p2Base-0', "Team Suns: takes the enemy kill even when behind on HP; got $pick");

// ── a weak attacker pops a Shield; the strong one goes to the base ──────────────────────────────────────────
// P3's Marine carries a Shield. Underworld Thug (2 power) is my weakest ready unit; Mace Windu (5) is ready too.
$shieldBoard = function () use ($ts) {
    $ts([1 => 30, 2 => 30, 3 => 25], [1 => [['SOR_247', true], ['SOR_149', true]], 3 => [['SOR_095', true, ['SOR_T02']]]]);
};
$shieldBoard();
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check($pick === 'p3GroundArena-0', "the WEAKEST ready attacker breaks the Shield; got $pick [" . $targets() . ']');
$shieldBoard();
$raiseAttack(1, 'myGroundArena-1');
$pick = $choose();
$check(str_contains($pick, 'Base-'), "near-miss: the STRONGEST attacker goes to a base; got $pick");

// ── free play: commit to a winning kill; never to a dead seat ─────────────────────────────────────────────
// Rule 2 (lethal-now) is what runs at free play. Its 2-seat read used "the opponent" — and a DEAD seat's base reads 0
// HP, so SWUBotLethalNow() stayed true for the rest of the game (research B).
$lethalRule = function () {
    global $gameName;
    $l = SWUBotLegalActions($gameName, 1);
    $ctx = ['style' => 'normal', 'seat' => 1, 'opp' => SWUBotOpponent(1), 'kind' => strval($l['kind'] ?? ''), 'type' => '',
            'param' => '', 'tooltip' => '', 'following' => [], 'actions' => (array)($l['actions'] ?? [])];
    $a = SWUBotRuleLethalNow($ctx);
    return $a === null ? 'null' : SWUBotActionKind($a);
};
$ts([1 => 25, 2 => 2, 3 => 20], [1 => [['SOR_095', true]]]);
$check($lethalRule() === 'attack', 'free play: a winning kill on P2 commits to an attack; got ' . $lethalRule());
$ts([1 => 10, 2 => 2, 3 => 25], [1 => [['SOR_095', true]]]);
$check($lethalRule() === 'null', 'free play: a kill that hands P3 the game is NOT committed to; got ' . $lethalRule());
$ts([1 => 30, 2 => 30, 3 => 30], [1 => [['SOR_095', true]]], 3, false, '13');
$check($lethalRule() === 'null', 'free play: an ELIMINATED seat is never "lethal now" (its base reads 0); got ' . $lethalRule());

// Two equally weak attackers: neither is "the weakest", so the attacker goes to a base, not the Shield.
$ts([1 => 30, 2 => 30, 3 => 25], [1 => [['SOR_247', true], ['SOR_247', true]], 3 => [['SOR_095', true, ['SOR_T02']]]]);
$raiseAttack(1, 'myGroundArena-0');
$pick = $choose();
$check(str_contains($pick, 'Base-'), "near-miss: with an EQUAL-power partner the attacker is not 'the weak one' and goes to a base; got $pick");

// ── "choose a player" ───────────────────────────────────────────────────────────────────────────────────────
$ts([1 => 30, 2 => 20, 3 => 25], []);
DecisionQueueController::AddDecision(1, 'OPTIONCHOOSE', SWUPlayerPickerLabels(1), 1, tooltip: 'Choose_a_player_to_discard_a_card');
$pick = $choose();
$check($pick === 'P3', "a HARMFUL pick goes to the healthiest opponent (P3), never \"You\"; got $pick");
$ts([1 => 30, 2 => 20, 3 => 25], []);
DecisionQueueController::AddDecision(1, 'OPTIONCHOOSE', SWUPlayerPickerLabels(1), 1, tooltip: 'Which_player_draws_2_cards?');
$pick = $choose();
$check($pick === 'You', "a BENEFICIAL pick is \"You\"; got $pick");

// ── never pay with my last HP ───────────────────────────────────────────────────────────────────────────────
// TWI_146 Steela Gerrera: "You may deal 2 damage to your base. If you do, search…" — at 1 HP that eliminates me.
$ts([1 => 2, 2 => 30, 3 => 30], []);
DecisionQueueController::AddDecision(1, 'YESNO', '-', 1, tooltip: 'Deal_2_to_your_base_to_search_for_a_Tactic_card?');
$pick = $choose();
$check($pick === 'NO', "declines \"deal 2 to your base\" at 2 HP (it would eliminate me); got $pick");
$ts([1 => 3, 2 => 30, 3 => 30], []);
DecisionQueueController::AddDecision(1, 'YESNO', '-', 1, tooltip: 'Deal_2_to_your_base_to_search_for_a_Tactic_card?');
$pick = $choose();
$check($pick === 'YES', "near-miss: at 3 HP it survives the 2 and takes the search; got $pick");

// ══ Targeting rules (owner, 2026-10-01 — refinement round) ═══════════════════════════════════════════════════
// $ask: raise a prompt for seat 1 and return the production chooser's pick. $next = the continuation behind it.
$ask = function (string $type, string $param, string $tooltip, string $next = '') use ($choose) {
    DecisionQueueController::AddDecision(1, $type, $param, 1, tooltip: $tooltip);
    if ($next !== '') DecisionQueueController::AddDecision(1, 'CUSTOM', $next, 1);
    return $choose();
};
$tsHands = function (array $hp, array $hands, array $units = []) use ($ts) { $ts($hp, $units, 3, false, '', $hands); };

// 1. Discard from hand → the opponent with the MOST cards in hand (P2 is weaker on HP, so the old "healthiest" pick was P3).
$tsHands([1 => 30, 2 => 20, 3 => 25], [2 => 5, 3 => 2]);
$check($ask('OPTIONCHOOSE', SWUPlayerPickerLabels(1), 'Choose_a_player_to_discard_a_card') === 'P2', 'R1 discard: the opponent with the most cards in hand (P2: 5 > 2)');
$tsHands([1 => 30, 2 => 20, 3 => 25], [2 => 1, 3 => 4]);
$check($ask('OPTIONCHOOSE', SWUPlayerPickerLabels(1), 'Choose_a_player_to_discard_a_card') === 'P3', 'R1 near-miss: swap the hands → P3');

// 2. Pings to a base → the healthiest opponent; a ping that DEFEATS a base follows the kill rule.
$ts([1 => 30, 2 => 20, 3 => 25], []);
$check(($p = $ask('MZCHOOSE', 'myBase-0&p2Base-0&p3Base-0', 'Deal_2_damage_to_a_base', 'DEAL_BASE_DAMAGE|2')) === 'p3Base-0', "R2 ping: the healthiest opponent's base (P3); got $p");
$ts([1 => 10, 2 => 2, 3 => 25], []);
$check(($p = $ask('MZCHOOSE', 'myBase-0&p2Base-0&p3Base-0', 'Deal_2_damage_to_a_base', 'DEAL_BASE_DAMAGE|2')) === 'p3Base-0', "R2 kill rule: no losing kill on P2 (me 10+5 < P3 25); got $p");
$ts([1 => 25, 2 => 2, 3 => 20], []);
$check(($p = $ask('MZCHOOSE', 'myBase-0&p2Base-0&p3Base-0', 'Deal_2_damage_to_a_base', 'DEAL_BASE_DAMAGE|2')) === 'p2Base-0', "R2 kill rule: takes the WINNING kill on P2; got $p");
$ts([1 => 30, 2 => 20, 3 => 25], []);
$check(($p = $ask('OPTIONCHOOSE', 'P2&P3', "Deal_2_to_which_opponent's_base?")) === 'P3', "R2 player-pick form: the healthiest (P3); got $p");
$ts([1 => 10, 2 => 2, 3 => 25], []);
$check(($p = $ask('OPTIONCHOOSE', 'P2&P3', "Deal_2_to_which_opponent's_base?")) === 'P3', "R2 player-pick form: no losing kill; got $p");
$ts([1 => 25, 2 => 2, 3 => 20], []);
$check(($p = $ask('OPTIONCHOOSE', 'P2&P3', "Deal_2_to_which_opponent's_base?")) === 'P2', "R2 player-pick form: takes the WINNING kill (P2, not the healthier P3); got $p");

// 3. Exhaust → the highest-power READY enemy unit. Mace (5) is exhausted, so the Marine (3) over the Thug (2).
$ts([], [2 => [['SOR_095', true]], 3 => [['SOR_149', false], ['SOR_247', true]]]);
$check(($p = $ask('MZCHOOSE', 'p3GroundArena-0&p3GroundArena-1&p2GroundArena-0', 'Exhaust_a_unit', 'EXHAUST_UNIT')) === 'p2GroundArena-0', "R3 exhaust: highest-power READY enemy (Marine; Mace is exhausted); got $p");
$ts([], [2 => [['SOR_095', true]], 3 => [['SOR_149', true], ['SOR_247', true]]]);
$check(($p = $ask('MZCHOOSE', 'p2GroundArena-0&p3GroundArena-1&p3GroundArena-0', 'Exhaust_a_unit', 'EXHAUST_UNIT')) === 'p3GroundArena-0', "R3 near-miss: Mace ready → Mace; got $p");
// A card's OWN continuation (not EXHAUST_UNIT, so rule 14 does not filter it first): still the highest-power READY enemy.
$ts([], [2 => [['SOR_095', true]], 3 => [['SOR_149', false]]]);
$check(($p = $ask('MZCHOOSE', 'p3GroundArena-0&p2GroundArena-0', 'Exhaust_an_enemy_unit', 'SOR_999#0')) === 'p2GroundArena-0', "R3 (card continuation): skips the exhausted Mace for the ready Marine; got $p");

// 4. Bounce → the highest-HP enemy Sentinel first, else the highest-power enemy unit.
$ts([], [2 => [['SOR_229', true]], 3 => [['SOR_149', true]]]);
$check(($p = $ask('MZCHOOSE', 'p3GroundArena-0&p2GroundArena-0', "Return_a_unit_to_its_owner's_hand", 'BOUNCE_UNIT')) === 'p2GroundArena-0', "R4 bounce: the enemy SENTINEL (Cell Block Guard) before the bigger Mace; got $p");
$ts([], [2 => [['SOR_095', true]], 3 => [['SOR_149', true]]]);
$check(($p = $ask('MZCHOOSE', 'p2GroundArena-0&p3GroundArena-0', "Return_a_unit_to_its_owner's_hand", 'BOUNCE_UNIT')) === 'p3GroundArena-0', "R4 near-miss: no Sentinel → highest power (Mace); got $p");

// 5. Capture / take control → the most VALUABLE enemy unit (listed second).
$ts([], [2 => [['SOR_247', true]], 3 => [['SOR_149', true]]]);
$check(($p = $ask('MZCHOOSE', 'p2GroundArena-0&p3GroundArena-0', 'Choose_an_enemy_unit_to_capture')) === 'p3GroundArena-0', "R5 capture: the most valuable enemy (Mace); got $p");
$ts([], [2 => [['SOR_247', true]], 3 => [['SOR_149', true]]]);
$check(($p = $ask('MZCHOOSE', 'p2GroundArena-0&p3GroundArena-0', 'Take_control_of_an_enemy_unit')) === 'p3GroundArena-0', "R5 take control: the most valuable enemy (Mace); got $p");

// 6. "Defeat a unit" → the highest threat (Mace), not the first listed (Thug).
$ts([], [2 => [['SOR_247', true]], 3 => [['SOR_149', true]]]);
$check(($p = $ask('MZCHOOSE', 'p2GroundArena-0&p3GroundArena-0', 'Defeat_a_unit', 'DEFEAT_UNIT')) === 'p3GroundArena-0', "R6 defeat: the highest threat (Mace); got $p");

// 7. Heal → my base when it is the LOWEST at the table; otherwise my most-damaged unit.
$ts([1 => 12, 2 => 20, 3 => 25], [1 => [['SOR_149', true]]]);
$u = &GetGroundArena(1); $u[0]->Damage = 3; unset($u);
$check(($p = $ask('MZCHOOSE', 'myGroundArena-0&myBase-0', 'Heal_3_damage_from_a_unit_or_base', 'HEAL_TARGET|3')) === 'myBase-0', "R7 heal: my base, the lowest at the table; got $p");
$ts([1 => 22, 2 => 20, 3 => 25], [1 => [['SOR_149', true]]]);
$u = &GetGroundArena(1); $u[0]->Damage = 3; unset($u);
$check(($p = $ask('MZCHOOSE', 'myBase-0&myGroundArena-0', 'Heal_3_damage_from_a_unit_or_base', 'HEAL_TARGET|3')) === 'myGroundArena-0', "R7 near-miss: my base is not the lowest → my damaged unit; got $p");
$ts([1 => 22, 2 => 20, 3 => 25], [1 => [['SOR_149', true], ['SOR_046', true]]]);
$u = &GetGroundArena(1); $u[0]->Damage = 1; $u[1]->Damage = 4; unset($u);
$check(($p = $ask('MZCHOOSE', 'myGroundArena-0&myGroundArena-1', 'Heal_3_damage_from_a_unit', 'HEAL_TARGET|3')) === 'myGroundArena-1', "R7 unit heal: the MOST damaged of my units (4 > 1, listed second); got $p");

// 8. Shields / Experience / buffs on my units → my strongest READY attacker.
$ts([], [1 => [['SOR_247', true], ['SOR_149', true]]]);
$check(($p = $ask('MZCHOOSE', 'myGroundArena-0&myGroundArena-1', 'Give_a_Shield_token_to_a_unit', 'GIVE_SHIELD')) === 'myGroundArena-1', "R8 Shield: my strongest ready attacker (Mace); got $p");
$ts([], [1 => [['SOR_247', true], ['SOR_149', false]]]);
$check(($p = $ask('MZCHOOSE', 'myGroundArena-1&myGroundArena-0', 'Give_an_Experience_token_to_a_unit', 'GIVE_EXPERIENCE')) === 'myGroundArena-0', "R8 near-miss: Mace exhausted → the ready Thug; got $p");

// 9. A beneficial pick that must name an enemy (TS26 Count Dooku's second pick) → the WEAKEST enemy (fewest units).
$ts([1 => 30, 2 => 25, 3 => 20], [2 => [['SOR_095', true], ['SOR_095', true]]]);
$check(($p = $ask('OPTIONCHOOSE', 'P2&P3', 'Second_player_to_heal_and_create_a_droid?')) === 'P3', "R9 Dooku: the weakest enemy (P3, no units); got $p");
$ts([1 => 30, 2 => 25, 3 => 20], [3 => [['SOR_095', true], ['SOR_095', true]]]);
$check(($p = $ask('OPTIONCHOOSE', 'P2&P3', 'Second_player_to_heal_and_create_a_droid?')) === 'P2', "R9 near-miss: units moved to P3 → P2; got $p");
$ts([1 => 30, 2 => 25, 3 => 20], []);
$check(($p = $ask('OPTIONCHOOSE', 'P1&P2&P3', 'First_player_to_heal_and_create_a_droid?')) === 'P1', "R9 Dooku's first pick is me (P1); got $p");

// 10/11. Mill and look-at-hand → a pseudo-random OPPONENT (never me), the same answer for the same prompt.
$ts([1 => 30, 2 => 25, 3 => 20], []);
$m1 = $ask('OPTIONCHOOSE', SWUPlayerPickerLabels(1), 'Choose_a_player_to_discard_the_top_2_cards_of_their_deck');
$ts([1 => 30, 2 => 25, 3 => 20], []);
$m2 = $ask('OPTIONCHOOSE', SWUPlayerPickerLabels(1), 'Choose_a_player_to_discard_the_top_2_cards_of_their_deck');
$check(in_array($m1, ['P2', 'P3'], true) && $m1 === $m2, "R10 mill: an opponent, reproducibly; got $m1 / $m2");
$ts([1 => 30, 2 => 25, 3 => 20], []);
$l1 = $ask('OPTIONCHOOSE', SWUPlayerPickerLabels(1), "Look_at_a_player's_hand");
$check(in_array($l1, ['P2', 'P3'], true), "R11 look at a hand: an opponent; got $l1");
// The pseudo-random pick must actually vary with the prompt (else it is just "the first opponent").
$seen = [];
foreach (['Discard_top_card_of_a_deck', 'Mill_2_from_a_deck', 'Discard_the_top_3_of_a_deck', 'Discard_the_top_card_of_a_deck_(round)', 'Choose_a_deck_to_mill'] as $tipX) {
    $ts([1 => 30, 2 => 25, 3 => 20], []);
    $seen[$ask('OPTIONCHOOSE', SWUPlayerPickerLabels(1), $tipX)] = true;
}
$check(isset($seen['P2']) && isset($seen['P3']), 'R10 the random pick spreads over both opponents across prompts; got ' . json_encode(array_keys($seen)));

bot_test_finish();
