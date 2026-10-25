<?php
/**
 * Phase 3b (Wind): allies, fifth batch (keyword and static-effect allies). Written on the 'quiet' deck.
 */

// ---- Concealed Marksman ----

gaWindRanged('concealed-marksman-ranged-four-while-distant', 'Concealed Marksman', 5, 'Ranged 4 while distant: 1 + 4 = 5 damage', '**Ranged 4**');

gaScenario('concealed-marksman-class-bonus-true-sight-attacks-a-stealth-unit')
    ->deckTemplate('quiet', 'Concealed Marksman')
    ->readyFor('Concealed Marksman', classBonus: true)
    ->seed('myField', 'Concealed Marksman')
    ->seed('myField', 'Haze Droid', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 1, 'True Sight: the Marksman could attack the stealth Haze Droid')
    ->tested('Concealed Marksman')
    ->covers(['true-sight', 'stealth', 'class-bonus', 'combat'], '[Class Bonus] **True Sight** *(This ally can attack units with stealth.)*')
    ->register();

gaScenario('concealed-marksman-without-the-class-bonus-cannot-attack-a-stealth-unit')
    ->deckTemplate('quiet', 'Concealed Marksman')
    ->readyFor('Concealed Marksman')
    ->seed('myField', 'Concealed Marksman')
    ->seed('myField', 'Haze Droid', player: 2)
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->rejects(1, 'theirField-1', 'No class bonus, no true sight: the stealth Haze Droid is not a legal target')
    ->answer(1, 'theirField-0')
    ->expectDamage('p2Field-1', 0, 'The Haze Droid was not attacked')
    ->tested('Concealed Marksman')
    ->covers(['true-sight', 'class-bonus', 'condition'], '[Class Bonus] **True Sight**')
    ->register();

// ---- Haze Droid ----

gaScenario('haze-droid-stealth-cannot-be-attacked')
    ->deckTemplate('quiet', 'Haze Droid')
    ->readyFor('Haze Droid')
    ->seed('myField', 'Haze Droid')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Stealth: the Haze Droid is not a legal attack target')
    ->answer(2, 'theirField-0')
    ->expectDamage('p1Field-1', 0, 'The Haze Droid took no damage')
    ->tested('Haze Droid')
    ->covers(['stealth'], '**Stealth** *(This unit can’t be targeted by attacks unless permitted by **true sight**.)*')
    ->register();

// ---- Galewhisper Rogue ----

gaScenario('galewhisper-rogue-class-bonus-stealth-cannot-be-attacked')
    ->deckTemplate('quiet', 'Galewhisper Rogue')
    ->readyFor('Galewhisper Rogue', classBonus: true)
    ->seed('myField', 'Galewhisper Rogue')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Class Bonus stealth: the Rogue is not a legal attack target')
    ->answer(2, 'theirField-0')
    ->expectDamage('p1Field-1', 0, 'The Rogue took no damage')
    ->tested('Galewhisper Rogue')
    ->covers(['stealth', 'class-bonus'], '[Class Bonus] **Stealth**')
    ->register();

gaScenario('galewhisper-rogue-without-the-class-bonus-can-be-attacked')
    ->deckTemplate('quiet', 'Galewhisper Rogue')
    ->readyFor('Galewhisper Rogue')
    ->seed('myField', 'Galewhisper Rogue')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->pass(1)
    ->expectDamage('p1Field-1', 1, 'No class bonus, no stealth: the Rogue was attacked')
    ->tested('Galewhisper Rogue')
    ->covers(['stealth', 'class-bonus', 'condition'], '[Class Bonus] **Stealth**')
    ->register();

// ---- Galewind Scout ----

gaWindRanged('galewind-scout-ranged-three-while-distant', 'Galewind Scout', 5, 'Ranged 3 while distant: 2 + 3 = 5 damage', '**Ranged 3**');

// ---- Daunting Panda ----

gaVigorCase('daunting-panda-vigor-wakes-it-at-the-end-of-the-turn', 'Daunting Panda', 'Daunting Panda', [],
    ['vigor'], '**Vigor** *(At the beginning of your end phase, wake up this unit.)*', '2', 'Vigor: the rested Panda woke at the beginning of the end phase');

// ---- Courtside Beastkeeper ----

