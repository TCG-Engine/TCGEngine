<?php
/**
 * Phase 3d (Norm): unique allies, first batch (statics, enters and simple triggers). 'quiet' deck.
 */

// ---- Bedivere, Woodland Overseer ----

gaScenario('bedivere-with-another-beast-ally-has-taunt')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bedivere, Woodland Overseer')
    ->seed('myField', 'Bedivere, Woodland Overseer', properties: ['Status' => 2])
    ->seed('myField', 'Gray Wolf')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Another Beast ally: Bedivere has taunt and protects the champion')
    ->tested('Bedivere, Woodland Overseer')
    ->covers(['condition', 'taunt'], 'Bedivere has **taunt** as long as you control another Animal or Beast ally.')
    ->register();

gaScenario('bedivere-class-bonus-level-three-on-death-buffs-each-beast-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Bedivere, Woodland Overseer', classBonus: true, level: 3)
    ->seed('myField', 'Bedivere, Woodland Overseer')
    ->seed('myField', 'Gray Wolf')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2, 'Counters' => ['buff' => 1]])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Class Bonus] [Level 3+] On Death: the Beast ally got a buff counter (it moved down to index 1)')
    ->tested('Bedivere, Woodland Overseer')
    ->covers(['class-bonus', 'level-gate', 'on-death', 'counter'], '[Class Bonus] [Level 3+] **On Death:** Put a **buff** counter on each Animal and/or Beast ally you control.')
    ->register();

// ---- Blanche, Sheltering Saint ----

gaScenario('blanche-prevents-non-combat-damage-to-another-unit-equal-to-the-cards-in-memory')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Blanche, Sheltering Saint')
    ->seed('myField', 'Blanche, Sheltering Saint')
    ->seed('myField', 'Giant Tortoise')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')
    ->seed('myHand', 'Merciless Toss')
    ->castSpell('Merciless Toss', pay: 3, window: '', target: 'myField-2')
    ->expectDamage('p1Field-2', 0, 'Merciless Toss\'s 2 damage to the other unit was prevented (five cards in memory)')
    ->tested('Blanche, Sheltering Saint')
    ->covers(['prevention', 'variable'], 'If another unit you control would be dealt non-combat damage, prevent an amount of that damage equal to the amount of cards in your memory.')
    ->register();

// ---- Gildas, Chronicler of Aesa ----

gaScenario('gildas-balance-with-equal-hand-and-memory-gets-plus-three-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gildas, Chronicler of Aesa')
    ->seed('myField', 'Gildas, Chronicler of Aesa')
    ->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')
    ->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')->seed('myMemory', 'Dungeon Guide')
    ->observe()
    ->expectPower('p1Field-1', 4, 'Seven cards in hand and in memory: 1 + 3 power')
    ->tested('Gildas, Chronicler of Aesa')
    ->covers(['balance', 'condition', 'stat-change'], '***Balance** — *Gildas gets +3 [POWER] as long as the amount of cards in your hand and memory are equal.')
    ->register();

gaScenario('gildas-balance-with-unequal-hand-and-memory-has-its-printed-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Gildas, Chronicler of Aesa')
    ->seed('myField', 'Gildas, Chronicler of Aesa')
    ->observe()
    ->expectPower('p1Field-1', 1, 'Seven in hand and none in memory: the printed 1 power')
    ->tested('Gildas, Chronicler of Aesa')
    ->covers(['balance', 'condition'], '***Balance** — *Gildas gets +3 [POWER] as long as the amount of cards in your hand and memory are equal.')
    ->register();

// ---- Geldus, Terror of Dorumegia ----

gaScenario('geldus-pride-four-does-not-obey-a-level-three-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Geldus, Terror of Dorumegia', level: 3)
    ->seed('myField', 'Geldus, Terror of Dorumegia')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'Pride 4: a level 3 champion is not enough')
    ->tested('Geldus, Terror of Dorumegia')
    ->covers(['pride', 'keyword'], '**Pride 4**')
    ->register();

gaScenario('geldus-level-three-costs-two-less')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Geldus, Terror of Dorumegia', level: 3)
    ->seed('myHand', 'Geldus, Terror of Dorumegia')
    ->play('Geldus, Terror of Dorumegia')->pay(4)
    ->expectCardExists('myField', 'Geldus, Terror of Dorumegia', '[Level 3+] 6 - 2 = 4')
    ->tested('Geldus, Terror of Dorumegia')
    ->covers(['level-gate', 'cost-reduction'], '[Level 3+] This card costs 2 less to activate.')
    ->register();

// ---- Lancelot, Goliath of Aesa ----

