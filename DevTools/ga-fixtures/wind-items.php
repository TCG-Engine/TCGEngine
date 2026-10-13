<?php
/**
 * Phase 3b (Wind): items and regalia. Written on the 'quiet' deck.
 */

// ---- Draw-into-memory items ----

foreach ([['Aenean Flux Generator', 3], ['Poisonous Breezecap', 3], ['Whisperwind Compass', 3]] as [$gaItem, $gaCost]) {
    $gaSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($gaItem)), '-');
    gaScenario("$gaSlug-on-enter-draws-a-card-into-memory")
        ->deckTemplate('quiet', $gaItem)
        ->readyFor($gaItem)
        ->seed('myHand', $gaItem)
        ->play($gaItem)->pay($gaCost)
        ->expectZoneCount('myMemory', $gaCost + 1, 1, "The $gaCost paid cards plus the card drawn into memory")
        ->tested($gaItem)
        ->covers(['on-enter', 'draw-into-memory'], '**On Enter: **Draw a card into your memory.')
        ->register();
}

// ---- Whisperwind Compass (Class Bonus) ----

gaScenario('whisperwind-compass-class-bonus-buffs-a-ranger-ally-that-becomes-distant')
    ->deckTemplate('quiet', 'Whisperwind Compass')
    ->readyFor('Whisperwind Compass', classBonus: true)
    ->seed('myField', 'Whisperwind Compass')
    ->seed('myField', 'Poised Bowman')
    ->seed('myHand', 'Winbless Rangefinder')
    ->play('Winbless Rangefinder')->pay(2)->answer(1, 'myField-2')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The Ranger ally became distant with no buff counters: it got one')
    ->tested('Whisperwind Compass')
    ->covers(['class-bonus', 'trigger', 'distant', 'counter'], '[Class Bonus] Whenever a Ranger ally you control becomes **distant**, if that ally has no **buff** counters on it, put a **buff** counter on it.')
    ->register();

gaScenario('whisperwind-compass-a-ranger-ally-that-already-has-a-buff-counter-gets-no-second')
    ->deckTemplate('quiet', 'Whisperwind Compass')
    ->readyFor('Whisperwind Compass', classBonus: true)
    ->seed('myField', 'Whisperwind Compass')
    ->seed('myField', 'Poised Bowman', properties: ['Counters' => ['buff' => 1]])
    ->seed('myHand', 'Winbless Rangefinder')
    ->play('Winbless Rangefinder')->pay(2)->answer(1, 'myField-2')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'It already had a buff counter: still one')
    ->tested('Whisperwind Compass')
    ->covers(['class-bonus', 'trigger', 'condition'], 'if that ally has no **buff** counters on it')
    ->register();

// ---- Speed Potion ----

gaScenario('speed-potion-brewed-on-enter-draws-a-card')
    ->deckTemplate('quiet', 'Speed Potion')
    ->readyFor('Speed Potion')
    ->seed('myField', 'Manaroot')->seed('myField', 'Blightroot')
    ->seed('myHand', 'Speed Potion')
    ->play('Speed Potion')->answer(1, 'YES')->target('myField-1')->target('myField-2')
    ->expectZoneCount('myHand', 8, 1, 'Brewed (no reserve cost): 8 - 1 played + the drawn card')
    ->expectZoneCount('myMemory', 0, 1, 'Brewed: nothing was paid from the hand')
    ->tested('Speed Potion')
    ->covers(['brew', 'on-enter', 'draw'], '**Brew —** Two Herbs', '**On Enter:** If CARDNAME was **brewed**, draw a card.')
    ->register();

gaScenario('speed-potion-sacrifice-gains-agility-three-this-turn')
    ->deckTemplate('quiet', 'Speed Potion')
    ->readyFor('Speed Potion')
    ->seed('myField', 'Speed Potion')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectGlobalEffect(1, 'AGILITY_3', 'Sacrificing the potion gains agility 3 this turn')
    ->expectZoneCount('myField', 1, 1, 'The potion was sacrificed')
    ->tested('Speed Potion')
    ->covers(['sacrifice', 'agility'], '**Sacrifice CARDNAME:** You gain **agility 3** for this turn.')
    ->register();

// ---- Wildgrowth Elixir ----

