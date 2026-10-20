<?php
/**
 * Phase 3d (Norm): regalia items, third batch. Same helper as norm-items.php (gaNormItem: the item is seeded first at myField-1, banish-self items leave the field when activated).
 */

gaNormItem('azure-protective-trinket-banish-banishes-up-to-three-fire-cards-from-a-graveyard', 'Azure Protective Trinket', 0, 0,
    fn($s) => $s->seed('myGraveyard', 'Spark Fairy', player: 2)->seed('myGraveyard', 'Spark Fairy', player: 2),
    fn($s) => $s->answer(1, 'theirGraveyard-0')->answer(1, 'theirGraveyard-0')->expectZoneCount('theirGraveyard', 0, 1, 'Both fire cards were banished')->expectZoneCount('theirBanish', 2, 1, 'Into their owner\'s banishment'),
    ['activated-ability', 'banish', 'targeting', 'banish-self'], '**Banish CARDNAME:** Banish up to three target fire element cards from a single graveyard.');

gaNormItem('duxal-proclamation-banish-with-no-opposing-allies-gives-allies-plus-one-power', 'Duxal Proclamation', 0, 0,
    fn($s) => $s->seed('myField', 'Dungeon Guide'),
    fn($s) => $s->expectPower('p1Field-1', 2, 'Each opponent controls no allies: allies get +1 power, 1 + 1'),
    ['activated-ability', 'stat-change', 'condition', 'banish-self'], '**Banish CARDNAME**: Allies you control get +1 [POWER] until end of turn. Activate this ability only if each opponent controls no allies.');

gaNormItem('duxal-proclamation-with-an-opposing-ally-cannot-be-activated', 'Duxal Proclamation', 0, 0,
    fn($s) => $s->seed('myField', 'Dungeon Guide', player: 2),
    fn($s) => $s->expectCardExists('myField', 'Duxal Proclamation', 'An opponent controls an ally: the ability was refused and the item stays'),
    ['activated-ability', 'condition'], 'Activate this ability only if each opponent controls no allies.');

gaNormItem('fan-of-insight-banish-returns-a-card-from-memory-to-hand', 'Fan of Insight', 0, 0,
    fn($s) => $s->seed('myMemory', 'Gray Wolf'),
    fn($s) => $s->answer(1, 'myMemory-0')->expectCardExists('myHand', 'Gray Wolf', 'The memory card returned to hand'),
    ['activated-ability', 'zone-movement', 'banish-self'], '**Banish CARDNAME:** Return a card from your memory to your hand.');

gaNormItem('orb-of-hubris-banish-draws-up-to-three-and-shuffles-that-many-back', 'Orb of Hubris', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectCardExists('myBanish', 'Orb of Hubris', 'The Orb banished itself'),
    ['activated-ability', 'draw', 'zone-movement', 'banish-self'], '**Banish CARDNAME:** Draw up to three cards, then shuffle that amount of cards from your hand into your deck.');

gaNormItem('sharpening-stone-banish-gives-a-weapon-plus-one-power-and-draws', 'Sharpening Stone', 0, 0,
    fn($s) => $s->seed('myField', 'Training Sword'),
    fn($s) => $s->answer(1, 'myField-1')->expectPower('p1Field-1', 2, 'The Sword got +1 power: 1 + 1')->expectZoneCount('myHand', 8, 1, 'A card was drawn'),
    ['activated-ability', 'stat-change', 'draw', 'targeting', 'banish-self'], '**Banish CARDNAME:** Up to one target Dagger or Sword weapon you control gets +1 [POWER] until end of turn. Draw a card.');

gaNormItem('apotheosis-rite-banish-makes-the-champion-an-ascendant-and-draws', 'Apotheosis Rite', 0, 0,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('myHand', 8, 1, 'A card was drawn')->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"type":"CHAMPION,ASCENDANT"}}', 'The champion became an Ascendant in addition to its other types'),
    ['activated-ability', 'draw', 'type-change', 'banish-self'], '**Banish CARDNAME:** Your champion becomes an Ascendant in addition to its other types. Draw a card.');

gaNormItem('oneiric-key-pay-four-and-banish-draws-into-memory', 'Oneiric Key', 0, 4,
    fn($s) => null,
    fn($s) => $s->expectZoneCount('myMemory', 5, 1, 'Four paid plus one drawn into memory'),
    ['activated-ability', 'draw-into-memory', 'banish-self'], '(4), **Banish CARDNAME:** Draw a card into your memory.');

gaNormItem('ghostsight-glass-pay-three-and-rest-gives-a-unit-true-sight', 'Ghostsight Glass', 0, 3,
    fn($s) => $s->seed('myField', 'Dungeon Guide'),
    fn($s) => $s->answer(1, 'myField-2')->expectProperty('p1Field-1', 'Status', '1', 'The Glass rested')->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","TRUE_SIGHT"]', 'The unit gained true sight'),
    ['activated-ability', 'rest', 'true-sight', 'targeting'], '(3), [REST]**:** Target unit gains **true sight** until end of turn.');

gaNormItem('fauna-friend-with-two-non-human-ally-cards-in-the-graveyard-gives-a-non-human-ally-plus-one-power', 'Fauna Friend', 0, 0,
    fn($s) => $s->seed('myGraveyard', 'Gray Wolf')->seed('myGraveyard', 'Gray Wolf')->seed('myField', 'Gray Wolf'),
    fn($s) => $s->answer(1, 'myField-2')->expectPower('p1Field-2', 3, 'A non-Human ally got +1 power: 2 + 1'),
    ['activated-ability', 'rest', 'stat-change', 'condition', 'targeting'], '[REST]**:** Target non-Human ally gets +1 [POWER] until end of turn. Activate this ability only if you have two or more non-Human ally cards in your graveyard.');

