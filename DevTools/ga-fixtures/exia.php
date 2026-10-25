<?php
/**
 * Phase 3e (Exia). readyFor() enables the exia element (and the class bonus where asked) through the champion lineage; damage: puts damage counters on the champion. 'quiet' deck.
 */

// ---- Crimson Rupture ----

gaScenario('crimson-rupture-damage-twenty-costs-two-less-and-destroys-an-item-or-weapon')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crimson Rupture', damage: 20)
    ->seed('myField', 'Training Sword', player: 2)
    ->seed('myHand', 'Crimson Rupture')
    ->play('Crimson Rupture')->pay(1)->target('theirField-1')->passBoth()
    ->expectZoneCount('theirField', 1, 1, 'The weapon was destroyed')
    ->tested('Crimson Rupture')
    ->covers(['damage-threshold', 'cost-reduction', 'destroy', 'targeting'], '[Damage 20+] This card costs 2 less to activate.', 'Destroy target item or weapon.')
    ->register();

gaScenario('crimson-rupture-below-twenty-damage-costs-its-full-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crimson Rupture', damage: 19)
    ->seed('myField', 'Training Sword', player: 2)
    ->seed('myHand', 'Crimson Rupture')
    ->play('Crimson Rupture')->pay(1)
    ->expectZoneCount('theirField', 2, 1, 'Nineteen damage: one payment is not enough and nothing was destroyed')
    ->tested('Crimson Rupture')
    ->covers(['damage-threshold', 'condition'], '[Damage 20+] This card costs 2 less to activate.')
    ->register();

// ---- Bloodshroud Temper ----

gaScenario('bloodshroud-temper-gives-a-weapon-omnishroud-and-with-damage-twenty-two-durability-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bloodshroud Temper', damage: 20)
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Bloodshroud Temper')
    ->play('Bloodshroud Temper')->pay(2)->target('myField-1')->passBoth()
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","OMNISHROUD"]', 'The weapon gained omnishroud')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":4}', 'The sword\'s 2 durability counters plus two')
    ->tested('Bloodshroud Temper')
    ->covers(['omnishroud', 'damage-threshold', 'durability'], 'Target weapon gains **omnishroud** until end of turn.', '[Damage 20+] Put two **durability** counters on that weapon.')
    ->register();

// ---- Devil's Lifeline ----

gaScenario('bloodshroud-temper-below-twenty-damage-gives-no-durability-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bloodshroud Temper', damage: 19)
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Bloodshroud Temper')
    ->play('Bloodshroud Temper')->pay(2)->target('myField-1')->passBoth()
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'Only the 2 durability counters the sword entered with')
    ->tested('Bloodshroud Temper')
    ->covers(['damage-threshold', 'condition'], '[Damage 20+] Put two **durability** counters on that weapon.')
    ->register();

// ---- Devil's Lifeline ----

gaScenario('devils-lifeline-deals-unpreventable-damage-to-your-champion-and-scavenges-two-plus-that-many')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Devil\'s Lifeline')
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide', 'Dungeon Guide', 'Crimson Rupture'])
    ->seed('myHand', 'Devil\'s Lifeline')
    ->play('Devil\'s Lifeline')->answer(1, '3')
    ->expectDamage('p1Field-0', 3, 'Three unpreventable damage to my own champion')
    ->expectCardExists('myHand', 'Crimson Rupture', 'Scavenge 2+3 = 5: the exia card fifth from the top was found')
    ->tested('Devil\'s Lifeline')
    ->covers(['unpreventable', 'self-damage', 'scavenge', 'scaling'], 'Deal up to 7 unpreventable damage to your champion. Then **scavenge 2+X** for an exia element card, where **X** is the amount of damage dealt to your champion this way.')
    ->register();

// ---- Umbilical Ritual ----

gaScenario('umbilical-ritual-sacrifices-an-ally-as-the-additional-cost-to-recover-four-and-empower-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Umbilical Ritual', classBonus: true, damage: 6)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Umbilical Ritual')
    ->play('Umbilical Ritual')->target('myField-1')
    ->expectCardExists('myGraveyard', 'Dungeon Guide', 'The ally was sacrificed as the additional cost')
    ->expectDamage('p1Field-0', 2, 'Recover 4: 6 damage down to 2')
    ->expectProperty('p1Field-0', 'TurnEffects', '["TfBuA9PUAO","EMPOWERED","EMPOWER_PLUS_4","vz4kc558yx-ACTION_ACTIVATED"]', 'Empowered by 4')
    ->tested('Umbilical Ritual')
    ->covers(['additional-cost', 'sacrifice', 'recover', 'empower'], 'As an additional cost to activate this card, sacrifice an ally.', '**Recover 4** and **empower 4**.')
    ->register();