gaScenario('wildgrowth-elixir-recollection-puts-an-age-counter-on-it')
    ->deckTemplate('quiet', 'Wildgrowth Elixir')
    ->readyFor('Wildgrowth Elixir')
    ->seed('myField', 'Wildgrowth Elixir')
    ->reachTurnThree(fastCardInHand: true)
    ->expectProperty('p1Field-1', 'Counters', '{"age":1}', 'At the beginning of the recollection phase: one age counter')
    ->tested('Wildgrowth Elixir')
    ->covers(['recollection', 'counter'], 'At the beginning of your recollection phase, put an **age** counter on CARDNAME.')
    ->register();

gaScenario('wildgrowth-elixir-sacrifice-puts-buff-counters-equal-to-its-age-counters')
    ->deckTemplate('quiet', 'Wildgrowth Elixir')
    ->readyFor('Wildgrowth Elixir')
    ->seed('myField', 'Wildgrowth Elixir', properties: ['Counters' => ['age' => 3]])
    ->seed('myField', 'Dungeon Guide')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":3}', 'Three age counters: three buff counters (the potion left, so the Guide moved down)')
    ->tested('Wildgrowth Elixir')
    ->covers(['sacrifice', 'counter', 'targeting', 'scaling'], '**Sacrifice CARDNAME:** Put **X buff** counters on target ally, where **X** is the amount of **age** counters on CARDNAME.')
    ->register();

// ---- Windwalker Boots ----

gaScenario('windwalker-boots-class-bonus-end-phase-puts-a-preparation-counter-on-an-awake-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Windwalker Boots', classBonus: true)
    ->seed('myField', 'Windwalker Boots')
    ->endTurn()
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"preparation":1}', 'Awake champion: a preparation counter at the beginning of the end phase')
    ->tested('Windwalker Boots')
    ->covers(['class-bonus', 'end-phase', 'counter', 'condition'], '[Class Bonus] At the beginning of your end phase, if your champion is awake, put a **preparation** counter on them.')
    ->register();

gaScenario('windwalker-boots-banish-draws-a-card-with-five-preparation-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Windwalker Boots')
    ->patch('myField-0', ['Counters' => ['preparation' => 5]])
    ->seed('myField', 'Windwalker Boots')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myHand', 8, 1, 'Banished the Boots to draw a card')
    ->expectCardExists('myBanish', 'Windwalker Boots', 'The Boots were banished')
    ->tested('Windwalker Boots')
    ->covers(['banish', 'draw', 'counter', 'condition'], '**Banish CARDNAME:** Draw a card. Activate this ability only if your champion has five or more **preparation** counters on them.')
    ->register();

gaScenario('windwalker-boots-banish-needs-five-preparation-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Windwalker Boots')
    ->patch('myField-0', ['Counters' => ['preparation' => 4]])
    ->seed('myField', 'Windwalker Boots')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myHand', 7, 1, 'Four counters: refused, no card drawn')
    ->expectZoneCount('myBanish', 0, 1, 'The Boots stayed on the field')
    ->tested('Windwalker Boots')
    ->covers(['banish', 'condition'], 'Activate this ability only if your champion has five or more **preparation** counters on them.')
    ->register();

// ---- Effigy of Gaia ----

gaScenario('effigy-of-gaia-class-bonus-with-an-animal-ally-gives-your-champion-plus-one-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Effigy of Gaia', classBonus: true)
    ->seed('myField', 'Effigy of Gaia')
    ->seed('myField', 'Gale\'s Mare')
    ->endTurn()
    ->expectLevel('p1Field-0', 1, 'Class Bonus and an Animal ally: the champion is level 0 + 1')
    ->tested('Effigy of Gaia')
    ->covers(['class-bonus', 'level', 'static-ability', 'condition'], '[Class Bonus] Your champion gets +1 level as long as you control an Animal or Beast ally.')
    ->register();

gaScenario('effigy-of-gaia-without-an-animal-ally-gives-no-level')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Effigy of Gaia', classBonus: true)
    ->seed('myField', 'Effigy of Gaia')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->expectLevel('p1Field-0', 0, 'No Animal or Beast ally: no extra level')
    ->tested('Effigy of Gaia')
    ->covers(['class-bonus', 'level', 'condition'], 'as long as you control an Animal or Beast ally')
    ->register();

