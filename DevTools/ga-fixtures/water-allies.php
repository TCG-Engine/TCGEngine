<?php
/**
 * Phase 3b (Water): allies. Deluge N counts the water element cards in the controller's graveyard; 'Giant Tortoise' is a vanilla water ally used as filler.
 */

if (!function_exists('gaWaterGraveyard')) {
    // Seed $n filler water cards into player $player's graveyard on the scenario.
    function gaWaterGraveyard(GaScenario $scenario, int $n, int $player = 1): GaScenario {
        for ($i = 0; $i < $n; ++$i) $scenario->seed('myGraveyard', 'Giant Tortoise', player: $player);
        return $scenario;
    }
    // A deluge static: the ally on the field with $water filler cards and the expected power / life.
    function gaDelugeStatic(string $slug, string $ally, int $water, int $power, ?int $life, string $label, string $clause, bool $classBonus = false): void {
        $s = gaScenario($slug)->standardDeck($ally)->readyFor($ally, classBonus: $classBonus);
        gaWaterGraveyard($s, $water);
        $s->seed('myField', $ally)->observe()
          ->expectPower('p1Field-1', $power, $label);
        if ($life !== null) $s->expectLife('p1Field-1', $life, $label);
        $s->tested($ally)->covers(['static-ability', 'deluge', 'stat-change'], $clause)->register();
    }
}

gaDelugeStatic('aqueous-stallion-four-water-cards-in-the-graveyard-give-plus-three-power', 'Aqueous Stallion', 4, 3, null, 'Four water cards: 0 + 3 = 3 POWER', 'As long as you have four or more water element cards in your graveyard, CARDNAME gets +3 [POWER].');
gaDelugeStatic('aqueous-stallion-three-water-cards-keep-its-printed-power', 'Aqueous Stallion', 3, 0, null, 'Three water cards: the printed 0 POWER', 'As long as you have four or more water element cards in your graveyard, CARDNAME gets +3 [POWER].');
gaDelugeStatic('current-groover-deluge-three-gets-plus-one-power', 'Current Groover', 3, 2, null, 'Deluge 3: 1 + 1 = 2 POWER', '***Deluge 3 --*** As long as you have three or more water element cards in your graveyard, CARDNAME gets +1[POWER].');
gaDelugeStatic('current-groover-two-water-cards-keep-its-printed-power', 'Current Groover', 2, 1, null, 'Two water cards: the printed 1 POWER', '***Deluge 3 --*** As long as you have three or more water element cards in your graveyard, CARDNAME gets +1[POWER].');
gaDelugeStatic('floodborne-warrior-deluge-two-gets-plus-one-power-and-life', 'Floodborne Warrior', 2, 2, 4, 'Deluge 2: 1/3 becomes 2/4', '***Deluge 2 --*** As long as you have two or more water element cards in your graveyard, CARDNAME gets +1[POWER] and +1[LIFE].');
gaDelugeStatic('floodborne-warrior-one-water-card-keeps-its-printed-stats', 'Floodborne Warrior', 1, 1, 3, 'One water card: the printed 1/3', '***Deluge 2 --*** As long as you have two or more water element cards in your graveyard, CARDNAME gets +1[POWER] and +1[LIFE].');
gaDelugeStatic('fountain-bladehand-deluge-three-gets-plus-one-power-and-life', 'Fountain Bladehand', 3, 2, 3, 'Deluge 3: 1/2 becomes 2/3', '***Deluge 3 --*** As long as there are three or more water element cards in your graveyard, CARDNAME gets +1[POWER] and +1[LIFE].');
gaDelugeStatic('fountain-bladehand-two-water-cards-keep-its-printed-stats', 'Fountain Bladehand', 2, 1, 2, 'Two water cards: the printed 1/2', '***Deluge 3 --*** As long as there are three or more water element cards in your graveyard, CARDNAME gets +1[POWER] and +1[LIFE].');

// ---- on-enter ----
gaScenario('austere-priestess-class-bonus-draws-when-your-hand-is-empty')
    ->standardDeck('Austere Priestess')
    ->readyFor('Austere Priestess', classBonus: true)
    ->emptyZone('myHand')
    ->seed('myHand', 'Austere Priestess')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->playSlot(0)->pay(3)->pass(2)
    ->expectZoneCount('myHand', 1, 1, 'The hand was empty after paying: [Class Bonus] On Enter drew a card')
    ->tested('Austere Priestess')
    ->covers(['on-enter', 'class-bonus', 'condition', 'draw'], '[Class Bonus] **On Enter**: If you have no cards in your hand, draw a card.')
    ->register();

gaScenario('austere-priestess-with-cards-in-hand-does-not-draw')
    ->standardDeck('Austere Priestess')
    ->readyFor('Austere Priestess', classBonus: true)
    ->emptyZone('myHand')
    ->seed('myHand', 'Austere Priestess')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')->seed('myHand', 'Dungeon Guide')
    ->playSlot(0)->pay(3)->pass(2)
    ->expectZoneCount('myHand', 1, 1, 'One card is left in hand, so nothing was drawn')
    ->tested('Austere Priestess')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter**: If you have no cards in your hand, draw a card.')
    ->register();

gaScenario('bubble-mage-enter-rests-a-unit-and-class-bonus-adds-damage')
    ->standardDeck('Bubble Mage')
    ->readyFor('Bubble Mage', classBonus: true)
    ->seed('myHand', 'Bubble Mage')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->castSpell('Bubble Mage', pay: 3, window: 'opp', target: 'theirField-1')
    ->expectProperty('p2Field-1', 'Status', '1', 'The target unit is rested')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","0n0DM1T9gz"]', '[Class Bonus] It takes 1 extra damage until end of turn')
    ->tested('Bubble Mage')
    ->covers(['on-enter', 'rest', 'class-bonus', 'damage-modifier'], '**On Enter:** Rest target unit. **Class Bonus:** Until end of turn, if that unit would take damage, it takes that much damage plus 1 instead.')
    ->register();

gaScenario('bubble-mage-without-the-class-bonus-only-rests')
    ->standardDeck('Bubble Mage')
    ->readyFor('Bubble Mage')
    ->seed('myHand', 'Bubble Mage')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->castSpell('Bubble Mage', pay: 3, window: 'opp', target: 'theirField-1')
    ->expectProperty('p2Field-1', 'Status', '1', 'The target unit is rested')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'No Class Bonus: no extra-damage effect')
    ->tested('Bubble Mage')
    ->covers(['on-enter', 'rest', 'class-bonus', 'condition'], '**Class Bonus:** Until end of turn, if that unit would take damage, it takes that much damage plus 1 instead.')
    ->register();