gaScenario('umbilical-ritual-cannot-be-activated-without-an-ally-to-sacrifice')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Umbilical Ritual', classBonus: true, damage: 6)
    ->seed('myHand', 'Umbilical Ritual')
    ->play('Umbilical Ritual')
    ->expectDamage('p1Field-0', 6, 'No ally to sacrifice: nothing was activated and no damage was recovered')
    ->expectZoneCount('myHand', 8, 1, 'The card stayed in hand')
    ->tested('Umbilical Ritual')
    ->covers(['additional-cost', 'condition'], 'As an additional cost to activate this card, sacrifice an ally.')
    ->register();

// ---- Vascular Collapse ----

$gaVascularClause = 'Choose one— • Destroy target non-champion object. • [Level 9+] Destroy two target non-champion objects. • [Level 15+] Destroy all non-champion objects.';

gaScenario('vascular-collapse-level-fifteen-mode-destroys-all-non-champion-objects')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vascular Collapse', classBonus: true, level: 15)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Vascular Collapse')
    ->play('Vascular Collapse')->pay(2)->answer(1, 'C')
    ->expectZoneCount('theirField', 1, 1, 'Only the opposing champion remains')
    ->expectZoneCount('myField', 1, 1, 'And only my champion: my own non-champion object was destroyed too')
    ->tested('Vascular Collapse')->covers(['modal', 'destroy', 'level-gate'], $gaVascularClause)->register();

gaScenario('vascular-collapse-first-mode-destroys-one-target-non-champion-object')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Vascular Collapse', classBonus: true)
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Vascular Collapse')
    ->play('Vascular Collapse')->pay(2)->answer(1, 'theirField-1')
    ->expectZoneCount('theirField', 2, 1, 'Below level 9 only one object is destroyed: the champion and one object remain')
    ->expectCardExists('theirField', 'Dungeon Guide', 'The other opposing object was left alone')
    ->tested('Vascular Collapse')->covers(['modal', 'destroy', 'targeting'], $gaVascularClause)->register();

// ---- Annihilation ----

