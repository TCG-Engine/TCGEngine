<?php
/**
 * Phase 3d (Norm), boons that had no implementation at all until this pass (Nuwa, ...). 'quiet' deck.
 */

// ---- Lesser Boon of Nuwa ----

gaScenario('lesser-boon-of-nuwa-bestowed-generates-a-refurbish-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Nuwa', classBonus: true)
    ->seed('myHand', 'Lesser Boon of Nuwa')
    ->castSpell('Lesser Boon of Nuwa', pay: 2)
    ->expectZoneCount('myMemory', 3, 1, 'The two paid cards plus the generated Refurbish')
    ->expectCardExists('myMemory', 'Refurbish', 'Refurbish was generated into memory')
    ->tested('Lesser Boon of Nuwa')
    ->covers(['generate', 'on-gain'], 'As you gain this boon, **generate** a Refurbish card and put it into your memory.')
    ->register();

gaScenario('lesser-boon-of-nuwa-activating-a-craft-card-gives-a-weapon-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Nuwa', classBonus: true)
    ->seed('myField', 'Lesser Boon of Nuwa')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Refurbish')
    ->play('Refurbish')->pay(4)->target('myField-2')->target('myField-2')->passBoth()
    ->expectPower('p1Field-2', 2, 'Training Sword: 1 POWER plus 1 from the boon')
    ->expectProperty('p1Field-2', 'Counters', '{"durability":4}', 'Refurbish also put its two durability counters on the Sword (2 + 2)')
    ->tested('Lesser Boon of Nuwa')
    ->covers(['trigger', 'stat-change'], 'Whenever you activate a Craft card, target weapon gets +1[POWER] until end of turn.')
    ->register();

gaScenario('lesser-boon-of-nuwa-a-card-that-is-not-a-craft-does-not-trigger')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lesser Boon of Nuwa', classBonus: true)
    ->seed('myField', 'Lesser Boon of Nuwa')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)->answer(1, 'NO')
    ->expectPower('p1Field-2', 1, 'Dungeon Guide is not a Craft card: no bonus')
    ->expectDqEmpty()
    ->tested('Lesser Boon of Nuwa')
    ->covers(['trigger', 'condition'], 'Whenever you activate a Craft card, target weapon gets +1[POWER] until end of turn.')
    ->register();

// ---- Greater Boon of Isis ----

gaScenario('greater-boon-of-isis-adds-level-divided-by-three-to-spell-damage')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Isis', classBonus: true, level: 3)
    ->seed('myField', 'Greater Boon of Isis')
    ->seed('myHand', 'Aenean Scorching Comet')
    ->castSpell('Aenean Scorching Comet', pay: 2, target: 'theirField-0')
    ->expectDamage('theirField-0', 5, 'Level 3 (Class Bonus): Scorching Comet deals 4, plus 3 / 3 = 1 from the boon')
    ->tested('Greater Boon of Isis')
    ->covers(['damage', 'scaling', 'static'], 'Spell cards you own on the effects stack have “If this source would deal damage, it deals that much damage plus **X** instead, where **X** is **LV** divided by three, rounded down.”')
    ->register();

gaScenario('greater-boon-of-isis-rounds-down-below-level-three')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Isis', classBonus: true, level: 2)
    ->seed('myField', 'Greater Boon of Isis')
    ->seed('myHand', 'Aenean Scorching Comet')
    ->castSpell('Aenean Scorching Comet', pay: 2, target: 'theirField-0')
    ->expectDamage('theirField-0', 2, 'Level 2: 2 / 3 rounds down to 0, so the Comet deals its printed 2')
    ->tested('Greater Boon of Isis')
    ->covers(['damage', 'scaling'], 'where **X** is **LV** divided by three, rounded down.')
    ->register();

// ---- Greater Boon of Dux ----

gaScenario('greater-boon-of-dux-lets-a-wind-foster-ally-be-activated-without-the-wind-element')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Dux', classBonus: true)
    ->seed('myField', 'Greater Boon of Dux')
    ->seed('myHand', 'Young Peacekeeper')
    ->play('Young Peacekeeper')->pay(3)
    ->expectCardExists('myField', 'Young Peacekeeper', 'The wind ally was activated with no wind element enabled')
    ->tested('Greater Boon of Dux')
    ->covers(['element-requirement', 'static'], 'Ignore the elemental requirements of non-advanced element ally cards with **foster** you activate.')
    ->register();

