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

// ---- Reconnaissance Scout ----

gaScenario('reconnaissance-scout-class-bonus-on-enter-makes-another-unit-distant-and-shields-it')
    ->deckTemplate('quiet', 'Reconnaissance Scout')
    ->readyFor('Reconnaissance Scout', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Reconnaissance Scout')
    ->play('Reconnaissance Scout')->pay(2)->pass(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT","PREVENT_ALL_1"]', 'The other unit became distant and has the next-1-damage prevention')
    ->tested('Reconnaissance Scout')
    ->covers(['on-enter', 'class-bonus', 'distant', 'prevent-damage', 'targeting'], '[Class Bonus] **On Enter**: Another target unit you control becomes **distant**. Prevent the next 1 damage that would be dealt to that unit this turn.')
    ->register();

gaScenario('reconnaissance-scout-without-the-class-bonus-on-enter-does-nothing')
    ->deckTemplate('quiet', 'Reconnaissance Scout')
    ->readyFor('Reconnaissance Scout')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Reconnaissance Scout')
    ->play('Reconnaissance Scout')->pay(2)->pass(1)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No class bonus: the Tortoise is untouched')
    ->tested('Reconnaissance Scout')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter**')
    ->register();

// ---- Adept Swordmaster ----

gaScenario('adept-swordmaster-class-bonus-weapons-you-control-get-plus-one-power')
    ->deckTemplate('quiet', 'Adept Swordmaster')
    ->readyFor('Adept Swordmaster', classBonus: true)
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myField', 'Adept Swordmaster')
    ->observe()
    ->expectPower('p1Field-1', 2, 'Clarent has 1 printed POWER plus 1 from the Swordmaster\'s class bonus')
    ->tested('Adept Swordmaster')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] Weapons you control get +1 [POWER].')
    ->register();

gaScenario('adept-swordmaster-without-the-class-bonus-weapons-keep-their-power')
    ->deckTemplate('quiet', 'Adept Swordmaster')
    ->readyFor('Adept Swordmaster')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myField', 'Adept Swordmaster')
    ->observe()
    ->expectPower('p1Field-1', 1, 'No class bonus: the printed 1 POWER')
    ->tested('Adept Swordmaster')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] Weapons you control get +1 [POWER].')
    ->register();

// ---- Gustguard Bastion ----