gaScenario('annihilation-class-bonus-damages-your-champion-and-destroys-non-champion-objects-with-that-reserve-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Annihilation', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Annihilation')
    ->play('Annihilation')->pay(3)->answer(1, '3')
    ->expectDamage('p1Field-0', 3, 'Three damage to my own champion')
    ->expectZoneCount('myField', 1, 1, 'Dungeon Guide (reserve cost 3) was destroyed')
    ->expectCardExists('theirField', 'Giant Tortoise', 'Giant Tortoise (reserve cost 4) was not')
    ->tested('Annihilation')
    ->covers(['class-bonus', 'self-damage', 'destroy', 'reserve-cost-match'], '[Class Bonus] Deal up to 7 damage to your champion. Destroy all non-champion objects with reserve cost equal to the amount of damage dealt to your champion this way.')
    ->register();

// ---- Bloodseeker Magus ----

gaScenario('bloodseeker-magus-enter-deals-three-unpreventable-damage-to-your-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bloodseeker Magus')
    ->seed('myHand', 'Bloodseeker Magus')
    ->play('Bloodseeker Magus')->pay(1)
    ->expectDamage('p1Field-0', 3, 'On Enter: 3 damage to my own champion')
    ->tested('Bloodseeker Magus')
    ->covers(['on-enter', 'self-damage', 'unpreventable'], '**On Enter:** Deal 3 unpreventable damage to your champion.')
    ->register();

gaScenario('bloodseeker-magus-damage-twenty-ephemerate-one-activates-it-from-the-graveyard')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lu Bu, Wrath Incarnate', damage: 20)
    ->seed('myGraveyard', 'Bloodseeker Magus')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(1)
    ->expectCardExists('myField', 'Bloodseeker Magus', 'Activated from the graveyard for (1)')
    ->expectDamage('p1Field-0', 23, 'Twenty damage counters plus the 3 from its On Enter')
    ->tested('Bloodseeker Magus')
    ->covers(['damage-threshold', 'ephemerate', 'graveyard-activation'], '[Damage 20+] **Ephemerate —** (1)')
    ->register();

// ---- Conduit of Bloodfire ----

gaScenario('conduit-of-bloodfire-enter-removes-the-opposing-champions-damage-and-gains-a-buff-per-four')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Conduit of Bloodfire', classBonus: true)
    ->patch('myField-0', ['Damage' => 9], player: 2)
    ->seed('myHand', 'Conduit of Bloodfire')
    ->play('Conduit of Bloodfire')->pay(3)
    ->expectDamage('p2Field-0', 0, 'All nine damage counters were removed from the opposing champion')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Nine counters removed: two buff counters (one per four)')
    ->tested('Conduit of Bloodfire')
    ->covers(['on-enter', 'remove-damage', 'counter'], '**On Enter:** Remove all damage counters from each champion your opponents control. For every four counters removed this way, put a **buff** counter on CARDNAME.')
    ->register();

// ---- Plasma Vanguard ----

gaScenario('plasma-vanguard-class-bonus-with-ten-or-less-damage-enters-with-three-buff-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Plasma Vanguard', classBonus: true, damage: 10)
    ->seed('myHand', 'Plasma Vanguard')
    ->play('Plasma Vanguard')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":3}', 'Ten damage counters (ten or less): three buff counters')
    ->tested('Plasma Vanguard')
    ->covers(['class-bonus', 'on-enter', 'counter', 'condition'], '[Class Bonus] **On Enter:** If your champion has ten or less damage counters on them, put three **buff** counters on CARDNAME.')
    ->register();

gaScenario('plasma-vanguard-class-bonus-with-eleven-damage-gets-no-buff-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Plasma Vanguard', classBonus: true, damage: 11)
    ->seed('myHand', 'Plasma Vanguard')
    ->play('Plasma Vanguard')->pay(3)
    ->expectProperty('p1Field-1', 'Counters', '[]', 'Eleven damage counters: no buff counters')
    ->tested('Plasma Vanguard')
    ->covers(['class-bonus', 'on-enter', 'condition'], 'If your champion has ten or less damage counters on them')
    ->register();

// ---- Wrathful Slime ----

gaScenario('wrathful-slime-class-bonus-enter-puts-a-buff-counter-per-five-damage-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Wrathful Slime', classBonus: true, level: 4, damage: 10)
    ->seed('myHand', 'Wrathful Slime')
    ->play('Wrathful Slime')->pay(4)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', 'Ten damage counters: two buff counters')
    ->tested('Wrathful Slime')
    ->covers(['class-bonus', 'on-enter', 'counter', 'damage-scaling'], '[Class Bonus] **On Enter**: For every five damage counters on your champion, put a **buff** counter on Wrathful Slime.')
    ->register();

// ---- Ischemic Soldier ----

gaScenario('ischemic-soldier-sacrificing-it-draws-two-cards-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Ischemic Soldier')
    ->seed('myField', 'Ischemic Soldier')
    ->seed('myHand', 'Umbilical Ritual')
    ->play('Umbilical Ritual')->target('myField-1')
    ->expectZoneCount('myMemory', 2, 1, 'Sacrificed as the Ritual\'s cost: two cards drawn into memory')
    ->tested('Ischemic Soldier')
    ->covers(['trigger', 'sacrifice', 'draw-into-memory'], 'Whenever you sacrifice CARDNAME, draw two cards into your memory.')
    ->register();

// ---- Embryonic Hemosynth ----

gaScenario('embryonic-hemosynth-class-bonus-enters-with-a-bulwark-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Embryonic Hemosynth', classBonus: true)
    ->seed('myHand', 'Embryonic Hemosynth')
    ->play('Embryonic Hemosynth')
    ->expectProperty('p1Field-1', 'Counters', '{"bulwark":1}', 'Class Bonus: a bulwark counter')
    ->tested('Embryonic Hemosynth')
    ->covers(['class-bonus', 'bulwark', 'counter'], '[Class Bonus] **Bulwark**')
    ->register();

// ---- Savage Sunder ----

gaScenario('savage-sunder-class-bonus-on-hit-recovers-five')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Savage Sunder', classBonus: true, player: 2)
    ->patch('myField-0', ['Damage' => 7, 'Counters' => ['_overrides' => ['classes' => 'WARRIOR']]], player: 2)
    ->seed('myHand', 'Savage Sunder', player: 2)->endTurn()
    ->play('Savage Sunder', 2)->pay(3, 2)->target('theirField-0', 2)
    ->answer(2, 'YES')
    ->expectDamage('p1Field-0', 3, 'The attack hit for 3')
    ->expectDamage('p2Field-0', 2, 'Recover 5: 7 damage down to 2')
    ->tested('Savage Sunder')
    ->covers(['class-bonus', 'on-hit', 'recover'], '[Class Bonus] **On Hit**: You may **recover 5**.')
    ->register();

gaFloatingPays('savage-sunder-class-bonus-floating-memory-pays-a-memory-cost', 'Savage Sunder', '[Class Bonus] **Floating Memory**', 'WARRIOR');
gaFloatingPays('savage-sunder-without-the-class-bonus-cannot-pay-a-memory-cost', 'Savage Sunder', '[Class Bonus] **Floating Memory**', 'MAGE', false);

// ---- Siphoning Stab ----

gaScenario('siphoning-stab-class-bonus-with-a-polearm-weapon-recovers-the-damage-dealt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Siphoning Stab', classBonus: true, damage: 6)
    ->seed('myField', 'Huaji of Heaven\'s Rise')
    ->seed('myHand', 'Siphoning Stab')
    ->reachTurnThree()
    ->play('Siphoning Stab')->pay(2)->answer(1, 'myField-1')->target('theirField-0')->pass(2)
    ->expectDamage('p1Field-0', 2, 'The hit dealt 3 + the polearm\'s 1 = 4, recovered: 6 down to 2')
    ->tested('Siphoning Stab')
    ->covers(['class-bonus', 'polearm', 'on-hit', 'recover'], '[Class Bonus] As long as the attacker is attacking using a Polearm weapon, CARDNAME has "**On Hit:** **Recover X**, where **X** is the amount of damage dealt by this hit."')
    ->register();

gaScenario('siphoning-stab-without-a-polearm-weapon-recovers-nothing')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Siphoning Stab', classBonus: true, damage: 6)
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Siphoning Stab')
    ->reachTurnThree()
    ->play('Siphoning Stab')->pay(2)->answer(1, 'myField-1')->target('theirField-0')->pass(2)
    ->expectDamage('p1Field-0', 6, 'A Sword weapon: no recover')
    ->tested('Siphoning Stab')
    ->covers(['class-bonus', 'polearm', 'condition'], 'As long as the attacker is attacking using a Polearm weapon')
    ->register();

// ---- Blood Dragon's Pact (Ally Link) ----

gaScenario('blood-dragons-pact-linked-ally-gets-plus-four-and-the-end-phase-deals-four-to-your-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Blood Dragon\'s Pact', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Blood Dragon\'s Pact')
    ->play('Blood Dragon\'s Pact')->target('myField-1')->pay(1)
    ->expectPower('p1Field-1', 5, 'Dungeon Guide 1 power + 4')
    ->expectLife('p1Field-1', 7, 'Dungeon Guide 3 life + 4')
    ->endTurn()
    ->expectDamage('p1Field-0', 4, 'At the beginning of the end phase: 4 unpreventable damage to my champion')
    ->tested('Blood Dragon\'s Pact')
    ->covers(['ally-link', 'stat-change', 'end-phase', 'self-damage'], 'Linked ally gets +4[POWER] and +4[LIFE]. At the beginning of your end phase, deal 4 unpreventable damage to your champion.')
    ->register();

// ---- Perfusive Envelopment ----

gaScenario('perfusive-envelopment-enters-with-a-blood-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Perfusive Envelopment', classBonus: true, damage: 5)
    ->seed('myHand', 'Perfusive Envelopment')
    ->play('Perfusive Envelopment')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"blood":1}', 'It entered with a blood counter')
    ->tested('Perfusive Envelopment')
    ->covers(['enters-with-counter', 'blood'], 'CARDNAME enters the field with a **blood** counter on it.')
    ->register();

