<?php
// Current-code regressions for the remaining Phase B findings.
foreach ([false, true] as $distant) {
    gaScenario('steady-sharpshooter-spends-aim-' . ($distant ? 'distant' : 'near'))
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Steady Sharpshooter')
        ->seed('myField', 'Steady Sharpshooter', player: 2, properties: ['Status' => 2, 'Counters' => ['aim' => 1], 'TurnEffects' => $distant ? ['DISTANT'] : []])
        ->endTurn()->attack('myField-1', 'theirField-0', player: 2)->answer(2, 'YES')->pass(1)
        ->expectDamage('p1Field-0', $distant ? 5 : 1, 'Only an already distant attacker benefits from the two aim counters')
        ->expectPower('p2Field-1', 1, 'Attack-only ranged expires with combat')
        ->expectProperty('p2Field-1', 'Counters', '[]', 'All aim counters were paid')
        ->tested('Steady Sharpshooter')->covers(['on-attack', 'counter', 'ranged', 'expiration'], 'For each aim counter removed this way, gains ranged 2 for this attack.')->register();
}

gaScenario('ritai-stablemaster-grants-horse-kindle-three')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Ritai Stablemaster')
    ->seed('myGraveyard', 'Red Slime')->seed('myGraveyard', 'Red Slime')->seed('myGraveyard', 'Red Slime')->seed('myHand', 'Lumbering Steed')
    ->play('Lumbering Steed')->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-0')->answer(1, 'myGraveyard-0')->pay(1)
    ->expectCardExists('myField', 'Lumbering Steed', 'The Horse entered using granted Kindle')
    ->expectZoneCount('myBanish', 3, 1, 'Three fire cards paid three of its four reserve')
    ->expectZoneCount('myMemory', 1, 1, 'The remaining one reserve was paid')
    ->tested('Ritai Stablemaster')->covers(['kindle', 'cost', 'horse'], 'Horse cards you activate have kindle 3.')->register();

gaScenario('corhazi-trapper-pays-preparation-before-its-target')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Corhazi Trapper')
    ->seed('myField', 'Corhazi Trapper')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectProperty('p1Field-0', 'Counters', '[]', 'Preparation is removed before choosing the effect target', step: 1)
    ->target('theirField-0')
    ->expectProperty('p2Field-0', 'TurnEffects', '["sdbzr5zs29-debuff"]', 'The ability can target a champion unit')
    ->tested('Corhazi Trapper')->covers(['activated-ability', 'cost', 'targeting'], '[REST], Remove a preparation counter from your champion: Target unit attacks get -3 POWER this turn.')->register();

gaScenario('lesser-boon-of-distance-targets-boon-granted-ranger')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Lesser Boon of Apollo')
    ->seed('myField', 'Lesser Boon of Distance')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-0')->pay(2)
    ->expectProperty('p1Field-0', 'TurnEffects', '["DISTANT"]', 'The champion acquired Ranger through Apollo')
    ->tested('Lesser Boon of Distance')->covers(['targeting', 'effective-class', 'distant'], '(2): Target Ranger unit becomes distant.')->register();

foreach ([false, true] as $bonus) {
    gaScenario('trained-birdroid-powercell-' . ($bonus ? 'with-bonus' : 'without-bonus'))
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Trained Birdroid', classBonus: $bonus)
        ->seed('myField', 'Trained Birdroid')->seed('myField', 'Powercell', properties: ['Status' => 2])
        ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->pay(1)->target('myField-1')
        ->expectPower('p1Field-1', $bonus ? 5 : 4, 'The Powercell buff is two; the class-bonus trigger adds one')
        ->expectZoneCount('myField', 2, 1, 'The event still works after the Powercell token is sacrificed')
        ->tested('Trained Birdroid')->covers(['activated-ability', 'trigger', 'class-bonus'], '[Class Bonus] Whenever you activate an ability of a Powercell item targeting another ally you control, gets +1 POWER this turn.')->register();
}

gaScenario('greater-boon-of-parvati-regalia-rest-prevents-one')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Ignite the Soul')->seed('myField', 'Greater Boon of Parvati')
    ->seed('myField', 'Training Sword', properties: ['Status' => 2])->seed('myHand', 'Ignite the Soul')
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-0')
    ->expectProperty('p1Field-2', 'Status', '1', 'The granted ability pays REST')
    ->pass(1)->action(1, 10002, 'myHand-8!FSM!')->pay(1)->target('myField-0')
    ->expectDamage('p1Field-0', 0, 'The granted ability prevents the spell damage')
    ->tested('Greater Boon of Parvati')->covers(['granted-ability', 'rest', 'prevention'], 'Regalia you control have REST: Prevent the next 1 damage to target unit this turn.')->register();

