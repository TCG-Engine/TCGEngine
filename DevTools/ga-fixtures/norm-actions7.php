<?php
/**
 * Phase 3d (Norm): actions and phantasias, seventh batch. 'quiet' deck.
 */

// ---- Strategic Warfare ----

gaScenario('strategic-warfare-allies-get-plus-one-power-until-end-of-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Strategic Warfare')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Strategic Warfare')
    ->castSpell('Strategic Warfare', pay: 2, window: '')
    ->expectPower('p1Field-1', 2, 'Allies you control get +1 power: 1 + 1')
    ->tested('Strategic Warfare')
    ->covers(['stat-change'], 'Allies you control get +1 [POWER] until end of turn.')
    ->register();

gaScenario('strategic-warfare-level-two-gives-a-unit-true-sight')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Strategic Warfare', level: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Strategic Warfare')
    ->castSpell('Strategic Warfare', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","i1f0ht2tsn_SIGHT"]', '[Level 2+] The unit gained true sight')
    ->tested('Strategic Warfare')
    ->covers(['level-gate', 'true-sight', 'targeting'], '[Level 2+] Target unit gains **true sight** until end of turn.')
    ->register();

// ---- Withering Grasp / Aphotic Ruin ----

gaScenario('withering-grasp-puts-a-wither-counter-on-a-non-champion-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Withering Grasp')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Withering Grasp')
    ->castSpell('Withering Grasp', pay: 2, window: '', target: 'theirField-1')
    ->expectProperty('p2Field-1', 'Counters', '{"wither":1}', 'A wither counter went on the target')
    ->tested('Withering Grasp')
    ->covers(['counter', 'wither', 'targeting'], 'Put a **wither** counter on target non-champion object.')
    ->register();

gaScenario('aphotic-ruin-puts-four-wither-counters-on-a-non-champion-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Aphotic Ruin')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Aphotic Ruin')
    ->castSpell('Aphotic Ruin', pay: 6, window: '', target: 'theirField-1')
    ->expectProperty('p2Field-1', 'Counters', '{"wither":4}', 'Four wither counters went on the target')
    ->tested('Aphotic Ruin')
    ->covers(['counter', 'wither', 'targeting'], 'Put four **wither** counters on target non-champion object.')
    ->register();

// ---- Indolent Leisure ----

gaScenario('indolent-leisure-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Indolent Leisure')
    ->seed('myHand', 'Indolent Leisure')
    ->castSpell('Indolent Leisure', pay: 3, window: '')
    ->expectZoneCount('myHand', 5, 1, 'A card was drawn (8 - 1 - 3 + 1)')
    ->tested('Indolent Leisure')
    ->covers(['draw'], 'Draw a card.')
    ->register();

gaScenario('indolent-leisure-class-bonus-level-three-also-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Indolent Leisure', classBonus: true, level: 3)
    ->seed('myHand', 'Indolent Leisure')
    ->castSpell('Indolent Leisure', pay: 3, window: '')
    ->expectZoneCount('myMemory', 4, 1, '[Class Bonus] [Level 3+] Three paid plus one drawn into memory')
    ->tested('Indolent Leisure')
    ->covers(['class-bonus', 'level-gate', 'draw-into-memory'], '[Class Bonus] [Level 3+] Draw a card into your memory.')
    ->register();

// ---- Sleight of Hand ----

gaScenario('sleight-of-hand-gives-a-suited-ally-stealth-and-class-bonus-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sleight of Hand', classBonus: true)
    ->seed('myField', 'Two of Spades')
    ->seed('myHand', 'Sleight of Hand')
    ->castSpell('Sleight of Hand', pay: 2, window: 'me')->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH"]', 'The Suited ally gained stealth')
    ->expectZoneCount('myMemory', 3, 1, '[Class Bonus] Two paid plus one drawn into memory')
    ->tested('Sleight of Hand')
    ->covers(['stealth', 'class-bonus', 'draw-into-memory', 'targeting'], 'Target Suited ally you control gains **stealth** until end of turn.', '[Class Bonus] Draw a card into your memory.')
    ->register();

// ---- Vantage Point ----

gaScenario('vantage-point-makes-the-target-unit-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vantage Point')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Vantage Point')
    ->castSpell('Vantage Point', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'The unit became distant')
    ->tested('Vantage Point')
    ->covers(['distant', 'targeting'], 'Target unit becomes **distant**.')
    ->register();

// ---- Gunsmith's Arsenal ----

gaScenario('gunsmiths-arsenal-class-bonus-costs-two-less-and-materializes-a-gun')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gunsmith\'s Arsenal', classBonus: true)
    ->seed('myMaterial', 'Prototype Pistol')
    ->seed('myHand', 'Gunsmith\'s Arsenal')
    ->castSpell('Gunsmith\'s Arsenal', pay: 2, window: '')->answer(1, 'myMaterial-4')
    ->expectCardExists('myField', 'Prototype Pistol', 'Only the Gun was offered and it was materialized')
    ->expectLevel('p1Field-0', 0, 'No champion was materialized')
    ->tested('Gunsmith\'s Arsenal')
    ->covers(['class-bonus', 'cost-reduction', 'materialize'], 'Materialize a Bullet or Gun card from your material deck.')
    ->register();

