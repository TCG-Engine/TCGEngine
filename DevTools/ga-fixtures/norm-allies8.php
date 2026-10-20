<?php
/**
 * Phase 3d (Norm): allies, eighth batch (On Enter / On Attack allies with choices, champion-bonus allies). 'quiet' deck.
 */

// ---- Corrosive Juggler ----

gaScenario('corrosive-juggler-enter-resting-it-deals-one-damage-and-shrinks-the-targets-attacks')
    ->deckTemplate('quiet', 'Corrosive Juggler')
    ->readyFor('Corrosive Juggler')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Corrosive Juggler')
    ->play('Corrosive Juggler')->pay(2)->answer(1, 'A')->answer(1, 'theirField-1')
    ->expectDamage('p2Field-1', 1, 'The Juggler rested and dealt 1 damage to the target unit')
    ->expectProperty('p1Field-1', 'Status', '1', 'On Enter: the Juggler rested')
    ->tested('Corrosive Juggler')
    ->covers(['on-enter', 'optional-choice', 'rest', 'damage', 'stat-change', 'targeting'], '**On Enter:** You may rest CARDNAME. When you do, deal 1 damage to target unit. That unit\'s attacks get -2[POWER] until end of turn.')
    ->register();

gaScenario('corrosive-juggler-enter-declined-does-nothing')
    ->deckTemplate('quiet', 'Corrosive Juggler')
    ->readyFor('Corrosive Juggler')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Corrosive Juggler')
    ->play('Corrosive Juggler')->pay(2)->answer(1, 'B')
    ->expectDamage('p2Field-1', 0, 'Declined: no damage')
    ->expectProperty('p1Field-1', 'Status', '2', 'Declined: the Juggler stays awake')
    ->tested('Corrosive Juggler')
    ->covers(['on-enter', 'optional-choice-decline'], '**On Enter:** You may rest CARDNAME.')
    ->register();

// ---- Buddy Raccoon ----

gaScenario('buddy-raccoon-enter-returns-another-raccoon-to-its-owners-hand')
    ->deckTemplate('quiet', 'Buddy Raccoon')
    ->readyFor('Buddy Raccoon')
    ->seed('myField', 'Banner Raccoon')
    ->seed('myHand', 'Buddy Raccoon')
    ->play('Buddy Raccoon')->pay(2)->answer(1, 'myField-1')
    ->expectCardExists('myHand', 'Banner Raccoon', 'On Enter: the other Raccoon returned to hand')
    ->tested('Buddy Raccoon')
    ->covers(['on-enter', 'zone-movement', 'targeting'], '**On Enter: **You may return another target Raccoon ally you control to its owner\'s hand.')
    ->register();

// ---- Portly Raccoon ----

gaScenario('portly-raccoon-enter-revealing-two-raccoons-makes-the-opponent-banish-a-graveyard-card')
    ->deckTemplate('quiet', 'Portly Raccoon')
    ->readyFor('Portly Raccoon')
    ->seed('myHand', 'Banner Raccoon')
    ->seed('myHand', 'Excitable Raccoon')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Portly Raccoon')
    ->play('Portly Raccoon')->pay(3)->answer(1, 'YES')->answer(2, 'myGraveyard-0')
    ->expectZoneCount('myGraveyard', 0, 2, 'The opponent banished their only graveyard card')
    ->expectZoneCount('myBanish', 1, 2, 'It went to their own banishment')
    ->expectZoneCount('myBanish', 0, 1, 'Not to the Raccoon controller\'s banishment')
    ->tested('Portly Raccoon')
    ->covers(['on-enter', 'reveal', 'banish'], '**On Enter:** You may reveal two Raccoon cards from your hand and/or memory. When you do, target opponent banishes a card from their graveyard.')
    ->register();

// ---- Unbroken Mustang ----

gaScenario('unbroken-mustang-enter-destroys-an-item-with-reserve-cost-three-or-less')
    ->deckTemplate('quiet', 'Unbroken Mustang')
    ->readyFor('Unbroken Mustang')
    ->seed('myField', 'Powercell', player: 2)
    ->seed('myHand', 'Unbroken Mustang')
    ->play('Unbroken Mustang')->pay(4)->answer(1, 'theirField-1')
    ->expectZoneCount('theirField', 1, 1, 'The Powercell (reserve 1) was destroyed')
    ->tested('Unbroken Mustang')
    ->covers(['on-enter', 'destroy', 'targeting'], '**On Enter:** Destroy up to one target item or weapon with memory cost 0 or reserve cost 3 or less.')
    ->register();

