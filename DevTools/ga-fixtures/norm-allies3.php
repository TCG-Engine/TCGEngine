<?php
/**
 * Phase 3d (Norm): allies, third batch (On Enter / On Death allies). Written on the 'quiet' deck.
 */

// ---- Novice Healer ----

gaScenario('novice-healer-class-bonus-enter-recovers-three')
    ->deckTemplate('quiet', 'Novice Healer')
    ->readyFor('Novice Healer', classBonus: true, damage: 5)
    ->seed('myHand', 'Novice Healer')
    ->play('Novice Healer')->pay(3)
    ->expectDamage('p1Field-0', 2, '[Class Bonus] Recover 3: 5 - 3')
    ->tested('Novice Healer')
    ->covers(['class-bonus', 'on-enter', 'recover'], '[Class Bonus] **On Enter:** **Recover 3**.')
    ->register();

gaScenario('novice-healer-without-the-class-bonus-does-not-recover')
    ->deckTemplate('quiet', 'Novice Healer')
    ->readyFor('Novice Healer', damage: 5)
    ->seed('myHand', 'Novice Healer')
    ->play('Novice Healer')->pay(3)
    ->expectDamage('p1Field-0', 5, 'No class bonus: no recovery')
    ->tested('Novice Healer')
    ->covers(['class-bonus', 'condition', 'recover'], '[Class Bonus] **On Enter:** **Recover 3**.')
    ->register();

// ---- Owl Familiar ----

gaScenario('owl-familiar-class-bonus-enter-glimpses-two')
    ->deckTemplate('quiet', 'Owl Familiar')
    ->readyFor('Owl Familiar', classBonus: true)
    ->deckTop(['Dungeon Guide', 'Gray Wolf'])
    ->seed('myHand', 'Owl Familiar')
    ->play('Owl Familiar')->pay(1)
    ->answer(1, 'Top=em6eEh9q8y;Bottom=hJ2xh9lNMR')
    ->expectCardExists('myField', 'Owl Familiar', 'The Owl entered and glimpsed')
    ->tested('Owl Familiar')
    ->covers(['class-bonus', 'on-enter', 'glimpse'], '[Class Bonus] **On Enter:** **Glimpse 2**.')
    ->register();

// ---- Cell Assembler ----

gaScenario('cell-assembler-enter-paying-two-summons-a-rested-powercell')
    ->deckTemplate('quiet', 'Cell Assembler')
    ->readyFor('Cell Assembler')
    ->seed('myHand', 'Cell Assembler')
    ->play('Cell Assembler')->pay(2)->answer(1, 'YES')->pay(2)
    ->expectCardExists('myField', 'Powercell', 'Paid (2): a Powercell was summoned')
    ->expectProperty('p1Field-2', 'Status', '1', 'The Powercell entered rested')
    ->tested('Cell Assembler')
    ->covers(['on-enter', 'optional-cost', 'summon'], '**On Enter:** You may pay (2). If you do, **summon** a Powercell token rested.')
    ->register();

gaScenario('cell-assembler-enter-declined-summons-nothing')
    ->deckTemplate('quiet', 'Cell Assembler')
    ->readyFor('Cell Assembler')
    ->seed('myHand', 'Cell Assembler')
    ->play('Cell Assembler')->pay(2)->answer(1, 'NO')
    ->expectZoneCount('myField', 2, 1, 'Declined: only the champion and the Assembler are on the field')
    ->tested('Cell Assembler')
    ->covers(['on-enter', 'optional-cost'], '**On Enter:** You may pay (2). If you do, **summon** a Powercell token rested.')
    ->register();

// ---- Delivery Droid ----

gaScenario('delivery-droid-on-death-summons-a-powercell')
    ->deckTemplate('quiet', 'Delivery Droid')
    ->readyFor('Delivery Droid')
    ->seed('myField', 'Delivery Droid')
    ->seed('myField', 'Rotund Squirrel', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, '-')
    ->expectCardExists('myGraveyard', 'Delivery Droid', 'The Droid died')
    ->expectCardExists('myField', 'Powercell', 'On Death: a Powercell token was summoned')
    ->tested('Delivery Droid')
    ->covers(['on-death', 'summon'], '**On Death:** **Summon** a Powercell token.')
    ->register();

// ---- Slime Swarm ----

gaScenario('slime-swarm-class-bonus-enter-summons-two-rested-baby-slimes')
    ->deckTemplate('quiet', 'Slime Swarm')
    ->readyFor('Slime Swarm', classBonus: true)
    ->seed('myHand', 'Slime Swarm')
    ->play('Slime Swarm')->pay(3)
    ->expectCardExists('myField', 'Baby Slime', '[Class Bonus] Baby Slime tokens were summoned')
    ->expectZoneCount('myField', 4, 1, 'The champion, the Swarm and two Baby Slimes')
    ->expectProperty('p1Field-2', 'Status', '1', 'The first Baby Slime entered rested')
    ->tested('Slime Swarm')
    ->covers(['class-bonus', 'on-enter', 'summon'], '[Class Bonus] **On Enter:** **Summon** two Baby Slime tokens rested.')
    ->register();

