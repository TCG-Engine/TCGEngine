<?php
/**
 * Phase 3b (Wind): attack cards. Turn 3, play the attack from the first seeded slot (7), pay its cost, target the opposing champion.
 */

// ---- Rallied Advance ----
// Player 2 attacks on turn 2 with no recollection phase before it (their seeded rested ally stays rested), which is why the wake-up
// attacks are written from their seat.

foreach ([
    ['rallied-advance-class-bonus-on-attack-wakes-an-ally', true, '2', '[Class Bonus] On Attack: the rested ally woke up', ['combat', 'on-attack', 'class-bonus', 'status', 'targeting']],
    ['rallied-advance-without-the-class-bonus-leaves-the-ally-rested', false, '1', 'No class bonus: the ally stays rested', ['on-attack', 'class-bonus', 'condition']],
] as [$slug, $classBonus, $status, $label, $mechanics]) {
    $s = gaScenario($slug)
        ->deckTemplate('quiet', 'Rallied Advance')
        ->readyFor('Rallied Advance', classBonus: $classBonus, player: 2)
        ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => 1])
        ->seed('myHand', 'Rallied Advance', player: 2)
        ->endTurn()
        ->play('Rallied Advance', 2)->pay(2, 2)->target('theirField-0', 2);
    if ($classBonus) $s->answer(2, 'myField-1');
    $s->expectProperty('p2Field-1', 'Status', $status, $label);
    if (!$classBonus) $s->expectDamage('p1Field-0', 1, 'The attack dealt its 1 POWER to the champion');
    $s->tested('Rallied Advance')
        ->covers($mechanics, '[Class Bonus] **On Attack:** Wake up target ally you control.')
        ->register();
}

// ---- Crosswind Cuts ----

gaScenario('crosswind-cuts-class-bonus-costs-two-less-and-deals-its-power')
    ->deckTemplate('quiet', 'Crosswind Cuts')
    ->readyFor('Crosswind Cuts', classBonus: true, player: 2)
    ->seed('myHand', 'Crosswind Cuts', player: 2)
    ->endTurn()
    ->play('Crosswind Cuts', 2)->pay(3, 2)->target('theirField-0', 2)
    ->expectDamage('p1Field-0', 2, 'The attack dealt its 2 POWER')
    ->expectZoneCount('myMemory', 3, 2, 'Class Bonus: cost 5 - 2 = 3')
    ->tested('Crosswind Cuts')
    ->covers(['combat', 'class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 2 less to activate.')
    ->register();

gaScenario('crosswind-cuts-without-the-class-bonus-pays-the-full-five')
    ->deckTemplate('quiet', 'Crosswind Cuts')
    ->readyFor('Crosswind Cuts', player: 2)
    ->seed('myHand', 'Crosswind Cuts', player: 2)
    ->endTurn()
    ->play('Crosswind Cuts', 2)->pay(5, 2)->target('theirField-0', 2)
    ->expectDamage('p1Field-0', 2, 'The attack dealt its 2 POWER')
    ->expectZoneCount('myMemory', 5, 2, 'No class bonus: full cost 5')
    ->tested('Crosswind Cuts')
    ->covers(['combat', 'class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 2 less to activate.')
    ->register();

const GA_MULTISTRIKE_TEXT = '**Multistrike 1** *(You may assign up to one additional target as you declare this attack.)*';

// P2 attacks with Crosswind Cuts (2 POWER) while P1 controls a champion and two Dungeon Guides.
function gaMultistrikeCuts(string $slug) {
    return gaScenario($slug)
        ->deckTemplate('quiet', 'Crosswind Cuts')
        ->readyFor('Crosswind Cuts', classBonus: true, player: 2)
        ->seed('myHand', 'Crosswind Cuts', player: 2)
        ->seed('myField', 'Dungeon Guide')
        ->seed('myField', 'Dungeon Guide')
        ->endTurn()
        ->play('Crosswind Cuts', 2)->pay(3, 2)->target('theirField-0', 2);
}

gaMultistrikeCuts('crosswind-cuts-multistrike-one-assigns-an-additional-target-and-both-take-the-damage')
    ->answer(2, 'theirField-1')->answer(1, '-')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 2, 'The primary target took the 2 POWER')
    ->expectDamage('p1Field-1', 2, 'The additional target took the 2 POWER')
    ->expectDamage('p1Field-2', 0, 'The unassigned Dungeon Guide was not a defender')
    ->tested('Crosswind Cuts')
    ->covers(['keyword', 'multistrike', 'combat'], GA_MULTISTRIKE_TEXT)
    ->register();

gaMultistrikeCuts('crosswind-cuts-multistrike-zero-additional-targets-is-a-normal-single-target-attack')
    ->answer(2, '-')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 2, 'Only the primary target took damage')
    ->expectDamage('p1Field-1', 0, 'No additional target was assigned')
    ->expectDamage('p1Field-2', 0, 'No additional target was assigned')
    ->tested('Crosswind Cuts')
    ->covers(['keyword', 'multistrike', 'condition'], GA_MULTISTRIKE_TEXT)
    ->register();

