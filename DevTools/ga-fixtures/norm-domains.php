<?php
/**
 * Phase 3d (Norm): domains and tokens. Seeding a card with an On Enter runs it, so the domain On Enters are observed straight after the seed. 'quiet' deck.
 */

// ---- Hub of Innovation / Wool Brook ----

gaScenario('hub-of-innovation-enter-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Hub of Innovation')
    ->seed('myHand', 'Hub of Innovation')
    ->play('Hub of Innovation')->pay(3)
    ->expectZoneCount('myMemory', 4, 1, 'Three paid plus one drawn into memory')
    ->tested('Hub of Innovation')
    ->covers(['on-enter', 'draw-into-memory'], '**On Enter:** Draw a card into your memory.')
    ->register();

gaScenario('wool-brook-enters-with-a-refinement-counter-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Wool Brook')
    ->seed('myHand', 'Wool Brook')
    ->play('Wool Brook')->pay(4)
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":1}', 'It entered with a refinement counter')
    ->expectZoneCount('myMemory', 5, 1, 'Four paid plus one drawn into memory')
    ->tested('Wool Brook')
    ->covers(['on-enter', 'counter', 'draw-into-memory'], 'CARDNAME enters the field with a **refinement** counter on it.', '**On Enter:** Draw a card into your memory.')
    ->register();

gaScenario('wool-brook-pay-six-and-remove-a-refinement-counter-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Wool Brook')
    ->seed('myField', 'Wool Brook', properties: ['Counters' => ['refinement' => 1]])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(6)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The refinement counter was removed')
    ->expectZoneCount('myMemory', 8, 1, 'One card drawn into memory when it was seeded (its On Enter), six paid and one drawn')
    ->tested('Wool Brook')
    ->covers(['activated-ability', 'counter', 'draw-into-memory'], '(6)**, Remove a refinement counter from CARDNAME: **Draw a card into your memory.')
    ->register();

// ---- Palatial Concourse ----

gaScenario('palatial-concourse-glimpses-one-at-the-recollection-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Palatial Concourse')
    ->seed('myField', 'Palatial Concourse')
    ->deckTop(['Dungeon Guide'])
    ->endTurn()->endTurn(2)->pass(1)
    ->rejects(1, 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=', 'Glimpse 1: arranging two cards is refused', 100)
    ->answer(1, 'Top=em6eEh9q8y;Bottom=')
    ->expectDqEmpty()
    ->tested('Palatial Concourse')
    ->covers(['trigger', 'glimpse'], 'At the beginning of your recollection phase, **glimpse 1**.')
    ->register();

// ---- Oasis Trading Post ----

gaScenario('oasis-trading-post-three-rest-glimpses-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Oasis Trading Post')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide'])
    ->seed('myField', 'Oasis Trading Post')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectProperty('p1Field-1', 'Status', '1', 'The domain rested')
    ->tested('Oasis Trading Post')
    ->covers(['activated-ability', 'rest', 'glimpse'], '(3), [REST]: **Glimpse 2**.')
    ->register();

gaScenario('oasis-trading-post-four-rest-gathers')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Oasis Trading Post')
    ->seed('myField', 'Oasis Trading Post')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(4)
    ->expectZoneCount('myField', 3, 1, 'Gather: a plant token joined the champion and the domain')
    ->tested('Oasis Trading Post')
    ->covers(['activated-ability', 'rest', 'gather'], '(4), [REST]: **Gather**.')
    ->register();

gaScenario('oasis-trading-post-five-rest-summons-an-automaton-drone')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Oasis Trading Post')
    ->seed('myField', 'Oasis Trading Post')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:2')->pay(5)
    ->expectCardExists('myField', 'Automaton Drone', 'An Automaton Drone token was summoned')
    ->tested('Oasis Trading Post')
    ->covers(['activated-ability', 'rest', 'summon'], '(5), [REST]: **Summon** an Automaton Drone token.')
    ->register();

// ---- Epicurean Institute ----

gaScenario('epicurean-institute-recollection-sacrifices-it-and-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Epicurean Institute')
    ->seed('myField', 'Epicurean Institute')
    ->reachTurnThree()
    ->expectCardExists('myGraveyard', 'Epicurean Institute', 'The domain was sacrificed at the recollection phase')
    ->expectZoneCount('myHand', 9, 1, 'A card was drawn (7 + the turn draw + 1)')
    ->tested('Epicurean Institute')
    ->covers(['trigger', 'sacrifice', 'draw'], 'At the beginning of your recollection phase, sacrifice CARDNAME and draw a card.')
    ->register();

// ---- Mad Tea Party ----

gaScenario('mad-tea-party-an-ally-entering-recovers-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mad Tea Party', damage: 4)
    ->seed('myField', 'Mad Tea Party')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)
    ->expectDamage('p1Field-0', 3, 'Whenever an ally enters the field: recover 1 (4 - 1)')
    ->tested('Mad Tea Party')
    ->covers(['trigger', 'recover'], 'Whenever an ally enters the field, **recover 1**.')
    ->register();

// ---- Exalted Dorumegian Throne ----