gaScenario('without-greater-boon-of-dux-a-wind-foster-ally-cannot-be-activated-without-wind')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Dux', classBonus: true)
    ->seed('myHand', 'Young Peacekeeper')
    ->action(1, 10002, 'myHand-7!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myField', 1, 1, 'Only the champion: wind is not enabled')
    ->tested('Greater Boon of Dux')
    ->covers(['element-requirement', 'condition'], 'Ignore the elemental requirements of non-advanced element ally cards with **foster** you activate.')
    ->register();

gaScenario('greater-boon-of-dux-gets-a-training-counter-whenever-an-ally-becomes-fostered')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Dux', classBonus: true)
    ->readyFor('Young Peacekeeper')
    ->seed('myField', 'Greater Boon of Dux')
    ->seed('myField', 'Young Peacekeeper')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'Counters', '{"training":1}', 'Young Peacekeeper became fostered at the recollection phase')
    ->tested('Greater Boon of Dux')
    ->covers(['trigger', 'counter'], 'Whenever an ally you control becomes **fostered**, put a **training** counter on CARDNAME.')
    ->register();

gaScenario('greater-boon-of-dux-with-two-training-counters-buffs-allies-and-fosters-them-as-they-enter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Dux', classBonus: true)
    ->seed('myField', 'Greater Boon of Dux', properties: ['Counters' => ['training' => 2]])
    ->seed('myHand', 'Young Peacekeeper')
    ->play('Young Peacekeeper')->pay(3)
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","FOSTERED"]', 'The ally became fostered as it entered')
    ->expectPower('p1Field-2', 3, 'Young Peacekeeper: 1 POWER +1 while fostered +1 from the boon')
    ->tested('Greater Boon of Dux')
    ->covers(['static', 'stat-change', 'foster'], 'As long as CARDNAME has two or more **training** counters on it, allies you control get +1[POWER] and have “**On Enter: **This ally becomes **fostered**.”')
    ->register();

// ---- Greater Boon of Horses ----

gaScenario('greater-boon-of-horses-puts-horses-up-to-ten-reserve-from-memory-onto-the-field-with-buff-counters-and-draws-for-each')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Horses', classBonus: true, level: 2)
    ->seed('myMemory', 'Lumbering Steed')
    ->seed('myMemory', 'Unbroken Mustang')
    ->seed('myMemory', 'Caretaker Horse')
    ->seed('myMemory', 'Ordinary Horse')
    ->seed('myHand', 'Greater Boon of Horses')
    ->castSpell('Greater Boon of Horses', pay: 6)
    ->answer(1, 'myMemory-0')->answer(1, 'myMemory-0')->answer(1, 'myMemory-0')
    ->expectZoneCount('myField', 5, 1, 'Champion, the boon and three horses (4 + 4 + 2 = 10 reserve)')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', 'Lumbering Steed entered with a buff counter')
    ->expectProperty('p1Field-4', 'Counters', '{"buff":1}', 'Caretaker Horse entered with a buff counter')
    ->expectZoneCount('myMemory', 10, 1, 'Ordinary Horse (3 would exceed the budget) stayed, six paid cards, three drawn into memory')
    ->tested('Greater Boon of Horses')
    ->covers(['zone-movement', 'counter', 'draw-discard'], 'As you gain this boon, put any amount of Horse ally cards with total reserve cost 10 or less from your memory onto the field.')
    ->register();

// ---- Greater Boon of Enki ----

gaScenario('greater-boon-of-enki-bestowed-draws-a-card')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Enki', classBonus: true, level: 2)
    ->seed('myHand', 'Greater Boon of Enki')
    ->castSpell('Greater Boon of Enki', pay: 5)
    ->expectZoneCount('myHand', 3, 1, 'Hand: 7 + the boon - the boon - five paid + one drawn')
    ->tested('Greater Boon of Enki')
    ->covers(['draw-discard', 'on-gain'], 'As you gain this boon, draw a card.')
    ->register();