gaMultistrikeCuts('crosswind-cuts-multistrike-cannot-add-the-primary-target-again-or-a-second-extra')
    ->rejects(2, 'theirField-0', 'The primary target is not an additional target')
    ->rejects(2, 'theirField-1&theirField-2', 'Multistrike 1 allows only one additional target')
    ->answer(2, 'theirField-1')->answer(1, '-')
    ->expectDqEmpty()
    ->expectDamage('p1Field-1', 2, 'Multistrike 1 assigned the one additional target')
    ->expectDamage('p1Field-2', 0, 'The second extra was beyond Multistrike 1')
    ->tested('Crosswind Cuts')
    ->covers(['keyword', 'multistrike', 'condition'], GA_MULTISTRIKE_TEXT)
    ->register();

// Windstrike Soldier's granted multistrike 2: an ally attack assigns up to two additional targets (all three opposing units are defenders).
gaScenario('windstrike-soldier-multistrike-two-assigns-two-additional-targets-and-all-three-take-damage')
    ->deckTemplate('quiet', 'Windstrike Soldier')
    ->readyFor('Windstrike Soldier', classBonus: true, player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Windstrike Soldier', player: 2)
    ->endTurn()->pass(2)
    ->play('Windstrike Soldier', 2)->pay(3, 2)->answer(2, 'YES')->pay(3, 2)
    ->attack('myField-1', 'theirField-0', player: 2)->answer(2, 'theirField-1&theirField-2')
    ->answer(1, '-')->answer(1, '-')
    ->expectDqEmpty()
    ->expectDamage('p1Field-0', 2, 'The primary target took the Soldier\'s 2 POWER')
    ->expectDamage('p1Field-1', 2, 'The first additional target took the damage')
    ->expectDamage('p1Field-2', 2, 'The second additional target took the damage')
    ->tested('Windstrike Soldier')
    ->covers(['keyword', 'multistrike', 'combat'], 'You may assign up to two additional targets as you declare an attack with an ally with **multistrike 2**.')
    ->register();

// ---- Poised Strike ----

gaScenario('poised-strike-prepared-class-bonus-on-hit-wakes-the-champion')
    ->deckTemplate('quiet', 'Poised Strike')
    ->readyFor('Poised Strike', classBonus: true, player: 2)
    ->patch('myField-0', ['Counters' => ['preparation' => 1, '_overrides' => ['classes' => 'ASSASSIN']]], player: 2)
    ->seed('myHand', 'Poised Strike', player: 2)
    ->endTurn()
    ->play('Poised Strike', 2)->pay(2, 2)->answer(2, 'YES')->target('theirField-0', 2)
    ->expectProperty('p2Field-0', 'Status', '2', 'Prepared + Class Bonus + hit: the attacking champion woke up')
    ->expectDamage('p1Field-0', 3, 'The attack dealt its 3 POWER')
    ->tested('Poised Strike')
    ->covers(['combat', 'prepare', 'class-bonus', 'on-hit', 'status'], '**Prepare 1**', '[Class Bonus] **On Hit:** If CARDNAME was prepared, wake up your champion.')
    ->register();

gaScenario('poised-strike-unprepared-on-hit-leaves-the-champion-rested')
    ->deckTemplate('quiet', 'Poised Strike')
    ->readyFor('Poised Strike', classBonus: true, player: 2)
    ->seed('myHand', 'Poised Strike', player: 2)
    ->endTurn()
    ->play('Poised Strike', 2)->pay(2, 2)->target('theirField-0', 2)
    ->expectProperty('p2Field-0', 'Status', '1', 'Not prepared: the champion stays rested after attacking')
    ->expectDamage('p1Field-0', 3, 'The attack dealt its 3 POWER')
    ->tested('Poised Strike')
    ->covers(['prepare', 'condition'], '[Class Bonus] **On Hit:** If CARDNAME was prepared, wake up your champion.')
    ->register();

// ---- Command attacks (an ally performs the attack) ----
foreach ([
    ['bishops-cross-on-kill-of-an-odd-life-unit-draws-a-card', 'Owl Familiar', 'The 1-life Owl Familiar was killed by the commanded Pawn Piece (2 POWER)', 7, 'Odd life stat (1): a card was drawn (8 - Bishop\'s Cross - 2 paid + 1)', ['command-chessman', 'on-kill', 'draw-discard', 'combat', 'targeting'], ['**Command Chessman**', '**On Kill:** If the killed unit had an odd life stat, draw a card.']],
    ['bishops-cross-on-kill-of-an-even-life-unit-draws-nothing', 'Gray Wolf', 'The 2-life Gray Wolf was killed', 6, 'Even life stat (2): no card drawn (8 - Bishop\'s Cross - 2 paid)', ['on-kill', 'condition'], ['**On Kill:** If the killed unit had an odd life stat, draw a card.']],
] as [$slug, $victim, $deathLabel, $handCount, $drawLabel, $mechanics, $clauses]) {
    gaScenario($slug)
        ->deckTemplate('quiet', 'Bishop\'s Cross')
        ->readyFor('Bishop\'s Cross')
        ->seed('myField', 'Pawn Piece')
        ->seed('myField', $victim, player: 2)
        ->seed('myHand', 'Bishop\'s Cross')
        ->reachTurnThree()
        ->play('Bishop\'s Cross')->pay(2)->target('myField-1')->target('theirField-1')->pass(2)
        ->expectZoneCount('theirField', 1, 1, $deathLabel)
        ->expectZoneCount('myHand', $handCount, 1, $drawLabel)
        ->tested('Bishop\'s Cross')
        ->covers($mechanics, ...$clauses)
        ->register();
}

// ---- En Passant ----

foreach ([
    ['en-passant-a-pawn-that-kills-with-it-gets-two-buff-counters', [], '{"buff":2}', 'On Kill with no buff counters: two buff counters on the Pawn', ['command-chessman', 'on-kill', 'counter', 'combat'], 'As long as a Chessman Pawn ally is attacking with CARDNAME, it has "**On Kill: **If there are no **buff** counters on this ally, put two **buff** counters on it."'],
    ['en-passant-a-pawn-that-already-has-a-buff-counter-gets-no-more', ['Counters' => ['buff' => 1]], '{"buff":1}', 'Already has a buff counter: the On Kill adds nothing', ['on-kill', 'condition'], 'If there are no **buff** counters on this ally, put two **buff** counters on it.'],
] as [$slug, $properties, $expectedCounters, $label, $mechanics, $clause]) {
    gaScenario($slug)
        ->deckTemplate('quiet', 'En Passant')
        ->readyFor('En Passant')
        ->seed('myField', 'Pawn Piece', properties: $properties)
        ->seed('myField', 'Owl Familiar', player: 2)
        ->seed('myHand', 'En Passant')
        ->reachTurnThree()
        ->play('En Passant')->pay(2)->target('myField-1')->target('theirField-1')->pass(2)
        ->expectProperty('p1Field-1', 'Counters', $expectedCounters, $label)
        ->tested('En Passant')
        ->covers($mechanics, $clause)
        ->register();
}

// ---- Eternal Directive ----

foreach ([
    ['eternal-directive-class-bonus-on-attack-puts-a-buff-counter-on-the-automaton', true, '{"buff":1}', '[Class Bonus] On Attack: a buff counter on the attacker', ['command-chessman', 'class-bonus', 'on-attack', 'counter', 'combat'], ['**Command Automaton**', '[Class Bonus] **On Attack:** Put a **buff** counter on the attacker.']],
    ['eternal-directive-without-the-class-bonus-adds-no-counter', false, '[]', 'No class bonus: no counter', ['class-bonus', 'condition'], ['[Class Bonus] **On Attack:** Put a **buff** counter on the attacker.']],
] as [$slug, $classBonus, $counters, $label, $mechanics, $clauses]) {
    gaScenario($slug)
        ->deckTemplate('quiet', 'Eternal Directive')
        ->readyFor('Eternal Directive', classBonus: $classBonus)
        ->seed('myField', 'Automaton Drone')
        ->seed('myHand', 'Eternal Directive')
        ->reachTurnThree()
        ->play('Eternal Directive')->pay(2)->target('myField-1')->target('theirField-0')->pass(2)
        ->expectProperty('p1Field-1', 'Counters', $counters, $label)
        ->tested('Eternal Directive')
        ->covers($mechanics, ...$clauses)
        ->register();
}

// ---- Razorblade Execution (Imbue 3) ----

gaScenario('razorblade-execution-imbued-class-bonus-on-kill-puts-a-preparation-counter-on-the-champion')
    ->deckTemplate('quiet', 'Razorblade Execution')
    ->readyFor('Razorblade Execution', classBonus: true)
    ->seed('myField', 'Owl Familiar', player: 2)
    ->seed('myHand', 'Razorblade Execution')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->seed('myHand', 'Fairy Whispers')
    ->reachTurnThree(fastCardInHand: true)
    ->play('Razorblade Execution')->answer(1, 'YES')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->answer(1, 'myHand-7')->target('theirField-1')->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The 1-life Owl Familiar was killed by 4 POWER')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"preparation":1}', 'Imbued + Class Bonus On Kill: a preparation counter on the champion')
    ->tested('Razorblade Execution')
    ->covers(['imbue', 'class-bonus', 'on-kill', 'counter', 'combat'], '**Imbue 3**', '[Class Bonus] **On Kill**: If Razorblade Execution is **imbued**, put a **preparation** counter on your champion.')
    ->register();

gaScenario('razorblade-execution-not-imbued-on-kill-adds-no-preparation-counter')
    ->deckTemplate('quiet', 'Razorblade Execution')
    ->readyFor('Razorblade Execution', classBonus: true)
    ->seed('myField', 'Owl Familiar', player: 2)
    ->seed('myHand', 'Razorblade Execution')
    ->reachTurnThree()
    ->play('Razorblade Execution')->answer(1, 'NO')->pay(3)->target('theirField-1')->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The Owl Familiar was killed')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"}}', 'Not imbued: no preparation counter (only the class override from readyFor)')
    ->tested('Razorblade Execution')
    ->covers(['imbue', 'condition'], '[Class Bonus] **On Kill**: If Razorblade Execution is **imbued**, put a **preparation** counter on your champion.')
    ->register();