gaScenario('courtside-beastkeeper-class-bonus-gives-beast-allies-plus-one-power')
    ->deckTemplate('quiet', 'Courtside Beastkeeper')
    ->readyFor('Courtside Beastkeeper', classBonus: true)
    ->seed('myField', 'Courtside Beastkeeper')
    ->seed('myField', 'Inferno Slime')
    ->endTurn()
    ->expectPower('p1Field-2', 4, 'Inferno Slime 3 + 1 power from the Beastkeeper')
    ->expectPower('p1Field-1', 1, 'The Beastkeeper itself is not a Beast')
    ->tested('Courtside Beastkeeper')
    ->covers(['class-bonus', 'stat-change', 'static-ability'], '[Class Bonus] Beast allies you control get +1 [POWER].')
    ->register();

gaScenario('courtside-beastkeeper-without-the-class-bonus-beasts-keep-their-power')
    ->deckTemplate('quiet', 'Courtside Beastkeeper')
    ->readyFor('Courtside Beastkeeper')
    ->seed('myField', 'Courtside Beastkeeper')
    ->seed('myField', 'Inferno Slime')
    ->endTurn()
    ->expectPower('p1Field-2', 3, 'No class bonus: Inferno Slime keeps its printed 3 power')
    ->tested('Courtside Beastkeeper')
    ->covers(['class-bonus', 'stat-change', 'condition'], '[Class Bonus] Beast allies you control get +1 [POWER].')
    ->register();

// ---- Axis Gale Scholar ----

gaScenario('axis-gale-scholar-north-currents-give-it-plus-two-power')
    ->deckTemplate('quiet', 'Axis Gale Scholar')
    ->readyFor('Axis Gale Scholar')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->seed('myField', 'Axis Gale Scholar')
    ->endTurn()
    ->expectPower('p1Field-1', 3, 'North: 1 + 2 power')
    ->expectLife('p1Field-1', 3, 'No South bonus')
    ->tested('Axis Gale Scholar')
    ->covers(['currents', 'stat-change', 'condition'], 'As long as your Shifting Currents face North, CARDNAME gets +2 [POWER].')
    ->register();

gaScenario('axis-gale-scholar-south-currents-give-allies-plus-one-life')
    ->deckTemplate('quiet', 'Axis Gale Scholar')
    ->readyFor('Axis Gale Scholar')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'SOUTH'])
    ->seed('myField', 'Axis Gale Scholar')
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->expectLife('p1Field-2', 4, 'South: Dungeon Guide 3 + 1 life')
    ->expectLife('p1Field-1', 4, 'South: the Scholar 3 + 1 life')
    ->expectPower('p1Field-1', 1, 'No North bonus')
    ->tested('Axis Gale Scholar')
    ->covers(['currents', 'stat-change', 'condition'], 'As long as your Shifting Currents face South, allies you control get +1 [LIFE].')
    ->register();

// ---- Music Aficionado / Quadrille's Gryphon / Fanclub Leader (Melody payoffs) ----

gaScenario('music-aficionado-costs-three-less-after-a-melody-this-turn')
    ->deckTemplate('quiet', 'Music Aficionado')
    ->readyFor('Music Aficionado')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Belted Tune')
    ->seed('myHand', 'Music Aficionado')
    ->castSpell('Belted Tune', pay: 2, window: '', target: 'myField-1')
    ->play('Music Aficionado')->pay(1)
    ->expectCardExists('myField', 'Music Aficionado', 'A Melody was activated this turn: the Aficionado cost 4 - 3 = 1')
    ->expectZoneCount('myMemory', 3, 1, 'Two for the Melody and one for the Aficionado')
    ->tested('Music Aficionado')
    ->covers(['melody', 'cost-reduction', 'condition'], 'As long as you\'ve activated a Harmony or Melody card this turn, this card costs 3 less to activate.')
    ->register();

gaScenario('music-aficionado-costs-one-after-breaths-coloratura')
    ->deckTemplate('quiet', 'Music Aficionado')
    ->readyFor('Music Aficionado')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Breath\'s Coloratura')
    ->seed('myHand', 'Music Aficionado')
    ->castSpell('Breath\'s Coloratura', pay: 3, window: '')
    ->play('Music Aficionado')->pay(1)
    ->expectCardExists('myField', 'Music Aficionado', 'Coloratura is a Melody: Aficionado enters after paying only 1 reserve')
    ->expectZoneCount('myMemory', 4, 1, 'Three reserve for Coloratura and one for Aficionado')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'Aficionado also receives Coloratura\'s Human-entry buff')
    ->expectDqEmpty()
    ->tested('Music Aficionado', 'Breath\'s Coloratura')
    ->covers(['melody', 'cost-reduction', 'condition'], 'As long as you\'ve activated a Harmony or Melody card this turn, this card costs 3 less to activate.')
    ->register();

