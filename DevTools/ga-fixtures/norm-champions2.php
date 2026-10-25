<?php
/**
 * Phase 3d (Norm): champion lineage cards (Alice, Golden Queen and Zander). The lineage card is a Subcard of the starting champion, so its Inherited Effect and Lineage Release apply. 'quiet' deck.
 */

// ---- Alice, Golden Queen ----

gaScenario('alice-golden-queen-lineage-release-prevents-damage-to-each-awake-chessman-ally')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Alice, Golden Queen'])
    ->seed('myField', 'Weiss Bishop', properties: ['Status' => 2])
    ->seed('myField', 'Weiss Bishop', properties: ['Status' => 1])
    ->seed('myField', 'Dungeon Guide', properties: ['Status' => 2])
    ->action(1, 10001, 'myField-0!CustomInput!Activate:0')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN","PREVENT_ALL_3"]', 'The awake Chessman ally can prevent the next 3 damage')
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'A rested Chessman ally gets nothing')
    ->expectProperty('p1Field-3', 'TurnEffects', '["ENTERED_THIS_TURN"]', 'A non-Chessman ally gets nothing')
    ->tested('Alice, Golden Queen')
    ->covers(['lineage-release', 'prevention', 'condition'], '**Lineage Release** — For each awake Chessman ally you control, prevent the next 3 damage that would be dealt to that ally this turn.')
    ->register();

gaScenario('alice-golden-queen-inherited-effect-chessman-command-attacks-get-plus-one-power')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(lineage: ['Alice, Golden Queen'])
    ->seed('myField', 'Golden Pawn')
    ->seed('myHand', 'Classical Opening')
    ->reachTurnThree(fastCardInHand: true)
    ->play('Classical Opening')->pay(2)->pass(1)->target('myField-1')->target('theirField-0')->pass(1)->pass(2)->pass(1)->pass(2)
    ->expectDamage('p2Field-0', 3, 'The Golden Pawn attacked with 0 power + the card\'s 2 + 1 from the inherited effect')
    ->tested('Alice, Golden Queen')
    ->covers(['inherited-effect', 'stat-change', 'command-chessman'], '**Inherited Effect** — Chessman Command attack cards you activate enter the intent with +1[POWER].')
    ->register();

// ---- Zander, Always Watching ----

if (!function_exists('gaZanderAttack')) {
    function gaZanderAttack(string $slug, int $status, int $damage, string $label): void {
        $s = gaScenario($slug)
            ->deckTemplate('quiet', 'Dungeon Guide')
            ->champion(lineage: ['Zander, Always Watching'])
            ->seed('myField', 'Giant Tortoise', player: 2, properties: ['Status' => $status])
            ->seed('myHand', 'Off With Her Head')
            ->reachTurnThree()
            ->play('Off With Her Head')->pay(2)->target('theirField-1');
        if ($status === 2) $s->pass(2);
        $s->expectDamage('p2Field-1', $damage, $label)
            ->tested('Zander, Always Watching')
            ->covers(['inherited-effect', 'condition', 'stat-change'], '**Inherited Effect:** As long as CARDNAME is attacking a rested unit, CARDNAME\'s attacks get +1 [POWER].')
            ->register();
    }
}

gaZanderAttack('zander-always-watching-inherited-effect-attacking-a-rested-unit-gets-plus-one-power', 1, 3, 'A rested unit: Off With Her Head\'s 2 + 1 from the inherited effect');
gaZanderAttack('zander-always-watching-inherited-effect-attacking-an-awake-unit-has-the-printed-power', 2, 2, 'An awake unit: the printed 2');

// ---- Servile Possessions (mastery, [Ciel Bonus]) ----

if (!function_exists('gaServile')) {
    function gaServile(string $slug, int $omens, int $damage, string $label, bool $ciel = true): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
        if ($ciel) $s->champion(cardName: 'Ciel, Omenbringer');
        $s->seed('myMastery', 'Servile Possessions')->seed('myField', 'Training Sword');
        for ($i = 0; $i < $omens; ++$i) $s->seed('myBanish', 'Dungeon Guide', properties: ['Counters' => ['omen' => 1]]);
        return $s->reachTurnThree()->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, 'theirField-0')->passBoth();
    }
}

$gaServileClause = '[Ciel Bonus] Whenever your champion attacks, depending on the amount of omens you have— 1 to 2— That attack gets +1[POWER]. 3 to 4— That attack gets +2[POWER]. 5 or more— That attack gets +3[POWER]. Draw a card into your memory.';

gaServile('servile-possessions-ciel-bonus-with-one-omen-the-attack-gets-plus-one-power', 1, 2, '')
    ->expectDamage('p2Field-0', 2, 'One omen: the sword\'s 1 + 1')
    ->tested('Servile Possessions')->covers(['ciel-bonus', 'omen', 'stat-change', 'on-attack'], $gaServileClause)->register();

gaServile('servile-possessions-ciel-bonus-with-three-omens-the-attack-gets-plus-two-power', 3, 3, '')
    ->expectDamage('p2Field-0', 3, 'Three omens: the sword\'s 1 + 2')
    ->tested('Servile Possessions')->covers(['ciel-bonus', 'omen', 'stat-change', 'on-attack'], $gaServileClause)->register();

