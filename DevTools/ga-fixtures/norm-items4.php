<?php
/**
 * Phase 3d (Norm): regalia, fourth batch (counters, discounts and the leftover weapons). 'quiet' deck.
 */

// ---- Veilara's Promise ----

gaScenario('veilaras-promise-activating-a-spell-puts-a-refinement-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Veilara\'s Promise')
    ->seed('myField', 'Veilara\'s Promise')
    ->seed('myHand', 'Purification')
    ->castSpell('Purification', pay: 1, window: '')
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":1}', 'Activating a Spell: a refinement counter')
    ->tested('Veilara\'s Promise')
    ->covers(['trigger', 'counter'], 'Whenever you activate a Spell card, put a **refinement** counter on CARDNAME.')
    ->register();

// ---- Alchemist's Kit ----

gaScenario('alchemists-kit-gathering-puts-a-refinement-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Alchemist\'s Kit')
    ->seed('myField', 'Alchemist\'s Kit')
    ->seed('myHand', 'Tend the Land')
    ->castSpell('Tend the Land', pay: 3, window: 'me')
    ->expectProperty('p1Field-1', 'Counters', '{"refinement":1}', 'Gathering: a refinement counter')
    ->tested('Alchemist\'s Kit')
    ->covers(['trigger', 'counter', 'gather'], 'Whenever you **gather**, put a **refinement** counter on CARDNAME.')
    ->register();

// ---- Temporal Spectrometer ----

gaScenario('temporal-spectrometer-rest-puts-a-time-counter-on-it')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Temporal Spectrometer')
    ->seed('myField', 'Temporal Spectrometer')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Counters', '{"time":1}', 'A time counter')
    ->expectProperty('p1Field-1', 'Status', '1', 'The Spectrometer rested')
    ->tested('Temporal Spectrometer')
    ->covers(['activated-ability', 'rest', 'counter', 'divine-relic'], '[REST]**:** Put a **time** counter on CARDNAME.')
    ->register();

// ---- Lotor Trinket ----

gaScenario('lotor-trinket-enter-scavenges-six-for-a-raccoon-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lotor Trinket')
    ->deckTop(['Dungeon Guide', 'Banner Raccoon'])
    ->seed('myField', 'Lotor Trinket')
    ->expectCardExists('myHand', 'Banner Raccoon', 'On Enter: the Raccoon ally within the top six was put into the hand')
    ->tested('Lotor Trinket')
    ->covers(['on-enter', 'scavenge'], '**On Enter:** **Scavenge 6** for a Raccoon ally card.')
    ->register();

gaScenario('lotor-trinket-class-bonus-raccoons-get-plus-one-power-while-the-opponent-has-no-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lotor Trinket', classBonus: true)
    ->seed('myField', 'Lotor Trinket')
    ->answer(1, 'PASS')
    ->seed('myField', 'Portly Raccoon')
    ->observe()
    ->expectPower('p1Field-2', 3, '[Class Bonus] The opponent has no graveyard: Raccoon allies get +1, 2 + 1')
    ->tested('Lotor Trinket')
    ->covers(['class-bonus', 'stat-change', 'condition'], '[Class Bonus] As long as an opponent has no cards in their graveyard, Raccoon allies you control get +1[POWER].')
    ->register();

// ---- Navigation Compass ----

gaScenario('navigation-compass-rest-and-discarding-a-domain-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Navigation Compass')
    ->seed('myField', 'Navigation Compass')
    ->seed('myHand', 'Hub of Innovation')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myHand-7')
    ->expectCardExists('myGraveyard', 'Hub of Innovation', 'The domain was discarded')
    ->expectZoneCount('myMemory', 1, 1, 'A card was drawn into memory')
    ->tested('Navigation Compass')
    ->covers(['activated-ability', 'rest', 'discard', 'draw-into-memory'], '[REST]**, Discard a domain card:** Draw a card into your memory.')
    ->register();

// ---- Wayfinder's Map ----

gaScenario('wayfinders-map-domain-cards-you-activate-cost-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Wayfinder\'s Map')
    ->seed('myField', 'Wayfinder\'s Map')
    ->seed('myHand', 'Hub of Innovation')
    ->play('Hub of Innovation')->pay(2)
    ->expectCardExists('myField', 'Hub of Innovation', 'A domain costs 1 less: 3 - 1 = 2')
    ->tested('Wayfinder\'s Map')
    ->covers(['static', 'cost-reduction'], 'Domain cards you activate cost 1 less to activate.')
    ->register();

// ---- VelTech Presidential Card ----

gaScenario('veltech-presidential-card-veltech-cards-you-activate-cost-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('VelTech Presidential Card')
    ->seed('myField', 'VelTech Presidential Card')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'CookTech Apron')
    ->play('CookTech Apron')->target('myField-2')->pay(1)
    ->expectLife('p1Field-2', 5, 'A VelTech item costs 1 less: 2 - 1 = 1, and the link applied')
    ->tested('VelTech Presidential Card')
    ->covers(['static', 'cost-reduction', 'divine-relic'], 'VelTech cards you activate cost 1 less to activate.')
    ->register();

// ---- Poisonous Apple ----

gaScenario('poisonous-apple-a-floating-memory-card-banished-from-your-graveyard-deals-two-unpreventable-damage')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(level: 0)
    ->seed('myField', 'Poisonous Apple')
    ->answer(1, 'myField-0')
    ->seed('myGraveyard', 'Ordinary Horse')
    ->endTurn()->endTurn(2)->target('myMaterial-0')->target('myGraveyard-0')
    ->expectDamage('p1Field-1', 2, 'Banishing the floating memory card for the level-up payment dealt 2 unpreventable damage to the champion')
    ->tested('Poisonous Apple')
    ->covers(['on-enter', 'control-change', 'trigger', 'damage', 'floating-memory'], '**On Enter**: Choose a player. That player gains control of Poisonous Apple.', 'Whenever a card with **floating memory** is banished from your graveyard, deal 2 unpreventable damage to your champion.')
    ->register();

// ---- Oathbreaker's Justice ----

gaScenario('oathbreakers-justice-attacking-with-it-costs-three-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Oathbreaker\'s Justice')
    ->seed('myField', 'Oathbreaker\'s Justice')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->pay(3)->pass(2)
    ->expectDamage('p2Field-0', 2, 'The Maul\'s printed 2 power hit the champion')
    ->expectZoneCount('myMemory', 3, 1, 'The additional (3) was paid')
    ->tested('Oathbreaker\'s Justice')
    ->covers(['additional-cost', 'weapon', 'combat'], 'As an additional cost to use this weapon for an attack, pay (3).')
    ->register();