gaScenario('greater-boon-of-enki-summons-a-copy-of-a-cheap-token-the-first-time-each-turn-only')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Enki', classBonus: true, level: 2)
    ->seed('myField', 'Greater Boon of Enki')
    ->seed('myHand', 'Cell Production')
    ->seed('myHand', 'Cell Production')
    ->play('Cell Production')->pay(3)
    ->expectZoneCount('myField', 5, 1, 'Champion, Enki and three Powercells: the two summoned plus one copy', step: 4)
    ->play('Cell Production')->pay(3)
    ->expectZoneCount('myField', 7, 1, 'The second time this turn there is no copy: two more Powercells')
    ->tested('Greater Boon of Enki')
    ->covers(['trigger', 'token', 'once-per-turn'], 'Whenever you **summon** one or more tokens with reserve cost 1 or less for the first time each turn, **summon** a copy of one of those tokens.')
    ->register();

gaScenario('without-greater-boon-of-enki-cell-production-summons-exactly-two-powercells')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Cell Production', classBonus: true)
    ->seed('myHand', 'Cell Production')
    ->play('Cell Production')->pay(3)
    ->expectZoneCount('myField', 3, 1, 'Champion and two Powercells')
    ->tested('Cell Production')
    ->covers(['token'], '**Summon** two Powercell tokens rested.')
    ->register();

// ---- Greater Boon of Inari ----

gaScenario('greater-boon-of-inari-removes-a-preparation-counter-for-agility-and-buffs-the-next-attack-on-a-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'level' => 2, 'preparation' => 2]])
    ->seed('myField', 'Greater Boon of Inari')
    ->seed('myField', 'Training Sword')
    ->reachTurnThree()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectGlobalEffect(1, 'AGILITY_3', 'The champion gained agility 3 for this turn')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"level":2,"preparation":1}', 'One preparation counter was removed', step: 4)
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-2')->answer(1, 'theirField-0')->pass(2)
    ->expectDamage('theirField-0', 3, 'Training Sword\'s 1 POWER plus 2 from the boon')
    ->tested('Greater Boon of Inari')
    ->covers(['activated-ability', 'cost', 'agility', 'stat-change'], '**Remove a preparation counter from your champion: **You gain **agility 3** for this turn.', 'Whenever you gain **agility**, your champion\'s next attack this turn against a champion gets +2[POWER].')
    ->register();

gaScenario('greater-boon-of-inari-is-refused-without-a-preparation-counter')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'level' => 2]])
    ->seed('myField', 'Greater Boon of Inari')
    ->reachTurnThree()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectGlobalEffect(1, 'AGILITY_3', 'No preparation counter to remove: no agility', 0)
    ->tested('Greater Boon of Inari')
    ->covers(['activated-ability', 'cost', 'condition'], '**Remove a preparation counter from your champion: **You gain **agility 3** for this turn.')
    ->register();

gaScenario('greater-boon-of-inari-can-only-be-used-once-per-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'ASSASSIN'], 'level' => 2, 'preparation' => 2]])
    ->seed('myField', 'Greater Boon of Inari')
    ->reachTurnThree()
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"ASSASSIN"},"level":2,"preparation":1}', 'The second activation was refused: still one counter left')
    ->tested('Greater Boon of Inari')
    ->covers(['activated-ability', 'once-per-turn'], 'Activate this ability only at slow speed and only once per turn.')
    ->register();

// ---- Greater Boon of Luxera ----