gaScenario('perfusive-envelopment-recollection-recovers-the-amount-of-blood-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Perfusive Envelopment', classBonus: true, damage: 5)
    ->seed('myField', 'Perfusive Envelopment', properties: ['Counters' => ['blood' => 2]])
    ->reachTurnThree()
    ->expectDamage('p1Field-0', 3, 'Recover 2 (two blood counters): 5 down to 3')
    ->tested('Perfusive Envelopment')
    ->covers(['recollection', 'recover', 'blood'], 'At the beginning of your recollection phase, **recover X**, where **X** is the amount of **blood** counters on CARDNAME.')
    ->register();

gaScenario('perfusive-envelopment-a-sacrificed-ally-adds-a-blood-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Perfusive Envelopment', classBonus: true)
    ->seed('myField', 'Perfusive Envelopment', properties: ['Counters' => ['blood' => 1]])
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Umbilical Ritual')
    ->play('Umbilical Ritual')->target('myField-2')
    ->expectProperty('p1Field-1', 'Counters', '{"blood":2}', 'An ally was sacrificed: one more blood counter')
    ->tested('Perfusive Envelopment')
    ->covers(['trigger', 'sacrifice', 'counter', 'blood'], 'Whenever a player sacrifices an ally, put a **blood** counter on CARDNAME.')
    ->register();

