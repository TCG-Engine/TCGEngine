<?php
/**
 * Phase 3d (Norm): allies, first batch (static stat and keyword allies). Written on the 'quiet' deck.
 */

// ---- Charged Mannequin ----

gaScenario('charged-mannequin-with-a-powercell-gets-plus-one-power')
    ->deckTemplate('quiet', 'Charged Mannequin')
    ->readyFor('Charged Mannequin')
    ->seed('myField', 'Charged Mannequin')
    ->seed('myField', 'Powercell')
    ->observe()
    ->expectPower('p1Field-1', 2, 'A Powercell is controlled: 1 + 1 power')
    ->tested('Charged Mannequin')
    ->covers(['condition', 'stat-change'], 'As long as you control a Powercell, CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('charged-mannequin-without-a-powercell-has-its-printed-power')
    ->deckTemplate('quiet', 'Charged Mannequin')
    ->readyFor('Charged Mannequin')
    ->seed('myField', 'Charged Mannequin')
    ->observe()
    ->expectPower('p1Field-1', 1, 'No Powercell: the printed 1 power')
    ->tested('Charged Mannequin')
    ->covers(['condition', 'stat-change'], 'As long as you control a Powercell, CARDNAME gets +1 [POWER].')
    ->register();

// ---- Overcharged Droid ----

gaScenario('overcharged-droid-with-two-powercells-gets-plus-one-power-and-life')
    ->deckTemplate('quiet', 'Overcharged Droid')
    ->readyFor('Overcharged Droid')
    ->seed('myField', 'Overcharged Droid')
    ->seed('myField', 'Powercell')
    ->seed('myField', 'Powercell')
    ->observe()
    ->expectPower('p1Field-1', 3, 'Two Powercells: 2 + 1 power')
    ->expectLife('p1Field-1', 4, 'Two Powercells: 3 + 1 life')
    ->tested('Overcharged Droid')
    ->covers(['condition', 'stat-change'], 'As long as you control two or more Powercell items, CARDNAME gets +1[POWER] and +1[LIFE].')
    ->register();

gaScenario('overcharged-droid-with-one-powercell-has-its-printed-stats')
    ->deckTemplate('quiet', 'Overcharged Droid')
    ->readyFor('Overcharged Droid')
    ->seed('myField', 'Overcharged Droid')
    ->seed('myField', 'Powercell')
    ->observe()
    ->expectPower('p1Field-1', 2, 'One Powercell is not enough: 2 power')
    ->expectLife('p1Field-1', 3, 'One Powercell is not enough: 3 life')
    ->tested('Overcharged Droid')
    ->covers(['condition', 'stat-change'], 'As long as you control two or more Powercell items, CARDNAME gets +1[POWER] and +1[LIFE].')
    ->register();

// ---- Reliable Cavalier ----

gaScenario('reliable-cavalier-with-a-horse-ally-gets-plus-three-power')
    ->deckTemplate('quiet', 'Reliable Cavalier')
    ->readyFor('Reliable Cavalier')
    ->seed('myField', 'Reliable Cavalier')
    ->seed('myField', 'Ordinary Horse')
    ->observe()
    ->expectPower('p1Field-1', 4, 'Equestrian: a Horse ally is controlled, 1 + 3 power')
    ->tested('Reliable Cavalier')
    ->covers(['equestrian', 'condition', 'stat-change'], '***Equestrian —*** As long as you control a Horse ally, CARDNAME gets +3[POWER].')
    ->register();

gaScenario('reliable-cavalier-without-a-horse-ally-has-its-printed-power')
    ->deckTemplate('quiet', 'Reliable Cavalier')
    ->readyFor('Reliable Cavalier')
    ->seed('myField', 'Reliable Cavalier')
    ->observe()
    ->expectPower('p1Field-1', 1, 'No Horse ally: the printed 1 power')
    ->tested('Reliable Cavalier')
    ->covers(['equestrian', 'condition', 'stat-change'], '***Equestrian —*** As long as you control a Horse ally, CARDNAME gets +3[POWER].')
    ->register();

// ---- Banner Raccoon ----

gaScenario('banner-raccoon-gives-other-raccoons-plus-one-power-while-the-opponent-has-no-graveyard')
    ->deckTemplate('quiet', 'Banner Raccoon')
    ->readyFor('Banner Raccoon')
    ->seed('myField', 'Banner Raccoon')
    ->seed('myField', 'Portly Raccoon')
    ->observe()
    ->expectPower('p1Field-2', 3, 'The opponent has no graveyard: the other Raccoon gets +1, 2 + 1 power')
    ->expectPower('p1Field-1', 1, 'Banner Raccoon does not pump itself')
    ->tested('Banner Raccoon')
    ->covers(['condition', 'stat-change'], 'As long as an opponent has no cards in their graveyard, other Raccoon allies you control get +1[POWER].')
    ->register();

gaScenario('banner-raccoon-gives-nothing-once-the-opponent-has-a-graveyard-card')
    ->deckTemplate('quiet', 'Banner Raccoon')
    ->readyFor('Banner Raccoon')
    ->seed('myField', 'Banner Raccoon')
    ->seed('myField', 'Portly Raccoon')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->observe()
    ->expectPower('p1Field-2', 2, 'The opponent has a graveyard card: no bonus')
    ->tested('Banner Raccoon')
    ->covers(['condition', 'stat-change'], 'As long as an opponent has no cards in their graveyard, other Raccoon allies you control get +1[POWER].')
    ->register();

// ---- Excitable Raccoon ----

gaScenario('excitable-raccoon-opponent-without-a-graveyard-gets-plus-one-power-and-vigor')
    ->deckTemplate('quiet', 'Excitable Raccoon')
    ->readyFor('Excitable Raccoon')
    ->seed('myField', 'Excitable Raccoon', properties: ['Status' => 1])
    ->observe()
    ->expectPower('p1Field-1', 2, 'The opponent has no graveyard: 1 + 1 power')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '2', 'Vigor woke the Raccoon at the end phase')
    ->tested('Excitable Raccoon')
    ->covers(['condition', 'stat-change', 'vigor'], 'As long as an opponent has no cards in their graveyard, CARDNAME gets +1[POWER] and has **vigor**.')
    ->register();

