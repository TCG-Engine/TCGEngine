<?php
/**
 * Phase 3b (Wind): allies, third batch (class-bonus allies). Written on the 'quiet' deck.
 */

// ---- Windrider Mage ----

gaScenario('windrider-mage-recollection-returns-it-to-hand-for-an-enlighten-counter')
    ->deckTemplate('quiet', 'Windrider Mage')
    ->readyFor('Windrider Mage', classBonus: true)
    ->seed('myField', 'Windrider Mage')
    ->reachTurnThree()
    ->answer(1, 'YES')
    ->expectCardExists('myHand', 'Windrider Mage', 'Class Bonus: the Mage returned to hand at the beginning of the recollection phase')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"},"enlighten":1}', 'An enlighten counter went on the champion')
    ->tested('Windrider Mage')
    ->covers(['class-bonus', 'zone-movement', 'counter'], '[Class Bonus] At the beginning of your recollection phase, you may return CARDNAME to your hand. If you do, put an **enlighten** counter on your champion.')
    ->register();

gaScenario('windrider-mage-recollection-declined-stays-on-the-field')
    ->deckTemplate('quiet', 'Windrider Mage')
    ->readyFor('Windrider Mage', classBonus: true)
    ->seed('myField', 'Windrider Mage')
    ->reachTurnThree()
    ->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'CardID', 'ZfCtSldRIy', 'Declined: the Mage stays on the field')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"}}', 'No enlighten counter')
    ->tested('Windrider Mage')
    ->covers(['class-bonus', 'zone-movement'], '[Class Bonus] At the beginning of your recollection phase, you may return CARDNAME to your hand.')
    ->register();

gaScenario('windrider-mage-without-the-class-bonus-is-not-offered-the-return')
    ->deckTemplate('quiet', 'Windrider Mage')
    ->readyFor('Windrider Mage')
    ->seed('myField', 'Windrider Mage')
    ->reachTurnThree()
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'CardID', 'ZfCtSldRIy', 'No class bonus: no prompt, the Mage stays')
    ->tested('Windrider Mage')
    ->covers(['class-bonus'], '[Class Bonus] At the beginning of your recollection phase')
    ->register();

// ---- Armored Valkyrie ----

gaScenario('armored-valkyrie-retaliates-with-two-extra-power-under-the-class-bonus')
    ->deckTemplate('quiet', 'Armored Valkyrie')
    ->readyFor('Armored Valkyrie', classBonus: true)
    ->seed('myField', 'Armored Valkyrie')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p2Field-1', 4, 'Retaliating with 2 + 2 power: 4 damage, lethal for the 3-life attacker')
    ->tested('Armored Valkyrie')
    ->covers(['class-bonus', 'stat-change', 'retaliate'], '[Class Bonus] As long as CARDNAME is retaliating, it gets +2 [POWER].')
    ->register();

gaScenario('armored-valkyrie-without-the-class-bonus-retaliates-for-its-printed-power')
    ->deckTemplate('quiet', 'Armored Valkyrie')
    ->readyFor('Armored Valkyrie')
    ->seed('myField', 'Armored Valkyrie')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p2Field-1', 2, 'No class bonus: 2 power only')
    ->tested('Armored Valkyrie')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] As long as CARDNAME is retaliating, it gets +2 [POWER].')
    ->register();

// ---- Charged Assailant ----

gaScenario('charged-assailant-on-enter-with-a-powercell-gains-agility-three')
    ->deckTemplate('quiet', 'Charged Assailant')
    ->readyFor('Charged Assailant', classBonus: true)
    ->seed('myField', 'Powercell')
    ->seed('myHand', 'Charged Assailant')
    ->play('Charged Assailant')->pay(3)
    ->expectGlobalEffect(1, 'AGILITY_3', 'On Enter with a Powercell: agility 3 this turn', 1, 4)
    ->endTurn()->pass(1)->pass(1)
    ->expectZoneCount('myMemory', 0, 1, 'Agility 3: the three paid memory cards returned to hand at the beginning of the end phase')
    ->expectZoneCount('myHand', 7, 1, 'Four left in hand after paying three and playing one, plus the three returned')
    ->tested('Charged Assailant')
    ->covers(['on-enter', 'class-bonus', 'condition', 'agility'], '[Class Bonus] **On Enter:** If you control a Powercell, you gain **agility 3** for this turn.')
    ->register();

