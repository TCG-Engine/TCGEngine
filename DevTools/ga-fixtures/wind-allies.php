<?php
/**
 * Phase 3b (Wind): allies. Written on the 'quiet' deck (no fast cards in the opening hands) so no priority window opens between steps.
 */

// ---- Cellforger Droid ----

gaScenario('cellforger-droid-on-enter-summons-a-rested-powercell')
    ->deckTemplate('quiet', 'Cellforger Droid')
    ->readyFor('Cellforger Droid')
    ->seed('myHand', 'Cellforger Droid')
    ->play('Cellforger Droid')->pay(2)
    ->expectCardExists('myField', 'Powercell', 'On Enter: a Powercell token was summoned')
    ->expectProperty('p1Field-2', 'Status', '1', 'It was summoned rested')
    ->tested('Cellforger Droid')
    ->covers(['on-enter', 'token', 'summon'], '**On Enter:** **Summon** a Powercell token rested.')
    ->register();

gaScenario('cellforger-droid-rest-ability-pays-four-and-summons-another-powercell')
    ->deckTemplate('quiet', 'Cellforger Droid')
    ->readyFor('Cellforger Droid')
    ->seed('myField', 'Cellforger Droid')
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)
    ->expectCardExists('myField', 'Powercell', 'The activated ability summoned a Powercell')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Droid rested for the ability')
    ->expectZoneCount('myMemory', 4, 1, 'The (4) cost was paid')
    ->tested('Cellforger Droid')
    ->covers(['activated-ability', 'rest', 'token', 'summon'], '(4)**, [REST]:** **Summon** a Powercell token rested.')
    ->register();

// ---- Winbless Gatekeeper ----

gaScenario('winbless-gatekeeper-on-enter-pays-two-and-buffs-a-guardian-ally')
    ->deckTemplate('quiet', 'Winbless Gatekeeper')
    ->readyFor('Winbless Gatekeeper')
    ->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Winbless Gatekeeper')
    ->play('Winbless Gatekeeper')->pay(2)->answer(1, 'YES')->pay(2)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'On Enter paid: the Guardian Automaton Drone got a buff counter')
    ->tested('Winbless Gatekeeper')
    ->covers(['on-enter', 'counter', 'optional-cost', 'targeting'], '**On Enter:** You may pay (2). When you do, put a **buff** counter on target Guardian ally you control.')
    ->register();

gaScenario('winbless-gatekeeper-on-enter-declined-puts-no-counter')
    ->deckTemplate('quiet', 'Winbless Gatekeeper')
    ->readyFor('Winbless Gatekeeper')
    ->seed('myField', 'Automaton Drone')
    ->seed('myHand', 'Winbless Gatekeeper')
    ->play('Winbless Gatekeeper')->pay(2)->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Declined: no counter')
    ->tested('Winbless Gatekeeper')
    ->covers(['on-enter', 'optional-cost'], '**On Enter:** You may pay (2).')
    ->register();

// ---- Blackmarket Broker ----

gaScenario('blackmarket-broker-on-enter-puts-a-preparation-counter-on-the-champion')
    ->deckTemplate('quiet', 'Blackmarket Broker')
    ->readyFor('Blackmarket Broker')
    ->seed('myHand', 'Blackmarket Broker')
    ->play('Blackmarket Broker')->pay(4)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Enter: one preparation counter on the champion')
    ->tested('Blackmarket Broker')
    ->covers(['on-enter', 'counter'], '**On Enter:** Put a **preparation** counter on your champion.')
    ->register();

gaScenario('blackmarket-broker-with-three-preparation-counters-has-stealth-and-cannot-be-attacked')
    ->deckTemplate('quiet', 'Blackmarket Broker')
    ->readyFor('Blackmarket Broker')
    ->patch('myField-0', ['Counters' => ['preparation' => 3]])
    ->seed('myField', 'Blackmarket Broker')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Three preparation counters: the Broker has stealth, so it is not a legal attack target')->answer(2, 'theirField-0')
    ->expectDamage('p1Field-1', 0, 'The Broker took no damage')
    ->tested('Blackmarket Broker')
    ->covers(['stealth', 'condition', 'counter'], 'CARDNAME has **stealth** as long as your champion has three or more **preparation** counters on them.')
    ->register();

// Seeding the Broker onto the field runs its On Enter, so the champion gets one more counter than the patch gives it.
gaScenario('blackmarket-broker-with-two-preparation-counters-can-be-attacked')
    ->deckTemplate('quiet', 'Blackmarket Broker')
    ->readyFor('Blackmarket Broker')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myField', 'Blackmarket Broker')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->pass(1)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'One patched counter plus the Broker\'s On Enter: two')
    ->expectDamage('p1Field-1', 1, 'Two counters: no stealth, the Broker was attacked and took 1')
    ->tested('Blackmarket Broker')
    ->covers(['stealth', 'condition'], 'as long as your champion has three or more **preparation** counters')
    ->register();