gaScenario('corhazi-trapper-enter-puts-a-preparation-counter-on-your-champion')
    ->standardDeck('Corhazi Trapper')
    ->readyFor('Corhazi Trapper')
    ->seed('myHand', 'Corhazi Trapper')
    ->castSpell('Corhazi Trapper', pay: 3, window: 'opp')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Enter: a preparation counter went on your champion')
    ->tested('Corhazi Trapper')
    ->covers(['on-enter', 'preparation'], '**On Enter:** Put a **preparation** counter on your champion.')
    ->register();

gaScenario('drifting-rogue-enter-puts-a-preparation-counter-on-your-champion')
    ->standardDeck('Drifting Rogue')
    ->readyFor('Drifting Rogue')
    ->seed('myHand', 'Drifting Rogue')
    ->castSpell('Drifting Rogue', pay: 3, window: 'opp')
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', 'On Enter: a preparation counter went on your champion')
    ->tested('Drifting Rogue')
    ->covers(['on-enter', 'preparation'], '**On Enter:** Put a **preparation** counter on your champion.')
    ->register();

gaScenario('convalescing-mare-class-bonus-enter-recovers-and-pumps-other-allies')
    ->standardDeck('Convalescing Mare')
    ->readyFor('Convalescing Mare', classBonus: true, damage: 3)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Convalescing Mare')
    ->castSpell('Convalescing Mare', pay: 2, window: 'opp')->pass(1)
    ->expectDamage('p1Field-0', 2, '[Class Bonus] Recover 1: 3 damage down to 2')
    ->expectLife('p1Field-1', 4, 'The other ally got +1 LIFE until end of turn')
    ->tested('Convalescing Mare')
    ->covers(['on-enter', 'class-bonus', 'recover', 'stat-change'], '[Class Bonus] **On Enter**: **Recover 1**. Other allies you control get +1 [LIFE] until end of turn.')
    ->register();

gaScenario('convalescing-mare-without-the-class-bonus-does-nothing-on-enter')
    ->standardDeck('Convalescing Mare')
    ->readyFor('Convalescing Mare', damage: 3)
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Convalescing Mare')
    ->castSpell('Convalescing Mare', pay: 2, window: 'opp')
    ->expectDamage('p1Field-0', 3, 'No Class Bonus: no recover')
    ->expectLife('p1Field-1', 3, 'No Class Bonus: no life bonus')
    ->tested('Convalescing Mare')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter**: **Recover 1**. Other allies you control get +1 [LIFE] until end of turn.')
    ->register();

gaScenario('floodward-steed-enter-puts-a-bulwark-counter-on-target-ally')
    ->standardDeck('Floodward Steed')
    ->readyFor('Floodward Steed')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myHand', 'Floodward Steed')
    ->castSpell('Floodward Steed', pay: 3, window: 'opp', target: 'myField-1')
    ->expectProperty('p1Field-1', 'Counters', '{"bulwark":1}', 'On Enter: the target ally got a bulwark counter')
    ->tested('Floodward Steed')
    ->covers(['on-enter', 'bulwark', 'targeting'], '**On Enter:** Put a **bulwark** counter on target ally.')
    ->register();

gaScenario('hydrocask-droid-enter-with-a-floating-memory-card-in-the-graveyard-gets-plus-two-power')
    ->standardDeck('Hydrocask Droid')
    ->readyFor('Hydrocask Droid')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Hydrocask Droid')
    ->castSpell('Hydrocask Droid', pay: 2, window: 'opp')
    ->expectPower('p1Field-1', 3, 'A floating memory card in the graveyard: 1 + 2 = 3 POWER until end of turn')
    ->tested('Hydrocask Droid')
    ->covers(['on-enter', 'floating-memory', 'stat-change', 'condition'], '**On Enter:** If you have one or more cards with **floating memory** in your graveyard, CARDNAME gets +2[POWER] until end of turn.')
    ->register();

gaScenario('hydrocask-droid-enter-without-a-floating-memory-card-keeps-its-power')
    ->standardDeck('Hydrocask Droid')
    ->readyFor('Hydrocask Droid')
    ->seed('myHand', 'Hydrocask Droid')
    ->castSpell('Hydrocask Droid', pay: 2, window: 'opp')
    ->expectPower('p1Field-1', 1, 'No floating memory card in the graveyard: the printed 1 POWER')
    ->tested('Hydrocask Droid')
    ->covers(['on-enter', 'floating-memory', 'condition'], '**On Enter:** If you have one or more cards with **floating memory** in your graveyard, CARDNAME gets +2[POWER] until end of turn.')
    ->register();

gaScenario('dewy-slime-enter-banishes-a-floating-memory-card-to-draw-and-recover-two')
    ->standardDeck('Dewy Slime')
    ->readyFor('Dewy Slime', damage: 4)
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Dewy Slime')
    ->castSpell('Dewy Slime', pay: 2, window: 'opp')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectDamage('p1Field-0', 2, 'Recover 2: 4 damage down to 2')
    ->expectZoneCount('myHand', 6, 1, 'Drew a card (7 + the seeded Slime - Slime - 2 paid + 1)')
    ->tested('Dewy Slime')
    ->covers(['on-enter', 'floating-memory', 'banish', 'draw', 'recover'], '**On Enter:** You may banish a card with **floating memory** from your graveyard. If you do, draw a card and **recover 2**.')
    ->register();

gaScenario('froglet-footman-enter-banishes-a-floating-memory-card-for-a-buff-counter')
    ->standardDeck('Froglet Footman')
    ->readyFor('Froglet Footman')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Froglet Footman')
    ->castSpell('Froglet Footman', pay: 3, window: 'opp')->answer(1, 'myGraveyard-0')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'On Enter: a buff counter went on the Footman')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->tested('Froglet Footman')
    ->covers(['on-enter', 'floating-memory', 'banish', 'buff-counter'], '**On Enter**: You may banish a card with **floating memory** from your graveyard. If you do, put a **buff** counter on Froglet Footman.')
    ->register();

gaScenario('halocline-scout-class-bonus-enter-rests-a-unit')
    ->standardDeck('Halocline Scout')
    ->readyFor('Halocline Scout', classBonus: true)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Halocline Scout')
    ->castSpell('Halocline Scout', pay: 3, window: 'opp', target: 'theirField-1')
    ->expectProperty('p2Field-1', 'Status', '1', '[Class Bonus] The target unit is rested')
    ->tested('Halocline Scout')
    ->covers(['on-enter', 'class-bonus', 'rest'], '[Class Bonus] **On Enter:** Rest target unit.')
    ->register();

gaScenario('halocline-scout-without-the-class-bonus-does-not-rest')
    ->standardDeck('Halocline Scout')
    ->readyFor('Halocline Scout')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Halocline Scout')
    ->castSpell('Halocline Scout', pay: 3, window: 'opp')
    ->expectProperty('p2Field-1', 'Status', '2', 'No Class Bonus: the unit stays awake')
    ->tested('Halocline Scout')
    ->covers(['on-enter', 'class-bonus', 'condition'], '[Class Bonus] **On Enter:** Rest target unit.')
    ->register();