// ---- Transfusive Aura ----

gaScenario('transfusive-aura-enter-draws-a-card-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Transfusive Aura', classBonus: true)
    ->seed('myHand', 'Transfusive Aura')
    ->play('Transfusive Aura')->pay(2)
    ->expectZoneCount('myMemory', 3, 1, 'Two payments plus the card drawn into memory')
    ->tested('Transfusive Aura')
    ->covers(['on-enter', 'draw-into-memory'], '**On Enter:** Draw a card into your memory.')
    ->register();

gaScenario('transfusive-aura-damage-twenty-recover-becomes-two-plus-the-amount')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lu Bu, Wrath Incarnate', damage: 20, classes: 'CLERIC')
    ->seed('myField', 'Transfusive Aura')
    ->answer(1, '-')
    ->seed('myHand', 'Novice Healer')
    ->play('Novice Healer')->pay(3)
    ->expectDamage('p1Field-0', 15, 'Novice Healer recovers 3, which becomes 2 + 3 = 5: 20 down to 15')
    ->tested('Transfusive Aura')
    ->covers(['damage-threshold', 'replacement', 'recover'], '[Damage 20+] If you would **recover** an amount, **recover 2+X** instead, where **X** is that amount.')
    ->register();

// ---- Varicose Amplification ----

gaScenario('varicose-amplification-damage-to-your-champion-adds-that-many-blood-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Varicose Amplification')
    ->seed('myField', 'Varicose Amplification')
    ->seed('myHand', 'Off With Her Head', player: 2)
    ->endTurn()
    ->play('Off With Her Head', 2)->pay(2, 2)->target('theirField-0', 2)->passBoth()
    ->expectDamage('p1Field-0', 2, 'The champion took 2')
    ->expectProperty('p1Field-1', 'Counters', '{"blood":2}', 'Two blood counters')
    ->tested('Varicose Amplification')
    ->covers(['trigger', 'damage', 'counter', 'blood'], 'Whenever an amount of damage is dealt to your champion, put that many **blood** counters on CARDNAME.')
    ->register();

gaScenario('varicose-amplification-main-phase-sacrifices-it-and-empowers-by-three-plus-the-blood-counters')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Varicose Amplification')
    ->seed('myField', 'Varicose Amplification', properties: ['Counters' => ['blood' => 2]])
    ->reachTurnThree()
    ->expectCardExists('myGraveyard', 'Varicose Amplification', 'Sacrificed at the beginning of the main phase')
    ->expectProperty('p1Field-0', 'TurnEffects', '["Tt2Ew9RmIt","EMPOWERED","EMPOWER_PLUS_5"]', 'Empowered by 3 + 2 blood counters')
    ->tested('Varicose Amplification')
    ->covers(['main-phase', 'sacrifice', 'empower', 'blood'], 'At the beginning of your main phase, sacrifice CARDNAME and **empower 3+X**, where **X** is the amount of **blood** counters that was on CARDNAME.')
    ->register();