gaScenario('excitable-raccoon-with-an-opponent-graveyard-card-has-neither')
    ->deckTemplate('quiet', 'Excitable Raccoon')
    ->readyFor('Excitable Raccoon')
    ->seed('myField', 'Excitable Raccoon', properties: ['Status' => 1])
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->observe()
    ->expectPower('p1Field-1', 1, 'The opponent has a graveyard card: the printed 1 power')
    ->endTurn()
    ->expectProperty('p1Field-1', 'Status', '1', 'No vigor: the Raccoon stays rested')
    ->tested('Excitable Raccoon')
    ->covers(['condition', 'stat-change', 'vigor'], 'As long as an opponent has no cards in their graveyard, CARDNAME gets +1[POWER] and has **vigor**.')
    ->register();

// ---- Wingpeak Patriarch ----

gaScenario('wingpeak-patriarch-gives-other-bird-objects-plus-one-power')
    ->deckTemplate('quiet', 'Wingpeak Patriarch')
    ->readyFor('Wingpeak Patriarch')
    ->seed('myField', 'Wingpeak Patriarch')
    ->seed('myField', 'Owl Familiar')
    ->seed('myField', 'Dungeon Guide')
    ->observe()
    ->expectPower('p1Field-2', 2, 'The Owl Familiar is a Bird: 1 + 1 power')
    ->expectPower('p1Field-3', 1, 'The Dungeon Guide is not a Bird: unchanged')
    ->expectPower('p1Field-1', 1, 'Other Bird objects only: the Patriarch itself is unchanged')
    ->tested('Wingpeak Patriarch')
    ->covers(['stat-change'], 'Other Bird objects you control get +1 [POWER].')
    ->register();

// ---- Royal Bear ----

gaScenario('royal-bear-class-bonus-gets-plus-one-power-and-life')
    ->deckTemplate('quiet', 'Royal Bear')
    ->readyFor('Royal Bear', classBonus: true)
    ->seed('myField', 'Royal Bear')
    ->observe()
    ->expectPower('p1Field-1', 3, '[Class Bonus] 2 + 1 power')
    ->expectLife('p1Field-1', 4, '[Class Bonus] 3 + 1 life')
    ->tested('Royal Bear')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] CARDNAME gets +1 [POWER] and +1 [LIFE].')
    ->register();

