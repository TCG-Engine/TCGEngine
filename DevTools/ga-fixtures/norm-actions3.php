<?php
/**
 * Phase 3d (Norm): actions, third batch (prevention and attack restrictions). 'quiet' deck.
 * Prevention is shown on turn three: the Guide attacks the opponent's Lumbering Steed (3 power) and is retaliated against.
 */

// ---- Evasive Positioning ----

gaScenario('evasive-positioning-prevents-the-next-two-damage-to-the-target-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Evasive Positioning')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Evasive Positioning')
    ->reachTurnThree(fastCardInHand: true)
    ->castSpell('Evasive Positioning', pay: 1, window: '', target: 'myField-1')
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectDamage('p1Field-1', 1, 'The Steed retaliated for 3, two of it prevented')
    ->tested('Evasive Positioning')
    ->covers(['prevention', 'targeting'], 'Prevent the next 2 damage that would be dealt to target unit this turn.')
    ->register();

gaScenario('evasive-positioning-a-champion-target-becomes-distant')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Evasive Positioning')
    ->seed('myHand', 'Evasive Positioning')
    ->castSpell('Evasive Positioning', pay: 1, window: '', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","PREVENT_ALL_2","DISTANT"]', 'If that unit is a champion, it becomes distant (and the prevention marker is on it)')
    ->tested('Evasive Positioning')
    ->covers(['distant', 'condition'], 'If that unit is a champion, it becomes **distant**.')
    ->register();

// ---- Gearshift Block ----

gaScenario('gearshift-block-prevents-the-next-damage-to-the-target-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gearshift Block')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Gearshift Block')
    ->reachTurnThree(fastCardInHand: true)
    ->castSpell('Gearshift Block', pay: 3, window: '', target: 'myField-1')
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectDamage('p1Field-1', 0, 'All the damage dealt to the ally was prevented')
    ->tested('Gearshift Block')
    ->covers(['prevention', 'targeting'], 'The next time damage would be dealt to target ally you control this turn, prevent that damage.')
    ->register();

gaScenario('gearshift-block-class-bonus-costs-one-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gearshift Block', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Gearshift Block')
    ->castSpell('Gearshift Block', pay: 2, window: '', target: 'myField-1')
    ->expectZoneCount('myMemory', 2, 1, '[Class Bonus] Two cards paid instead of three')
    ->tested('Gearshift Block')
    ->covers(['class-bonus', 'cost-reduction'], '[Class Bonus] This card costs 1 less to activate.')
    ->register();

// ---- Fleeting Guard ----

gaScenario('fleeting-guard-prevents-one-damage-per-ephemeral-object-plus-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Fleeting Guard')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Haunting Apparition', properties: ['TurnEffects' => ['EPHEMERAL']])
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Fleeting Guard')
    ->reachTurnThree(fastCardInHand: true)
    ->castSpell('Fleeting Guard', pay: 2, window: '', target: 'myField-1')
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectDamage('p1Field-1', 1, 'One ephemeral object: X = 2 of the Steed\'s 3 damage prevented')
    ->tested('Fleeting Guard')
    ->covers(['prevention', 'variable', 'ephemeral', 'targeting'], 'Until end of turn, if target unit would be dealt damage, prevent **X** of that damage where **X** is the amount of **ephemeral** objects you control plus 1.')
    ->register();

// ---- Resolute Stand ----

gaScenario('resolute-stand-prevents-three-combat-damage-to-the-target-unit')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Resolute Stand')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Resolute Stand')
    ->reachTurnThree(fastCardInHand: true)
    ->castSpell('Resolute Stand', pay: 3, window: '', target: 'myField-1')
    ->attack('myField-1', 'theirField-1')->answer(2, 'myField-1')
    ->expectDamage('p1Field-1', 0, 'Three of the retaliation\'s combat damage prevented')
    ->tested('Resolute Stand')
    ->covers(['prevention', 'combat', 'targeting'], 'If target unit would be dealt combat damage this turn, prevent 3 of that damage.')
    ->register();

gaScenario('resolute-stand-level-two-is-free-and-skips-the-next-draw-phase')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Resolute Stand', level: 2)
    ->seed('myHand', 'Resolute Stand')
    ->castSpell('Resolute Stand', pay: 0, window: '', target: 'myField-0')
    ->expectZoneCount('myMemory', 0, 1, '[Level 2+] Activated without paying its reserve cost')
    ->tested('Resolute Stand')
    ->covers(['level-gate', 'alt-cost'], '[Level 2+] You may activate this card without paying its reserve cost. If you do, skip your next draw phase.')
    ->register();

// ---- Bring Down the Mighty ----

gaScenario('bring-down-the-mighty-the-target-ally-cannot-attack-on-the-next-turn')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bring Down the Mighty')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Bring Down the Mighty')
    ->castSpell('Bring Down the Mighty', pay: 1, window: '', target: 'theirField-1')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->expectProperty('p2Field-1', 'Status', '2', 'The ally cannot attack: it stays awake')
    ->tested('Bring Down the Mighty')
    ->covers(['restriction', 'targeting'], 'Until the beginning of your next turn, target ally can\'t attack.')
    ->register();

gaScenario('bring-down-the-mighty-a-unique-ally-draws-into-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bring Down the Mighty')
    ->seed('myField', 'Camil, Basked Abundance', player: 2)
    ->seed('myHand', 'Bring Down the Mighty')
    ->castSpell('Bring Down the Mighty', pay: 1, window: '', target: 'theirField-1')
    ->expectZoneCount('myMemory', 2, 1, 'A unique ally: one paid plus one drawn into memory')
    ->tested('Bring Down the Mighty')
    ->covers(['condition', 'draw-into-memory'], 'If that ally is unique, draw a card into your memory.')
    ->register();

// ---- Provoking Stand ----

gaScenario('provoking-stand-the-target-ally-gains-taunt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Provoking Stand')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Provoking Stand')
    ->castSpell('Provoking Stand', pay: 1, window: '', target: 'myField-1')
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while the ally is awake')
    ->tested('Provoking Stand')
    ->covers(['taunt', 'targeting'], 'Target ally gains **taunt** until the beginning of your next turn.')
    ->register();

// ---- Plea for Peace ----

gaScenario('plea-for-peace-opposing-attack-declarations-cost-one')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Plea for Peace')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Plea for Peace')
    ->castSpell('Plea for Peace', pay: 1, window: '')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->pay(1, 2)->answer(2, 'theirField-0')
    ->expectZoneCount('myMemory', 1, 2, 'The attack declaration cost the opponent one card')
    ->expectDamage('p1Field-0', 1, 'The attack was declared after paying')
    ->tested('Plea for Peace')
    ->covers(['restriction', 'cost'], 'Until the beginning of your next turn, players can\'t declare attacks unless they pay (1) for each attack declaration.')
    ->register();

// ---- Peaceful Reunion ----

gaScenario('peaceful-reunion-neither-you-nor-the-target-player-can-declare-attacks')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Peaceful Reunion')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->seed('myHand', 'Peaceful Reunion')
    ->castSpell('Peaceful Reunion', pay: 4, window: '')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')
    ->expectProperty('p2Field-1', 'Status', '2', 'The opponent cannot declare an attack: the Guide stays awake')
    ->tested('Peaceful Reunion')
    ->covers(['restriction', 'banish-self'], 'Until the beginning of your next turn, you and target player can\'t declare attacks. Banish Peaceful Reunion.')
    ->register();