// ---- Cardiac Vessel ----

gaScenario('cardiac-vessel-class-bonus-moves-all-damage-counters-onto-it-and-draws-with-fifteen-or-more')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lu Bu, Wrath Incarnate', damage: 16, classes: 'CLERIC')
    ->seed('myHand', 'Cardiac Vessel')
    ->play('Cardiac Vessel')->pay(3)
    ->expectDamage('p1Field-0', 0, 'All sixteen damage counters moved onto the Vessel')
    ->expectDamage('p1Field-1', 16, 'On the Vessel')
    ->expectZoneCount('myHand', 5, 1, 'Eight in hand minus the phantasia and three payments, plus the card drawn for fifteen or more counters (8 - 1 - 3 + 1)')
    ->tested('Cardiac Vessel')
    ->covers(['class-bonus', 'cost-reduction', 'on-enter', 'damage-counters', 'draw'], 'Move all damage counters from your champion onto CARDNAME. If fifteen or more counters were moved this way, draw a card.')
    ->register();

// ---- Zhang Liao, Bloodmonger ----

gaScenario('zhang-liao-bloodmonger-enter-may-recover-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Zhang Liao, Bloodmonger', damage: 5)
    ->seed('myHand', 'Zhang Liao, Bloodmonger')
    ->play('Zhang Liao, Bloodmonger')->pay(3)->answer(1, 'YES')
    ->expectDamage('p1Field-0', 2, 'Recover 3: 5 down to 2')
    ->tested('Zhang Liao, Bloodmonger')
    ->covers(['on-enter', 'recover', 'optional-choice'], '**On Enter**: You may **recover 3**.')
    ->register();

gaScenario('zhang-liao-bloodmonger-class-bonus-recollection-with-no-damage-deals-twenty-to-your-champion-and-draws-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lu Bu, Wrath Incarnate', classes: 'WARRIOR,CLERIC')
    ->seed('myField', 'Zhang Liao, Bloodmonger')
    ->answer(1, 'NO')
    ->reachTurnThree()
    ->expectDamage('p1Field-0', 20, 'Zero damage counters at the recollection: 20 unpreventable damage to my champion')
    ->expectZoneCount('myHand', 11, 1, 'Seven opening cards, the turn draw and three more')
    ->tested('Zhang Liao, Bloodmonger')
    ->covers(['class-bonus', 'recollection', 'self-damage', 'draw', 'condition'], '[Class Bonus] At the beginning of your recollection phase, if your champion has zero damage counters on them, deal 20 unpreventable damage to your champion and draw three cards.')
    ->register();

// ---- Zhao Yun, Dragonsblood ----

gaScenario('zhao-yun-dragonsblood-class-bonus-on-attack-trades-two-damage-for-plus-two-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Zhao Yun, Dragonsblood', classBonus: true)
    ->seed('myField', 'Zhao Yun, Dragonsblood')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')
    ->expectDamage('p1Field-0', 2, 'He dealt 2 unpreventable damage to my champion')
    ->expectDamage('p2Field-0', 4, 'And hit for 2 + 2')
    ->tested('Zhao Yun, Dragonsblood')
    ->covers(['class-bonus', 'on-attack', 'self-damage', 'stat-change'], '[Class Bonus] **On Attack:** You may have CARDNAME deal 2 unpreventable damage to your champion. If you do, this attack gets +2 [POWER].')
    ->register();

// ---- Huaji of Abyssal Fall ----

gaScenario('huaji-of-abyssal-fall-on-attack-deals-ten-unpreventable-damage-to-the-attacker-for-plus-three-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Huaji of Abyssal Fall', classBonus: true)
    ->seed('myField', 'Huaji of Abyssal Fall', properties: ['Counters' => ['durability' => 2]])
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->answer(1, 'YES')
    ->expectDamage('p1Field-0', 10, 'The weapon dealt 10 unpreventable damage to the attacker')
    ->expectDamage('p2Field-0', 6, 'The weapon\'s 3 power plus 3')
    ->tested('Huaji of Abyssal Fall')
    ->covers(['on-attack', 'self-damage', 'stat-change', 'optional-choice'], '**On Attack:** You may have CARDNAME deal 10 unpreventable damage to the attacker. If you do, this attack gets +3 [POWER].')
    ->register();