// ---- Liquid Amnesia ----

gaScenario('liquid-amnesia-class-bonus-recollection-puts-an-age-counter-on-it')
    ->deckTemplate('quiet', 'Liquid Amnesia')
    ->readyFor('Liquid Amnesia', classBonus: true)
    ->seed('myField', 'Liquid Amnesia')
    ->reachTurnThree(fastCardInHand: true)
    ->expectProperty('p1Field-1', 'Counters', '{"age":1}', 'Class Bonus: an age counter at the beginning of the recollection phase')
    ->tested('Liquid Amnesia')
    ->covers(['class-bonus', 'recollection', 'counter'], '[Class Bonus] At the beginning of your recollection phase, put an **age** counter on CARDNAME.')
    ->register();

gaScenario('liquid-amnesia-without-the-class-bonus-gets-no-age-counter')
    ->deckTemplate('quiet', 'Liquid Amnesia')
    ->readyFor('Liquid Amnesia')
    ->seed('myField', 'Liquid Amnesia')
    ->reachTurnThree(fastCardInHand: true)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No class bonus: no age counter')
    ->tested('Liquid Amnesia')
    ->covers(['class-bonus', 'recollection', 'condition'], '[Class Bonus] At the beginning of your recollection phase')
    ->register();

gaScenario('liquid-amnesia-sacrifice-banishes-a-random-memory-card-per-three-age-counters')
    ->deckTemplate('quiet', 'Liquid Amnesia')
    ->readyFor('Liquid Amnesia')
    ->seed('myField', 'Liquid Amnesia', properties: ['Counters' => ['age' => 6]])
    ->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)->seed('myMemory', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('theirMemory', 1, 1, 'Six age counters: two of the three memory cards were banished')
    ->expectZoneCount('theirBanish', 2, 1, 'Two cards in the opponent\'s banishment')
    ->expectZoneCount('myField', 1, 1, 'The Liquid Amnesia was sacrificed')
    ->tested('Liquid Amnesia')
    ->covers(['sacrifice', 'banish', 'counter', 'scaling'], '**Sacrifice CARDNAME:** For every three **age** counters that were on CARDNAME, banish a card at random from target player’s memory.')
    ->register();

// ---- Cloudstone Orb ----

// Regalia come from the material deck, so the Orb is seeded onto the field (which runs its On Enter).
gaScenario('cloudstone-orb-on-enter-empowers-by-the-number-of-wind-non-champion-objects')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cloudstone Orb')
    ->seed('myField', 'Windrider Mage')
    ->seed('myField', 'Cloudstone Orb')
    ->observe()
    ->expectProperty('p1Field-0', 'TurnEffects', '["ygqehvpblj-2","EMPOWERED"]', 'Empower 2: the Mage and the Orb are the two wind non-champion objects')
    ->tested('Cloudstone Orb')
    ->covers(['on-enter', 'empower', 'scaling'], '**On Enter:** **Empower X**, where **X** is the amount of wind element non-champion objects you control.')
    ->register();

gaScenario('cloudstone-orb-class-bonus-pays-three-to-return-to-the-material-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cloudstone Orb', classBonus: true)
    ->seed('myField', 'Cloudstone Orb')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)
    ->expectZoneCount('myField', 1, 1, 'The Orb left the field')
    ->expectCardExists('myMaterial', 'Cloudstone Orb', 'It returned to the material deck')
    ->tested('Cloudstone Orb')
    ->covers(['class-bonus', 'zone-movement', 'activated-ability'], '[Class Bonus] (3)**:** Return CARDNAME to its owner\'s material deck.')
    ->register();

// ---- Weapons: Ceremonial Stormblade, Zephyr's Edge, Emerald Pistol ----
// Weapons are regalia, so they are seeded onto the field (seeding runs the On Enter).