// ---- Healing Aura ----

gaScenario('healing-aura-recovers-one-at-the-recollection-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Healing Aura', damage: 3)
    ->seed('myField', 'Healing Aura')
    ->reachTurnThree()
    ->expectDamage('p1Field-0', 2, 'Recover 1 at the beginning of the recollection phase (3 - 1)')
    ->tested('Healing Aura')
    ->covers(['trigger', 'recover'], 'At the beginning of your recollection phase, **recover 1**.')
    ->register();

gaScenario('healing-aura-class-bonus-costs-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Healing Aura', classBonus: true)
    ->seed('myHand', 'Healing Aura')
    ->castSpell('Healing Aura', pay: 3, window: '')
    ->expectCardExists('myField', 'Healing Aura', '[Class Bonus] 4 - 1 = 3')
    ->tested('Healing Aura')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();

// ---- Waited Accord ----

gaScenario('waited-accord-level-two-sacrifice-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Waited Accord', level: 2)
    ->seed('myField', 'Waited Accord')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myHand', 8, 1, '[Level 2+] Sacrificed: a card was drawn')
    ->tested('Waited Accord')
    ->covers(['level-gate', 'activated-ability', 'sacrifice-self', 'draw'], '[Level 2+]** Sacrifice CARDNAME: **Draw a card.')
    ->register();

// ---- Wonderland's Reign ----

gaScenario('wonderlands-reign-cardistry-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Wonderland\'s Reign')
    ->seed('myField', 'Two of Spades')->seed('myField', 'Three of Spades')->seed('myField', 'Four of Spades')
    ->seed('myField', 'Wonderland\'s Reign')
    ->action(1, 10001, 'myField-4!CustomInput!Activate:0')->pay(6)
    ->expectZoneCount('myHand', 2, 1, 'Cardistry (10 - 4 distinct Suited reserve costs, the Reign\'s own 0 included = 6): six paid, one drawn')
    ->tested('Wonderland\'s Reign')
    ->covers(['cardistry', 'activated-ability', 'draw'], '***Cardistry*** — *(10)*: Draw a card.')
    ->register();

// ---- Blighted Jewel ----

gaScenario('blighted-jewel-enter-becomes-ephemeral-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Blighted Jewel')
    ->seed('myHand', 'Blighted Jewel')
    ->castSpell('Blighted Jewel', pay: 3)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","EPHEMERAL"]', 'On Enter: it became ephemeral')
    ->expectZoneCount('myMemory', 4, 1, 'Three paid plus one drawn into memory')
    ->tested('Blighted Jewel')
    ->covers(['on-enter', 'ephemeral', 'draw-into-memory'], '**On Enter**: Blighted Jewel becomes **ephemeral**. Draw a card into your memory.')
    ->register();

// ---- CookTech Apron / Knife ----

gaScenario('cooktech-knife-links-to-an-ally-which-gets-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('CookTech Knife')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'CookTech Knife')
    ->play('CookTech Knife')->target('myField-1')->pay(1)
    ->expectPower('p1Field-1', 2, 'Linked ally: 1 + 1 power')
    ->tested('CookTech Knife')
    ->covers(['link', 'stat-change'], 'Linked ally gets +1[POWER].')
    ->register();

gaScenario('cooktech-apron-links-to-an-ally-which-gets-plus-two-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('CookTech Apron')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'CookTech Apron')
    ->play('CookTech Apron')->target('myField-1')->pay(2)
    ->expectLife('p1Field-1', 5, 'Linked ally: 3 + 2 life')
    ->tested('CookTech Apron')
    ->covers(['link', 'stat-change'], 'Linked ally gets +2[LIFE].')
    ->register();
