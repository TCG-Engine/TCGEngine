<?php
/**
 * Phase 3b (Wind): allies, sixth batch (ranged / vigor / pride / taunt allies and counters). Written on the 'quiet' deck.
 */

// ---- Winbless Arbalest ----

gaWindRanged('winbless-arbalest-ranged-two-while-distant', 'Winbless Arbalest', 4, 'Ranged 2 while distant: 2 + 2 = 4 damage', '**Ranged 2**');

gaVigorMatrix('Winbless Arbalest', 'Winbless Arbalest', ['vigor', 'class-bonus'], '[Class Bonus] **Vigor**', [
    'winbless-arbalest-class-bonus-vigor-wakes-it-at-the-end-of-the-turn' => [['classBonus' => true], '2', 'Class Bonus vigor: the rested Arbalest woke at the end phase'],
    'winbless-arbalest-without-the-class-bonus-stays-rested' => [[], '1', 'No class bonus: no vigor', ['vigor', 'class-bonus', 'condition'], '[Class Bonus] **Vigor**'],
]);

// ---- Yunzhou Cavalry ----

gaWindRanged('yunzhou-cavalry-class-bonus-ranged-two-while-distant', 'Yunzhou Cavalry', 4, '[Class Bonus] Ranged 2 while distant: 2 + 2 = 4 damage', '[Class Bonus] **Ranged 2**', true, ['keyword', 'ranged', 'class-bonus', 'combat']);
gaWindRanged('yunzhou-cavalry-without-the-class-bonus-has-no-ranged', 'Yunzhou Cavalry', 2, 'No Class Bonus: only its printed 2 POWER', '[Class Bonus] **Ranged 2**', false, ['keyword', 'ranged', 'class-bonus', 'condition']);

gaScenario('yunzhou-cavalry-equestrian-on-enter-with-a-horse-becomes-distant')
    ->deckTemplate('quiet', 'Yunzhou Cavalry')
    ->readyFor('Yunzhou Cavalry')
    ->seed('myField', 'Gale\'s Mare')
    ->seed('myHand', 'Yunzhou Cavalry')
    ->play('Yunzhou Cavalry')->pay(3)
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', 'Equestrian: a Horse ally, so the Cavalry became distant')
    ->tested('Yunzhou Cavalry')
    ->covers(['on-enter', 'distant', 'condition'], '***Equestrian*** — **On Enter:** If you control a Horse ally, CARDNAME becomes **distant**.')
    ->register();

gaScenario('yunzhou-cavalry-on-enter-without-a-horse-stays-not-distant')
    ->deckTemplate('quiet', 'Yunzhou Cavalry')
    ->readyFor('Yunzhou Cavalry')
    ->seed('myHand', 'Yunzhou Cavalry')
    ->play('Yunzhou Cavalry')->pay(3)
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No Horse: not distant')
    ->tested('Yunzhou Cavalry')
    ->covers(['on-enter', 'distant', 'condition'], 'If you control a Horse ally')
    ->register();

gaScenario('yunzhou-cavalry-class-bonus-vigor-wakes-it-at-the-end-of-the-turn')
    ->deckTemplate('quiet', 'Yunzhou Cavalry')
    ->readyFor('Yunzhou Cavalry', classBonus: true)
    ->seed('myField', 'Yunzhou Cavalry', properties: ['Status' => 1])
    ->seed('myField', 'Dungeon Guide')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'Class Bonus vigor: the rested Cavalry woke at the end phase')
    ->expectProperty('p1Field-1', 'Counters', '[]', 'It is not the only ally, so no buff counter')
    ->tested('Yunzhou Cavalry')
    ->covers(['vigor', 'class-bonus'], '[Class Bonus] **Vigor**')
    ->register();

gaScenario('yunzhou-cavalry-class-bonus-the-only-ally-gets-a-buff-counter-at-the-end-phase')
    ->deckTemplate('quiet', 'Yunzhou Cavalry')
    ->readyFor('Yunzhou Cavalry', classBonus: true)
    ->seed('myField', 'Yunzhou Cavalry')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The only ally you control: a buff counter at the beginning of the end phase')
    ->tested('Yunzhou Cavalry')
    ->covers(['class-bonus', 'end-phase', 'counter', 'condition'], '[Class Bonus] At the beginning of your end phase, if CARDNAME is the only ally you control, put a **buff** counter on CARDNAME.')
    ->register();