gaScenario('ceremonial-stormblade-class-bonus-on-enter-puts-a-durability-counter-per-wind-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ceremonial Stormblade', classBonus: true)
    ->seed('myField', 'Windrider Mage')->seed('myField', 'Windrider Mage')->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Ceremonial Stormblade')
    ->observe()
    ->expectProperty('p1Field-4', 'Counters', '{"durability":3}', 'The weapon\'s own durability counter plus one per wind ally (two Mages; the Dungeon Guide is not wind)')
    ->tested('Ceremonial Stormblade')
    ->covers(['on-enter', 'class-bonus', 'counter', 'scaling'], '[Class Bonus] **On Enter:** Put a **durability** counter on CARDNAME for each wind element ally you control.')
    ->register();

gaScenario('ceremonial-stormblade-without-the-class-bonus-gets-no-durability-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ceremonial Stormblade')
    ->seed('myField', 'Windrider Mage')
    ->seed('myField', 'Ceremonial Stormblade')
    ->observe()
    ->expectProperty('p1Field-2', 'Counters', '{"durability":1}', 'No class bonus: only the weapon\'s own durability counter')
    ->tested('Ceremonial Stormblade')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter:**')
    ->register();

gaScenario('zephyrs-edge-class-bonus-on-enter-outside-the-materialize-phase-gets-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Zephyr\'s Edge', classBonus: true)
    ->seed('myField', 'Zephyr\'s Edge')
    ->observe()
    ->expectPower('p1Field-1', 2, 'Not the materialize phase: 1 + 1 power')
    ->tested('Zephyr\'s Edge')
    ->covers(['on-enter', 'class-bonus', 'stat-change', 'condition'], '[Class Bonus] **On Enter:** If it\'s not your materialize phase, CARDNAME gets +1 [POWER] until end of turn.')
    ->register();

gaScenario('zephyrs-edge-without-the-class-bonus-has-its-printed-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Zephyr\'s Edge')
    ->seed('myField', 'Zephyr\'s Edge')
    ->observe()
    ->expectPower('p1Field-1', 1, 'No class bonus: printed 1 power')
    ->tested('Zephyr\'s Edge')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter:**')
    ->register();

// ---- Emerald Pistol ----

gaScenario('emerald-pistol-class-bonus-on-enter-materializes-a-memory-cost-zero-bullet')
    ->deck("# Material\n1 Spirit of Fire\n1 Plated Bullet\n1 Steel Slug\n# Main\n12 Dungeon Guide\n4 Fluffy Shopkeep")
    ->readyFor('Emerald Pistol', classBonus: true)
    ->seed('myField', 'Emerald Pistol')
    ->answer(1, 'myMaterial-0')
    ->expectCardExists('myField', 'Plated Bullet', 'The memory cost 0 Bullet was materialized (Steel Slug costs 1 memory, so it was not offered)')
    ->expectZoneCount('myMaterial', 1, 1, 'Only the Steel Slug is left in the material deck')
    ->tested('Emerald Pistol')
    ->covers(['on-enter', 'class-bonus', 'materialize'], '[Class Bonus] **On Enter:** Materialize a Bullet card with memory cost 0 from your material deck.')
    ->register();

// ---- Bullets: Windpiercer, Turbulent Bullet, Vanishing Shot (load into a Gun) ----

foreach ([['Windpiercer', '**Renewable**'], ['Turbulent Bullet', '**Renewable**'], ['Vanishing Shot', '[REST]**:** Load CARDNAME into target unloaded Gun weapon you control.']] as [$gaBullet, $gaClause]) {
    $gaSlug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($gaBullet)), '-');
    gaScenario("$gaSlug-rests-to-load-itself-into-an-unloaded-gun")
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->readyFor($gaBullet)
        ->seed('myField', 'Emerald Pistol')
        ->seed('myField', $gaBullet)
        ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->answer(1, 'myField-1')
        ->expectProperty('p1Field-1', 'Subcards', '["' . GaScenario::id($gaBullet) . '"]', 'The bullet is loaded into the Gun')
        ->expectZoneCount('myField', 2, 1, 'The bullet left the field: only the champion and the Gun remain')
        ->tested($gaBullet)
        ->covers(['load', 'rest', 'targeting'], '[REST]**:** Load CARDNAME into target unloaded Gun weapon you control.')
        ->register();
}

// ---- Windpiercer (loaded: On Attack) ----