gaScenario('charged-assailant-on-enter-without_a_powercell-gains-no-agility')
    ->deckTemplate('quiet', 'Charged Assailant')
    ->readyFor('Charged Assailant', classBonus: true)
    ->seed('myHand', 'Charged Assailant')
    ->play('Charged Assailant')->pay(3)
    ->expectGlobalEffect(1, 'AGILITY_3', 'No Powercell: no agility', 0)
    ->tested('Charged Assailant')
    ->covers(['on-enter', 'class-bonus', 'condition'], 'If you control a Powercell')
    ->register();

gaScenario('charged-assailant-on-enter-without-the-class-bonus-gains-no-agility')
    ->deckTemplate('quiet', 'Charged Assailant')
    ->readyFor('Charged Assailant')
    ->seed('myField', 'Powercell')
    ->seed('myHand', 'Charged Assailant')
    ->play('Charged Assailant')->pay(3)
    ->expectGlobalEffect(1, 'AGILITY_3', 'No class bonus: no agility', 0)
    ->tested('Charged Assailant')
    ->covers(['on-enter', 'class-bonus'], '[Class Bonus] **On Enter:**')
    ->register();

// ---- Blightheart Adept ----

gaScenario('blightheart-adept-on-enter-with-an-elysian-object-draws-into-memory')
    ->deckTemplate('quiet', 'Blightheart Adept')
    ->readyFor('Blightheart Adept', classBonus: true)
    ->seed('myField', 'Elysian Aspirant')
    ->seed('myHand', 'Blightheart Adept')
    ->play('Blightheart Adept')->pay(2)
    ->expectZoneCount('myMemory', 3, 1, 'Two paid cards plus the card drawn into memory')
    ->expectZoneCount('myDeck', 8, 1, 'The deck lost one card (9 to 8)')
    ->tested('Blightheart Adept')
    ->covers(['on-enter', 'class-bonus', 'condition', 'draw-discard'], '[Class Bonus] **On Enter:** If you control an Elysian object, draw a card into your memory.')
    ->register();

gaScenario('blightheart-adept-on-enter-without-an-elysian-object-draws-nothing')
    ->deckTemplate('quiet', 'Blightheart Adept')
    ->readyFor('Blightheart Adept', classBonus: true)
    ->seed('myHand', 'Blightheart Adept')
    ->play('Blightheart Adept')->pay(2)
    ->expectZoneCount('myMemory', 2, 1, 'No Elysian object: only the two paid cards')
    ->expectZoneCount('myDeck', 9, 1, 'Nothing was drawn')
    ->tested('Blightheart Adept')
    ->covers(['on-enter', 'class-bonus', 'condition'], 'If you control an Elysian object')
    ->register();

gaScenario('blightheart-adept-on-enter-without-the-class-bonus-draws-nothing')
    ->deckTemplate('quiet', 'Blightheart Adept')
    ->readyFor('Blightheart Adept')
    ->seed('myField', 'Elysian Aspirant')
    ->seed('myHand', 'Blightheart Adept')
    ->play('Blightheart Adept')->pay(2)
    ->expectZoneCount('myMemory', 2, 1, 'No class bonus: only the two paid cards')
    ->tested('Blightheart Adept')
    ->covers(['on-enter', 'class-bonus'], '[Class Bonus] **On Enter:**')
    ->register();

// ---- Windstream Mutt ----
// The reveal is at random, so each twin keeps its memory zone uniform: all wind, or all not.