gaScenario('quadrilles-gryphon-gets-a-buff-counter-whenever-you-activate-a-melody')
    ->deckTemplate('quiet', 'Quadrille\'s Gryphon')
    ->readyFor('Quadrille\'s Gryphon')
    ->seed('myField', 'Quadrille\'s Gryphon')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Belted Tune')
    ->castSpell('Belted Tune', pay: 2, window: '', target: 'myField-2')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A Melody was activated: the Gryphon got a buff counter')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'Belted Tune\'s own counter went on the Dungeon Guide')
    ->tested('Quadrille\'s Gryphon')
    ->covers(['melody', 'trigger', 'counter'], 'Whenever you activate a Melody or Harmony card, put a **buff** counter on Quadrille\'s Gryphon.')
    ->register();

gaScenario('fanclub-leader-resonator-allies-get-plus-one-power-when-you-activate-a-melody')
    ->deckTemplate('quiet', 'Fanclub Leader')
    ->readyFor('Fanclub Leader')
    ->seed('myField', 'Fanclub Leader')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Belted Tune')
    ->castSpell('Belted Tune', pay: 2, window: '', target: 'myField-2')
    ->expectPower('p1Field-1', 2, 'The Leader is a Resonator ally: 1 + 1 power')
    ->expectPower('p1Field-2', 2, 'The Dungeon Guide is not a Resonator: only its buff counter (1 + 1)')
    ->tested('Fanclub Leader')
    ->covers(['melody', 'trigger', 'stat-change'], 'Whenever you activate a Harmony or Melody card, Resonator allies you control get +1[POWER] until end of turn.')
    ->register();

// ---- Apprentice Aeromancer ----

gaScenario('apprentice-aeromancer-class-bonus-on-enter-empowers-two')
    ->deckTemplate('quiet', 'Apprentice Aeromancer')
    ->readyFor('Apprentice Aeromancer', classBonus: true)
    ->seed('myHand', 'Apprentice Aeromancer')
    ->play('Apprentice Aeromancer')->pay(2)
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","9f0nsj62l6","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2')
    ->tested('Apprentice Aeromancer')
    ->covers(['on-enter', 'class-bonus', 'empower'], '[Class Bonus] **On Enter:** **Empower 2**.')
    ->register();

gaScenario('music-aficionado-without-a-melody-this-turn-costs-the-printed-four')
    ->deckTemplate('quiet', 'Music Aficionado')
    ->readyFor('Music Aficionado')
    ->seed('myHand', 'Music Aficionado')
    ->play('Music Aficionado')->pay(1)
    ->expectZoneCount('myField', 1, 1, 'One payment is not enough: the Aficionado has not entered')
    ->tested('Music Aficionado')
    ->covers(['melody', 'cost-reduction', 'condition'], 'As long as you\'ve activated a Harmony or Melody card this turn')
    ->register();

// ---- Apprentice Aeromancer (second clause) ----

gaScenario('apprentice-aeromancer-class-bonus-gets-plus-one-power-when-you-activate-a-wind-spell')
    ->deckTemplate('quiet', 'Apprentice Aeromancer')
    ->readyFor('Apprentice Aeromancer', classBonus: true)
    ->seed('myField', 'Apprentice Aeromancer')
    ->seed('myField', 'Caretaker Horse')
    ->seed('myHand', 'Rearing Rebound')
    ->castSpell('Rearing Rebound', pay: 3, window: '', target: 'myField-2')
    ->expectPower('p1Field-1', 2, 'A wind Spell was activated: 1 + 1 power until end of turn')
    ->tested('Apprentice Aeromancer')
    ->covers(['class-bonus', 'trigger', 'stat-change'], '[Class Bonus] Whenever you activate a wind element Spell card, CARDNAME gets +1 [POWER] until end of turn.')
    ->register();

gaScenario('apprentice-aeromancer-without-the-class-bonus-gets-no-power-from-a-wind-spell')
    ->deckTemplate('quiet', 'Apprentice Aeromancer')
    ->readyFor('Apprentice Aeromancer')
    ->seed('myField', 'Apprentice Aeromancer')
    ->seed('myField', 'Caretaker Horse')
    ->seed('myHand', 'Rearing Rebound')
    ->castSpell('Rearing Rebound', pay: 3, window: '', target: 'myField-2')
    ->expectPower('p1Field-1', 1, 'No class bonus: still 1 power')
    ->tested('Apprentice Aeromancer')
    ->covers(['class-bonus', 'trigger', 'condition'], '[Class Bonus] Whenever you activate a wind element Spell card')
    ->register();