// ---- Lu Bu, Wrath Incarnate ----

gaScenario('lu-bu-wrath-incarnate-other-champions-get-minus-three-level-and-all-allies-minus-three-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lu Bu, Wrath Incarnate')
    ->seed('myField', 'Banner Knight')
    ->seed('myField', 'Banner Knight', player: 2)
    ->observe()
    ->expectLevel('p2Field-0', -3, 'The other champion: level 0 minus 3')
    ->expectPower('p1Field-1', -2, 'My own Banner Knight: 1 power minus 3 (all allies, including mine)')
    ->expectPower('p2Field-1', -2, 'The opposing Banner Knight too')
    ->expectLevel('p1Field-0', 4, 'Lu Bu himself is unaffected')
    ->tested('Lu Bu, Wrath Incarnate')
    ->covers(['static', 'level', 'stat-change'], 'Other champions get -3 level and all allies get -3 [POWER].')
    ->register();

gaScenario('lu-bu-wrath-incarnate-on-ally-kill-deals-the-killed-allys-power-plus-life-to-each-other-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Lu Bu, Wrath Incarnate')
    ->seed('myField', 'Banner Knight', player: 2)
    ->reachTurnThree()
    ->attack('myField-0', 'theirField-1')->pass(2)
    ->expectZoneCount('theirField', 1, 1, 'The Banner Knight died')
    ->expectDamage('p2Field-0', 3, 'Its power (-2, counted as 0) plus its life 3: 3 unpreventable damage to the other champion')
    ->tested('Lu Bu, Wrath Incarnate')
    ->covers(['on-ally-kill', 'unpreventable', 'damage'], '**On Ally Kill**: Deal **X** unpreventable damage to each other champion where **X** is the killed ally\'s power stat plus its life stat.')
    ->register();

// ---- Crimson Prescience ----

gaScenario('crimson-prescience-prevents-damage-from-sources-with-the-chosen-name')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Crimson Prescience', classBonus: true)
    ->seed('myHand', 'Crimson Prescience')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->answer(1, 'myHand-7')->pay(3)->answer(1, 'theirField-1')->passBoth()
    ->expectDamage('p1Field-0', 0, 'Dungeon Guide was the chosen name: its damage to my champion was prevented')
    ->tested('Crimson Prescience')
    ->covers(['reaction', 'name-choice', 'prevention'], 'Choose a card name. Until end of turn, if damage would be dealt to your champion by a source with that name, prevent that damage.')
    ->register();

gaScenario('without-crimson-prescience-the-dungeon-guide-hits-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)->pass(1)
    ->expectDamage('p1Field-0', 1, 'Control: the unprevented 1 damage')
    ->tested('Crimson Prescience')
    ->covers(['reaction', 'condition'], 'if damage would be dealt to your champion by a source with that name, prevent that damage.')
    ->register();

// ---- Limitless Defiance ----

gaScenario('limitless-defiance-banishes-the-top-card-of-the-deck-for-each-damage-counter-on-your-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Limitless Defiance', classBonus: true, damage: 4)
    ->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Limitless Defiance')
    ->play('Limitless Defiance')->pay(12)
    ->expectZoneCount('myBanish', 4, 1, 'Four damage counters: the top four cards of the deck were banished')
    ->tested('Limitless Defiance')
    ->covers(['banish', 'damage-scaling', 'deck'], 'For each damage counter on your champion, banish the top card of your deck.')
    ->register();

// ---- Lu Bu, Wrath Incarnate (the other printing, l5izukgdmh) ----

gaScenario('lu-bu-wrath-incarnate-alternate-printing-other-champions-get-minus-three-level-and-all-allies-minus-three-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: '#l5izukgdmh')
    ->seed('myField', 'Banner Knight')
    ->seed('myField', 'Banner Knight', player: 2)
    ->observe()
    ->expectLevel('p2Field-0', -3, 'The other champion: level 0 minus 3')
    ->expectPower('p1Field-1', -2, 'My own Banner Knight: 1 power minus 3')
    ->expectPower('p2Field-1', -2, 'The opposing Banner Knight too')
    ->tested('#l5izukgdmh')
    ->covers(['static', 'level', 'stat-change'], 'Other champions get -3 level and all allies get -3 [POWER].')
    ->register();