gaScenario('royal-bear-without-the-class-bonus-has-its-printed-stats')
    ->deckTemplate('quiet', 'Royal Bear')
    ->readyFor('Royal Bear')
    ->seed('myField', 'Royal Bear')
    ->observe()
    ->expectPower('p1Field-1', 2, 'No class bonus: 2 power')
    ->expectLife('p1Field-1', 3, 'No class bonus: 3 life')
    ->tested('Royal Bear')
    ->covers(['class-bonus', 'stat-change'], '[Class Bonus] CARDNAME gets +1 [POWER] and +1 [LIFE].')
    ->register();

gaScenario('royal-bear-pride-two-does-not-obey-a-level-zero-champion')
    ->deckTemplate('quiet', 'Royal Bear')
    ->readyFor('Royal Bear')
    ->seed('myField', 'Royal Bear')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'Pride 2: the Bear stays awake because it never attacked')
    ->tested('Royal Bear')
    ->covers(['pride', 'keyword'], '**Pride 2** *(This ally won’t obey you unless your champion is level 2 or higher. You can’t attack with, intercept with, or activate abilities of allies that don’t obey you.)*')
    ->register();

gaScenario('royal-bear-pride-two-obeys-a-level-two-champion')
    ->deckTemplate('quiet', 'Royal Bear')
    ->readyFor('Royal Bear', level: 2)
    ->seed('myField', 'Royal Bear')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectProperty('p1Field-1', 'Status', '1', 'Pride 2 is satisfied at level 2: the Bear attacked and rested')
    ->tested('Royal Bear')
    ->covers(['pride', 'keyword', 'level-gate'], '**Pride 2**')
    ->register();

// ---- Enraged Boars ----

gaScenario('enraged-boars-pride-five-obeys-a-level-five-champion')
    ->deckTemplate('quiet', 'Enraged Boars')
    ->readyFor('Enraged Boars', level: 5)
    ->seed('myField', 'Enraged Boars')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectProperty('p1Field-1', 'Status', '1', 'Pride 5 is satisfied at level 5: the Boars attacked and rested')
    ->tested('Enraged Boars')
    ->covers(['pride', 'keyword', 'level-gate'], '**Pride 5** *(This ally won\'t obey you unless your champion is level 5 or higher.)*')
    ->register();

gaScenario('enraged-boars-pride-five-does-not-obey-a-level-four-champion')
    ->deckTemplate('quiet', 'Enraged Boars')
    ->readyFor('Enraged Boars', level: 4)
    ->seed('myField', 'Enraged Boars')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'Pride 5: a level 4 champion is not enough, the Boars never attacked')
    ->tested('Enraged Boars')
    ->covers(['pride', 'keyword'], '**Pride 5** *(This ally won\'t obey you unless your champion is level 5 or higher.)*')
    ->register();

// ---- Bolstered Boar ----

gaScenario('bolstered-boar-with-two-buff-counters-has-taunt')
    ->deckTemplate('quiet', 'Bolstered Boar')
    ->readyFor('Bolstered Boar')
    ->seed('myField', 'Bolstered Boar', properties: ['Status' => 2, 'Counters' => ['buff' => 2]])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->action(2, 10002, 'myField-1!FSM!')
    ->rejects(2, 'theirField-0', 'Two buff counters give the Boar taunt: the champion cannot be attacked')
    ->tested('Bolstered Boar')
    ->covers(['condition', 'counter', 'taunt', 'steadfast', 'vigor', 'retort'], 'As long as Bolstered Boar has two or more **buff** counters on it, it has **steadfast**, **taunt**, **vigor**, and **retort 2**.')
    ->register();

gaScenario('bolstered-boar-with-one-buff-counter-has-no-taunt')
    ->deckTemplate('quiet', 'Bolstered Boar')
    ->readyFor('Bolstered Boar')
    ->seed('myField', 'Bolstered Boar', properties: ['Status' => 2, 'Counters' => ['buff' => 1]])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-0', player: 2)
    ->expectDamage('p1Field-0', 1, 'One buff counter: no taunt, so the champion could be attacked')
    ->tested('Bolstered Boar')
    ->covers(['condition', 'counter', 'taunt'], 'As long as Bolstered Boar has two or more **buff** counters on it, it has **steadfast**, **taunt**, **vigor**, and **retort 2**.')
    ->register();

// ---- Simple Slime ----

gaScenario('simple-slime-cannot-attack')
    ->deckTemplate('quiet', 'Simple Slime')
    ->readyFor('Simple Slime')
    ->seed('myField', 'Simple Slime')
    ->reachTurnThree()
    ->action(1, 10002, 'myField-1!FSM!')
    ->expectProperty('p1Field-1', 'Status', '2', 'Simple Slime cannot attack: it stays awake')
    ->tested('Simple Slime')
    ->covers(['restriction'], 'CARDNAME can\'t attack.')
    ->register();