gaServile('servile-possessions-ciel-bonus-with-five-omens-the-attack-gets-plus-three-power-and-draws-into-memory', 5, 4, '')
    ->expectDamage('p2Field-0', 4, 'Five omens: the sword\'s 1 + 3')
    ->expectZoneCount('myMemory', 1, 1, 'Five or more omens: a card was drawn into memory')
    ->tested('Servile Possessions')->covers(['ciel-bonus', 'omen', 'stat-change', 'draw-into-memory', 'on-attack'], $gaServileClause)->register();

gaServile('servile-possessions-without-the-ciel-bonus-the-attack-has-its-printed-power', 3, 1, '', false)
    ->expectDamage('p2Field-0', 1, 'No Ciel champion: the sword\'s printed 1')
    ->tested('Servile Possessions')->covers(['ciel-bonus', 'condition'], $gaServileClause)->register();

// ---- Shifting Currents (mastery, [Kongming Bonus]) ----

gaScenario('shifting-currents-kongming-bonus-end-phase-changes-its-direction')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->champion(cardName: 'Kongming, Wayward Maven')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->endTurn()->answer(1, 'EAST')
    ->expectProperty('p1Mastery-0', 'Direction', 'EAST', 'At the beginning of the end phase the direction was changed from North to East')
    ->tested('Shifting Currents')
    ->covers(['kongming-bonus', 'trigger', 'direction'], '[Kongming Bonus] At the beginning of your end phase, you may change the direction of your CARDNAME to a different direction of your choice.')
    ->register();

gaScenario('shifting-currents-without-the-kongming-bonus-keeps-its-direction')
    ->deckTemplate('quiet', 'Dungeon Guide')
    ->seed('myMastery', 'Shifting Currents', properties: ['Direction' => 'NORTH'])
    ->endTurn()
    ->expectProperty('p1Mastery-0', 'Direction', 'NORTH', 'No Kongming champion: no prompt and the direction is unchanged')
    ->tested('Shifting Currents')
    ->covers(['kongming-bonus', 'condition'], '[Kongming Bonus] At the beginning of your end phase, you may change the direction of your CARDNAME to a different direction of your choice.')
    ->register();

// ---- Fractured Memories (mastery, [Merlin Bonus]) ----

if (!function_exists('gaFractured')) {
    function gaFractured(string $slug, bool $merlin, array $oppSeeds, string $target): GaScenario {
        $s = gaScenario($slug)->deckTemplate('quiet', 'Dungeon Guide');
        if ($merlin) $s->champion(cardName: 'Merlin, Memorite Vassal');
        $s->seed('myMastery', 'Fractured Memories')->seed('myField', 'Training Sword');
        foreach ($oppSeeds as $o) $s->seed('myField', $o[0], player: 2, properties: $o[1] ?? []);
        return $s->patch('myField-0', ['Counters' => ['sheen' => 4]], player: 2)
            ->reachTurnThree()->action(1, 10002, 'myField-0!FSM!')->answer(1, 'myField-1')->answer(1, $target);
    }
}

gaFractured('fractured-memories-merlin-bonus-champion-hit-moves-the-sheen-counters-and-deals-three-damage', true, [], 'theirField-0')
    ->answer(1, 'YES')
    ->expectProperty('p1Mastery-0', 'Counters', '{"sheen":4}', 'All four sheen counters moved to Fractured Memories')
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":4}', 'The hit champion lost its sheen counters (only the damage tracker remains)')
    ->expectDamage('p2Field-0', 4, 'The sword\'s 1 plus 3 damage because three or more counters were moved')
    ->tested('Fractured Memories')
    ->covers(['merlin-bonus', 'on-champion-hit', 'counter', 'damage'], '[Merlin Bonus]Your champion has "**On Champion Hit:** You may move all **sheen** counters on the hit champion to your Fractured Memories. When three or more counters are moved this way, deal 3 damage to that champion."')
    ->register();

gaFractured('fractured-memories-merlin-bonus-declining-the-champion-hit-leaves-the-sheen-counters', true, [], 'theirField-0')
    ->answer(1, 'NO')
    ->expectProperty('p2Field-0', 'Counters', '{"sheen":4,"_champDamageThisTurn":1}', 'Declined: the hit champion keeps its sheen counters')
    ->expectDamage('p2Field-0', 1, 'Only the sword\'s 1 damage')
    ->tested('Fractured Memories')
    ->covers(['merlin-bonus', 'on-champion-hit', 'optional-choice'], 'You may move all **sheen** counters on the hit champion to your Fractured Memories.')
    ->register();

gaFractured('fractured-memories-without-the-merlin-bonus-the-champion-hit-does-nothing', false, [], 'theirField-0')
    ->expectProperty('p2Field-0', 'Counters', '{"sheen":4,"_champDamageThisTurn":1}', 'No Merlin champion: nothing is offered')
    ->tested('Fractured Memories')
    ->covers(['merlin-bonus', 'condition'], '[Merlin Bonus]Your champion has "**On Champion Hit:**')
    ->register();

gaFractured('fractured-memories-merlin-bonus-ally-kill-puts-the-killed-allys-sheen-counters-on-it', true, [['Gawain, Chivalrous Thief', ['Counters' => ['sheen' => 2], 'Status' => 1]]], 'theirField-1')
    ->expectProperty('p1Mastery-0', 'Counters', '{"sheen":2}', 'The killed ally had two sheen counters: they went onto Fractured Memories')
    ->tested('Fractured Memories')
    ->covers(['merlin-bonus', 'on-ally-kill', 'counter'], '[Merlin Bonus] Your champion has "**On Ally Kill:** Put **X** **sheen** counters onto your Fractured Memories, where **X** is the amount of **sheen** counters that were on the killed ally."')
    ->register();