// ---- Elder Mandrill / Gale's Mare (no printed rules text) ----

gaScenario('elder-mandrill-can-be-played-for-its-reserve-cost')
    ->deckTemplate('quiet', 'Elder Mandrill')
    ->readyFor('Elder Mandrill')
    ->seed('myHand', 'Elder Mandrill')
    ->play('Elder Mandrill')->pay(3)
    ->expectCardExists('myField', 'Elder Mandrill', 'The vanilla Beast entered the field')
    ->expectPower('p1Field-1', 1, 'Printed 1 power')
    ->expectLife('p1Field-1', 1, 'Printed 1 life')
    ->tested('Elder Mandrill')
    ->covers(['vanilla'], '')
    ->register();

gaScenario('gales-mare-can-be-played-for-its-reserve-cost')
    ->deckTemplate('quiet', 'Gale\'s Mare')
    ->readyFor('Gale\'s Mare')
    ->seed('myHand', 'Gale\'s Mare')
    ->play('Gale\'s Mare')->pay(3)
    ->expectCardExists('myField', 'Gale\'s Mare', 'The vanilla Horse entered the field')
    ->expectPower('p1Field-1', 2, 'Printed 2 power')
    ->expectLife('p1Field-1', 3, 'Printed 3 life')
    ->tested('Gale\'s Mare')
    ->covers(['vanilla'], '')
    ->register();

// ---- Breezy Looper ----

gaScenario('breezy-looper-level-one-on-enter-returns-a-melody-from-the-graveyard-to-memory')
    ->deckTemplate('quiet', 'Breezy Looper')
    ->readyFor('Breezy Looper', level: 1)
    ->seed('myGraveyard', 'Belted Tune')
    ->seed('myHand', 'Breezy Looper')
    ->play('Breezy Looper')->pay(4)->answer(1, 'myGraveyard-0')
    ->expectCardExists('myMemory', 'Belted Tune', 'The Melody went from the graveyard to memory')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing left in the graveyard')
    ->tested('Breezy Looper')
    ->covers(['on-enter', 'level-gate', 'zone-movement', 'targeting'], '[Level 1+] **On Enter:** Return target Harmony or Melody card from your graveyard to your memory.')
    ->register();

gaScenario('breezy-looper-level-zero-on-enter-returns-nothing')
    ->deckTemplate('quiet', 'Breezy Looper')
    ->readyFor('Breezy Looper', level: 0)
    ->seed('myGraveyard', 'Belted Tune')
    ->seed('myHand', 'Breezy Looper')
    ->play('Breezy Looper')->pay(4)
    ->expectZoneCount('myGraveyard', 1, 1, 'Level 0: the Melody stays in the graveyard')
    ->tested('Breezy Looper')
    ->covers(['on-enter', 'level-gate', 'condition'], '[Level 1+] **On Enter:**')
    ->register();

// ---- Poised Rearguard ----

gaScenario('poised-rearguard-enters-the-field-rested')
    ->deckTemplate('quiet', 'Poised Rearguard')
    ->readyFor('Poised Rearguard')
    ->seed('myHand', 'Poised Rearguard')
    ->play('Poised Rearguard')->pay(3)
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: it entered rested')
    ->tested('Poised Rearguard')
    ->covers(['enters-rested'], '**Hindered** *(This ally enters the field rested.)*')
    ->register();

gaScenario('poised-rearguard-class-bonus-with-a-horse-ally-wakes-up-on-enter')
    ->deckTemplate('quiet', 'Poised Rearguard')
    ->readyFor('Poised Rearguard', classBonus: true)
    ->seed('myField', 'Gale\'s Mare')
    ->seed('myHand', 'Poised Rearguard')
    ->play('Poised Rearguard')->pay(3)
    ->expectProperty('p1Field-2', 'Status', '2', 'Equestrian: a Horse ally, so the Rearguard woke up')
    ->tested('Poised Rearguard')
    ->covers(['on-enter', 'class-bonus', 'status', 'condition'], '[Class Bonus] ***Equestrian*** — **On Enter:** If you control a Horse ally, wake up CARDNAME.')
    ->register();

// ---- Rilewind Sentinel ----

