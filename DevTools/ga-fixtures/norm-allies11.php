<?php
/**
 * Phase 3d (Norm): allies, eleventh batch (Cardistry spades, loose ends). 'quiet' deck.
 */

// ---- Two of Spades ----

gaScenario('two-of-spades-cardistry-puts-a-buff-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Two of Spades')
    ->seed('myField', 'Two of Spades')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)
    ->expectProperty('p1Field-1', 'Counters', '{"cardistry_used":1,"buff":1}', 'Cardistry (2), one less for the lone Suited object: a buff counter for 1')
    ->expectZoneCount('myMemory', 1, 1, 'The ability cost 2 - 1 = 1')
    ->tested('Two of Spades')
    ->covers(['cardistry', 'activated-ability', 'counter'], '***Cardistry*** — (2): Put a **buff** counter on Two of Spades.')
    ->register();

gaScenario('two-of-spades-cardistry-costs-one-less-per-suited-object-with-a-different-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Two of Spades')
    ->seed('myField', 'Two of Spades')
    ->seed('myField', 'Three of Spades')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Counters', '{"cardistry_used":1,"buff":1}', 'Two Suited objects with different reserve costs make the (2) ability free')
    ->expectZoneCount('myMemory', 0, 1, 'Nothing was paid')
    ->tested('Two of Spades')
    ->covers(['cardistry', 'cost-reduction', 'activated-ability'], 'This ability costs (1) less to activate for each Suited object you control with different reserve costs.')
    ->register();

gaScenario('two-of-spades-cardistry-can-only-be-activated-once')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Two of Spades')
    ->seed('myField', 'Two of Spades')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)
    ->expectProperty('p1Field-1', 'Counters', '{"cardistry_used":1,"buff":1}', 'Activate this ability only once: the second activation did nothing')
    ->expectZoneCount('myMemory', 1, 1, 'Only the first activation was paid for')
    ->tested('Two of Spades')
    ->covers(['cardistry', 'once-per-game', 'activated-ability'], 'Activate this ability only once.')
    ->register();

// ---- Three of Spades ----

gaScenario('three-of-spades-cardistry-gives-a-suited-ally-plus-two-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Three of Spades')
    ->seed('myField', 'Three of Spades')
    ->seed('myField', 'Two of Spades')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->answer(1, 'myField-2')
    ->expectLife('p1Field-2', 3, 'Cardistry (3, minus 2 for the two Suited costs): the Suited ally got +2 life, 1 + 2')
    ->tested('Three of Spades')
    ->covers(['cardistry', 'activated-ability', 'targeting', 'stat-change'], '***Cardistry*** — (3): Target Suited ally you control gets +2[LIFE] until end of turn.')
    ->register();

// ---- Four of Spades ----

gaScenario('four-of-spades-cardistry-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Four of Spades')
    ->seed('myField', 'Four of Spades')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->expectZoneCount('myMemory', 4, 1, 'Cardistry (4 - 1): three paid plus one drawn into memory')
    ->tested('Four of Spades')
    ->covers(['cardistry', 'activated-ability', 'draw-into-memory'], '***Cardistry*** — (4): Draw a card into your memory.')
    ->register();

// ---- Five of Spades ----

gaScenario('five-of-spades-cardistry-gets-plus-five-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Five of Spades')
    ->seed('myField', 'Five of Spades')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)
    ->expectPower('p1Field-1', 6, 'Cardistry (5 - 1): 1 + 5 power until end of turn')
    ->tested('Five of Spades')
    ->covers(['cardistry', 'activated-ability', 'stat-change'], '***Cardistry*** — (5): Five of Spades gets +5[POWER] until end of turn.')
    ->register();

// ---- Six of Spades ----