// ---- Winbless Lookout ----

gaScenario('winbless-lookout-on-enter-puts-a-preparation-counter-on-your-champion')
    ->deckTemplate('quiet', 'Winbless Lookout')
    ->readyFor('Winbless Lookout')
    ->seed('myHand', 'Winbless Lookout')
    ->play('Winbless Lookout')->pay(2)
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Enter: one preparation counter on the champion')
    ->tested('Winbless Lookout')
    ->covers(['on-enter', 'counter'], '**On Enter:** Put a **preparation** counter on your champion.')
    ->register();

gaScenario('winbless-lookout-class-bonus-on-enter-glimpses-one')
    ->deckTemplate('quiet', 'Winbless Lookout')
    ->readyFor('Winbless Lookout', classBonus: true)
    ->deckTop(['Gale\'s Mare', 'Elder Mandrill'])
    ->seed('myHand', 'Winbless Lookout')
    ->play('Winbless Lookout')->pay(2)->answer(1, 'Top=;Bottom=flHhosV3nC')
    ->expectProperty('p1Deck-0', 'CardID', 'wu20yoje86', 'Glimpse 1: Gale\'s Mare went to the bottom, so Elder Mandrill is now on top')
    ->tested('Winbless Lookout')
    ->covers(['on-enter', 'class-bonus', 'glimpse'], '[Class Bonus] **On Enter:** **Glimpse 1**.')
    ->register();

// ---- Wilderness Harpist ----

gaScenario('wilderness-harpist-class-bonus-stealth-cannot-be-attacked')
    ->deckTemplate('quiet', 'Wilderness Harpist')
    ->readyFor('Wilderness Harpist', classBonus: true)
    ->seed('myField', 'Wilderness Harpist')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Class Bonus stealth: the Harpist is not a legal attack target')
    ->answer(2, 'theirField-0')
    ->expectDamage('p1Field-1', 0, 'The Harpist took no damage')
    ->tested('Wilderness Harpist')
    ->covers(['stealth', 'class-bonus'], '[Class Bonus] **Stealth**')
    ->register();

gaScenario('wilderness-harpist-melody-gives-your-champion-plus-one-level-until-end-of-turn')
    ->deckTemplate('quiet', 'Wilderness Harpist')
    ->readyFor('Wilderness Harpist')
    ->seed('myField', 'Wilderness Harpist')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Belted Tune')
    ->castSpell('Belted Tune', pay: 2, window: '', target: 'myField-2')
    ->expectLevel('p1Field-0', 1, 'A Melody was activated: the champion is level 0 + 1')
    ->expectPower('p1Field-1', 0, 'The marker is not a power bonus on the Harpist itself')
    ->tested('Wilderness Harpist')
    ->covers(['melody', 'trigger', 'level'], 'Whenever you activate a Harmony or Melody card, your champion gets +1 level until end of turn.')
    ->register();

// ---- Wildgrowth Feline ----

gaScenario('wildgrowth-feline-class-bonus-gets-a-buff-counter-when-another-beast-enters')
    ->deckTemplate('quiet', 'Wildgrowth Feline')
    ->readyFor('Wildgrowth Feline', classBonus: true)
    ->seed('myField', 'Wildgrowth Feline')
    ->seed('myHand', 'Gale\'s Mare')
    ->play('Gale\'s Mare')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Another Animal entered: the Feline got a buff counter')
    ->tested('Wildgrowth Feline')
    ->covers(['class-bonus', 'trigger', 'counter'], '[Class Bonus] Whenever another Animal and/or Beast ally enters the field under your control, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('wildgrowth-feline-a-non-animal-ally-entering-gives-no-counter')
    ->deckTemplate('quiet', 'Wildgrowth Feline')
    ->readyFor('Wildgrowth Feline', classBonus: true)
    ->seed('myField', 'Wildgrowth Feline')
    ->seed('myHand', 'Blightheart Adept')
    ->play('Blightheart Adept')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'A Human mage entered: no counter')
    ->tested('Wildgrowth Feline')
    ->covers(['class-bonus', 'trigger', 'condition'], 'Whenever another Animal and/or Beast ally enters')
    ->register();