// ---- ranged keyword family (the ally attacks from player 2 while distant) ----
if (!function_exists('gaWaterRanged')) {
    // $turnEndPasses: player 2's priority passes while the turn changes hands, for an ally that has a fast ability (it opens a response window at each step).
    function gaWaterRanged(string $slug, string $ally, int $damage, string $label, string $clause, bool $classBonus = false, array $mechanics = ['keyword', 'ranged', 'combat'], int $turnEndPasses = 0): void {
        $s = gaScenario($slug)
            ->standardDeck($ally)
            ->readyFor($ally, classBonus: $classBonus, player: 2)
            ->seed('myField', $ally, player: 2, properties: ['TurnEffects' => ['DISTANT'], 'Status' => 2])
            ->endTurn();
        for ($i = 0; $i < $turnEndPasses; ++$i) $s->pass(2);
        $s->attack('myField-1', 'theirField-0', player: 2)->pass(2)->pass(1)
            ->expectDamage('p1Field-0', $damage, $label)
            ->tested($ally)
            ->covers($mechanics, $clause)
            ->register();
    }
    // The same attack from a taunting ally's side: player 1 holds the taunter and player 2's Dungeon Guide has to hit it, not the champion.
    function gaWaterTaunt(string $slug, string $ally, array $extraSeed, string $clause, bool $classBonus = false): void {
        $s = gaScenario($slug)->standardDeck($ally)->readyFor($ally, classBonus: $classBonus);
        foreach ($extraSeed as $card) $s->seed('myField', $card);
        $s->seed('myField', $ally, properties: ['Status' => 2])->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2]);
        $s->endTurn()->action(2, 10002, 'myField-1!FSM!')
          ->rejects(2, 'theirField-0', 'Taunt: the champion cannot be attacked while the taunting ally is awake')
          ->tested($ally)->covers(['taunt', 'attack-declaration'], $clause)->register();
    }
}

gaWaterRanged('airship-cruiser-ranged-two-while-distant', 'Airship Cruiser', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');
gaWaterRanged('outrider-of-waves-ranged-two-while-distant', 'Outrider of Waves', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');
gaWaterRanged('imperial-scout-ranged-two-while-distant', 'Imperial Scout', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');
gaWaterRanged('waterlogged-ranger-ranged-two-while-distant', 'Waterlogged Ranger', 4, 'Ranged 2 while distant: 2 + 2 = 4 damage', '**Ranged 2**');
gaWaterRanged('cell-sharpshooter-ranged-two-while-distant', 'Cell Sharpshooter', 3, 'Ranged 2 while distant: 1 + 2 = 3 damage', '**Ranged 2**');
gaWaterRanged('misteye-archer-class-bonus-ranged-two-while-distant', 'Misteye Archer', 3, '[Class Bonus] Ranged 2 while distant: 1 + 2 = 3 damage', '[Class Bonus] **Ranged 2**', true, ['keyword', 'ranged', 'class-bonus', 'combat'], 2);
gaWaterRanged('misteye-archer-without-the-class-bonus-has-no-ranged', 'Misteye Archer', 1, 'No Class Bonus: only its printed 1 POWER', '[Class Bonus] **Ranged 2**', false, ['keyword', 'ranged', 'class-bonus', 'condition'], 2);
gaWaterRanged('krustallan-archer-class-bonus-ranged-three-while-distant', 'Krustallan Archer', 4, '[Class Bonus] Ranged 3 while distant: 1 + 3 = 4 damage', '[Class Bonus] **Ranged 3**', true, ['keyword', 'ranged', 'class-bonus', 'combat']);
gaWaterRanged('krustallan-archer-without-the-class-bonus-has-no-ranged', 'Krustallan Archer', 1, 'No Class Bonus: only its printed 1 POWER', '[Class Bonus] **Ranged 3**', false, ['keyword', 'ranged', 'class-bonus', 'condition']);
gaWaterRanged('acheron-express-officer-ranged-three-while-distant', 'Acheron Express Officer', 4, 'Ranged 3 while distant: 1 + 3 = 4 damage', '**Ranged 3**');

// ---- taunt family ----
gaWaterTaunt('throne-keeper-bullfrog-taunt-protects-the-champion', 'Throne-Keeper Bullfrog', [], '**Taunt**');
gaWaterTaunt('languid-toadtroll-taunt-protects-the-champion', 'Languid Toadtroll', [], '**Taunt**');
gaWaterTaunt('mocking-otter-taunt-protects-the-champion', 'Mocking Otter', [], '**Taunt**');
gaWaterTaunt('commanding-sea-titan-taunt-protects-the-champion', 'Commanding Sea Titan', [], '**Taunt**');
gaWaterTaunt('tidewall-sentinel-taunt-protects-the-champion', 'Tidewall Sentinel', [], '**Taunt**');
gaWaterTaunt('aquifer-seneschal-class-bonus-taunt-protects-the-champion', 'Aquifer Seneschal', [], '[Class Bonus] **Taunt**', true);
gaWaterTaunt('rivulet-adjutant-class-bonus-taunt-protects-the-champion', 'Rivulet Adjutant', [], '[Class Bonus] **Taunt**', true);
gaWaterTaunt('waverider-protector-class-bonus-taunt-protects-the-champion', 'Waverider Protector', [], '[Class Bonus] **Taunt**', true);

// ---- attack / activated / phase triggers ----
gaScenario('benevolent-battle-priest-class-bonus-attack-recovers-one')
    ->standardDeck('Benevolent Battle Priest')
    ->readyFor('Benevolent Battle Priest', classBonus: true, damage: 3)
    ->seed('myField', 'Benevolent Battle Priest')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p1Field-0', 2, '[Class Bonus] On Attack: Recover 1, 3 damage down to 2')
    ->tested('Benevolent Battle Priest')
    ->covers(['on-attack', 'class-bonus', 'recover'], '[Class Bonus] **On Attack:** **Recover 1**.')
    ->register();

gaScenario('benevolent-battle-priest-without-the-class-bonus-does-not-recover')
    ->standardDeck('Benevolent Battle Priest')
    ->readyFor('Benevolent Battle Priest', damage: 3)
    ->seed('myField', 'Benevolent Battle Priest')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectDamage('p1Field-0', 3, 'No Class Bonus: the damage stays')
    ->tested('Benevolent Battle Priest')
    ->covers(['on-attack', 'class-bonus', 'condition'], '[Class Bonus] **On Attack:** **Recover 1**.')
    ->register();

gaScenario('breakwater-cadet-class-bonus-attack-mills-the-chosen-player')
    ->standardDeck('Breakwater Cadet')
    ->readyFor('Breakwater Cadet', classBonus: true)
    ->seed('myField', 'Breakwater Cadet')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, '1')
    ->expectZoneCount('myGraveyard', 2, 2, '[Class Bonus] The opponent put their top two cards into their graveyard')
    ->expectZoneCount('myGraveyard', 0, 1, 'Your graveyard is untouched')
    ->tested('Breakwater Cadet')
    ->covers(['on-attack', 'class-bonus', 'mill', 'targeting-player'], '[Class Bonus] **On Attack:** Target player puts the top two cards of their deck into their graveyard.')
    ->register();

gaScenario('breakwater-cadet-without-the-class-bonus-mills-nobody')
    ->standardDeck('Breakwater Cadet')
    ->readyFor('Breakwater Cadet')
    ->seed('myField', 'Breakwater Cadet')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')
    ->expectZoneCount('myGraveyard', 0, 2, 'No Class Bonus: nothing is milled')
    ->expectZoneCount('myGraveyard', 0, 1, 'No Class Bonus: nothing is milled')
    ->tested('Breakwater Cadet')
    ->covers(['on-attack', 'class-bonus', 'condition'], '[Class Bonus] **On Attack:** Target player puts the top two cards of their deck into their graveyard.')
    ->register();

gaScenario('tidestone-bovine-attack-mills-two-from-the-opponent')
    ->standardDeck('Tidestone Bovine')
    ->readyFor('Tidestone Bovine')
    ->seed('myField', 'Tidestone Bovine')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'NO')
    ->expectZoneCount('myGraveyard', 2, 2, 'They put their top two cards into their graveyard')
    ->expectZoneCount('myGraveyard', 0, 1, 'Not yourself')
    ->tested('Tidestone Bovine')
    ->covers(['on-attack', 'mill', 'targeting-player'], '**On Attack:** Target player puts the top two cards of their deck into their graveyard.')
    ->register();

gaScenario('tidestone-bovine-attack-can-mill-yourself')
    ->standardDeck('Tidestone Bovine')
    ->readyFor('Tidestone Bovine')
    ->seed('myField', 'Tidestone Bovine')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')
    ->expectZoneCount('myGraveyard', 2, 1, 'You put your own top two cards into your graveyard')
    ->expectZoneCount('myGraveyard', 0, 2, 'Not the opponent')
    ->tested('Tidestone Bovine')
    ->covers(['on-attack', 'mill', 'targeting-player'], '**On Attack:** Target player puts the top two cards of their deck into their graveyard.')
    ->register();

gaScenario('purling-footman-attack-may-put-the-top-card-into-the-graveyard')
    ->standardDeck('Purling Footman')
    ->readyFor('Purling Footman')
    ->seed('myField', 'Purling Footman')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'YES')
    ->expectZoneCount('myGraveyard', 1, 1, 'The looked-at top card went into the graveyard')
    ->tested('Purling Footman')
    ->covers(['on-attack', 'look', 'mill', 'optional-choice'], '**On Attack**: Look at the top card of your deck. You may put it into your graveyard.')
    ->register();