gaScenario('slime-swarm-without-the-class-bonus-summons-nothing')
    ->deckTemplate('quiet', 'Slime Swarm')
    ->readyFor('Slime Swarm')
    ->seed('myHand', 'Slime Swarm')
    ->play('Slime Swarm')->pay(3)
    ->expectZoneCount('myField', 2, 1, 'No class bonus: only the champion and the Swarm')
    ->tested('Slime Swarm')
    ->covers(['class-bonus', 'condition', 'summon'], '[Class Bonus] **On Enter:** **Summon** two Baby Slime tokens rested.')
    ->register();

// ---- Shining Marchador ----

gaScenario('shining-marchador-enter-paying-two-puts-a-buff-counter-on-an-ally')
    ->deckTemplate('quiet', 'Shining Marchador')
    ->readyFor('Shining Marchador')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Shining Marchador')
    ->play('Shining Marchador')->pay(2)->answer(1, 'YES')->pay(2)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'Paid (2): the target ally got a buff counter')
    ->tested('Shining Marchador')
    ->covers(['on-enter', 'optional-cost', 'counter', 'targeting'], '**On Enter:** You may pay (2). When you do, put a **buff** counter on target ally you control.')
    ->register();

// ---- Pride's Smith ----

gaScenario('prides-smith-enter-puts-a-durability-counter-on-a-warrior-weapon')
    ->deckTemplate('quiet', 'Pride\'s Smith')
    ->readyFor('Pride\'s Smith')
    ->seed('myField', 'Training Sword')
    ->seed('myHand', 'Pride\'s Smith')
    ->play('Pride\'s Smith')->pay(2)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":3}', 'On Enter: the weapon went from 2 to 3 durability counters')
    ->tested('Pride\'s Smith')
    ->covers(['on-enter', 'counter', 'targeting'], '**On Enter**: Put a **durability** counter on target Warrior weapon you control.')
    ->register();

// ---- Scavenging Raccoon ----

gaScenario('scavenging-raccoon-enter-banishes-up-to-two-cards-from-a-graveyard')
    ->deckTemplate('quiet', 'Scavenging Raccoon')
    ->readyFor('Scavenging Raccoon')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myGraveyard', 'Gray Wolf', player: 2)
    ->seed('myGraveyard', 'Young Wyrmling', player: 2)
    ->seed('myHand', 'Scavenging Raccoon')
    ->play('Scavenging Raccoon')->pay(2)->answer(1, 'theirGraveyard-0')->answer(1, 'theirGraveyard-0')
    ->expectZoneCount('theirGraveyard', 1, 1, 'Two of the three graveyard cards were banished')
    ->expectZoneCount('theirBanish', 2, 1, 'Both banished cards went to their owner\'s banishment')
    ->tested('Scavenging Raccoon')
    ->covers(['on-enter', 'banish', 'targeting'], '**On Enter:** Banish up to two target cards from a single graveyard.')
    ->register();

// ---- Mystic Purifier ----

gaScenario('mystic-purifier-enter-paying-two-destroys-a-phantasia')
    ->deckTemplate('quiet', 'Mystic Purifier')
    ->readyFor('Mystic Purifier')
    ->seed('myField', 'Spirit Shard')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Mystic Purifier')
    ->play('Mystic Purifier')->pay(2)->answer(1, 'YES')->answer(1, 'myField-1')->pay(2)
    ->expectZoneCount('myField', 2, 1, 'Paid (2): the Spirit Shard phantasia was destroyed, leaving the champion and the Purifier')
    ->expectZoneCount('myMemory', 4, 1, 'Two for the Purifier and two for the optional cost')
    ->tested('Mystic Purifier')
    ->covers(['on-enter', 'optional-cost', 'destroy', 'targeting'], '**On Enter:** You may pay (2). When you do, destroy target phantasia.')
    ->register();

// ---- Tricky Chimps ----

gaScenario('tricky-chimps-class-bonus-enter-with-two-opposing-allies-gets-plus-two-power')
    ->deckTemplate('quiet', 'Tricky Chimps')
    ->readyFor('Tricky Chimps', classBonus: true)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Tricky Chimps')
    ->play('Tricky Chimps')->pay(2)
    ->expectPower('p1Field-1', 3, '[Class Bonus] The opponent controls two allies: 1 + 2 power this turn')
    ->tested('Tricky Chimps')
    ->covers(['class-bonus', 'on-enter', 'condition', 'stat-change'], '[Class Bonus] **On Enter:** If an opponent controls two or more allies, CARDNAME gets +2 [POWER] until end of turn.')
    ->register();

gaScenario('tricky-chimps-class-bonus-enter-with-one-opposing-ally-gets-nothing')
    ->deckTemplate('quiet', 'Tricky Chimps')
    ->readyFor('Tricky Chimps', classBonus: true)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Tricky Chimps')
    ->play('Tricky Chimps')->pay(2)
    ->expectPower('p1Field-1', 1, 'Only one opposing ally: the printed 1 power')
    ->tested('Tricky Chimps')
    ->covers(['class-bonus', 'on-enter', 'condition'], '[Class Bonus] **On Enter:** If an opponent controls two or more allies')
    ->register();