gaScenario('simple-slime-prevents-three-of-the-damage-dealt-to-it')
    ->deckTemplate('quiet', 'Simple Slime')
    ->readyFor('Simple Slime')
    ->seed('myField', 'Simple Slime', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)
    ->expectDamage('p1Field-1', 0, 'Dungeon Guide deals 2 damage, all of it prevented (3 of it would be)')
    ->tested('Simple Slime')
    ->covers(['prevention'], 'If damage would be dealt to CARDNAME, prevent 3 of that damage.')
    ->register();

gaScenario('simple-slime-twin-an-ordinary-ally-can-attack-at-the-same-point')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->readyFor('Dungeon Guide')
    ->seed('myField', 'Dungeon Guide')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->pass(2)
    ->expectProperty('p1Field-1', 'Status', '1', 'Control: an ordinary ally attacked on the same route, so the Slime\'s refusal is real')
    ->tested('Simple Slime')
    ->covers(['restriction'], 'CARDNAME can\'t attack.')
    ->register();

// ---- Palace Guard ----

gaWindTaunt('palace-guard-taunt-protects-the-champion', 'Palace Guard', '**Taunt**');

// ---- Unyielding Wraithguard ----

gaWindTaunt('unyielding-wraithguard-taunt-protects-the-champion', 'Unyielding Wraithguard', '**Taunt**');

// ---- Rotund Squirrel ----

gaScenario('rotund-squirrel-hindered-enters-the-field-rested')
    ->deckTemplate('quiet', 'Rotund Squirrel')
    ->readyFor('Rotund Squirrel')
    ->seed('myHand', 'Rotund Squirrel')
    ->play('Rotund Squirrel')->pay(2)
    ->expectProperty('p1Field-1', 'Status', '1', 'Hindered: the Squirrel entered rested')
    ->tested('Rotund Squirrel')
    ->covers(['keyword', 'hindered'], '**Hindered** *(This ally enters the field rested.)*')
    ->register();

// ---- Woodland Squirrels / Young Wyrmling (vanilla) ----

gaScenario('woodland-squirrels-costs-nothing-and-enters-with-its-printed-stats')
    ->deckTemplate('quiet', 'Woodland Squirrels')
    ->readyFor('Woodland Squirrels')
    ->seed('myHand', 'Woodland Squirrels')
    ->play('Woodland Squirrels')
    ->expectCardExists('myField', 'Woodland Squirrels', 'Reserve cost 0: it entered without any payment')
    ->expectPower('p1Field-1', 1, 'Printed 1 power')
    ->expectLife('p1Field-1', 1, 'Printed 1 life')
    ->tested('Woodland Squirrels')
    ->covers(['vanilla'], 'Woodland Squirrels (no rules text)')
    ->register();

gaScenario('young-wyrmling-enters-with-its-printed-stats')
    ->deckTemplate('quiet', 'Young Wyrmling')
    ->readyFor('Young Wyrmling')
    ->seed('myHand', 'Young Wyrmling')
    ->play('Young Wyrmling')->pay(2)
    ->expectCardExists('myField', 'Young Wyrmling', 'Paying the printed reserve 2 played it')
    ->expectPower('p1Field-1', 2, 'Printed 2 power')
    ->expectLife('p1Field-1', 2, 'Printed 2 life')
    ->tested('Young Wyrmling')
    ->covers(['vanilla'], 'Young Wyrmling (no rules text)')
    ->register();

// ---- Young Wyrmling (second printing) ----

gaScenario('young-wyrmling-second-printing-enters-with-its-printed-stats')
    ->deckTemplate('quiet', '#LjFUUKe83H')
    ->readyFor('#LjFUUKe83H')
    ->seed('myHand', '#LjFUUKe83H')
    ->play('#LjFUUKe83H')->pay(2)
    ->expectCardExists('myField', '#LjFUUKe83H', 'Paying the printed reserve 2 played it')
    ->expectPower('p1Field-1', 2, 'Printed 2 power')
    ->expectLife('p1Field-1', 2, 'Printed 2 life')
    ->tested('#LjFUUKe83H')
    ->covers(['vanilla'], 'Young Wyrmling (no rules text)')
    ->register();