// The back face has no printed durability; transformation preserves the front's counters.
gaScenario('huaji-transform-retains-front-face-durability')
    ->deckTemplate('quiet', 'Dungeon Guide')->champion(cardName: 'Lu Bu, Wrath Incarnate')
    ->seed('myField', "Huaji of Heaven's Rise")
    ->endTurn()->answer(1, 'YES')->pay(3)
    ->expectCardExists('myField', 'Huaji of Abyssal Fall', 'The weapon transformed into the back face')
    ->expectProperty('p1Field-1', 'Counters', '{"durability":2}', 'The real transformation retains two durability')
    ->tested('Huaji of Abyssal Fall')->covers(['transform', 'durability'], 'Retains durability counters as it transforms.')->register();

foreach (['Corhazi Trapper', 'Smoldering Cook', 'Geni, Gifted Mechanist'] as $card) {
    gaScenario('phase-b-cost-refuses-' . ($card === 'Corhazi Trapper' ? 'trapper-without-preparation' : ($card === 'Smoldering Cook' ? 'cook-without-food' : 'geni-without-graveyard')))
        ->deckTemplate('quiet', 'Dungeon Guide')->readyFor($card)->seed('myField', $card)
        ->patch('myField-0', ['Counters' => []])
        ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
        ->expectProperty('p1Field-1', 'Status', '2', 'An unpayable activation does not rest its source')
        ->expectZoneCount('myMemory', 0, 1, 'No reserve is spent on an unpayable activation')
        ->tested($card)->covers(['activated-ability', 'cost', 'restriction'], 'All printed costs must be payable before activating.')->register();
}

foreach (['suppressed', 'non-horse'] as $case) {
    $card = $case === 'suppressed' ? 'Lumbering Steed' : 'Gray Wolf';
    gaScenario('ritai-stablemaster-does-not-grant-kindle-' . $case)
        ->deckTemplate('quiet', 'Dungeon Guide')
        ->seed('myField', 'Ritai Stablemaster', properties: ['TurnEffects' => $case === 'suppressed' ? ['NO_ABILITIES'] : []])
        ->seed('myGraveyard', 'Red Slime')->seed('myHand', $card)
        ->play($card)->pay($case === 'suppressed' ? 4 : 2)
        ->expectCardExists('myField', $card, 'The card enters by normal reserve payment')
        ->expectCardExists('myGraveyard', 'Red Slime', 'No Kindle offer consumes the fire card')
        ->expectZoneCount('myBanish', 0, 1, 'The invalid grant never applies')
        ->tested('Ritai Stablemaster')->covers(['kindle', 'restriction', 'ability-suppression'], 'Horse cards you activate have kindle 3.')->register();
}

gaScenario('geni-floating-memory-cost-keeps-its-buff-effect')
    ->deckTemplate('quiet', 'Dungeon Guide')->readyFor('Geni, Gifted Mechanist')
    ->seed('myField', 'Geni, Gifted Mechanist')->seed('myField', 'Trained Birdroid')
    ->seed('myGraveyard', 'Brookside Scout')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->answer(1, 'myGraveyard-0')->target('myField-2')
    ->expectProperty('p1Field-2', 'Counters', '{"buff":2}', 'The card paid from the graveyard had floating memory')
    ->expectZoneCount('myField', 3, 1, 'A non-Automaton cost does not summon a Drone')
    ->expectCardExists('myBanish', 'Brookside Scout', 'The graveyard card was paid before its buff effect')
    ->tested('Geni, Gifted Mechanist')->covers(['cost', 'floating-memory', 'counter'], 'If the banished card had floating memory, put two buff counters on an Automaton ally you control.')->register();

// The granted ability must not inherit the native ability's banish-self cost.
gaScenario('greater-boon-of-parvati-does-not-pay-regalias-native-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Greater Boon of Parvati')
    ->seed('myField', 'Scepter of Fascination', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:1')->target('myField-0')
    ->expectCardExists('myField', 'Scepter of Fascination', 'The granted ability does not banish its source')
    ->expectProperty('p1Field-2', 'Status', '1', 'Only the granted REST cost is paid')
    ->expectProperty('p1Field-0', 'TurnEffects', '["PREVENT_ALL_1"]', 'The granted prevention resolves')
    ->expectZoneCount('myBanish', 0, 1, 'The native banish cost is not paid')
    ->tested('Greater Boon of Parvati')->covers(['granted-ability', 'cost', 'rest', 'prevention'], 'Regalia you control have REST: Prevent the next 1 damage to target unit this turn.')->register();

gaScenario('greater-boon-of-parvati-rested-regalia-cannot-pay-native-cost')
    ->deckTemplate('quiet', 'Dungeon Guide')->seed('myField', 'Greater Boon of Parvati')
    ->seed('myField', 'Scepter of Fascination', properties: ['Status' => 1])
    ->action(1, 10001, 'myField-2!CustomInput!Activate:1')
    ->expectCardExists('myField', 'Scepter of Fascination', 'An unpayable granted REST does not pay the native banish cost')
    ->expectZoneCount('myBanish', 0, 1, 'A rested source stays on the field')
    ->expectProperty('p1Field-0', 'TurnEffects', '[]', 'No prevention is granted without paying REST')
    ->tested('Greater Boon of Parvati')->covers(['granted-ability', 'cost', 'restriction'], 'The granted REST must be payable.')->register();