gaScenario('purling-footman-attack-may-keep-the-top-card')
    ->standardDeck('Purling Footman')
    ->readyFor('Purling Footman')
    ->seed('myField', 'Purling Footman')
    ->reachTurnThree()
    ->attack('myField-1', 'theirField-0')->answer(1, 'NO')
    ->expectZoneCount('myGraveyard', 0, 1, 'Declined: the card stays on top')
    ->tested('Purling Footman')
    ->covers(['on-attack', 'optional-choice-decline'], '**On Attack**: Look at the top card of your deck. You may put it into your graveyard.')
    ->register();

gaScenario('black-ice-spellweaver-sacrifice-gives-a-unit-minus-three-power-attacks')
    ->standardDeck('Black Ice Spellweaver')
    ->readyFor('Black Ice Spellweaver')
    ->seed('myField', 'Black Ice Spellweaver')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->target('theirField-1')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","M5LHimBiCn-debuff"]', 'The Dungeon Guide\'s attacks get -3 POWER until end of turn')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Spellweaver was sacrificed')
    ->tested('Black Ice Spellweaver')
    ->covers(['activated-ability', 'sacrifice', 'stat-change', 'targeting'], '**Sacrifice CARDNAME:** Target unit\'s attacks get -3[POWER] until end of turn.')
    ->register();

gaScenario('balmshot-nurse-rest-recovers-three-while-distant')
    ->standardDeck('Balmshot Nurse')
    ->readyFor('Balmshot Nurse', damage: 5)
    ->seed('myField', 'Balmshot Nurse', properties: ['TurnEffects' => ['DISTANT']])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectDamage('p1Field-0', 2, 'Distant: Recover 3, 5 damage down to 2')
    ->expectProperty('p1Field-1', 'Status', '1', '[REST] was paid')
    ->tested('Balmshot Nurse')
    ->covers(['activated-ability', 'rest', 'recover', 'condition'], '[REST]: **Recover 3**. Activate this ability only if CARDNAME is **distant**.')
    ->register();

gaScenario('balmshot-nurse-cannot-recover-when-it-is-not-distant')
    ->standardDeck('Balmshot Nurse')
    ->readyFor('Balmshot Nurse', damage: 5)
    ->seed('myField', 'Balmshot Nurse')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectDamage('p1Field-0', 5, 'Not distant: the ability cannot be activated')
    ->expectProperty('p1Field-1', 'Status', '2', 'It did not rest')
    ->tested('Balmshot Nurse')
    ->covers(['activated-ability', 'condition', 'distant'], 'Activate this ability only if CARDNAME is **distant**.')
    ->register();

gaScenario('cell-converter-end-phase-summons-a-rested-powercell')
    ->standardDeck('Cell Converter')
    ->readyFor('Cell Converter')
    ->seed('myField', 'Cell Converter')
    ->endTurn()
    ->expectCardExists('myField', 'Powercell', 'At the beginning of your end phase a Powercell token was summoned')
    ->expectProperty('p1Field-2', 'Status', '1', '...rested')
    ->tested('Cell Converter')
    ->covers(['end-phase', 'summon', 'token'], 'At the beginning of your end phase, **summon** a Powercell token rested.')
    ->register();

gaScenario('slick-torrentrider-recollection-phase-mills-the-top-card')
    ->standardDeck('Slick Torrentrider')
    ->readyFor('Slick Torrentrider')
    ->seed('myField', 'Slick Torrentrider')
    ->reachTurnThree()
    ->expectZoneCount('myGraveyard', 1, 1, 'At the beginning of your recollection phase the top card went into the graveyard')
    ->tested('Slick Torrentrider')
    ->covers(['recollection-phase', 'mill'], 'At the beginning of your recollection phase, put the top card of your deck into your graveyard.')
    ->register();