gaScenario('exalted-dorumegian-throne-allies-get-plus-one-power-and-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Exalted Dorumegian Throne')
    ->seed('myField', 'Exalted Dorumegian Throne')
    ->seed('myField', 'Dungeon Guide')
    ->observe()
    ->expectPower('p1Field-2', 2, 'Allies you control get +1 power: 1 + 1')
    ->expectLife('p1Field-2', 4, 'Allies you control get +1 life: 3 + 1')
    ->tested('Exalted Dorumegian Throne')
    ->covers(['stat-change', 'static'], 'Allies you control get +1 [POWER] and +1 [LIFE].')
    ->register();

// ---- Tower of Dis ----

gaScenario('tower-of-dis-discorp-automaton-allies-get-plus-one-power-and-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tower of Dis')
    ->seed('myField', 'Tower of Dis')
    ->seed('myField', 'Haze Droid')
    ->observe()
    ->expectPower('p1Field-2', 3, 'A DisCorp Automaton ally: 2 + 1 power')
    ->tested('Tower of Dis')
    ->covers(['stat-change', 'static'], 'DisCorp Automaton allies you control get +1[POWER] and +1[LIFE].')
    ->register();

// ---- Chibi, Battle of Red Cliffs ----

gaScenario('chibi-battle-of-red-cliffs-ally-attack-declarations-cost-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Chibi, Battle of Red Cliffs')
    ->seed('myField', 'Chibi, Battle of Red Cliffs', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-2!FSM!')->pay(1, 2)->answer(2, 'theirField-0')
    ->expectZoneCount('myMemory', 1, 2, 'The attack declaration cost one card')
    ->tested('Chibi, Battle of Red Cliffs')
    ->covers(['restriction', 'cost'], 'Players can\'t declare attacks with allies unless they pay (1) for each attack declaration.')
    ->register();

// ---- Tokens ----

foreach ([['Automaton Drone', 1, 1], ['Baby Slime', 1, 1], ['Fledgling', 0, 1], ['Training Dummy', 0, 2]] as [$gaToken, $gaPower, $gaLife]) {
    $gaSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($gaToken)), '-');
    gaScenario("$gaSlug-token-has-its-printed-stats")
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->seed('myField', $gaToken)
        ->observe()
        ->expectPower('p1Field-1', $gaPower, "Printed $gaPower power")
        ->expectLife('p1Field-1', $gaLife, "Printed $gaLife life")
        ->tested($gaToken)
        ->covers(['vanilla'], "$gaToken (no rules text)")
        ->register();
}

gaScenario('training-dummy-cannot-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Training Dummy')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'The Dummy cannot attack: it stays awake')
    ->tested('Training Dummy')
    ->covers(['restriction'], 'CARDNAME can\'t attack.')
    ->register();

foreach ([['Blightroot', 'i0a5uhjxhk'], ['Manaroot', '5joh300z2s']] as [$gaToken, $gaId]) {
    $gaSlug = strtolower($gaToken);
    gaScenario("$gaSlug-sacrifice-gives-the-champion-plus-one-level-until-end-of-turn")
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->seed('myField', $gaToken)
        ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
        ->expectLevel('p1Field-0', 1, 'Sacrificed: +1 level until end of turn')
        ->tested($gaToken)
        ->covers(['activated-ability', 'sacrifice-self', 'level'], '**Sacrifice CARDNAME:** Your champion gets +1 level until end of turn.')
        ->register();
}

foreach (['Fraysia', 'Silvershine'] as $gaToken) {
    $gaSlug = strtolower($gaToken);
    gaScenario("$gaSlug-sacrifice-recovers-one")
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->readyFor($gaToken, damage: 3)
        ->seed('myField', $gaToken)
        ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
        ->expectDamage('p1Field-0', 2, 'Recover 1: 3 - 1')
        ->tested($gaToken)
        ->covers(['activated-ability', 'sacrifice-self', 'recover'], '**Sacrifice CARDNAME:** **Recover 1**.')
        ->register();
}

foreach (['Razorvine', 'Springleaf'] as $gaToken) {
    $gaSlug = strtolower($gaToken);
    gaScenario("$gaSlug-sacrifice-puts-a-hand-card-on-the-bottom-and-draws")
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->seed('myField', $gaToken)
        ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myHand-0')
        ->expectZoneCount('myHand', 7, 1, 'One card to the bottom, one drawn')
        ->tested($gaToken)
        ->covers(['activated-ability', 'sacrifice-self', 'zone-movement', 'draw'], '**Sacrifice CARDNAME:** Put a card from your hand on the bottom of your deck. If you do, draw a card.')
        ->register();
}

gaScenario('memorite-shardwing-has-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Memorite Shardwing', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Stealth: the attack cannot target the Shardwing')
    ->tested('Memorite Shardwing')
    ->covers(['keyword', 'stealth'], '**Stealth,**')
    ->register();

gaScenario('elysian-test-subject-has-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Elysian Test Subject', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Stealth: the attack cannot target the Elysian Test Subject')
    ->tested('Elysian Test Subject')
    ->covers(['keyword', 'stealth'], '**Stealth**')
    ->register();

gaScenario('powercell-pay-one-rest-and-sacrifice-gives-an-automaton-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Powercell')
    ->seed('myField', 'Delivery Droid')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->answer(1, 'myField-1')
    ->expectPower('p1Field-1', 3, '+2 power until end of turn: 1 + 2')
    ->tested('Powercell')
    ->covers(['activated-ability', 'sacrifice-self', 'stat-change', 'targeting'], '(1), [REST]**, Sacrifice CARDNAME:** Target Automaton ally gets +2 [POWER] until end of turn.')
    ->register();
