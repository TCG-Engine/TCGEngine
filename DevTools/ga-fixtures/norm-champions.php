<?php
/**
 * Phase 3d (Norm): champions. A champion seeded onto the field runs its On Enter (the shape of wind-champions.php); activated champion abilities are activated from p1Field-0 or the seeded champion.
 */

foreach (['Lost Spirit', 'Morrigan, Lost Spirit', 'Priscilla, Lost Spirit', 'Shira, Lost Spirit', 'Spirit of Chess'] as $gaSpirit) {
    $gaSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($gaSpirit)), '-');
    gaScenario("$gaSlug-enter-draws-seven-cards")
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->seed('myField', $gaSpirit)
        ->observe()
        ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
        ->tested($gaSpirit)
        ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
        ->register();
}

// ---- Nameless Champion (fifteen class pairings): the (6) ability, once ----

foreach (['0794z3ffck', '86flbytki3', '98i5ak5nwo', 'as8yfa8ptg', 'b53ccl9ipn', 'f4rlv5dsrb', 'j9fiu22ltl', 'jk9w4buhwk', 'k7sz76vn6u', 'mic7hijxlg', 'nq6nhjy85f', 'pv6ichyxj0', 'thaqwi9apy', 'ztjuymn2ge'] as $gaId) {
    gaScenario("nameless-champion-$gaId-six-draws-a-card-and-puts-a-level-counter-on-it-once")
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->champion(cardName: "#$gaId")
        ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->pay(6)
        ->expectZoneCount('myHand', 2, 1, 'Drew a card after paying six (7 + 0 - 6 + 1)')
        ->expectLevel('p1Field-0', 2, 'A level counter: the base level 1 plus 1')
        ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
        ->expectLevel('p1Field-0', 2, 'Activate this ability only once: no second level counter')
        ->tested("#$gaId")
        ->covers(['activated-ability', 'draw', 'counter', 'once-per-game'], '(6)**:** Draw a card and put a **level** counter on CARDNAME. Activate this ability only once.')
        ->register();
}

// ---- Allen, Beast Beckoner ----

gaScenario('allen-beast-beckoner-enter-glimpses-three-and-takes-a-revealed-harmony-or-melody')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->deckTop(['Steady Verse', 'Dungeon Guide', 'Dungeon Guide'])
    ->seed('myField', 'Allen, Beast Beckoner')
    ->rejects(1, 'Top=sbierp5k1v,em6eEh9q8y;Bottom=', 'Glimpse 3: arranging two cards is refused', 100)
    ->answer(1, 'Top=sbierp5k1v,em6eEh9q8y,em6eEh9q8y;Bottom=')
    ->expectCardExists('myHand', 'Steady Verse', 'The revealed Melody (Steady Verse) was put into the hand')
    ->tested('Allen, Beast Beckoner')
    ->covers(['on-enter', 'glimpse', 'reveal'], '**On Enter: Glimpse 3**, then reveal the top card of your deck. If that card is a Harmony or Melody card, put it into your hand.')
    ->register();

gaScenario('allen-beast-beckoner-with-two-beast-allies-gets-plus-two-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Gray Wolf')
    ->seed('myField', 'Gray Wolf')
    ->patch('myField-0', ['CardID' => GaScenario::id('Allen, Beast Beckoner')])
    ->observe()
    ->expectLevel('p1Field-0', 4, 'Two Beast allies: Allen\'s base level 2 plus 2')
    ->tested('Allen, Beast Beckoner')
    ->covers(['condition', 'level', 'stat-change'], 'As long as you control two or more Animal and/or Beast allies, CARDNAME gets +2 level.')
    ->register();

// ---- Tristan, Grim Stalker ----

gaScenario('tristan-grim-stalker-end-phase-puts-a-preparation-counter-on-him-while-awake')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Grim Stalker')
    ->endTurn()
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'Awake at the beginning of the end phase: a preparation counter')
    ->tested('Tristan, Grim Stalker')
    ->covers(['trigger', 'condition', 'counter'], 'At the beginning of your end phase, if Tristan is awake, put a **preparation** counter on Tristan.')
    ->register();

gaScenario('tristan-grim-stalker-rested-gets-no-preparation-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Tristan, Grim Stalker')
    ->patch('myField-0', ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Rested: no preparation counter')
    ->tested('Tristan, Grim Stalker')
    ->covers(['trigger', 'condition'], 'At the beginning of your end phase, if Tristan is awake')
    ->register();

// ---- Merlin, Memory Thief ----

gaScenario('merlin-memory-thief-rest-banishes-a-graveyard-card-and-a-floating-memory-card-gives-a-level-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Merlin, Memory Thief')
    ->seed('myGraveyard', 'Ordinary Horse', player: 2)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')->answer(1, 'theirGraveyard-0')
    ->expectZoneCount('theirGraveyard', 0, 1, 'The graveyard card was banished')
    ->expectProperty('p1Field-0', 'Counters', '{"level":1}', 'It has floating memory: a level counter went on Merlin')
    ->tested('Merlin, Memory Thief')
    ->covers(['activated-ability', 'banish', 'counter', 'condition', 'rest'], '[REST]**:** Choose a card from a graveyard and banish it. If that card has **floating memory**, put a **level** counter on CARDNAME.')
    ->register();