gaScenario('misty-whispertail-sacrifice-draws-a-card-into-memory')
    ->standardDeck('Misty Whispertail')
    ->readyFor('Misty Whispertail')
    ->seed('myField', 'Misty Whispertail')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')
    ->expectZoneCount('myMemory', 1, 1, 'Not ephemeral: drew a card into memory')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Whispertail was sacrificed')
    ->tested('Misty Whispertail')
    ->covers(['activated-ability', 'sacrifice', 'draw-into-memory', 'condition'], '**Sacrifice CARDNAME:** If CARDNAME was not **ephemeral**, draw a card into your memory. Otherwise, **recover 3**.')
    ->register();

gaScenario('keeper-of-the-wild-sacrifice-gives-a-beast-plus-two-life')
    ->standardDeck('Keeper of the Wild')
    ->readyFor('Keeper of the Wild')
    ->seed('myField', 'Keeper of the Wild')
    ->seed('myField', 'Direwolf')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->answer(1, 'myField-1')
    ->expectLife('p1Field-1', 3, 'The Direwolf (1 LIFE) got +2 LIFE')
    ->expectZoneCount('myGraveyard', 1, 1, 'The Keeper was sacrificed')
    ->tested('Keeper of the Wild')
    ->covers(['activated-ability', 'sacrifice', 'stat-change', 'targeting'], '**Sacrifice Keeper of the Wild**: Up to one target Animal or Beast ally you control gets +2 [LIFE] until end of turn.')
    ->register();

gaScenario('powered-swordsman-class-bonus-death-summons-a-powercell')
    ->standardDeck('Aenean Spark Alight')
    ->readyFor('Aenean Spark Alight')
    ->champion(classes: 'WARRIOR')
    ->seed('myField', 'Powered Swordsman', properties: ['Damage' => 2])
    ->seed('myHand', 'Aenean Spark Alight')
    ->castSpell('Aenean Spark Alight', pay: 2, window: 'both', target: 'myField-1')
    ->expectCardExists('myField', 'Powercell', '[Class Bonus] On Death: a Powercell token was summoned')
    ->expectCardExists('myGraveyard', 'Powered Swordsman', 'The Swordsman died')
    ->tested('Powered Swordsman')
    ->covers(['on-death', 'class-bonus', 'summon', 'token'], '[Class Bonus] **On Death:** **Summon** a Powercell token.')
    ->register();

// ---- champions: the printed On Enter, exercised by seeding the champion onto the field (the starting champion's Enter is suppressed) ----
gaScenario('miao-spirit-of-water-enter-draws-seven-cards')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Miao, Spirit of Water')
    ->observe()
    ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
    ->tested('Miao, Spirit of Water')
    ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
    ->register();

gaScenario('minthe-spirit-of-water-enter-draws-seven-cards')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Minthe, Spirit of Water')
    ->observe()
    ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
    ->tested('Minthe, Spirit of Water')
    ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
    ->register();

gaScenario('sabrina-spirit-of-water-enter-draws-seven-cards')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Sabrina, Spirit of Water')
    ->observe()
    ->expectZoneCount('myHand', 14, 1, 'On Enter: drew seven cards on top of the opening seven')
    ->tested('Sabrina, Spirit of Water')
    ->covers(['on-enter', 'draw', 'champion'], '**On Enter:** Draw seven cards.')
    ->register();

gaScenario('fragmented-spirit-of-water-enter-glimpses-draws-six-and-summons-a-spirit-shard')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Fragmented Spirit of Water')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 13, 1, 'Drew six cards on top of the opening seven')
    ->expectCardExists('myField', 'Spirit Shard', 'Summoned a Spirit Shard token')
    ->tested('Fragmented Spirit of Water')
    ->covers(['on-enter', 'glimpse', 'draw', 'summon', 'champion'], '**On Enter:** **Glimpse 6**. Draw six cards. Then **summon** a Spirit Shard token.')
    ->register();

gaScenario('spirit-of-fortuitous-water-enter-glimpses-and-draws-seven-into-memory')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Spirit of Fortuitous Water')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->expectZoneCount('myMemory', 7, 1, 'Drew seven cards into memory')
    ->expectZoneCount('myHand', 7, 1, 'The hand is untouched')
    ->tested('Spirit of Fortuitous Water')
    ->covers(['on-enter', 'glimpse', 'draw-into-memory', 'champion'], '**On Enter**: **Glimpse 7**. Draw seven cards into your memory.')
    ->register();

gaScenario('spirit-of-serene-water-enter-glimpses-and-draws-six')
    ->standardDeck('Dungeon Guide')
    ->seed('myField', 'Spirit of Serene Water')
    ->answer(1, 'Top=px60u5n1do,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,px60u5n1do,em6eEh9q8y;Bottom=')
    ->expectZoneCount('myHand', 13, 1, 'Drew six cards on top of the opening seven')
    ->tested('Spirit of Serene Water')
    ->covers(['on-enter', 'glimpse', 'draw', 'champion'], '**On Enter:** **Glimpse 6**. Draw six cards.')
    ->register();

gaScenario('spirit-of-serene-water-lineage-release-recovers-six')
    ->standardDeck('Dungeon Guide')
    ->champion(lineage: ['Spirit of Serene Water'], damage: 8)
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
    ->expectDamage('p1Field-0', 2, 'Lineage Release -- Recover 6: 8 damage down to 2')
    ->tested('Spirit of Serene Water')
    ->covers(['lineage-release', 'recover'], '**Lineage Release** -- **Recover 6**.')
    ->register();

// ---- floating memory, glimpse, level-gated and trigger allies ----
gaScenario('rainweaver-mage-class-bonus-enter-banishes-a-floating-memory-card-to-empower-four')
    ->standardDeck('Rainweaver Mage')
    ->readyFor('Rainweaver Mage', classBonus: true)
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Rainweaver Mage')
    ->castSpell('Rainweaver Mage', pay: 3, window: 'opp')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ALLY_ACTIVATED","qb6zhphtw6","EMPOWERED","EMPOWER_PLUS_4"]', 'Empower 4: the next Spell resolves as if the champion got +4 level')
    ->tested('Rainweaver Mage')
    ->covers(['on-enter', 'class-bonus', 'floating-memory', 'banish', 'empower'], '[Class Bonus] **On Enter:** You may banish a card with **floating memory** from your graveyard. If you do, **empower 4**.')
    ->register();

gaScenario('rimesoul-bishop-enter-banishes-a-floating-memory-card-to-draw')
    ->standardDeck('Rimesoul Bishop')
    ->readyFor('Rimesoul Bishop')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Rimesoul Bishop')
    ->castSpell('Rimesoul Bishop', pay: 3, window: 'opp')->answer(1, 'myGraveyard-0')
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectZoneCount('myHand', 5, 1, 'Drew a card (8 - Bishop - 3 paid + 1)')
    ->tested('Rimesoul Bishop')
    ->covers(['on-enter', 'floating-memory', 'banish', 'draw'], '**On Enter:** You may banish a card with **floating memory** from your graveyard. If you do, draw a card.')
    ->register();