// ---- Tempus Stalker / Tempest Silverback / Windstalker Wolf (Pride) ----

gaScenario('tempus-stalker-does-not-obey-a-level-one-champion')
    ->deckTemplate('quiet', 'Tempus Stalker')
    ->readyFor('Tempus Stalker', level: 1)
    ->seed('myField', 'Tempus Stalker')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 0, 'Pride 2: a level 1 champion cannot make the Stalker attack')
    ->tested('Tempus Stalker')
    ->covers(['pride', 'condition'], '**Pride 2**')
    ->register();

gaScenario('tempus-stalker-class-bonus-recollection-puts-a-buff-counter-on-it')
    ->deckTemplate('quiet', 'Tempus Stalker')
    ->readyFor('Tempus Stalker', classBonus: true, level: 2)
    ->seed('myField', 'Tempus Stalker')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Class Bonus: a buff counter at the beginning of the recollection phase')
    ->tested('Tempus Stalker')
    ->covers(['class-bonus', 'recollection', 'counter'], '[Class Bonus] At the beginning of your recollection phase, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('tempest-silverback-class-bonus-gets-plus-two-power-and-life')
    ->deckTemplate('quiet', 'Tempest Silverback')
    ->readyFor('Tempest Silverback', classBonus: true, level: 5)
    ->seed('myField', 'Tempest Silverback')
    ->endTurn()
    ->expectPower('p1Field-1', 6, 'Class Bonus: 4 + 2 power')
    ->expectLife('p1Field-1', 6, 'Class Bonus: 4 + 2 life')
    ->tested('Tempest Silverback')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] CARDNAME gets +2 [POWER] and +2 [LIFE].')
    ->register();

gaScenario('tempest-silverback-without-the-class-bonus-has-its-printed-stats')
    ->deckTemplate('quiet', 'Tempest Silverback')
    ->readyFor('Tempest Silverback', level: 5)
    ->seed('myField', 'Tempest Silverback')
    ->endTurn()
    ->expectPower('p1Field-1', 4, 'No class bonus: 4 power')
    ->expectLife('p1Field-1', 4, 'No class bonus: 4 life')
    ->tested('Tempest Silverback')
    ->covers(['class-bonus', 'stat-change', 'condition'], '[Class Bonus] CARDNAME gets +2 [POWER] and +2 [LIFE].')
    ->register();

gaScenario('tempest-silverback-pride-five-does-not-obey-a-level-four-champion')
    ->deckTemplate('quiet', 'Tempest Silverback')
    ->readyFor('Tempest Silverback', level: 4)
    ->seed('myField', 'Tempest Silverback')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 0, 'Pride 5: a level 4 champion cannot make the Silverback attack')
    ->tested('Tempest Silverback')
    ->covers(['pride', 'condition'], '**Pride 5**')
    ->register();

gaScenario('windstalker-wolf-pride-five-does-not-obey-a-level-four-champion')
    ->deckTemplate('quiet', 'Windstalker Wolf')
    ->readyFor('Windstalker Wolf', level: 4)
    ->seed('myField', 'Windstalker Wolf')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 0, 'Pride 5: a level 4 champion cannot make the Wolf attack')
    ->tested('Windstalker Wolf')
    ->covers(['pride', 'condition'], '**Pride 5**')
    ->register();

gaScenario('windstalker-wolf-attacks-for-five-when-the-champion-reaches-the-pride-level')
    ->deckTemplate('quiet', 'Windstalker Wolf')
    ->readyFor('Windstalker Wolf', level: 5)
    ->seed('myField', 'Windstalker Wolf')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p2Field-0', 5, 'Level 5: the Wolf obeys and attacked for its 5 power')
    ->tested('Windstalker Wolf')
    ->covers(['pride', 'condition'], '**Pride 5**')
    ->register();

// ---- Vigilant Sentry / Sworn Windhand (taunt) ----