gaScenario('windstream-mutt-on-enter-reveals-a-wind-memory-card-and-buffs-another-ally')
    ->deckTemplate('quiet', 'Windstream Mutt')
    ->readyFor('Windstream Mutt', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')->seed('myHand', 'Windrider Mage')
    ->seed('myHand', 'Windstream Mutt')
    ->play('Windstream Mutt')->action(1, 100, 'myHand-9')->action(1, 100, 'myHand-8')->action(1, 100, 'myHand-7')
    ->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A wind card was revealed: the other ally got a buff counter')
    ->tested('Windstream Mutt')
    ->covers(['on-enter', 'class-bonus', 'reveal-look', 'counter'], '[Class Bonus] **On Enter:** Reveal a card at random from your memory. If that card is wind element, choose another ally you control and put a **buff** counter on it.')
    ->register();

gaScenario('windstream-mutt-on-enter-reveals-a-non-wind-memory-card-and-buffs-nothing')
    ->deckTemplate('quiet', 'Windstream Mutt')
    ->readyFor('Windstream Mutt', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Windstream Mutt')
    ->play('Windstream Mutt')->action(1, 100, 'myHand-9')->action(1, 100, 'myHand-8')->action(1, 100, 'myHand-7')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'A norm card was revealed: no counter')
    ->expectDqEmpty()
    ->tested('Windstream Mutt')
    ->covers(['on-enter', 'class-bonus', 'reveal-look', 'condition'], 'If that card is wind element')
    ->register();

// ---- Windstrike Soldier ----
// Multistrike is not implemented as a keyword (see ENGINE-FINDINGS): this pins the payment and the granted effect only.

gaScenario('windstrike-soldier-on-enter-pays-three-for-multistrike')
    ->deckTemplate('quiet', 'Windstrike Soldier')
    ->readyFor('Windstrike Soldier', classBonus: true)
    ->seed('myHand', 'Windstrike Soldier')
    ->play('Windstrike Soldier')->pay(3)->answer(1, 'YES')->pay(3)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","MULTISTRIKE_2"]', 'Paid: the Soldier gained multistrike 2')
    ->expectZoneCount('myMemory', 6, 1, 'Three for the card and three for the On Enter')
    ->tested('Windstrike Soldier')
    ->covers(['on-enter', 'class-bonus', 'optional-cost', 'multistrike'], '[Class Bonus] **On Enter: **You may pay (3). If you do, CARDNAME gains **multistrike 2** until end of turn.')
    ->register();

gaScenario('windstrike-soldier-on-enter-declined-gains-no-multistrike')
    ->deckTemplate('quiet', 'Windstrike Soldier')
    ->readyFor('Windstrike Soldier', classBonus: true)
    ->seed('myHand', 'Windstrike Soldier')
    ->play('Windstrike Soldier')->pay(3)->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'Declined: no multistrike')
    ->tested('Windstrike Soldier')
    ->covers(['on-enter', 'class-bonus', 'optional-cost'], 'You may pay (3).')
    ->register();

// ---- Poised Bowman ----

gaScenario('poised-bowman-on-enter-gains-vigor-until-end-of-turn')
    ->deckTemplate('quiet', 'Poised Bowman')
    ->readyFor('Poised Bowman')
    ->seed('myHand', 'Poised Bowman')
    ->play('Poised Bowman')->pay(3)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","VIGOR_EOT"]', 'On Enter: vigor until end of turn')
    ->tested('Poised Bowman')
    ->covers(['on-enter', 'vigor'], '**On Enter**: Poised Bowman gains **vigor** until end of turn.')
    ->register();

gaScenario('poised-bowman-distant-attack-gets-plus-three-power')
    ->deckTemplate('quiet', 'Poised Bowman')
    ->readyFor('Poised Bowman', player: 2)
    ->seed('myField', 'Poised Bowman', player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 4, 'Ranged 3 while distant: 1 + 3 = 4 damage')
    ->tested('Poised Bowman')
    ->covers(['keyword', 'ranged', 'combat'], '**Ranged 3** *(As long as this unit is **distant**, its attacks get +3 [POWER].)*')
    ->register();