gaScenario('windpiercer-on-attack-glimpses-one-and-a-wind-card-on-top-gives-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Windpiercer')
    ->deckTop(['Dungeon Guide', 'Windrider Mage']) // player 1 draws the first on turn 3, so the Mage is on top for the glimpse
    ->seed('myField', 'Emerald Pistol', properties: ['Subcards' => ['hreqhj1trn']])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(1, 'Top=ZfCtSldRIy;Bottom=')
    ->expectDamage('p2Field-0', 4, 'A wind card on top: Pistol 1 + Windpiercer 1 + 2 = 4 damage')
    ->tested('Windpiercer')
    ->covers(['on-attack', 'glimpse', 'stat-change', 'condition'], '**On Attack:** **Glimpse 1**, then reveal the top card of your deck. If that card is wind element, CARDNAME gets +2 [POWER].')
    ->register();

gaScenario('windpiercer-on-attack-a-non-wind-card-on-top-gives-no-bonus')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Windpiercer')
    ->deckTop(['Windrider Mage', 'Dungeon Guide'])
    ->seed('myField', 'Emerald Pistol', properties: ['Subcards' => ['hreqhj1trn']])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(1, 'Top=em6eEh9q8y;Bottom=')
    ->expectDamage('p2Field-0', 2, 'A non-wind card on top: Pistol 1 + Windpiercer 1 = 2 damage')
    ->tested('Windpiercer')
    ->covers(['on-attack', 'glimpse', 'condition'], 'If that card is wind element, CARDNAME gets +2 [POWER].')
    ->register();

// ---- Turbulent Bullet / Vanishing Shot (loaded: On Hit) ----

gaScenario('turbulent-bullet-class-bonus-on-hit-gives-up-to-two-allies-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Turbulent Bullet', classBonus: true)
    ->seed('myField', 'Emerald Pistol', properties: ['Subcards' => [GaScenario::id('Turbulent Bullet')]])
    ->seed('myField', 'Dungeon Guide')->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-0')->answer(1, 'myField-2')->answer(1, 'myField-3')
    ->expectPower('p1Field-2', 2, 'On Hit: the first Dungeon Guide got +1 power (1 + 1)')
    ->expectPower('p1Field-3', 2, 'On Hit: up to two allies: the second Dungeon Guide got +1 power too')
    ->tested('Turbulent Bullet')
    ->covers(['on-hit', 'class-bonus', 'stat-change', 'targeting'], '[Class Bonus] **On Hit:** Up to two target allies you control get +1 [POWER] until end of turn.')
    ->register();

gaScenario('vanishing-shot-on-ally-hit-may-return-the-hit-ally-to-its-owners-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vanishing Shot')
    ->seed('myField', 'Emerald Pistol', properties: ['Subcards' => [GaScenario::id('Vanishing Shot')]])
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->target('myField-1')->target('theirField-1')->pass(2)->answer(1, 'YES')
    ->expectCardExists('theirMemory', 'Giant Tortoise', 'The hit ally returned to its owner\'s memory')
    ->expectZoneCount('theirField', 1, 1, 'Only the champion is left on the opponent\'s field')
    ->tested('Vanishing Shot')
    ->covers(['on-hit', 'zone-movement', 'optional'], '**On Ally Hit:** You may return the hit ally to its owner\'s memory.')
    ->register();

// ---- Purifying Thurible / Windspire Crest / Gustmark Gauge / Bell of the Chosen ----

gaScenario('purifying-thurible-banishes-three-cards-from-the-opponents-graveyard-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Purifying Thurible')
    ->seed('myField', 'Purifying Thurible')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, '3')->pay(3)
    ->answer(2, 'myGraveyard-0')->answer(2, 'myGraveyard-0')->answer(2, 'myGraveyard-0')
    ->expectZoneCount('theirGraveyard', 1, 1, 'Three of four graveyard cards were banished by their owner')
    ->expectZoneCount('theirBanish', 3, 1, 'Three cards in the opponent\'s banishment')
    ->expectZoneCount('myMemory', 4, 1, 'Three paid cards plus the card drawn into memory (X was 3 or more)')
    ->expectCardExists('myBanish', 'Purifying Thurible', 'The Thurible banished itself')
    ->tested('Purifying Thurible')
    ->covers(['activated-ability', 'banish', 'draw-into-memory', 'scaling'], '(X)**, Banish CARDNAME:** Target opponent banishes **X** cards from their graveyard. If **X **is 3 or greater, draw a card into your memory.')
    ->register();