gaScenario('greater-boon-of-luxera-the-next-activation-of-the-chosen-name-draws-two-and-recovers-three-once')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Greater Boon of Luxera', classBonus: true, damage: 5)
    ->seed('myHand', 'Greater Boon of Luxera')
    ->seed('myHand', 'Dungeon Guide')
    ->seed('myHand', 'Dungeon Guide')
    ->play('Greater Boon of Luxera')->answer(1, 'Dungeon Guide')
    ->play('Dungeon Guide')->pay(3)->answer(1, 'NO')
    ->expectDamage('p1Field-0', 2, 'Recover 3 from 5 damage', step: 6)
    ->expectZoneCount('myHand', 7, 1, 'Hand: 10 - boon - Guide - 3 paid + 2 drawn', step: 6)
    ->play('Dungeon Guide')->pay(3)->answer(1, 'NO')
    ->expectZoneCount('myHand', 3, 1, 'The second Dungeon Guide drew nothing: only the next activation counts')
    ->tested('Greater Boon of Luxera')
    ->covers(['name-choice', 'draw-discard', 'recover', 'once'], 'As you gain this boon, choose a non-champion non-regalia card name. The next time you activate a card with the same name this game, draw two cards and **recover 3**.')
    ->register();

// ---- Lesser Boon of Knox ----

gaScenario('lesser-boon-of-knox-banishes-a-random-memory-card-face-down-draws-and-the-opponent-can-activate-it')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
20 Dungeon Guide

DECK
    )
    ->readyFor('Lesser Boon of Knox')
    ->champion(classes: 'ANOMALY', player: 1)
    ->champion(classes: 'MAGE', player: 2)
    ->seed('myHand', 'Lesser Boon of Knox')
    ->castSpell('Lesser Boon of Knox', pay: 4)
    ->expectZoneCount('myBanish', 1, 1, 'One memory card was banished')
    ->expectZoneCount('myMemory', 3, 1, 'Four paid cards, one banished')
    ->expectZoneCount('myHand', 4, 1, '8 - boon - four paid + one drawn')
    ->tested('Lesser Boon of Knox')
    ->covers(['random', 'banish', 'draw-discard'], 'As you gain this boon, each opponent banishes a card at random from your memory face down.', 'You draw a card for each card banished from your memory this way.')
    ->register();

gaScenario('lesser-boon-of-knox-the-opponent-activates-the-banished-card-ignoring-elements')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
20 Young Peacekeeper

DECK
    )
    ->readyFor('Lesser Boon of Knox')
    ->champion(classes: 'ANOMALY', player: 1)
    ->champion(classes: 'GUARDIAN', player: 2)
    ->seed('myHand', 'Lesser Boon of Knox')
    ->castSpell('Lesser Boon of Knox', pay: 4)
    ->endTurn(1)->pass(2)
    ->action(2, 10002, 'theirBanish-0!FSM!')->pay(3, 2)
    ->expectCardExists('myField', 'Young Peacekeeper', 'The opponent activated the wind Young Peacekeeper from player 1\'s banishment with no wind element', 2)
    ->expectDqEmpty()
    ->tested('Lesser Boon of Knox')
    ->covers(['activate-from-banish', 'element-requirement'], 'As long as that card is banished, that opponent may look at it and activate it, ignoring its elemental requirements.')
    ->register();

// ---- Greater Boon of Detachment ----

gaScenario('greater-boon-of-detachment-lets-an-unloaded-ranger-gun-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'RANGER'], 'level' => 2]])
    ->seed('myField', 'Greater Boon of Detachment')
    ->seed('myField', 'Contraband Revolver')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-2')->answer(1, 'theirField-0')->pass(2)
    ->expectDamage('theirField-0', 3, 'The unloaded Revolver attacked: 1 POWER plus 2 for its Class Bonus against a champion')
    ->tested('Greater Boon of Detachment')
    ->covers(['static', 'weapon', 'attack'], 'You may declare attacks with unloaded Ranger weapons you control as though they were loaded.')
    ->register();

gaScenario('without-greater-boon-of-detachment-an-unloaded-gun-cannot-attack')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'RANGER'], 'level' => 2]])
    ->seed('myField', 'Contraband Revolver')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-0!FSM!')
    ->expectDqEmpty()
    ->tested('Contraband Revolver')
    ->covers(['weapon', 'condition'], '*(Gun — Must be loaded to use for an attack and can’t be used with an attack card.)*')
    ->register();