gaScenario('lancelot-hindered-enters-rested')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lancelot, Goliath of Aesa')
    ->seed('myHand', 'Lancelot, Goliath of Aesa')
    ->play('Lancelot, Goliath of Aesa')->pay(4)
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: Lancelot entered rested')
    ->tested('Lancelot, Goliath of Aesa')
    ->covers(['keyword', 'hindered'], '**Hindered** *(This ally enters the field rested.)*')
    ->register();

gaScenario('lancelot-class-bonus-level-two-gets-plus-two-life')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lancelot, Goliath of Aesa', classBonus: true, level: 2)
    ->seed('myField', 'Lancelot, Goliath of Aesa')
    ->observe()
    ->expectLife('p1Field-1', 5, '[Class Bonus] [Level 2+] 3 + 2 life')
    ->tested('Lancelot, Goliath of Aesa')
    ->covers(['class-bonus', 'level-gate', 'stat-change'], '[Class Bonus] [Level 2+] CARDNAME gets +2 [LIFE].')
    ->register();

gaScenario('lancelot-on-attack-paying-three-gets-plus-three-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lancelot, Goliath of Aesa')
    ->seed('myField', 'Lancelot, Goliath of Aesa', properties: ['Status' => 2])
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')->pay(3)->pass(2)
    ->expectDamage('p2Field-0', 5, 'Paid (3): 2 + 3 = 5 damage')
    ->tested('Lancelot, Goliath of Aesa')
    ->covers(['on-attack', 'optional-cost', 'stat-change'], '**On Attack:** You may pay (3). If you do, CARDNAME gets +3 [POWER] until end of turn.')
    ->register();

// ---- Lawsur, the Carpenter ----

gaScenario('lawsur-enter-gets-power-for-each-other-specter-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Lawsur, the Carpenter')
    ->seed('myHand', 'Lawsur, the Carpenter')
    ->play('Lawsur, the Carpenter')->pay(3)
    ->expectPower('p1Field-1', 1, 'No other Specter ally: 1 + 0 power')
    ->tested('Lawsur, the Carpenter')
    ->covers(['on-enter', 'stat-change', 'variable'], '**On Enter**: Lawsur gets +**X**[POWER] until end of turn, where **X** is the amount of other Specter allies you control.')
    ->register();

// ---- Sadi, Blood Harvester ----

gaScenario('sadi-pay-two-returns-it-to-hand-and-puts-a-preparation-counter-on-the-champion')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sadi, Blood Harvester')
    ->seed('myField', 'Sadi, Blood Harvester')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)
    ->expectCardExists('myHand', 'Sadi, Blood Harvester', 'Sadi returned to hand')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'A preparation counter went on the champion')
    ->tested('Sadi, Blood Harvester')
    ->covers(['activated-ability', 'zone-movement', 'counter'], '(2)**:** Return CARDNAME to its owner\'s hand. If you do, put a **preparation** counter on your champion.')
    ->register();

// ---- Sun Ce, Weaponsmaster ----

gaScenario('sun-ce-class-bonus-enter-puts-a-durability-counter-on-a-warrior-weapon-and-warrior-weapons-get-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Sun Ce, Weaponsmaster', classBonus: true)
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Sun Ce, Weaponsmaster')
    ->play('Sun Ce, Weaponsmaster')->pay(3)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', '[Class Bonus] On Enter: a durability counter (2 + 1)')
    ->expectPower('p1Field-1', 2, '[Class Bonus] Warrior weapons get +1 power: 1 + 1')
    ->tested('Sun Ce, Weaponsmaster')
    ->covers(['class-bonus', 'on-enter', 'counter', 'stat-change', 'targeting'], '[Class Bonus] **On Enter**: Put a **durability** counter on target Warrior weapon you control.', '[Class Bonus] Warrior weapons you control get +1 [POWER].')
    ->register();

// ---- Noire, Ace of Spades ----

gaScenario('noire-with-another-suited-ally-has-stealth')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Noire, Ace of Spades')
    ->seed('myField', 'Noire, Ace of Spades', properties: ['Status' => 2])
    ->seed('myField', 'Two of Spades')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)->pass(1)->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-1', 'Another Suited ally: Noire has stealth')
    ->tested('Noire, Ace of Spades')
    ->covers(['condition', 'stealth'], 'As long as you control another Suited ally, Noire has **stealth**.')
    ->register();

// ---- Harvester Mk II ----

gaScenario('harvester-mk-ii-automaton-allies-summon-a-powercell-when-they-die')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Harvester Mk II')
    ->seed('myField', 'Harvester Mk II')
    ->seed('myField', 'Delivery Droid')
    ->seed('myField', 'Lumbering Steed', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-2', player: 2)->answer(1, '-')
    ->expectCardExists('myField', 'Powercell', 'The dying Automaton ally left a Powercell (and its own On Death one)')
    ->tested('Harvester Mk II')
    ->covers(['on-death', 'summon', 'static'], 'Automaton allies you control have “**On Death: Summon** a Powercell token.”')
    ->register();