gaScenario('purifying-thurible-banishing-two-cards-draws-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Purifying Thurible')
    ->seed('myField', 'Purifying Thurible')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, '2')->pay(2)
    ->answer(2, 'myGraveyard-0')->answer(2, 'myGraveyard-0')
    ->expectZoneCount('theirBanish', 2, 1, 'Two cards in the opponent\'s banishment')
    ->expectZoneCount('myMemory', 2, 1, 'X was 2: only the two paid cards, nothing drawn')
    ->tested('Purifying Thurible')
    ->covers(['activated-ability', 'banish', 'condition'], 'If **X **is 3 or greater, draw a card into your memory.')
    ->register();

gaScenario('windspire-crest-pays-four-and-banishes-itself-to-draw-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Windspire Crest')
    ->seed('myField', 'Windspire Crest')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:1')->pay(4)
    ->expectZoneCount('myHand', 4, 1, 'Four paid and one drawn: 7 - 4 + 1')
    ->expectCardExists('myBanish', 'Windspire Crest', 'The Crest banished itself')
    ->tested('Windspire Crest')
    ->covers(['activated-ability', 'banish', 'draw'], '(4), **Banish CARDNAME**: Draw a card.')
    ->register();

gaScenario('gustmark-gauge-pays-two-and-rests-to-glimpse-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gustmark Gauge')
    ->deckTop(['Dungeon Guide'])
    ->seed('myField', 'Gustmark Gauge')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->answer(1, 'Top=em6eEh9q8y;Bottom=')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Gauge rested for its ability')
    ->expectZoneCount('myMemory', 2, 1, 'The (2) cost was paid')
    ->tested('Gustmark Gauge')
    ->covers(['activated-ability', 'rest', 'glimpse'], '(2), [REST]: **Glimpse 1**.')
    ->register();

gaScenario('bell-of-the-chosen-on-enter-puts-a-quest-counter-on-your-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bell of the Chosen')
    ->seed('myField', 'Bell of the Chosen')
    ->observe()
    ->expectProperty('p1Field-0', 'Counters', '{"quest":1}', 'On Enter: a quest counter on the champion')
    ->tested('Bell of the Chosen')
    ->covers(['on-enter', 'counter'], '**On Enter**: Put a **quest** counter on your champion.')
    ->register();

// ---- Links: GustTech Shield, StrideTech W ----

gaScenario('gusttech-shield-links-to-an-ally-which-gets-vigor')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('GustTech Shield')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myHand', 'GustTech Shield')
    ->play('GustTech Shield')->answer(1, 'myField-1')->pay(3)
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'Linked ally has vigor: it woke at the end phase')
    ->tested('GustTech Shield')
    ->covers(['ally-link', 'vigor'], '**Ally Link**', 'Linked ally has **vigor**.')
    ->register();

gaScenario('stridetech-w-on-enter-with-the-linked-ally-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('StrideTech W')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'StrideTech W')
    ->play('StrideTech W')->answer(1, 'myField-1')->pay(2)
    ->expectZoneCount('myMemory', 3, 1, 'Two paid cards plus the card drawn into memory')
    ->tested('StrideTech W')
    ->covers(['ally-link', 'on-enter', 'draw-into-memory', 'condition'], '**Ally Link**', '**On Enter:** If you control the linked ally, draw a card into your memory.')
    ->register();

gaScenario('stridetech-w-pays-one-and-sacrifices-itself-to-return-the-linked-ally-to-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('StrideTech W')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'StrideTech W')
    ->play('StrideTech W')->answer(1, 'myField-1')->pay(2)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(1)
    ->expectCardExists('myMemory', 'Dungeon Guide', 'The linked ally was returned to its owner\'s memory')
    ->expectZoneCount('myField', 1, 1, 'Only the champion is left on the field')
    ->expectCardExists('myGraveyard', 'StrideTech W', 'StrideTech W was sacrificed')
    ->tested('StrideTech W')
    ->covers(['ally-link', 'sacrifice', 'zone-movement'], '**(1), Sacrifice CARDNAME: **Return the linked ally to its owner\'s memory.')
    ->register();
