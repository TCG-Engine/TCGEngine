<?php
/**
 * Phase 3d (Norm): regalia, fifth batch (taxes, links and reveals). 'quiet' deck.
 */

// ---- Tithe Proclamation ----

gaScenario('tithe-proclamation-enter-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tithe Proclamation')
    ->seed('myField', 'Tithe Proclamation')
    ->observe()
    ->expectZoneCount('myHand', 8, 1, 'On Enter: a card was drawn')
    ->tested('Tithe Proclamation')
    ->covers(['on-enter', 'draw'], '**On Enter:** Draw a card.')
    ->register();

// ---- Map of Hidden Passage ----

gaScenario('map-of-hidden-passage-enters-the-field-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Map of Hidden Passage')
    ->seed('myField', 'Map of Hidden Passage')
    ->observe()
    ->expectProperty('p1Field-1', 'Status', '1', 'The Map entered the field rested')
    ->tested('Map of Hidden Passage')
    ->covers(['status'], 'CARDNAME enters the field rested.')
    ->register();

// ---- Dropped Band ----

gaNormItem('dropped-band-banish-revealing-three-chessman-cards-summons-a-pawn-piece', 'Dropped Band', 0, 0,
    fn($s) => $s->seed('myHand', 'Weiss Bishop')->seed('myHand', 'Weiss Bishop')->seed('myHand', 'Weiss Bishop'),
    fn($s) => $s->answer(1, 'YES')->expectCardExists('myField', 'Pawn Piece', 'Three Chessman cards were revealed: a Pawn Piece token was summoned'),
    ['activated-ability', 'reveal', 'summon', 'banish-self'], '**Banish CARDNAME:** You may reveal three Chessman cards from your hand and/or memory. If you do, **summon** a Pawn Piece token.');

// ---- Crimson Protective Trinket ----

gaNormItem('crimson-protective-trinket-banish-banishes-wind-cards-among-two-random-revealed-memory-cards', 'Crimson Protective Trinket', 0, 0,
    fn($s) => $s->seed('myMemory', 'Longtail Grovesward', player: 2)->seed('myMemory', 'Longtail Grovesward', player: 2),
    fn($s) => $s->expectZoneCount('theirMemory', 0, 1, 'Both wind cards were banished from the opponent\'s memory')->expectZoneCount('theirBanish', 2, 1, 'Into their owner\'s banishment'),
    ['activated-ability', 'reveal', 'banish', 'banish-self'], '**Banish CARDNAME:** Target opponent reveals two cards at random from their memory. Banish each wind element card revealed this way.');

// ---- Links: Forged Scalemail / Protective Helm / Prototype Shield ----

gaScenario('forged-scalemail-linked-unit-damage-may-banish-it-to-prevent-two-and-draw')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Forged Scalemail')
    ->seed('myField', 'Giant Tortoise')
    ->patch('myField-1', ['Subcards' => [GaScenario::id('Forged Scalemail')]])
    ->seed('myField', 'Forged Scalemail')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id('Giant Tortoise')]])
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectDamage('p1Field-1', 1, 'The Steed\'s 3 damage was reduced by 2')
    ->expectCardExists('myBanish', 'Forged Scalemail', 'The Scalemail banished itself')
    ->expectZoneCount('myHand', 8, 1, 'A card was drawn')
    ->tested('Forged Scalemail')
    ->covers(['link', 'prevention', 'draw', 'optional-choice', 'banish-self'], 'If damage would be dealt to linked unit, you may banish Forged Scalemail. If you do, prevent 2 of that damage and draw a card.')
    ->register();