gaScenario('six-of-spades-stealth-cannot-be-targeted-by-attacks')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Six of Spades')
    ->seed('myField', 'Six of Spades', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Stealth: the attack cannot target the Six of Spades')
    ->tested('Six of Spades')
    ->covers(['keyword', 'stealth'], '**Stealth**')
    ->register();

gaScenario('six-of-spades-cardistry-in-response-to-an-attack-takes-the-attack-with-plus-two-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Six of Spades')
    ->seed('myField', 'Six of Spades')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(5)->answer(1, 'YES')->pass(2)->answer(1, '-')
    ->expectLife('p1Field-1', 5, 'Cardistry (6 - 1): 3 + 2 life until end of turn')
    ->expectDamage('p1Field-0', 0, 'The attack on the champion was redirected away from it')
    ->expectDamage('p1Field-1', 1, 'The Six of Spades took the redirected attack')
    ->tested('Six of Spades')
    ->covers(['cardistry', 'activated-ability', 'stat-change', 'stealth', 'redirect'], '***Cardistry —*** (6): CARDNAME loses **stealth** and gets +2 [LIFE] until end of turn. You may change the target of an attack to CARDNAME.')
    ->register();

// ---- Eight of Spades ----

gaScenario('eight-of-spades-cardistry-wakes-it-up')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Eight of Spades')
    ->seed('myField', 'Eight of Spades', properties: ['Status' => 1])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(7)
    ->expectProperty('p1Field-1', 'Status', '2', 'Cardistry (8 - 1): Eight of Spades woke up')
    ->tested('Eight of Spades')
    ->covers(['cardistry', 'activated-ability', 'wake'], '***Cardistry —*** (8): Wake up Eight of Spades.')
    ->register();

// ---- Haunting Apparition ----

gaScenario('haunting-apparition-ephemerate-from-the-graveyard-enters-ephemeral-with-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Haunting Apparition')
    ->seed('myGraveyard', 'Haunting Apparition')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(5)
    ->expectCardExists('myField', 'Haunting Apparition', 'Ephemerate (5): the Apparition came back from the graveyard')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'It entered ephemeral')
    ->tested('Haunting Apparition')
    ->covers(['ephemerate', 'graveyard-activation', 'ephemeral'], '**Ephemerate —** (5)')
    ->register();

gaScenario('haunting-apparition-ephemeral-has-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Haunting Apparition')
    ->seed('myField', 'Haunting Apparition', properties: ['TurnEffects' => ['EPHEMERAL'], 'Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Ephemeral: the Apparition has stealth')
    ->tested('Haunting Apparition')
    ->covers(['ephemeral', 'condition', 'stealth'], 'As long as CARDNAME is **ephemeral**, it has **stealth**.')
    ->register();

gaScenario('haunting-apparition-not-ephemeral-can-be-attacked')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Haunting Apparition')
    ->seed('myField', 'Haunting Apparition', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectDamage('p1Field-1', 1, 'Not ephemeral: no stealth, the attack landed')
    ->tested('Haunting Apparition')
    ->covers(['ephemeral', 'condition', 'stealth'], 'As long as CARDNAME is **ephemeral**, it has **stealth**.')
    ->register();

// ---- Lingering Banshee ----

gaScenario('lingering-banshee-ephemerate-costs-six')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lingering Banshee')
    ->seed('myGraveyard', 'Lingering Banshee')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(6)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'Ephemerate (6): it came back ephemeral')
    ->tested('Lingering Banshee')
    ->covers(['ephemerate', 'graveyard-activation', 'ephemeral'], '**Ephemerate** — (6).')
    ->register();

gaScenario('lingering-banshee-ephemerate-costs-three-less-with-an-ephemeral-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lingering Banshee')
    ->seed('myField', 'Haunting Apparition', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myGraveyard', 'Lingering Banshee')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(3)
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'An ephemeral object is controlled: ephemerate cost 6 - 3 = 3')
    ->tested('Lingering Banshee')
    ->covers(['ephemerate', 'cost-reduction', 'condition'], 'As long as you control one or more **ephemeral** objects, this card costs (3) less to activate this way.')
    ->register();
