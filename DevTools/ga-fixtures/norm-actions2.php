<?php
/**
 * Phase 3d (Norm): actions, second batch (counters, draws, damage and keyword grants). 'quiet' deck.
 */

// ---- Buried Grief ----

gaScenario('buried-grief-draws-two-then-puts-a-hand-card-on-top-of-the-deck')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Buried Grief')
    ->seed('myHand', 'Buried Grief')
    ->castSpell('Buried Grief', pay: 3, window: '')->answer(1, 'myHand-0')
    ->expectZoneCount('myHand', 5, 1, 'Eight with the seeded spell, minus the spell, three paid, plus two drawn, minus one put back (8 - 1 - 3 + 2 - 1)')
    ->tested('Buried Grief')
    ->covers(['draw', 'zone-movement'], 'Draw two cards, then put a card from your hand on top of your deck.')
    ->register();

// ---- Unearth Revelations ----

gaScenario('unearth-revelations-draws-two-then-puts-two-hand-cards-on-the-bottom')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Unearth Revelations')
    ->seed('myHand', 'Unearth Revelations')
    ->castSpell('Unearth Revelations', pay: 1, window: '')->answer(1, 'myHand-0')->answer(1, 'myHand-0')
    ->expectZoneCount('myHand', 6, 1, 'Eight with the seeded spell, minus the spell, one paid, plus two drawn, minus two put back (8 - 1 - 1 + 2 - 2)')
    ->tested('Unearth Revelations')
    ->covers(['draw', 'zone-movement'], 'Draw two cards, then put two cards from your hand on the bottom of your deck in any order.')
    ->register();

// ---- Cure the Flesh / Critical Recovery ----

gaScenario('cure-the-flesh-removes-all-damage-from-an-ally-and-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cure the Flesh')
    ->seed('myField', 'Giant Tortoise', properties: ['Damage' => 4])
    ->seed('myHand', 'Cure the Flesh')
    ->castSpell('Cure the Flesh', pay: 3, window: '', target: 'myField-1')
    ->expectDamage('p1Field-1', 0, 'All the damage was removed from the ally')
    ->expectZoneCount('myMemory', 4, 1, 'Three paid plus one drawn into memory')
    ->tested('Cure the Flesh')
    ->covers(['heal', 'draw-into-memory', 'targeting'], 'Remove all temporary damage from target ally. Draw a card into your memory.')
    ->register();