gaScenario('prototype-shield-class-bonus-prevents-three-damage-to-the-linked-unit-while-it-is-attacking')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Prototype Shield', classBonus: true)
    ->seed('myField', 'Giant Tortoise')
    ->patch('myField-1', ['Subcards' => [GaScenario::id('Prototype Shield')]])
    ->seed('myField', 'Prototype Shield')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id('Giant Tortoise')]])
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectDamage('p1Field-1', 0, '[Class Bonus] The retaliation\'s 3 damage to the attacking linked unit was prevented')
    ->tested('Prototype Shield')
    ->covers(['link', 'class-bonus', 'prevention', 'combat'], '[Class Bonus] If damage would be dealt to linked unit while it’s attacking, prevent 3 of that damage.')
    ->register();

// ---- Imperial Seal ----

gaScenario('imperial-seal-banish-enables-all-basic-elements-until-end-of-turn')
    ->deckTemplate('spirit-dungeon-guide', 'Dungeon Guide')
    ->seed('myField', 'Imperial Seal')
    ->seed('myHand', 'Spark Fairy')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->play('Spark Fairy')->pay(3)
    ->expectCardExists('myField', 'Spark Fairy', 'All basic elements were enabled: the fire card was played by a water champion')
    ->tested('Imperial Seal')
    ->covers(['activated-ability', 'element', 'banish-self', 'divine-relic'], '**Banish CARDNAME:** All basic elements are enabled for you until end of turn.')
    ->register();

// ---- Protective Helm ----

gaScenario('protective-helm-prevents-one-damage-to-the-linked-unit-from-a-distant-source')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Protective Helm')
    ->seed('myField', 'Giant Tortoise')
    ->patch('myField-1', ['Subcards' => [GaScenario::id('Protective Helm')]])
    ->seed('myField', 'Protective Helm')
    ->patch('myField-2', ['Counters' => ['linkedToAlly' => GaScenario::id('Giant Tortoise')]])
    ->seed('myField', 'Hidden Longbowman', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->answer(2, 'myField-0')
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectDamage('p1Field-1', 2, 'Ranged 2 made the attack 1 + 2 = 3, and the Helm prevented 1 of it from the distant source')
    ->tested('Protective Helm')
    ->covers(['link', 'prevention', 'distant'], 'If damage would be dealt to linked unit from a **distant** source, prevent 1 of that damage.')
    ->register();

// ---- Art of War ----

gaScenario('art-of-war-an-ally-that-entered-this-turn-cannot-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Art of War')
    ->seed('myField', 'Art of War')
    ->seed('myHand', 'Dungeon Guide')
    ->reachTurnThree()
    ->play('Dungeon Guide')->pay(3)
    ->action(1, 10002, 'myField-2!FSM!')
    ->expectProperty('p1Field-2', 'Status', '2', 'The Guide entered this turn: it could not attack and stayed awake')
    ->tested('Art of War')
    ->covers(['divine-relic', 'restriction'], 'Players can\'t declare attacks with allies that entered the field this turn.')
    ->register();

// ---- Mandate of Honor ----

gaScenario('mandate-of-honor-with-a-unique-ally-a-player-with-influence-eight-cannot-draw')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Mandate of Honor')
    ->seed('myField', 'Mandate of Honor')
    ->seed('myField', 'Camil, Basked Abundance')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Indolent Leisure')
    ->castSpell('Indolent Leisure', pay: 3, window: '')
    ->expectZoneCount('myHand', 4, 1, 'Influence 8 or more with a unique ally in play: the draw did nothing (8 - 1 - 3)')
    ->tested('Mandate of Honor')
    ->covers(['static', 'influence', 'restriction', 'draw'], 'As long as you control a unique ally, each player with **influence** eight or more can\'t draw cards.')
    ->register();

// ---- Candlelight Hourglass ----

gaScenario('candlelight-hourglass-enter-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Candlelight Hourglass')
    ->seed('myField', 'Candlelight Hourglass')
    ->observe()
    ->expectZoneCount('myHand', 8, 1, 'On Enter: a card was drawn')
    ->tested('Candlelight Hourglass')
    ->covers(['on-enter', 'draw'], '**On Enter**: Draw a card.')
    ->register();