gaScenario('sworn-windhand-retaliates-with-retort-two-plus-the-number-of-omens')
    ->deckTemplate('quiet', 'Sworn Windhand')
    ->readyFor('Sworn Windhand')
    ->seed('myField', 'Sworn Windhand')
    ->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p2Field-1', 4, 'Retort 2 + 1 omen: 1 + 3 = 4 damage')
    ->tested('Sworn Windhand')
    ->covers(['retort', 'omen', 'stat-change'], 'CARDNAME has **retort 2+X**, where **X** is the amount of **omens** you have.')
    ->register();

gaScenario('sworn-windhand-without-omens-retaliates-with-retort-two')
    ->deckTemplate('quiet', 'Sworn Windhand')
    ->readyFor('Sworn Windhand')
    ->seed('myField', 'Sworn Windhand')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p2Field-1', 3, 'Retort 2 and no omens: 1 + 2 = 3 damage')
    ->tested('Sworn Windhand')
    ->covers(['retort', 'omen', 'condition'], 'CARDNAME has **retort 2+X**')
    ->register();

// ---- Trained Hawk ----

gaScenario('trained-hawk-true-sight-attacks-a-stealth-unit')
    ->deckTemplate('quiet', 'Trained Hawk')
    ->readyFor('Trained Hawk')
    ->seed('myField', 'Trained Hawk')
    ->seed('myField', 'Haze Droid', player: 2)
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectDamage('p2Field-1', 2, 'True Sight: the Hawk attacked the stealth Haze Droid for 2')
    ->tested('Trained Hawk')
    ->covers(['true-sight', 'stealth', 'combat'], '**True Sight** *(This ally can attack units with **stealth**.)*')
    ->register();

gaVigorCase('trained-hawk-vigor-wakes-it-at-the-end-of-the-turn', 'Trained Hawk', 'Trained Hawk', [],
    ['vigor'], '**Vigor** *(This ally wakes up at the beginning of your end phase.)*', '2', 'Vigor: the rested Hawk woke at the end phase');

// ---- Sword Saint of Eveswind ----

gaScenario('sword-saint-of-eveswind-class-bonus-entering-from-a-banishment-gets-two-buff-counters')
    ->deckTemplate('quiet', 'Sword Saint of Eveswind')
    ->readyFor('Sword Saint of Eveswind', classBonus: true)
    ->seed('myField', 'Sword Saint of Eveswind')
    ->seed('myHand', 'Rearing Rebound')
    ->castSpell('Rearing Rebound', pay: 3, window: '', target: 'myField-1')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'It came back from the banishment: two buff counters')
    ->tested('Sword Saint of Eveswind')
    ->covers(['on-enter', 'class-bonus', 'counter', 'suppress', 'condition'], '[Class Bonus] **On Enter**: If Sword Saint of Eveswind entered from a banishment, put two **buff** counters on it.')
    ->register();

gaScenario('sword-saint-of-eveswind-entering-from-the-hand-gets-no-counters')
    ->deckTemplate('quiet', 'Sword Saint of Eveswind')
    ->readyFor('Sword Saint of Eveswind', classBonus: true)
    ->seed('myHand', 'Sword Saint of Eveswind')
    ->play('Sword Saint of Eveswind')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Entered from the hand: no counters')
    ->tested('Sword Saint of Eveswind')
    ->covers(['on-enter', 'class-bonus', 'condition'], 'If Sword Saint of Eveswind entered from a banishment')
    ->register();

// ---- Spirited Falconer ----

gaScenario('spirited-falconer-class-bonus-on-enter-buffs-up-to-two-animal-or-beast-allies')
    ->deckTemplate('quiet', 'Spirited Falconer')
    ->readyFor('Spirited Falconer', classBonus: true)
    ->seed('myField', 'Gale\'s Mare')
    ->seed('myField', 'Inferno Slime')
    ->seed('myHand', 'Spirited Falconer')
    ->play('Spirited Falconer')->pay(2)->pass(1)->answer(1, 'myField-1')->answer(1, 'myField-2')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'The Mare got a buff counter')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'The Slime got a buff counter')
    ->tested('Spirited Falconer')
    ->covers(['on-enter', 'class-bonus', 'counter', 'targeting'], '[Class Bonus] **On Enter:** Put a **buff** counter on up to two target Animal and/or Beast allies.')
    ->register();