gaScenario('rippleback-terrapin-enter-banishes-a-floating-memory-card-for-a-buff-counter-and-a-card')
    ->standardDeck('Rippleback Terrapin')
    ->readyFor('Rippleback Terrapin')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Rippleback Terrapin')
    ->castSpell('Rippleback Terrapin', pay: 4, window: 'opp')->answer(1, 'myGraveyard-0')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'On Enter: a buff counter went on the Terrapin')
    ->expectZoneCount('myHand', 4, 1, 'Drew a card (8 - Terrapin - 4 paid + 1)')
    ->tested('Rippleback Terrapin')
    ->covers(['on-enter', 'floating-memory', 'banish', 'buff-counter', 'draw'], '**On Enter:** You may banish a card with **floating memory** from your graveyard. If you do, put a **buff** counter on CARDNAME and draw a card.')
    ->register();

gaScenario('tidestone-seeker-class-bonus-enter-glimpses-three')
    ->standardDeck('Tidestone Seeker')
    ->readyFor('Tidestone Seeker', classBonus: true)
    ->seed('myHand', 'Tidestone Seeker')
    ->castSpell('Tidestone Seeker', pay: 2, window: 'opp')->answer(1, 'Top=fv1sjj2cgs;Bottom=px60u5n1do,em6eEh9q8y')
    ->expectProperty('p1Deck-0', 'CardID', 'fv1sjj2cgs', 'Glimpse 3: one card stayed on top...')
    ->expectProperty('p1Deck-8', 'CardID', 'em6eEh9q8y', '...and the other two went to the bottom of the deck')
    ->tested('Tidestone Seeker')
    ->covers(['on-enter', 'class-bonus', 'glimpse'], '[Class Bonus] **On Enter**: **Glimpse 3**.')
    ->register();

gaScenario('sword-saint-of-eventide-enter-may-mill-the-top-card-and-class-bonus-counts-four-water-cards')
    ->standardDeck('Sword Saint of Eventide')
    ->readyFor('Sword Saint of Eventide', classBonus: true)
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myHand', 'Sword Saint of Eventide')
    ->castSpell('Sword Saint of Eventide', pay: 2, window: 'opp')->answer(1, 'myTempZone-0')
    ->expectZoneCount('myGraveyard', 5, 1, 'On Enter: the looked-at top card was put into the graveyard')
    ->expectPower('p1Field-1', 2, '[Class Bonus] Four water cards in the graveyard: 1 + 1 = 2 POWER')
    ->tested('Sword Saint of Eventide')
    ->covers(['on-enter', 'look', 'mill', 'class-bonus', 'deluge', 'stat-change'], '**On Enter:** Look at the top card of your deck. You may put it into your graveyard.', '[Class Bonus] As long as you have four or more water element cards in your graveyard, CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('seasprite-diver-enter-banishes-a-card-from-a-graveyard')
    ->standardDeck('Seasprite Diver')
    ->readyFor('Seasprite Diver')
    ->seed('myGraveyard', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Seasprite Diver')
    ->castSpell('Seasprite Diver', pay: 4, window: 'opp', target: 'theirGraveyard-0')
    ->expectZoneCount('myBanish', 1, 2, 'The targeted graveyard card was banished')
    ->expectZoneCount('myGraveyard', 0, 2, 'Their graveyard is empty')
    ->tested('Seasprite Diver')
    ->covers(['on-enter', 'banish', 'targeting'], '**On Enter:** Banish target card in a graveyard.')
    ->register();

gaScenario('seasprite-diver-costs-one-less-when-an-opponent-has-four-cards-in-their-graveyard')
    ->standardDeck('Seasprite Diver')
    ->readyFor('Seasprite Diver')
    ->seed('myGraveyard', 'Giant Tortoise', player: 2)->seed('myGraveyard', 'Giant Tortoise', player: 2)->seed('myGraveyard', 'Giant Tortoise', player: 2)->seed('myGraveyard', 'Giant Tortoise', player: 2)
    ->seed('myHand', 'Seasprite Diver')
    ->castSpell('Seasprite Diver', pay: 3, window: 'opp', target: 'theirGraveyard-0')
    ->expectZoneCount('myMemory', 3, 1, 'Cost 4 - 1 = 3 with four cards in their graveyard')
    ->tested('Seasprite Diver')
    ->covers(['cost-reduction', 'condition'], 'As long as an opponent has four or more cards in their graveyard, this card costs 1 less to activate.')
    ->register();

gaScenario('frostbinder-apostle-level-two-enter-gives-a-rested-ally-minus-four-life')
    ->standardDeck('Frostbinder Apostle')
    ->readyFor('Frostbinder Apostle', level: 2)
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 1])
    ->seed('myHand', 'Frostbinder Apostle')
    ->castSpell('Frostbinder Apostle', pay: 3, window: 'opp', target: 'theirField-1')
    ->expectProperty('p2Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","x7u6wzh973"]', '[Level 2+] The rested ally got the -4 LIFE effect until end of turn')
    ->tested('Frostbinder Apostle')
    ->covers(['on-enter', 'level', 'stat-change', 'rested', 'targeting'], '[Level 2+] **On Enter:** Target rested ally you don\'t control gets -4 [LIFE] until end of turn.')
    ->register();

gaScenario('drowned-exorcist-level-two-enter-banishes-non-water-cards-from-both-graveyards')
    ->standardDeck('Drowned Exorcist')
    ->readyFor('Drowned Exorcist', level: 2)
    ->seed('myGraveyard', 'Dungeon Guide')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myGraveyard', 'Dungeon Guide', player: 2)
    ->seed('myHand', 'Drowned Exorcist')
    ->castSpell('Drowned Exorcist', pay: 4, window: 'opp')
    ->expectZoneCount('myBanish', 1, 1, 'Your non-water Dungeon Guide was banished')
    ->expectZoneCount('myBanish', 1, 2, 'Their non-water Dungeon Guide was banished')
    ->expectCardExists('myGraveyard', 'Giant Tortoise', 'The water card stayed in the graveyard')
    ->tested('Drowned Exorcist')
    ->covers(['on-enter', 'level', 'banish', 'graveyard'], '[Level 2+] **On Enter:** Each player banishes all non-water element cards from their graveyard.')
    ->register();

gaScenario('awakened-frostguard-class-bonus-foster-banishes-a-floating-memory-card-for-a-buff-and-a-card')
    ->standardDeck('Awakened Frostguard')
    ->readyFor('Awakened Frostguard', classBonus: true)
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myField', 'Awakened Frostguard')
    ->reachTurnThree()->answer(1, 'myGraveyard-0')
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'On Foster: a buff counter went on the Frostguard')
    ->expectZoneCount('myHand', 9, 1, 'Drew a card for the banished one (8 + 1)')
    ->expectProperty('p1Field-1', 'TurnEffects', '["FOSTERED"]', 'It is fostered')
    ->tested('Awakened Frostguard')
    ->covers(['foster', 'on-foster', 'class-bonus', 'floating-memory', 'buff-counter', 'draw'], '[Class Bonus] **Foster**', '**On Foster:** You may banish up to two cards with **floating memory** from your graveyard. For each card banished this way, put a **buff** counter on CARDNAME and draw a card.')
    ->register();