gaScenario('greater-boon-of-detachment-gives-a-ranger-weapon-plus-three-power-while-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['TurnEffects' => ['DISTANT'], 'Counters' => ['_overrides' => ['classes' => 'RANGER'], 'level' => 2]])
    ->seed('myField', 'Greater Boon of Detachment')
    ->seed('myField', 'Contraband Revolver')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->answer(1, 'myField-2')
    ->expectPower('p1Field-2', 4, 'Contraband Revolver: 1 POWER plus 3')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectDqEmpty()
    ->tested('Greater Boon of Detachment')
    ->covers(['activated-ability', 'stat-change', 'once-per-turn', 'distant'], '(3): Target Ranger weapon gets +3[POWER] until end of turn. Activate this ability only once during each of your turns, and only if your champion is **distant**.')
    ->register();

gaScenario('greater-boon-of-detachment-power-ability-is-refused-while-the-champion-is-not-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'RANGER'], 'level' => 2]])
    ->seed('myField', 'Greater Boon of Detachment')
    ->seed('myField', 'Contraband Revolver')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectPower('p1Field-2', 1, 'Not distant: the ability was refused')
    ->expectDqEmpty()
    ->tested('Greater Boon of Detachment')
    ->covers(['activated-ability', 'distant', 'condition'], 'Activate this ability only once during each of your turns, and only if your champion is **distant**.')
    ->register();

// ---- Gate of Alterity ----

gaScenario('gate-of-alterity-moves-to-the-bottom-of-the-lineage-and-makes-the-champion-the-chosen-class')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myField', 'Gate of Alterity')
    ->seed('myField', 'Sealed Blade')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, '6')
    ->expectZoneCount('myField', 2, 1, 'The Gate left the field: champion and Sealed Blade')
    ->expectProperty('p1Field-0', 'Subcards', '["j9s7xQdpf1"]', 'The Gate is the bottom of the lineage')
    ->expectPower('p1Field-1', 3, 'The champion is a Warrior now: Sealed Blade gets its Class Bonus (2 + 1)')
    ->tested('Gate of Alterity')
    ->covers(['lineage', 'class-grant', 'rest'], '[REST]**:** Put CARDNAME on the bottom of your champion\'s lineage then choose a class. CARDNAME gains "**Inherited Effect:** This object is the chosen class in addition to its other classes."')
    ->register();

// ---- Prismatic Spirit ----

gaScenario('prismatic-spirit-draws-six-and-enables-the-two-chosen-basic-elements')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'GUARDIAN']]])
    ->seed('myField', 'Prismatic Spirit')
    ->seed('myHand', 'Young Peacekeeper')
    ->answer(1, '1,2')
    ->expectZoneCount('myHand', 14, 1, 'Seven in hand plus the seeded Young Peacekeeper plus six drawn', step: 1)
    ->action(1, 10002, 'myHand-13!FSM!')->pay(3)
    ->expectCardExists('myField', 'Young Peacekeeper', 'Wind was chosen: the wind ally could be activated')
    ->tested('Prismatic Spirit')
    ->covers(['on-enter', 'draw-discard', 'element-grant'], '**On Enter:** Draw six cards then choose two basic elements. CARDNAME gains "**Inherited Effect:** This object is each of the chosen elements in addition to its other elements."')
    ->register();

gaScenario('prismatic-spirit-does-not-enable-the-basic-element-that-was-not-chosen')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'GUARDIAN']]])
    ->seed('myField', 'Prismatic Spirit')
    ->seed('myHand', 'Young Peacekeeper')
    ->answer(1, '0,1')
    ->action(1, 10002, 'myHand-13!FSM!')
    ->expectDqEmpty()
    ->expectZoneCount('myField', 2, 1, 'Fire and water chosen: the wind ally was refused')
    ->tested('Prismatic Spirit')
    ->covers(['element-grant', 'condition'], 'choose two basic elements')
    ->register();

// ---- Dusklight Communion ----

if (!function_exists('gaDusklightCommunion')) {
    function gaDusklightCommunion(string $slug): GaScenario {
        return gaScenario($slug)->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Arisanna, Astral Zenith
1 Diana, Duskstalker
# Main
20 Dungeon Guide

DECK
        )->patch('myField-0', ['Counters' => ['_overrides' => ['classes' => 'CLERIC'], 'level' => 2]])->seed('myHand', 'Dusklight Communion');
    }
}