// ---- Imperious Highlander ----

gaScenario('imperious-highlander-enter-gets-power-for-each-ally-the-opponent-controls-more')
    ->deckTemplate('quiet', 'Imperious Highlander')
    ->readyFor('Imperious Highlander')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Imperious Highlander')
    ->play('Imperious Highlander')->pay(3)
    ->expectPower('p1Field-1', 3, 'The opponent has two allies to our one (the Highlander): X = 1, 2 + 1 power')
    ->tested('Imperious Highlander')
    ->covers(['on-enter', 'stat-change', 'variable'], '**On Enter:** CARDNAME gets +**X** [POWER] until end of turn where **X** is the amount of allies target opponent controls more than you control.')
    ->register();

// ---- Young Beastbonder ----

gaScenario('young-beastbonder-class-bonus-enter-puts-two-buff-counters-on-a-beast')
    ->deckTemplate('quiet', 'Young Beastbonder')
    ->readyFor('Young Beastbonder', classBonus: true)
    ->seed('myField', 'Gray Wolf')
    ->seed('myHand', 'Young Beastbonder')
    ->play('Young Beastbonder')->pay(2)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":2}', '[Class Bonus] A Beast gets two buff counters')
    ->tested('Young Beastbonder')
    ->covers(['class-bonus', 'on-enter', 'counter', 'targeting'], '[Class Bonus] **On Enter:** Put a **buff** counter on another target ally you control. If that ally is a Beast, put two **buff** counters on it instead.')
    ->register();

gaScenario('young-beastbonder-class-bonus-enter-puts-one-buff-counter-on-a-non-beast')
    ->deckTemplate('quiet', 'Young Beastbonder')
    ->readyFor('Young Beastbonder', classBonus: true)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Young Beastbonder')
    ->play('Young Beastbonder')->pay(2)->answer(1, 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Class Bonus] A non-Beast gets one buff counter')
    ->tested('Young Beastbonder')
    ->covers(['class-bonus', 'on-enter', 'counter', 'condition'], '[Class Bonus] **On Enter:** Put a **buff** counter on another target ally you control. If that ally is a Beast, put two **buff** counters on it instead.')
    ->register();

// ---- Mechanical Hare ----

gaScenario('mechanical-hare-class-bonus-enter-puts-a-buff-counter-on-itself')
    ->deckTemplate('quiet', 'Mechanical Hare')
    ->readyFor('Mechanical Hare', classBonus: true)
    ->seed('myHand', 'Mechanical Hare')
    ->play('Mechanical Hare')->pay(2)
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', '[Class Bonus] On Enter: a buff counter')
    ->tested('Mechanical Hare')
    ->covers(['class-bonus', 'on-enter', 'counter'], '[Class Bonus] **On Enter:** Put a **buff** counter on CARDNAME.')
    ->register();

// ---- Skilled Plainsman / Stolid Vanguard ----

gaScenario('skilled-plainsman-class-bonus-equestrian-enter-with-a-horse-gets-a-buff-counter')
    ->deckTemplate('quiet', 'Skilled Plainsman')
    ->readyFor('Skilled Plainsman', classBonus: true)
    ->seed('myField', 'Ordinary Horse')
    ->seed('myHand', 'Skilled Plainsman')
    ->play('Skilled Plainsman')->pay(3)
    ->expectProperty('p1Field-2', 'Counters', '{"buff":1}', '[Class Bonus] Equestrian: a Horse ally is controlled, so a buff counter')
    ->tested('Skilled Plainsman')
    ->covers(['class-bonus', 'equestrian', 'on-enter', 'counter', 'condition'], '[Class Bonus] ***Equestrian* — On Enter:** If you control a Horse ally, put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('stolid-vanguard-equestrian-enter-with-a-horse-gets-plus-two-power')
    ->deckTemplate('quiet', 'Stolid Vanguard')
    ->readyFor('Stolid Vanguard')
    ->seed('myField', 'Ordinary Horse')
    ->seed('myHand', 'Stolid Vanguard')
    ->play('Stolid Vanguard')->pay(2)
    ->expectPower('p1Field-2', 3, 'Equestrian: a Horse ally is controlled, 1 + 2 power this turn')
    ->tested('Stolid Vanguard')
    ->covers(['equestrian', 'on-enter', 'stat-change', 'condition'], '***Equestrian* — On Enter:** If you control a Horse ally, CARDNAME gets +2 [POWER] until end of turn.')
    ->register();

gaScenario('stolid-vanguard-equestrian-enter-without-a-horse-gets-nothing')
    ->deckTemplate('quiet', 'Stolid Vanguard')
    ->readyFor('Stolid Vanguard')
    ->seed('myHand', 'Stolid Vanguard')
    ->play('Stolid Vanguard')->pay(2)
    ->expectPower('p1Field-1', 1, 'No Horse ally: the printed 1 power')
    ->tested('Stolid Vanguard')
    ->covers(['equestrian', 'on-enter', 'condition'], '***Equestrian* — On Enter:** If you control a Horse ally')
    ->register();