gaScenario('gustguard-bastion-class-bonus-on-enter-prevents-two-damage-to-another-unit')
    ->deckTemplate('quiet', 'Gustguard Bastion')
    ->readyFor('Gustguard Bastion', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Gustguard Bastion')
    ->play('Gustguard Bastion')->pay(3)->pass(1)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2"]', 'Prevent the next 2 damage marker')
    ->tested('Gustguard Bastion')
    ->covers(['on-enter', 'class-bonus', 'prevent-damage', 'targeting'], '[Class Bonus] **On Enter:** Prevent the next 2 damage that would be dealt to another target unit you control this turn.')
    ->register();

gaScenario('gustguard-bastion-without-the-class-bonus-on-enter-does-nothing')
    ->deckTemplate('quiet', 'Gustguard Bastion')
    ->readyFor('Gustguard Bastion')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myHand', 'Gustguard Bastion')
    ->play('Gustguard Bastion')->pay(3)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No class bonus: no prevention')
    ->tested('Gustguard Bastion')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter:**')
    ->register();

// ---- Cell Generator ----

gaScenario('cell-generator-class-bonus-on-foster-summons-two-rested-powercells')
    ->deckTemplate('quiet', 'Cell Generator')
    ->readyFor('Cell Generator', classBonus: true)
    ->seed('myField', 'Cell Generator')
    ->reachTurnThree()
    ->observe()
    ->expectZoneCount('myField', 4, 1, 'Fostered at the recollection phase: the champion, the Generator and two Powercell tokens')
    ->expectProperty('p1Field-2', 'Status', '1', 'The Powercells were summoned rested')
    ->tested('Cell Generator')
    ->covers(['foster', 'on-foster', 'class-bonus', 'token', 'summon'], '[Class Bonus] **On Foster:** **Summon** two Powercell tokens rested.')
    ->register();

gaScenario('cell-generator-without-the-class-bonus-on-foster-summons-nothing')
    ->deckTemplate('quiet', 'Cell Generator')
    ->readyFor('Cell Generator')
    ->seed('myField', 'Cell Generator')
    ->reachTurnThree()
    ->observe()
    ->expectZoneCount('myField', 2, 1, 'No class bonus: no tokens')
    ->tested('Cell Generator')
    ->covers(['foster', 'class-bonus', 'condition'], '[Class Bonus] **On Foster:**')
    ->register();

// ---- Cutthroat Operative ----

gaScenario('cutthroat-operative-class-bonus-activating-an-attack-card-puts-a-buff-counter-on-it')
    ->deckTemplate('quiet', 'Cutthroat Operative')
    ->readyFor('Cutthroat Operative', classBonus: true)
    ->seed('myField', 'Cutthroat Operative')
    ->seed('myHand', 'Rallied Advance')
    ->reachTurnThree()
    ->play('Rallied Advance')->pay(2)->target('theirField-0')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Activating an attack card gave the Operative a buff counter')
    ->tested('Cutthroat Operative')
    ->covers(['class-bonus', 'trigger', 'counter'], '[Class Bonus] Whenever you activate an attack card, if CARDNAME has less than two **buff** counters on it, put a **buff** counter on it.')
    ->register();

gaScenario('cutthroat-operative-with-two-buff-counters-gets-no-more')
    ->deckTemplate('quiet', 'Cutthroat Operative')
    ->readyFor('Cutthroat Operative', classBonus: true)
    ->seed('myField', 'Cutthroat Operative', properties: ['Counters' => ['buff' => 2]])
    ->seed('myHand', 'Rallied Advance')
    ->reachTurnThree()
    ->play('Rallied Advance')->pay(2)->target('theirField-0')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Already at two buff counters: no third')
    ->tested('Cutthroat Operative')
    ->covers(['class-bonus', 'trigger', 'condition'], 'if CARDNAME has less than two **buff** counters on it')
    ->register();

gaScenario('cutthroat-operative-without-the-class-bonus-gets-no-counter')
    ->deckTemplate('quiet', 'Cutthroat Operative')
    ->readyFor('Cutthroat Operative')
    ->seed('myField', 'Cutthroat Operative')
    ->seed('myHand', 'Rallied Advance')
    ->reachTurnThree()
    ->play('Rallied Advance')->pay(2)->target('theirField-0')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No class bonus: no counter')
    ->tested('Cutthroat Operative')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] Whenever you activate an attack card')
    ->register();

// ---- Turbulent Bounty Hunter ----

gaScenario('turbulent-bounty-hunter-class-bonus-gets-a-buff-counter-when-an-ally-it-damaged-dies')
    ->deckTemplate('quiet', 'Turbulent Bounty Hunter')
    ->readyFor('Turbulent Bounty Hunter', classBonus: true)
    ->seed('myField', 'Turbulent Bounty Hunter')
    ->seed('myField', 'Owl Familiar', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The 1-life Owl Familiar died')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'An ally it damaged died: a buff counter')
    ->tested('Turbulent Bounty Hunter')
    ->covers(['class-bonus', 'trigger', 'counter', 'combat'], '[Class Bonus] Whenever an ally that CARDNAME has dealt damage to this turn dies, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('turbulent-bounty-hunter-without-the-class-bonus-gets-no-counter')
    ->deckTemplate('quiet', 'Turbulent Bounty Hunter')
    ->readyFor('Turbulent Bounty Hunter')
    ->seed('myField', 'Turbulent Bounty Hunter')
    ->seed('myField', 'Owl Familiar', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The Owl Familiar died')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No class bonus: no counter')
    ->tested('Turbulent Bounty Hunter')
    ->covers(['class-bonus', 'condition'], '[Class Bonus] Whenever an ally that CARDNAME has dealt damage to this turn dies')
    ->register();