gaScenario('rilewind-sentinel-class-bonus-foster-becomes-fostered')
    ->deckTemplate('quiet', 'Rilewind Sentinel')
    ->readyFor('Rilewind Sentinel', classBonus: true)
    ->seed('myField', 'Rilewind Sentinel')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'TurnEffects', '["FOSTERED"]', 'Foster: undamaged since the end of the last turn, so it became fostered')
    ->tested('Rilewind Sentinel')
    ->covers(['foster', 'class-bonus'], '[Class Bonus] **Foster**')
    ->register();

gaScenario('rilewind-sentinel-fostered-gives-allies-vigor')
    ->deckTemplate('quiet', 'Rilewind Sentinel')
    ->readyFor('Rilewind Sentinel')
    ->seed('myField', 'Rilewind Sentinel', properties: ['TurnEffects' => ['FOSTERED']])
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-2', 'Status', '2', 'Fostered Sentinel: the rested ally has vigor and woke up at the end phase')
    ->tested('Rilewind Sentinel')
    ->covers(['foster', 'vigor', 'static-ability', 'condition'], 'As long as Rilewind Sentinel is **fostered**, allies you control have **vigor**.')
    ->register();

gaScenario('rilewind-sentinel-not-fostered-gives-allies-no-vigor')
    ->deckTemplate('quiet', 'Rilewind Sentinel')
    ->readyFor('Rilewind Sentinel')
    ->seed('myField', 'Rilewind Sentinel')
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 1])
    ->endTurn()
    ->expectProperty('p1Field-2', 'Status', '1', 'Not fostered: the rested ally stays rested')
    ->tested('Rilewind Sentinel')
    ->covers(['foster', 'vigor', 'condition'], 'As long as Rilewind Sentinel is **fostered**')
    ->register();

// ---- Ghost Hunter ----

gaWindRanged('ghost-hunter-ranged-two-while-distant', 'Ghost Hunter', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');

gaScenario('ghost-hunter-attacking-an-ephemeral-ally-gets-plus-three-power')
    ->deckTemplate('quiet', 'Ghost Hunter')
    ->readyFor('Ghost Hunter', player: 2)
    ->seed('myField', 'Dungeon Guide', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myField', 'Ghost Hunter', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->pass(1)
    ->expectDamage('p1Field-1', 4, 'An ephemeral target: 1 + 3 = 4 damage')
    ->tested('Ghost Hunter')
    ->covers(['combat', 'stat-change', 'condition', 'ephemeral'], 'As long as Ghost Hunter is attacking an **ephemeral** ally, it gets +3[POWER].')
    ->register();

gaScenario('ghost-hunter-attacking-a-non-ephemeral-ally-has-its-printed-power')
    ->deckTemplate('quiet', 'Ghost Hunter')
    ->readyFor('Ghost Hunter', player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Ghost Hunter', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->pass(1)
    ->expectDamage('p1Field-1', 1, 'Not ephemeral: only 1 damage')
    ->tested('Ghost Hunter')
    ->covers(['combat', 'stat-change', 'condition'], 'As long as Ghost Hunter is attacking an **ephemeral** ally')
    ->register();

// ---- Imperial Alchemist ----

gaEnterGatherCase('imperial-alchemist-class-bonus-on-enter-gathers-a-herb-token', 'Imperial Alchemist', 2, true, 3, 'Gather summoned one herb token beside the champion and the Alchemist')
    ->tested('Imperial Alchemist')
    ->covers(['on-enter', 'class-bonus', 'token', 'gather'], '[Class Bonus] **On Enter:** **Gather**.')
    ->register();

gaEnterGatherCase('imperial-alchemist-without-the-class-bonus-gathers-nothing', 'Imperial Alchemist', 2, false, 2, 'No class bonus: no token')
    ->tested('Imperial Alchemist')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter:** **Gather**.')
    ->register();

gaScenario('imperial-alchemist-gets-a-buff-counter-whenever-you-brew-a-potion')
    ->deckTemplate('quiet', 'Imperial Alchemist')
    ->readyFor('Imperial Alchemist')
    ->seed('myField', 'Imperial Alchemist')
    ->seed('myField', 'Manaroot')
    ->seed('myField', 'Blightroot')
    ->seed('myHand', 'Empowering Tincture')
    ->play('Empowering Tincture')->answer(1, 'YES')->target('myField-2')->target('myField-3')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'A Potion was brewed: the Alchemist got a buff counter')
    ->tested('Imperial Alchemist')
    ->covers(['brew', 'trigger', 'counter'], 'Whenever you **brew** a Potion, put a **buff** counter on CARDNAME.')
    ->register();