// ---- Nocturnal Blossom ----

gaScenario('nocturnal-blossom-recovers-one-at-the-beginning-of-your-end-phase')
    ->deckTemplate('quiet', 'Nocturnal Blossom')
    ->readyFor('Nocturnal Blossom', damage: 4)
    ->seed('myField', 'Nocturnal Blossom')
    ->seed('myHand', 'Dungeon Guide')
    ->endTurn()
    ->expectDamage('p1Field-0', 3, 'End phase: recover 1 (4 - 1)')
    ->tested('Nocturnal Blossom')
    ->covers(['recover', 'end-phase-trigger', 'stealth'], 'At the beginning of your end phase, **recover 1**.')
    ->register();

// ---- Sasha, Purifying Acolyte ----

gaScenario('sasha-purifying-acolyte-on-foster-banishes-every-card-in-every-graveyard')
    ->deckTemplate('quiet', 'Sasha, Purifying Acolyte')
    ->readyFor('Sasha, Purifying Acolyte')
    ->seed('myField', 'Sasha, Purifying Acolyte')
    ->seed('myGraveyard', 'Dungeon Guide')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->reachTurnThree()
    ->observe()
    ->expectZoneCount('myGraveyard', 0, 1, 'On Foster: your graveyard was banished')
    ->expectZoneCount('theirGraveyard', 0, 1, 'On Foster: the opposing graveyard was banished too')
    ->expectZoneCount('myBanish', 1, 1, 'The card went to banishment')
    ->expectZoneCount('theirBanish', 2, 1, 'Both opposing cards went to banishment')
    ->tested('Sasha, Purifying Acolyte')
    ->covers(['foster', 'on-foster', 'banish'], '**On Foster:** Banish all cards in all graveyards.')
    ->register();

gaScenario('sasha-purifying-acolyte-fostered-a-resolved-action-is-banished-instead-of-entering-the-graveyard')
    ->deckTemplate('quiet', 'Sasha, Purifying Acolyte')
    ->readyFor('Sasha, Purifying Acolyte')
    ->seed('myField', 'Sasha, Purifying Acolyte')
    ->seed('myHand', 'Nascent Blast')
    ->reachTurnThree(fastCardInHand: true)
    ->castSpell('Nascent Blast', pay: 3, window: '', target: 'theirField-0')
    ->expectDamage('p2Field-0', 3, 'The Blast resolved')
    ->expectCardExists('myBanish', 'Nascent Blast', 'Fostered Sasha: the resolved action was banished')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing entered the graveyard')
    ->tested('Sasha, Purifying Acolyte')
    ->covers(['foster', 'banish', 'zone-movement'], 'If one or more cards would enter a player\'s graveyard while CARDNAME is **fostered**, banish them instead.')
    ->register();

gaScenario('sasha-purifying-acolyte-not-fostered-an-action-enters-the-graveyard')
    ->deckTemplate('quiet', 'Sasha, Purifying Acolyte')
    ->readyFor('Sasha, Purifying Acolyte')
    ->seed('myField', 'Sasha, Purifying Acolyte')
    ->seed('myHand', 'Nascent Blast')
    ->play('Nascent Blast')->pay(3)->target('theirField-0')
    ->expectCardExists('myGraveyard', 'Nascent Blast', 'Turn 1: Sasha is not fostered yet, so the Blast enters the graveyard')
    ->tested('Sasha, Purifying Acolyte')
    ->covers(['foster', 'condition'], 'while CARDNAME is **fostered**')
    ->register();

// ---- Liu Bei, Oathkeeper ----

gaScenario('liu-bei-becomes-distant-whenever-another-unit-you-control-becomes-distant')
    ->deckTemplate('quiet', 'Liu Bei, Oathkeeper')
    ->readyFor('Skirting Step')
    ->seed('myField', 'Liu Bei, Oathkeeper')
    ->seed('myHand', 'Skirting Step')
    ->play('Skirting Step')->answer(1, 'NO')->pay(2)->answer(1, 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","DISTANT"]', 'The champion became distant')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'Liu Bei became distant when the champion did')
    ->tested('Liu Bei, Oathkeeper')
    ->covers(['distant', 'trigger'], 'Whenever another unit you control becomes **distant**, CARDNAME becomes **distant**.')
    ->register();