gaScenario('critical-recovery-removes-all-damage-and-buffs-an-automaton')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Critical Recovery')
    ->seed('myField', 'Delivery Droid', properties: ['Damage' => 0])
    ->seed('myHand', 'Critical Recovery')
    ->castSpell('Critical Recovery', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Automaton ally got a buff counter')
    ->tested('Critical Recovery')
    ->covers(['heal', 'counter', 'condition', 'targeting'], 'Remove all temporary damage from target ally. If that ally is an Automaton, put a **buff** counter on it.')
    ->register();

gaScenario('critical-recovery-a-non-automaton-gets-no-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Critical Recovery')
    ->seed('myField', 'Gray Wolf', properties: ['Damage' => 1])
    ->seed('myHand', 'Critical Recovery')
    ->castSpell('Critical Recovery', pay: 2, window: '', target: 'myField-1')
    ->expectDamage('p1Field-1', 0, 'The damage was removed')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Not an Automaton: no buff counter')
    ->tested('Critical Recovery')
    ->covers(['heal', 'condition'], 'If that ally is an Automaton, put a **buff** counter on it.')
    ->register();

// ---- Merciless Toss ----

gaScenario('merciless-toss-deals-six-damage-to-a-damaged-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Merciless Toss')
    ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Damage' => 1])
    ->seed('myHand', 'Merciless Toss')
    ->castSpell('Merciless Toss', pay: 3, window: '', target: 'theirField-1')
    ->expectCardExists('myGraveyard', 'Giant Tortoise', 'Damaged ally: 6 damage destroyed the Tortoise (1 + 6 >= 6 life)', 2)
    ->tested('Merciless Toss')
    ->covers(['damage', 'condition', 'targeting'], 'Deal 6 damage to target ally if it\'s damaged. Otherwise, deal 2 damage to it.')
    ->register();

gaScenario('merciless-toss-deals-two-damage-to-an-undamaged-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Merciless Toss')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Merciless Toss')
    ->castSpell('Merciless Toss', pay: 3, window: '', target: 'theirField-1')
    ->expectDamage('p2Field-1', 2, 'Undamaged ally: 2 damage')
    ->tested('Merciless Toss')
    ->covers(['damage', 'condition'], 'Otherwise, deal 2 damage to it.')
    ->register();

// ---- Invective Instruction ----

gaScenario('invective-instruction-gives-an-ally-plus-three-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Invective Instruction')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Invective Instruction')
    ->castSpell('Invective Instruction', pay: 2, window: '', target: 'myField-1')
    ->expectPower('p1Field-1', 4, '+3 power until end of turn: 1 + 3')
    ->tested('Invective Instruction')
    ->covers(['stat-change', 'targeting'], 'Target ally gets +3 [POWER] until end of turn.')
    ->register();

gaScenario('invective-instruction-class-bonus-with-a-non-human-ally-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Invective Instruction', classBonus: true)
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Invective Instruction')
    ->castSpell('Invective Instruction', pay: 2, window: '', target: 'myField-1')
    ->expectZoneCount('myMemory', 3, 1, '[Class Bonus] A non-Human ally: two paid plus one drawn into memory')
    ->tested('Invective Instruction')
    ->covers(['class-bonus', 'draw-into-memory', 'condition'], '[Class Bonus] If you control a non-Human ally, draw a card into your memory.')
    ->register();

// ---- Manufacture Cell ----

gaScenario('manufacture-cell-summons-a-rested-powercell-and-draws-with-an-automaton')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Manufacture Cell')
    ->seed('myField', 'Delivery Droid')
    ->seed('myHand', 'Manufacture Cell')
    ->castSpell('Manufacture Cell', pay: 2, window: '')
    ->expectCardExists('myField', 'Powercell', 'A Powercell was summoned')
    ->expectProperty('p1Field-2', 'Status', '1', 'It entered rested')
    ->expectZoneCount('myHand', 6, 1, 'An Automaton object is controlled: a card was drawn (8 - 1 - 2 + 1)')
    ->tested('Manufacture Cell')
    ->covers(['summon', 'draw', 'condition'], '**Summon** a Powercell token rested. If you control an Automaton object, draw a card.')
    ->register();

// ---- Refurbish ----

gaScenario('refurbish-puts-two-durability-counters-on-a-sword-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Refurbish')
    ->seed('myField', 'Clarent, Sword of Peace')
    ->seed('myHand', 'Refurbish')
    ->castSpell('Refurbish', pay: 4, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":4}', 'Two durability counters went on the Sword (2 + 2)')
    ->tested('Refurbish')
    ->covers(['counter', 'targeting'], 'Put two **durability** counters on target Sword weapon you control.')
    ->register();

// ---- Sharpen Blade ----

gaScenario('sharpen-blade-class-bonus-gives-a-dagger-plus-two-power-and-a-preparation-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sharpen Blade', classBonus: true)
    ->seed('myField', 'Emergent Dagger')
    ->seed('myHand', 'Sharpen Blade')
    ->castSpell('Sharpen Blade', pay: 2, window: '', target: 'myField-1')
    ->expectPower('p1Field-1', 3, 'The Dagger got +2 power until end of turn: 1 + 2')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"preparation":1}', '[Class Bonus] A preparation counter went on the champion')
    ->tested('Sharpen Blade')
    ->covers(['stat-change', 'class-bonus', 'counter', 'targeting'], 'Target Dagger weapon gets +2 [POWER] until end of turn.', '[Class Bonus] Put a **preparation** counter on your champion.')
    ->register();

// ---- Stolen Chance / Surreptitious Scheme ----

gaScenario('stolen-chance-removes-a-preparation-counter-from-a-champion-and-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Stolen Chance')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]])
    ->seed('myHand', 'Stolen Chance')
    ->castSpell('Stolen Chance', pay: 1, window: '', target: 'myField-0')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The preparation counter was removed')
    ->expectZoneCount('myHand', 7, 1, 'A card was drawn (8 - 1 - 1 + 1)')
    ->tested('Stolen Chance')
    ->covers(['counter', 'draw', 'condition', 'targeting'], 'Remove a **preparation** counter from target champion. If a **preparation** counter was removed this way, draw a card.')
    ->register();