gaScenario('poised-bowman-not-distant-attack-has-base-power')
    ->deckTemplate('quiet', 'Poised Bowman')
    ->readyFor('Poised Bowman', player: 2)
    ->seed('myField', 'Poised Bowman', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 1, 'Not distant: only its 1 POWER')
    ->tested('Poised Bowman')
    ->covers(['keyword', 'ranged', 'condition'], 'As long as this unit is **distant**')
    ->register();

// ---- Armed Squallguard ----

gaScenario('armed-squallguard-with-an-attack-omen-gets-plus-one-power')
    ->deckTemplate('quiet', 'Armed Squallguard')
    ->readyFor('Armed Squallguard')
    ->seed('myField', 'Armed Squallguard')
    ->seed('myBanish', 'Crosswind Cuts', properties: ['Counters' => ['omen' => 1]])
    ->endTurn()
    ->expectPower('p1Field-1', 3, 'An attack omen: 2 + 1 power')
    ->expectLife('p1Field-1', 2, 'No ally omen: life unchanged')
    ->tested('Armed Squallguard')
    ->covers(['omen', 'stat-change', 'condition'], 'As long as you have one or more attack omens, Armed Squallguard gets +1[POWER].')
    ->register();

gaScenario('armed-squallguard-with-an-ally-omen-gets-plus-one-life')
    ->deckTemplate('quiet', 'Armed Squallguard')
    ->readyFor('Armed Squallguard')
    ->seed('myField', 'Armed Squallguard')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->endTurn()
    ->expectPower('p1Field-1', 2, 'No attack omen: power unchanged')
    ->expectLife('p1Field-1', 3, 'An ally omen: 2 + 1 life')
    ->tested('Armed Squallguard')
    ->covers(['omen', 'stat-change', 'condition'], 'As long as you have one or more ally omens, Armed Squallguard gets +1[LIFE].')
    ->register();

gaScenario('armed-squallguard-without-omens-has-its-printed-stats')
    ->deckTemplate('quiet', 'Armed Squallguard')
    ->readyFor('Armed Squallguard')
    ->seed('myField', 'Armed Squallguard')
    ->endTurn()
    ->expectPower('p1Field-1', 2, 'No omens: 2 power')
    ->expectLife('p1Field-1', 2, 'No omens: 2 life')
    ->tested('Armed Squallguard')
    ->covers(['omen', 'condition'], 'As long as you have one or more attack omens')
    ->register();

// ---- Blightheart Thaumaturge ----

gaScenario('blightheart-thaumaturge-on-enter-with-an-elysian-object-gets-plus-two-power')
    ->deckTemplate('quiet', 'Blightheart Thaumaturge')
    ->readyFor('Blightheart Thaumaturge')
    ->seed('myField', 'Elysian Aspirant')
    ->seed('myHand', 'Blightheart Thaumaturge')
    ->play('Blightheart Thaumaturge')->pay(3)
    ->expectPower('p1Field-2', 4, 'An Elysian object: 2 + 2 power until end of turn')
    ->tested('Blightheart Thaumaturge')
    ->covers(['on-enter', 'stat-change', 'condition'], '**On Enter: **If you control an Elysian object, CARDNAME gets +2[POWER] until end of turn.')
    ->register();

gaScenario('blightheart-thaumaturge-on-enter-without-an-elysian-object-keeps-its-power')
    ->deckTemplate('quiet', 'Blightheart Thaumaturge')
    ->readyFor('Blightheart Thaumaturge')
    ->seed('myHand', 'Blightheart Thaumaturge')
    ->play('Blightheart Thaumaturge')->pay(3)
    ->expectPower('p1Field-1', 2, 'No Elysian object: printed 2 power')
    ->tested('Blightheart Thaumaturge')
    ->covers(['on-enter', 'condition'], 'If you control an Elysian object')
    ->register();

// ---- Fleetfoot Filly ----