gaScenario('floodward-sergeant-prevents-the-first-damage-dealt-to-it-each-turn')
    ->standardDeck('Floodward Sergeant')
    ->readyFor('Floodward Sergeant')
    ->seed('myField', 'Floodward Sergeant', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectDamage('p1Field-1', 0, 'The 1 damage from the Dungeon Guide was prevented')
    ->expectDamage('p2Field-1', 1, 'The Sergeant retaliated for its 1 POWER')
    ->tested('Floodward Sergeant')
    ->covers(['replacement-effect', 'prevention', 'once-per-turn'], 'If damage would be dealt to CARDNAME, prevent that damage. Apply this replacement effect only once each turn.')
    ->register();

gaScenario('stillwater-patrol-true-sight-attacks-a-stealth-unit-with-plus-one-power')
    ->standardDeck('Stillwater Patrol')
    ->readyFor('Stillwater Patrol')
    ->seed('myField', 'Stillwater Patrol')
    ->seed('myField', 'Black Ice Spellweaver', player: 2)
    ->endTurn()->pass(2)->pass(2)->endTurn(2)->pass(2)->pass(2)->pass(1)->pass(2)
    ->attack('myField-1', 'theirField-1')->pass(2)
    ->expectCardExists('myGraveyard', 'Black Ice Spellweaver', 'True Sight let it attack the stealth unit and it destroyed it', 2)
    ->tested('Stillwater Patrol')
    ->covers(['true-sight', 'stealth', 'combat'], '**True Sight** *(This ally can attack units with **stealth**.)*')
    ->register();

gaScenario('mistbound-watcher-class-bonus-end-phase-puts-an-enlighten-counter-on-your-champion')
    ->standardDeck('Mistbound Watcher')
    ->readyFor('Mistbound Watcher', classBonus: true)
    ->seed('myField', 'Mistbound Watcher')
    ->endTurn()
    ->expectProperty('p1Field-0', 'Counters', '{"_overrides":{"classes":"MAGE"},"enlighten":1}', '[Class Bonus] At the beginning of your end phase an enlighten counter went on your champion')
    ->tested('Mistbound Watcher')
    ->covers(['end-phase', 'class-bonus', 'counter'], '[Class Bonus] At the beginning of your end phase, put an **enlighten** counter on your champion.')
    ->register();

gaScenario('intrepid-highwayman-retaliating-gets-plus-three-power')
    ->standardDeck('Intrepid Highwayman')
    ->readyFor('Intrepid Highwayman')
    ->seed('myField', 'Intrepid Highwayman', properties: ['Status' => 2])
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()
    ->attack('myField-1', 'theirField-1', player: 2)->answer(1, 'myField-1')
    ->expectCardExists('myGraveyard', 'Dungeon Guide', 'Retaliating it had 1 + 3 = 4 POWER and destroyed the 3 LIFE attacker', 2)
    ->tested('Intrepid Highwayman')
    ->covers(['retaliation', 'stat-change', 'condition'], 'As long as CARDNAME is retaliating, it gets +3 [POWER].')
    ->register();

// ---- Suited allies, fosters and statics ----
gaScenario('two-of-diamonds-cardistry-empowers-two')
    ->standardDeck('Two of Diamonds')
    ->seed('myField', 'Two of Diamonds')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(1)->pass(1)->pass(2)
    ->expectProperty('p1Field-0', 'TurnEffects', '["mzwTQzuXZa","EMPOWERED","EMPOWER_PLUS_2"]', 'Empower 2 went on the champion')
    ->expectProperty('p1Field-1', 'Counters', '{"cardistry_used":1}', 'The once-only marker is set')
    ->expectZoneCount('myMemory', 1, 1, 'Cost (2) less 1 for one Suited object with a different cost = 1')
    ->tested('Two of Diamonds')
    ->covers(['cardistry', 'empower', 'cost-reduction', 'once-per-game'], '***Cardistry*** -- (2): **Empower 2**. This ability costs (1) less to activate for each Suited object you control with different reserve costs. Activate this ability only once.')
    ->register();

gaScenario('three-of-diamonds-cardistry-looks-at-three-and-mills-one')
    ->standardDeck('Three of Diamonds')
    ->seed('myField', 'Three of Diamonds')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(2)->answer(1, 'myTempZone-0')->answer(1, 'Top=IZ2IiPsxe9,em6eEh9q8y;Bottom=')->pass(1)->pass(2)
    ->expectZoneCount('myGraveyard', 1, 1, 'One of the three looked-at cards went into the graveyard')
    ->expectZoneCount('myDeck', 8, 1, 'The other two went back on top (9 - 1)')
    ->tested('Three of Diamonds')
    ->covers(['cardistry', 'look', 'mill', 'cost-reduction'], '***Cardistry*** -- (3): Look at the top three cards of your deck. Put one of them into your graveyard and the rest back on top of your deck in any order.')
    ->register();

gaScenario('four-of-diamonds-cardistry-generates-a-bolt-for-each-revealed-suited-card')
    ->standardDeck('Four of Diamonds')
    ->seed('myField', 'Four of Diamonds')
    ->seed('myMemory', 'Two of Diamonds')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->answer(1, 'myMemory-0')->pass(1)->pass(2)
    ->expectCardExists('myMemory', 'Bolt of Diamonds', 'One revealed Suited card: a Bolt of Diamonds was generated into memory', 1)
    ->expectZoneCount('myMemory', 5, 1, 'The seeded memory card, 3 paid and one generated Bolt of Diamonds')
    ->tested('Four of Diamonds')
    ->covers(['cardistry', 'reveal', 'generate'], '***Cardistry*** -- (4): Reveal up to two Suited cards from your memory. For each card revealed this way, **generate** a Bolt of Diamonds card and put it into your memory.')
    ->register();

gaScenario('five-of-diamonds-cardistry-draws-a-card-into-memory')
    ->standardDeck('Five of Diamonds')
    ->seed('myField', 'Five of Diamonds')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(4)->pass(1)->pass(2)
    ->expectZoneCount('myMemory', 5, 1, '4 paid plus the card drawn into memory')
    ->tested('Five of Diamonds')
    ->covers(['cardistry', 'draw-into-memory', 'cost-reduction'], '***Cardistry*** -- (5): Draw a card into your memory. This ability costs (1) less to activate for each Suited object you control with different reserve costs. Activate this ability only once.')
    ->register();

gaScenario('disciple-of-the-waves-deluge-three-costs-one-less')
    ->standardDeck('Disciple of the Waves')
    ->readyFor('Disciple of the Waves')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myHand', 'Disciple of the Waves')
    ->castSpell('Disciple of the Waves', pay: 2, window: 'opp')
    ->expectCardExists('myField', 'Disciple of the Waves', 'Deluge 3: cost 3 - 1 = 2, the ally entered')
    ->expectZoneCount('myMemory', 2, 1, 'Only two cards were paid')
    ->tested('Disciple of the Waves')
    ->covers(['cost-reduction', 'deluge'], '***Deluge 3*** -- As long as you have three or more water element cards in your graveyard, this card costs 1 less to activate.')
    ->register();

gaScenario('disciple-of-the-waves-with-fewer-than-three-water-cards-costs-full-price')
    ->standardDeck('Disciple of the Waves')
    ->readyFor('Disciple of the Waves')
    ->seed('myGraveyard', 'Giant Tortoise')->seed('myGraveyard', 'Giant Tortoise')
    ->seed('myHand', 'Disciple of the Waves')
    ->castSpell('Disciple of the Waves', pay: 3, window: 'opp')
    ->expectCardExists('myField', 'Disciple of the Waves', 'Two water cards: no Deluge, full cost 3 paid')
    ->expectZoneCount('myMemory', 3, 1, 'Three cards were paid')
    ->tested('Disciple of the Waves')
    ->covers(['cost-reduction', 'deluge', 'condition'], '***Deluge 3*** -- As long as you have three or more water element cards in your graveyard, this card costs 1 less to activate.')
    ->register();

gaScenario('beguiling-bandit-attackers-must-pay-one-to-target-it')
    ->standardDeck('Beguiling Bandit')
    ->readyFor('Beguiling Bandit')
    ->seed('myField', 'Beguiling Bandit')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->pay(1, 2)->answer(1, 'myField-1')
    ->expectZoneCount('myMemory', 1, 2, 'Declaring an attack that targets the Bandit cost (1)')
    ->tested('Beguiling Bandit')
    ->covers(['static-ability', 'attack-tax', 'targeting'], 'Players can\'t declare attacks targeting Beguiling Bandit unless they pay (1) for each attack declaration.')
    ->register();

gaScenario('beguiling-bandit-an-attack-on-the-champion-costs-nothing')
    ->standardDeck('Beguiling Bandit')
    ->readyFor('Beguiling Bandit')
    ->seed('myField', 'Beguiling Bandit')
    ->seed('myField', 'Dungeon Guide', player: 2, properties: ['Status' => 2])
    ->endTurn()->pass(1)
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-0')
    ->expectZoneCount('myMemory', 0, 2, 'Attacking the champion is free')
    ->expectDamage('p1Field-0', 1, 'The attack landed on the champion')
    ->tested('Beguiling Bandit')
    ->covers(['static-ability', 'attack-tax', 'condition'], 'Players can\'t declare attacks targeting Beguiling Bandit unless they pay (1) for each attack declaration.')
    ->register();

gaScenario('nanyue-portsman-equestrian-with-a-horse-ally-gets-plus-one-power')
    ->standardDeck('Nanyue Portsman')
    ->readyFor('Nanyue Portsman')
    ->seed('myField', 'Nanyue Portsman')
    ->seed('myField', 'Flashfire Horse')
    ->observe()
    ->expectPower('p1Field-1', 2, 'Equestrian with a Horse ally: 1 + 1 = 2 POWER')
    ->tested('Nanyue Portsman')
    ->covers(['static-ability', 'equestrian', 'stat-change'], '***Equestrian*** -- As long as you control a Horse ally, CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('nanyue-portsman-without-a-horse-ally-keeps-its-printed-power')
    ->standardDeck('Nanyue Portsman')
    ->readyFor('Nanyue Portsman')
    ->seed('myField', 'Nanyue Portsman')
    ->observe()
    ->expectPower('p1Field-1', 1, 'No Horse ally: the printed 1 POWER')
    ->tested('Nanyue Portsman')
    ->covers(['static-ability', 'equestrian', 'condition'], '***Equestrian*** -- As long as you control a Horse ally, CARDNAME gets +1 [POWER].')
    ->register();

gaScenario('tidebreaker-sentinel-class-bonus-foster-mills-three-and-gets-life-and-taunt')
    ->standardDeck('Tidebreaker Sentinel')
    ->readyFor('Tidebreaker Sentinel', classBonus: true)
    ->seed('myField', 'Tidebreaker Sentinel')
    ->reachTurnThree()
    ->expectZoneCount('myGraveyard', 3, 1, 'On Foster: the top three cards went into the graveyard')
    ->expectLife('p1Field-1', 5, 'As long as it is fostered: 3 + 2 = 5 LIFE')
    ->expectProperty('p1Field-1', 'TurnEffects', '["FOSTERED"]', 'It is fostered')
    ->tested('Tidebreaker Sentinel')
    ->covers(['foster', 'on-foster', 'class-bonus', 'mill', 'stat-change', 'taunt'], '[Class Bonus] **Foster**', '**On Foster**: Put the top three cards of your deck into your graveyard.', 'As long as Tidebreaker Sentinel is **fostered**, it gets +2 [LIFE] and has **taunt**.')
    ->register();

gaScenario('krustallan-patrol-foster-puts-a-buff-counter-on-itself')
    ->standardDeck('Krustallan Patrol')
    ->readyFor('Krustallan Patrol')
    ->seed('myField', 'Krustallan Patrol')
    ->reachTurnThree()
    ->expectProperty('p1Field-1', 'Counters', '{"buff":1}', 'On Foster: a buff counter went on the Patrol')
    ->expectProperty('p1Field-1', 'TurnEffects', '["FOSTERED"]', 'It is fostered')
    ->tested('Krustallan Patrol')
    ->covers(['foster', 'on-foster', 'buff-counter'], '**Foster**', '**On Foster:** Put a **buff** counter on CARDNAME.')
    ->register();

gaScenario('imperial-apprentice-spell-activation-banishes-a-floating-memory-card-to-draw')
    ->standardDeck('Imperial Apprentice')
    ->readyFor('Imperial Apprentice')
    ->seed('myField', 'Imperial Apprentice')
    ->seed('myGraveyard', 'Croaking Webfoot')
    ->seed('myHand', 'Bolt of Diamonds')
    ->castSpell('Bolt of Diamonds', pay: 2, target: 'theirField-0')->answer(1, 'myGraveyard-0')->pass(1)->pass(2)
    ->expectZoneCount('myBanish', 1, 1, 'The floating memory card was banished')
    ->expectDamage('p2Field-0', 1, 'The Bolt of Diamonds resolved')
    ->tested('Imperial Apprentice')
    ->covers(['triggered-ability', 'spell-activation', 'floating-memory', 'banish', 'draw'], 'Whenever you activate a Spell card, you may banish a card with **floating memory** from your graveyard. If you do, draw a card.')
    ->register();