// ---- Inner Court Schemer ----

gaScenario('inner-court-schemer-on-attack-removing-a-preparation-counter-gets-plus-two-power')
    ->deckTemplate('quiet', 'Inner Court Schemer')
    ->readyFor('Inner Court Schemer')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myField', 'Inner Court Schemer')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')->pass(2)
    ->expectDamage('p2Field-0', 3, 'On Attack: the preparation counter was removed for +2 power, 1 + 2 = 3')
    ->tested('Inner Court Schemer')
    ->covers(['on-attack', 'optional-cost', 'counter', 'stat-change'], '**On Attack:** You may remove a **preparation** counter from your champion. If you do, this attack gets +2 [POWER].')
    ->register();

// ---- Jovial Tinkerer ----

gaScenario('jovial-tinkerer-class-bonus-enter-with-a-veltech-item-draws-into-memory')
    ->deckTemplate('quiet', 'Jovial Tinkerer')
    ->readyFor('Jovial Tinkerer', classBonus: true)
    ->seed('myField', 'CookTech Knife')
    ->seed('myHand', 'Jovial Tinkerer')
    ->play('Jovial Tinkerer')->pay(3)
    ->expectZoneCount('myMemory', 4, 1, '[Class Bonus] A VelTech item is controlled: three paid plus one drawn into memory')
    ->tested('Jovial Tinkerer')
    ->covers(['class-bonus', 'on-enter', 'draw-into-memory', 'condition'], '[Class Bonus] **On Enter: **If you control a VelTech item, draw a card into your memory.')
    ->register();

gaScenario('jovial-tinkerer-class-bonus-enter-without-a-veltech-item-draws-nothing')
    ->deckTemplate('quiet', 'Jovial Tinkerer')
    ->readyFor('Jovial Tinkerer', classBonus: true)
    ->seed('myHand', 'Jovial Tinkerer')
    ->play('Jovial Tinkerer')->pay(3)
    ->expectZoneCount('myMemory', 3, 1, 'No VelTech item: only the three paid cards in memory')
    ->tested('Jovial Tinkerer')
    ->covers(['class-bonus', 'on-enter', 'condition'], '[Class Bonus] **On Enter: **If you control a VelTech item, draw a card into your memory.')
    ->register();

// ---- Pole-armed Steed (Jin Bonus) ----

gaBonus('pole-armed-steed-jin-bonus-enter-materializes-a-polearm-regalia', 'Jin, Fate Defiant')
    ->seed('myMaterial', 'Steel Halberd')
    ->seed('myHand', 'Pole-armed Steed')
    ->play('Pole-armed Steed')->pay(3)->answer(1, 'myMaterial-4')
    ->expectCardExists('myField', 'Steel Halberd', '[Jin Bonus] The Polearm regalia was materialized')
    ->tested('Pole-armed Steed')
    ->covers(['jin-bonus', 'on-enter', 'materialize'], '[Jin Bonus] **On Enter:** Materialize a Polearm regalia card from your material deck.')
    ->register();

// ---- Golden Knight (Alice Bonus) ----

gaBonus('golden-knight-alice-bonus-attacking-a-taunt-unit-gets-plus-one-power', 'Alice, Distorted Queen')
    ->seed('myField', 'Golden Knight')
    ->seed('myField', 'Palace Guard', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 3, '[Alice Bonus] Attacking a taunt unit: 2 + 1 = 3 damage')
    ->tested('Golden Knight')
    ->covers(['alice-bonus', 'condition', 'stat-change', 'combat'], '[Alice Bonus] As long as CARDNAME is attacking a unit with **intercept** or **taunt**, CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('golden-knight-without-the-alice-bonus-has-its-printed-power-against-taunt')
    ->deckTemplate('quiet', 'Golden Knight')
    ->readyFor('Golden Knight')
    ->seed('myField', 'Golden Knight')
    ->seed('myField', 'Palace Guard', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 2, 'No Alice Bonus: the printed 2 damage')
    ->tested('Golden Knight')
    ->covers(['alice-bonus', 'condition', 'stat-change'], '[Alice Bonus] As long as CARDNAME is attacking a unit with **intercept** or **taunt**, CARDNAME gets +1 [POWER].')
    ->register();