gaScenario('surreptitious-scheme-removes-a-level-counter-from-a-champion-and-draws')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Surreptitious Scheme')
    ->patch('myField-0', ['Counters' => ['level' => 1]])
    ->seed('myHand', 'Surreptitious Scheme')
    ->castSpell('Surreptitious Scheme', pay: 1, window: '', target: 'myField-0')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'The level counter was removed')
    ->tested('Surreptitious Scheme')
    ->covers(['counter', 'draw', 'condition', 'targeting'], 'Remove a **level** counter from target champion. If **level** counter was removed this way, draw a card.')
    ->register();

// ---- Strategic Planning ----

gaScenario('strategic-planning-glimpses-two-and-puts-a-preparation-counter-on-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Strategic Planning')
    ->deckTop(['Dungeon Guide', 'Gray Wolf'])
    ->seed('myHand', 'Strategic Planning')
    ->castSpell('Strategic Planning', pay: 2, window: '')->answer(1, 'Top=em6eEh9q8y,hJ2xh9lNMR;Bottom=')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'A preparation counter went on the champion')
    ->tested('Strategic Planning')
    ->covers(['glimpse', 'counter'], '**Glimpse 2**. Put a **preparation** counter on your champion.')
    ->register();

// ---- Sleight of Hand / Sidestep ----

gaScenario('sidestep-gives-an-ally-stealth-until-end-of-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sidestep')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Sidestep')
    ->castSpell('Sidestep', pay: 2, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH"]', 'The ally gained stealth until end of turn')
    ->tested('Sidestep')
    ->covers(['stealth', 'targeting'], 'Target ally gains **stealth** until end of turn.')
    ->register();

gaScenario('sidestep-level-two-costs-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sidestep', level: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Sidestep')
    ->castSpell('Sidestep', pay: 1, window: '', target: 'myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","STEALTH"]', '[Level 2+] Paying 1 instead of 2 resolved it')
    ->tested('Sidestep')
    ->covers(['level-gate', 'cost-reduction'], '[Level 2+] This card costs 1 less to activate.')
    ->register();

// ---- Tomb Sweep ----

gaScenario('tomb-sweep-banishes-a-card-in-a-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tomb Sweep')
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Tomb Sweep')
    ->castSpell('Tomb Sweep', pay: 2, window: '', target: 'theirGraveyard-0')
    ->expectZoneCount('theirGraveyard', 0, 1, 'The graveyard card was banished')
    ->expectZoneCount('theirBanish', 1, 1, 'Into its owner\'s banishment')
    ->tested('Tomb Sweep')
    ->covers(['banish', 'targeting'], 'Banish target card in a graveyard.')
    ->register();

gaScenario('tomb-sweep-level-one-costs-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Tomb Sweep', level: 1)
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myHand', 'Tomb Sweep')
    ->castSpell('Tomb Sweep', pay: 1, window: '', target: 'theirGraveyard-0')
    ->expectZoneCount('theirGraveyard', 0, 1, '[Level 1+] Paying 1 instead of 2 resolved it')
    ->tested('Tomb Sweep')
    ->covers(['level-gate', 'cost-reduction'], '[Level 1+] This card costs 1 less to activate.')
    ->register();

// ---- Expose Darkness ----

gaScenario('expose-darkness-a-stealth-unit-makes-you-draw-and-loses-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Expose Darkness')
    ->seed('myField', 'Haze Droid', player: 2)
    ->seed('myHand', 'Expose Darkness')
    ->castSpell('Expose Darkness', pay: 1, window: '', target: 'theirField-1')
    ->expectZoneCount('myHand', 7, 1, 'The target has stealth: a card was drawn (8 - 1 - 1 + 1)')
    ->tested('Expose Darkness')
    ->covers(['stealth', 'draw', 'condition', 'targeting'], 'If target unit has **stealth**, draw a card. Then that unit loses **stealth** until end of turn.')
    ->register();