gaNormItem('cell-reactor-rest-and-sacrificing-a-powercell-buffs-an-automaton-ally', 'Cell Reactor', 0, 0,
    fn($s) => $s->seed('myField', 'Powercell')->seed('myField', 'Delivery Droid'),
    fn($s) => $s->answer(1, 'myField-2')->answer(1, 'myField-2')->pass(1)->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The Automaton ally (now at index 2) got a buff counter')->expectProperty('p1Field-1', 'Status', '1', 'The Reactor rested')->expectZoneCount('myField', 3, 1, 'The Powercell was sacrificed'),
    ['activated-ability', 'rest', 'sacrifice', 'counter', 'targeting'], '**[REST], Sacrifice a Powercell:** Put a **buff** counter on target Automaton ally.');

gaScenario('mercurial-heart-enter-puts-a-buff-counter-on-an-automaton-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mercurial Heart')
    ->seed('myField', 'Delivery Droid')
    ->seed('myField', 'Mercurial Heart')
    ->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'On Enter: the Automaton ally got a buff counter')
    ->tested('Mercurial Heart')
    ->covers(['on-enter', 'counter', 'targeting', 'divine-relic'], '**On Enter:** Put a **buff** counter on target Automaton ally you control.')
    ->register();

gaScenario('quickstep-treads-enter-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Quickstep Treads')
    ->seed('myField', 'Quickstep Treads')
    ->observe()
    ->expectZoneCount('myHand', 8, 1, 'On Enter: a card was drawn')
    ->tested('Quickstep Treads')
    ->covers(['on-enter', 'draw'], '**On Enter: **Draw a card.')
    ->register();

gaScenario('resplendent-kite-shield-enter-puts-one-refinement-counter-and-class-bonus-two')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Resplendent Kite Shield', classBonus: true)
    ->seed('myField', 'Resplendent Kite Shield')
    ->observe()
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":2}', '[Class Bonus] Two refinement counters')
    ->tested('Resplendent Kite Shield')
    ->covers(['on-enter', 'class-bonus', 'counter'], '**On Enter:** Put a **refinement** counter on CARDNAME. **Class Bonus:** Put two **refinement** counters on it instead.')
    ->register();

gaScenario('resplendent-kite-shield-without-the-class-bonus-enters-with-one-refinement-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Resplendent Kite Shield')
    ->seed('myField', 'Resplendent Kite Shield')
    ->observe()
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":1}', 'One refinement counter')
    ->tested('Resplendent Kite Shield')
    ->covers(['on-enter', 'class-bonus', 'counter', 'condition'], '**On Enter:** Put a **refinement** counter on CARDNAME.')
    ->register();

gaScenario('sages-urn-recollection-puts-an-age-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sage\'s Urn')
    ->seed('myField', 'Sage\'s Urn')
    ->reachTurnThree(fastCardInHand: true)
    ->expectProperty('p1Field-1', 'Counters', '{"age":1}', 'An age counter at the recollection phase')
    ->tested('Sage\'s Urn')
    ->covers(['trigger', 'counter'], 'At the beginning of your recollection phase, if there are less than four **age** counters on CARDNAME, put an **age** counter on it.')
    ->register();

gaScenario('prismatic-codex-recollection-puts-an-age-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Prismatic Codex')
    ->seed('myField', 'Prismatic Codex')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'Counters', '{"age":1}', 'An age counter at the recollection phase')
    ->tested('Prismatic Codex')
    ->covers(['trigger', 'counter'], 'At the beginning of your recollection phase, put an **age** counter on CARDNAME.')
    ->register();

gaScenario('sanguine-goblet-champion-damage-puts-that-many-blood-counters-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sanguine Goblet')
    ->seed('myField', 'Sanguine Goblet')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectProperty('p1Field-1', 'Counters', '{"blood":1}', 'One damage to the champion: one blood counter')
    ->tested('Sanguine Goblet')
    ->covers(['trigger', 'counter', 'damage'], 'Whenever your champion takes damage, put that many **blood** counters on CARDNAME.')
    ->register();

gaNormItem('sanguine-goblet-with-eight-blood-counters-banish-draws-a-card', 'Sanguine Goblet', 0, 0,
    fn($s) => $s->patch('myField-1', ['Counters' => ['blood' => 8]]),
    fn($s) => $s->expectZoneCount('myHand', 8, 1, 'Eight blood counters: a card was drawn'),
    ['activated-ability', 'draw', 'condition'], 'Activate this ability only if CARDNAME has eight or more **blood** counters on it.');

gaNormItem('channeling-stone-banish-makes-the-next-card-cost-two-less', 'Channeling Stone', 0, 0,
    fn($s) => $s->seed('myHand', 'Cell Production'),
    fn($s) => $s->play('Cell Production')->pay(1)->expectZoneCount('myField', 3, 1, 'Cell Production (3) cost 1 after the Stone: two Powercells arrived'),
    ['activated-ability', 'cost-reduction', 'banish-self'], '**Banish CARDNAME:** The next card you activate this turn costs 2 less to activate.');