// ---- Whirlwind Reaper ----

gaScenario('whirlwind-reaper-end-phase-removes-a-preparation-counter-to-wake-up')
    ->deckTemplate('quiet', 'Whirlwind Reaper')
    ->readyFor('Whirlwind Reaper')
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myField', 'Whirlwind Reaper', properties: ['Status' => 1])
    ->endTurn()->answer(1, 'YES')
    ->expectProperty('p1Field-1', 'Status', '2', 'A preparation counter was removed: the Reaper woke up')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'One preparation counter left')
    ->tested('Whirlwind Reaper')
    ->covers(['end-phase', 'counter', 'status', 'optional-cost'], 'At the beginning of your end phase, you may remove a **preparation** counter from your champion. If you do, wake up Whirlwind Reaper.')
    ->register();

gaScenario('whirlwind-reaper-end-phase-declined-stays-rested')
    ->deckTemplate('quiet', 'Whirlwind Reaper')
    ->readyFor('Whirlwind Reaper')
    ->patch('myField-0', ['Counters' => ['preparation' => 2]])
    ->seed('myField', 'Whirlwind Reaper', properties: ['Status' => 1])
    ->endTurn()->answer(1, 'NO')
    ->expectProperty('p1Field-1', 'Status', '1', 'Declined: still rested')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":2}', 'No counter removed')
    ->tested('Whirlwind Reaper')
    ->covers(['end-phase', 'optional-cost'], 'you may remove a **preparation** counter')
    ->register();

// ---- Whirlwind Vizier ----

gaScenario('whirlwind-vizier-class-bonus-gets-plus-one-life')
    ->deckTemplate('quiet', 'Whirlwind Vizier')
    ->readyFor('Whirlwind Vizier', classBonus: true)
    ->seed('myField', 'Whirlwind Vizier')
    ->endTurn()
    ->expectLife('p1Field-1', 3, 'Class Bonus: 2 + 1 life')
    ->tested('Whirlwind Vizier')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] CARDNAME gets +1 [LIFE].')
    ->register();

gaScenario('whirlwind-vizier-without-the-class-bonus-has-its-printed-life')
    ->deckTemplate('quiet', 'Whirlwind Vizier')
    ->readyFor('Whirlwind Vizier')
    ->seed('myField', 'Whirlwind Vizier')
    ->endTurn()
    ->expectLife('p1Field-1', 2, 'No class bonus: 2 life')
    ->tested('Whirlwind Vizier')
    ->covers(['class-bonus', 'stat-change', 'condition'], '[Class Bonus] CARDNAME gets +1 [LIFE].')
    ->register();

gaScenario('whirlwind-vizier-pays-three-and-sacrifices-itself-to-destroy-a-non-fractal-phantasia')
    ->deckTemplate('quiet', 'Whirlwind Vizier')
    ->readyFor('Whirlwind Vizier')
    ->seed('myField', 'Whirlwind Vizier')
    ->seed('myField', 'Bygone Days', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('theirField-1')
    ->expectCardExists('theirGraveyard', 'Bygone Days', 'The phantasia was destroyed', 1)
    ->expectCardExists('myGraveyard', 'Whirlwind Vizier', 'The Vizier sacrificed itself')
    ->tested('Whirlwind Vizier')
    ->covers(['activated-ability', 'rest', 'sacrifice', 'destroy', 'targeting'], '(3), [REST]**, Sacrifice CARDNAME:** Destroy target non-Fractal phantasia.')
    ->register();

gaScenario('whirlwind-vizier-cannot-target-a-fractal-phantasia')
    ->deckTemplate('quiet', 'Whirlwind Vizier')
    ->readyFor('Whirlwind Vizier')
    ->seed('myField', 'Whirlwind Vizier')
    ->seed('myField', 'Unstable Fractal', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'Status', '2', 'No legal target: the activation was refused and the Vizier did not rest')
    ->tested('Whirlwind Vizier')
    ->covers(['activated-ability', 'destroy', 'condition'], 'Destroy target non-Fractal phantasia.')
    ->register();