gaDusklightCommunion('dusklight-communion-banishing-an-umbra-card-from-the-material-deck-takes-one-level-from-every-champion')
    ->play('Dusklight Communion')->answer(1, 'myMaterial-1')->pay(3)
    ->expectCardExists('myBanish', 'Diana, Duskstalker', 'The umbra card was banished from the material deck as the additional cost')
    ->expectLevel('p1Field-0', 1, 'Champions get -1 level: 2 down to 1')
    ->tested('Dusklight Communion')
    ->covers(['additional-cost', 'material-deck', 'level'], 'As an additional cost to activate this card, banish an astra or umbra element card from your material deck.', 'If the banished card was umbra element, CARDNAME gains "Champions get -1 level."')
    ->register();

gaDusklightCommunion('dusklight-communion-banishing-an-astra-card-destroys-a-phantasia')
    ->seed('myField', 'Unstable Fractal', player: 2)
    ->play('Dusklight Communion')->answer(1, 'myMaterial-0')->pay(3)->answer(1, 'theirField-1')
    ->expectZoneCount('theirField', 1, 1, 'The opponent\'s phantasia was destroyed')
    ->expectLevel('p1Field-0', 2, 'Astra: no level change')
    ->tested('Dusklight Communion')
    ->covers(['destroy', 'phantasia', 'condition'], 'If the banished card was astra element, destroy up to one target phantasia.')
    ->register();

gaDusklightCommunion('dusklight-communion-cannot-be-activated-without-an-astra-or-umbra-card-in-the-material-deck')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
20 Dungeon Guide

DECK
    )
    ->action(1, 10002, 'myHand-7!FSM!')
    ->expectDqEmpty()
    ->tested('Dusklight Communion')
    ->covers(['additional-cost', 'condition'], 'As an additional cost to activate this card, banish an astra or umbra element card from your material deck.')
    ->register();

// ---- Heavenly Guide ----
// The engine approximates "a card on top of its lineage that wasn't played" as "the champion has any lineage card" (nothing tracks whether a lineage card was played).

if (!function_exists('gaHeavenlyGuide')) {
    function gaHeavenlyGuide(string $slug): GaScenario {
        return gaScenario($slug)->deck(<<<'DECK'
# Material
1 Spirit of Fire
1 Lorraine, Wandering Warrior
1 Lorraine, Blademaster
# Main
20 Dungeon Guide

DECK
        )->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Fire'], level: 1)
         ->seed('myHand', 'Heavenly Guide');
    }
}

gaHeavenlyGuide('heavenly-guide-level-two-levels-up-your-champion-against-a-non-spirit-champion-with-lineage')
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Fire'], player: 2)
    ->play('Heavenly Guide')->pay(3)
    ->expectCardExists('myField', 'Lorraine, Blademaster', 'The champion levelled up into Lorraine, Blademaster')
    ->tested('Heavenly Guide')
    ->covers(['on-enter', 'level-up', 'condition'], '[Level 2+] **On Enter:** If an opponent controls a non-Spirit champion with a card on top of its lineage that wasn\'t played, level up your champion.')
    ->register();

gaHeavenlyGuide('heavenly-guide-does-not-level-up-against-a-spirit-champion')
    ->play('Heavenly Guide')->pay(3)
    ->expectCardExists('myField', 'Lorraine, Wandering Warrior', 'The opponent\'s champion is a Spirit: no level up')
    ->tested('Heavenly Guide')
    ->covers(['on-enter', 'condition'], 'If an opponent controls a non-Spirit champion with a card on top of its lineage that wasn\'t played, level up your champion.')
    ->register();

gaHeavenlyGuide('heavenly-guide-below-level-two-does-nothing')
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Fire'], level: 0)
    ->champion(cardName: 'Lorraine, Wandering Warrior', lineage: ['Spirit of Fire'], player: 2)
    ->play('Heavenly Guide')->pay(3)
    ->expectCardExists('myField', 'Lorraine, Wandering Warrior', 'Level 1 is below [Level 2+]: no level up')
    ->tested('Heavenly Guide')
    ->covers(['level-threshold', 'condition'], '[Level 2+] **On Enter:**')
    ->register();