gaScenario('fleetfoot-filly-gets-a-buff-counter-whenever-a-player-suppresses-an-object')
    ->standardDeck('Fleetfoot Filly')
    ->readyFor('Fleetfoot Filly')
    ->seed('myField', 'Fleetfoot Filly')
    ->seed('myField', 'Caretaker Horse')
    ->seed('myHand', 'Rearing Rebound')
    ->castSpell('Rearing Rebound', pay: 3, window: '', target: 'myField-2')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A suppress happened: the Filly got a buff counter')
    ->tested('Fleetfoot Filly')
    ->covers(['trigger', 'counter', 'suppress'], 'Whenever a player **suppresses** an object, put a **buff** counter on Fleetfoot Filly.')
    ->register();

gaScenario('fleetfoot-filly-gets-no-counter-without-a-suppress')
    ->standardDeck('Fleetfoot Filly')
    ->readyFor('Fleetfoot Filly')
    ->seed('myField', 'Fleetfoot Filly')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '[]', 'No suppress: no counter')
    ->tested('Fleetfoot Filly')
    ->covers(['trigger', 'counter'], 'Whenever a player **suppresses** an object')
    ->register();

// ---- Jadelight Protector ----

gaScenario('jadelight-protector-south-currents-give-allies-steadfast-and-retort')
    ->deckTemplate('quiet', 'Jadelight Protector')
    ->readyFor('Jadelight Protector')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'SOUTH'])
    ->seed('myField', 'Jadelight Protector')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, 'myField-2')
    ->expectDamage('p2Field-1', 2, 'The rested Guide retaliated (steadfast) with 1 + 1 retort power')
    ->tested('Jadelight Protector')
    ->covers(['static', 'condition', 'currents', 'keyword'], 'As long as your Shifting Currents face South, allies you control have **steadfast** and **retort 1**.')
    ->register();

gaScenario('jadelight-protector-other-currents-leave-allies-without-steadfast')
    ->deckTemplate('quiet', 'Jadelight Protector')
    ->readyFor('Jadelight Protector')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Jadelight Protector')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)
    ->expectDamage('p2Field-1', 0, 'North: a rested Guide cannot retaliate')
    ->tested('Jadelight Protector')
    ->covers(['static', 'condition', 'currents'], 'As long as your Shifting Currents face South')
    ->register();

// ---- Blightheart Penitent ----

gaScenario('blightheart-penitent-cascade-one-prevents-the-next-two-damage-to-a-unit')
    ->deckTemplate('quiet', 'Blightheart Penitent')
    ->readyFor('Blightheart Penitent')
    ->seed('myField', 'Elysian Aspirant')
    ->seed('myField', 'Blightheart Penitent')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_2"]', 'Step 1: the next 2 damage to the Aspirant is prevented')
    ->expectProperty('p1Field-2', 'Status', '1', 'The Penitent rested for the ability')
    ->tested('Blightheart Penitent')
    ->covers(['activated-ability', 'rest', 'cascade', 'prevent-damage', 'condition'], '[REST]**:** If you control an Elysian object, **cascade**— <br>• 1— Prevent the next 2 damage that would be dealt to target unit this turn.')
    ->register();

gaScenario('blightheart-penitent-cascade-two-puts-a-buff-counter-on-an-ally')
    ->deckTemplate('quiet', 'Blightheart Penitent')
    ->readyFor('Blightheart Penitent')
    ->seed('myField', 'Elysian Aspirant')
    ->seed('myField', 'Blightheart Penitent')
    ->patch('myField-2', ['Counters' => ['cascade' => 1]])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Step 2: a buff counter on the target ally')
    ->tested('Blightheart Penitent')
    ->covers(['activated-ability', 'cascade', 'counter', 'targeting'], '• 2 and 3— Put a **buff** counter on target ally.')
    ->register();

gaScenario('blightheart-penitent-without-an-elysian-object-cannot-use-its-ability')
    ->deckTemplate('quiet', 'Blightheart Penitent')
    ->readyFor('Blightheart Penitent')
    ->seed('myField', 'Blightheart Penitent')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Status', '2', 'No Elysian object: the activation was refused and the Penitent did not rest')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'The cascade did not advance')
    ->expectDqEmpty()
    ->tested('Blightheart Penitent')
    ->covers(['activated-ability', 'condition'], 'If you control an Elysian object')
    ->register();
