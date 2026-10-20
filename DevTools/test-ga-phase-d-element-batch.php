<?php
// Retained loaded-state checks for the Phase D element-membership batch: generated bodies that compared CardElement() with === / !== now use GAIsElement(),
// so an Exalted Fire / Wind / Water card counts. Supplements, does not replace, gameplay fixtures.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_d_elements_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function elemCheck($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
    echo "PASS $label\n";
}
function elemLoad($slug = 'slipstream-target-cost-ordinary') {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('Tests/Integration/GrandArchiveSim/' . $slug . '/initial_gamestate.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
}
function elemQueueOptions($player) {
    $queue = &GetDecisionQueue($player);
    $options = [];
    foreach ($queue as $d) if (in_array($d->Type, ['MZMAYCHOOSE', 'MZCHOOSE', 'MZMULTICHOOSE'], true)) $options[] = $d->Param ?? '';
    return implode('&', $options);
}
$exaltedFire = 'HPDawzCDdr';  // EXALTED,FIRE
$exaltedWind = 'iullthfLXc';  // EXALTED,WIND
$exaltedWater = 'GGRtLQgaYU'; // EXALTED,WATER
$plainFire = 'wc8tuhuy4x';
try {
    // The active handler must be the maintained override, not the generated body.
    $keys = [['activateAbilityAbilities', '96659ytyj2:0'], ['enterAbilities', '1o0tKizBZ6:0'], ['enterAbilities', '8eyeqhc37y:0'], ['enterAbilities', 'ba0tqvwlp1:0'],
        ['enterAbilities', 'wc8tuhuy4x:0'], ['enterAbilities', 'x8bd7ozuj6:0'], ['enterAbilities', 'qe1pkerbi3:0'], ['cardActivatedAbilities', 'y6isxy5lh2:0'],
        ['discardCardAbilities', 'KEhmWGivJp:0'], ['onAttackAbilities', '7xgwve1d47:0'], ['onAttackAbilities', 'hreqhj1trn:0'], ['onAttackAbilities', 'iuixf9rdmu:0'],
        ['onAttackAbilities', 'oqk1l75tlz:0'], ['onAttackAbilities', 'q5CXVeHGIf:0'], ['onAttackAbilities', 'qmyn2rz308:0'], ['onHitAbilities', 'TgYTZg6TaG:0'],
        ['customDQHandlers', 'THjSE7caau:0:Enter-1'], ['customDQHandlers', 'r7oifozaog:0:OnAttack-1']];
    foreach ($keys as [$table, $key]) {
        $closure = $GLOBALS[$table][$key] ?? null;
        elemCheck($closure !== null && str_ends_with((new ReflectionFunction($closure))->getFileName(), 'GeneratedAbilityOverrides.php'), "{$table}[{$key}] is the maintained override");
    }
    foreach ([[$exaltedFire, 'FIRE', true], [$plainFire, 'FIRE', true], [$exaltedFire, 'WIND', false], [$exaltedWind, 'WIND', true], [$exaltedWater, 'WATER', true], [$exaltedWater, 'FIRE', false], [null, 'FIRE', false]] as [$id, $element, $expected]) {
        elemCheck(GAIsElement($id, $element) === $expected, "GAIsElement(" . ($id ?? 'null') . ", $element)");
    }

    // Wc8tuhuy4x: On Enter offers the fire cards in hand.
    foreach ([$exaltedFire => true, $exaltedWind => false] as $cardID => $qualifies) {
        elemLoad();
        $hand = &GetHand(1); $hand = [];
        AddHand(1, CardID: $cardID);
        $queue = &GetDecisionQueue(1); $queue = [];
        $enterAbilities['wc8tuhuy4x:0'](1);
        elemCheck((strpos(elemQueueOptions(1), 'myHand-0') !== false) === $qualifies, "wc8tuhuy4x offers $cardID: " . ($qualifies ? 'yes' : 'no'));
    }

    // Drowned Exorcist (qe1pkerbi3): non-water cards are banished from both graveyards; Exalted Water stays.
    elemLoad();
    foreach ([1, 2] as $seat) { $gy = &GetGraveyard($seat); $gy = []; AddGraveyard($seat, CardID: $exaltedWater); AddGraveyard($seat, CardID: $exaltedFire); }
    foreach (GetField(1) as $obj) if (PropertyContains(CardType($obj->CardID), 'CHAMPION')) { $obj->Counters['level'] = 2; break; }
    $enterAbilities['qe1pkerbi3:0'](1);
    DecisionQueueController::CleanupRemovedCards();
    foreach ([1, 2] as $seat) {
        $ids = array_map(fn($o) => $o->CardID, array_values(array_filter(GetGraveyard($seat), fn($o) => !$o->removed)));
        elemCheck($ids === [$exaltedWater], "Drowned Exorcist keeps only the Exalted Water card in seat $seat graveyard");
    }

    // Ritai-style fire discard payoff (KEhmWGivJp): an Exalted Fire discard adds the buff counter.
    foreach ([$exaltedFire => 1, $exaltedWind => 0] as $cardID => $expected) {
        elemLoad();
        $obj = AddField(1, CardID: 'KEhmWGivJp', Owner: 1, Controller: 1);
        DecisionQueueController::StoreVariable('discardedCardID', $cardID);
        $discardCardAbilities['KEhmWGivJp:0'](1);
        elemCheck(GetCounterCount($obj, 'buff') === $expected, "KEhmWGivJp discarding $cardID gives $expected buff counters");
    }

    // ---- Behaviour checks for the remaining overrides. Any ordinary card is made dual-element by editing $elementData for the duration of a check. ----
    $dummy = 'em6eEh9q8y';
    $withElement = function ($element, $fn) use ($dummy) {
        global $elementData;
        $saved = $elementData[$dummy];
        $elementData[$dummy] = $element;
        try { return $fn(); } finally { $elementData[$dummy] = $saved; }
    };
    $champion = function ($seat = 1) { foreach (GetField($seat) as $o) if (!$o->removed && PropertyContains(CardType($o->CardID), 'CHAMPION')) return $o; throw new RuntimeException('no champion'); };
    $queueTypes = function () { $q = &GetDecisionQueue(1); return array_map(fn($d) => $d->Type, $q); };
    $clearQueue = function () { $q = &GetDecisionQueue(1); $q = []; };
    $live = fn($zone) => count(array_filter(GetZone($zone), fn($o) => !$o->removed));

    // Banish (96659ytyj2): two revealed memory cards, wind ones are banished.
    foreach (['EXALTED,WIND' => 2, 'EXALTED,FIRE' => 0] as $element => $banished) {
        elemLoad();
        $mem = &GetMemory(2); $mem = [];
        AddMemory(2, CardID: $dummy); AddMemory(2, CardID: $dummy);
        $withElement($element, fn() => $activateAbilityAbilities['96659ytyj2:0'](1));
        DecisionQueueController::CleanupRemovedCards();
        elemCheck($live('theirBanish') === $banished, "96659ytyj2 with $element memory cards banishes $banished");
    }

    // Windstream Mutt (1o0tKizBZ6): revealed wind card lets you pick another ally.
    foreach (['EXALTED,WIND' => true, 'EXALTED,FIRE' => false] as $element => $offers) {
        elemLoad();
        $champion()->Counters['_overrides']['classes'] = 'TAMER';
        $mutt = AddField(1, CardID: '1o0tKizBZ6', Owner: 1, Controller: 1);
        AddField(1, CardID: $dummy, Owner: 1, Controller: 1);
        $mem = &GetMemory(1); $mem = []; AddMemory(1, CardID: $dummy);
        $clearQueue();
        DecisionQueueController::StoreVariable('mzID', 'myField-' . $mutt->mzIndex);
        $withElement($element, fn() => $enterAbilities['1o0tKizBZ6:0'](1));
        elemCheck(in_array('MZCHOOSE', $queueTypes(), true) === $offers, "1o0tKizBZ6 revealing $element " . ($offers ? 'offers' : 'does not offer') . ' a buff target');
    }

    // Polkhawk lineage (8eyeqhc37y): a fire card in the lineage sets the effect.
    foreach (['EXALTED,FIRE' => 1, 'EXALTED,WIND' => 0] as $element => $expected) {
        elemLoad();
        $champion()->Subcards = [$dummy];
        $effects = &GetGlobalEffects(1); $effects = [];
        $withElement($element, fn() => $enterAbilities['8eyeqhc37y:0'](1));
        $count = count(array_filter(GetGlobalEffects(1), fn($o) => $o->CardID === 'POLKHAWK_NEXT_RANGER_DISTANT'));
        elemCheck($count === $expected, "8eyeqhc37y with $element lineage card adds $expected effects");
    }

    // Equestrian (ba0tqvwlp1) and wc8-style hand offers.
    foreach (['EXALTED,FIRE' => true, 'EXALTED,WIND' => false] as $element => $offers) {
        elemLoad();
        AddField(1, CardID: 'r5uyjq37zh', Owner: 1, Controller: 1);
        $hand = &GetHand(1); $hand = []; AddHand(1, CardID: $dummy);
        $clearQueue();
        $withElement($element, fn() => $enterAbilities['ba0tqvwlp1:0'](1));
        elemCheck(in_array('MZMULTICHOOSE', $queueTypes(), true) === $offers, "ba0tqvwlp1 hand card $element " . ($offers ? 'is' : 'is not') . ' offered');
    }

    // Vanitas (x8bd7ozuj6): another wind card in the lineage glimpses.
    foreach (['EXALTED,WIND' => true, 'EXALTED,FIRE' => false] as $element => $glimpses) {
        elemLoad();
        $champion()->Subcards = [$dummy];
        $clearQueue();
        $withElement($element, fn() => $enterAbilities['x8bd7ozuj6:0'](1));
        elemCheck((count($queueTypes()) > 0) === $glimpses, "x8bd7ozuj6 lineage $element " . ($glimpses ? 'glimpses' : 'does not glimpse'));
    }

    // Force Load (y6isxy5lh2): a fire/norm 0-cost Bullet in the material deck.
    foreach (['EXALTED,FIRE' => true, 'EXALTED,WIND' => false] as $element => $offers) {
        elemLoad();
        $material = AddMaterial(1, CardID: '97n2jnltv5');
        $clearQueue();
        global $elementData;
        $saved = $elementData['97n2jnltv5']; $elementData['97n2jnltv5'] = $element;
        try { $cardActivatedAbilities['y6isxy5lh2:0'](1); } finally { $elementData['97n2jnltv5'] = $saved; }
        elemCheck(in_array('MZCHOOSE', $queueTypes(), true) === $offers, "y6isxy5lh2 material Bullet $element " . ($offers ? 'is' : 'is not') . ' offered');
    }

    // Ranger water-card mill choice (7xgwve1d47) and Windpiercer (hreqhj1trn): revealed top card.
    foreach (['EXALTED,WATER' => true, 'EXALTED,FIRE' => false] as $element => $asks) {
        elemLoad();
        $champion()->Counters['_overrides']['classes'] = 'RANGER';
        $deck = &GetDeck(1); $deck = []; AddDeck(1, CardID: $dummy);
        $clearQueue();
        $withElement($element, fn() => $onAttackAbilities['7xgwve1d47:0'](1));
        elemCheck(in_array('YESNO', $queueTypes(), true) === $asks, "7xgwve1d47 top card $element " . ($asks ? 'asks' : 'does not ask'));
    }
    foreach (['EXALTED,WIND' => true, 'EXALTED,FIRE' => false] as $element => $buffed) {
        elemLoad();
        $weapon = AddField(1, CardID: 'hreqhj1trn', Owner: 1, Controller: 1);
        $deck = &GetDeck(1); $deck = []; AddDeck(1, CardID: $dummy);
        $clearQueue();
        DecisionQueueController::StoreVariable('mzID', 'myField-' . $weapon->mzIndex);
        $withElement($element, fn() => $onAttackAbilities['hreqhj1trn:0'](1));
        elemCheck(in_array('hreqhj1trn-power', $weapon->TurnEffects ?? [], true) === $buffed, "hreqhj1trn top card $element " . ($buffed ? 'grants' : 'does not grant') . ' power');
    }

    // Diana bonus wind count (iuixf9rdmu) and Polkhawk bonus (oqk1l75tlz) read the intent.
    foreach (['EXALTED,WIND' => true, 'EXALTED,FIRE' => false] as $element => $buffs) {
        elemLoad();
        $champion()->Subcards = ['m7f6r8f3y8'];
        AddField(1, CardID: $dummy, Owner: 1, Controller: 1);
        $intent = &GetIntent(1); $intent = []; AddIntent(1, CardID: $dummy);
        $clearQueue();
        $withElement($element, fn() => $onAttackAbilities['iuixf9rdmu:0'](1));
        elemCheck((count($queueTypes()) > 0) === $buffs, "iuixf9rdmu intent $element " . ($buffs ? 'buffs' : 'does not buff'));
    }
    foreach (['EXALTED,FIRE' => 2, 'EXALTED,WIND' => 0] as $element => $damage) {
        elemLoad();
        $champ = $champion();
        $champ->CardID = 'ryvfq3huqj';
        $intent = &GetIntent(1); $intent = []; AddIntent(1, CardID: $dummy);
        DecisionQueueController::StoreVariable('CombatWeapon', 'myField-' . $champ->mzIndex);
        $theirChamp = $champion(2);
        $before = intval($theirChamp->Damage);
        $withElement($element, fn() => $onAttackAbilities['oqk1l75tlz:0'](1));
        DecisionQueueController::CleanupRemovedCards();
        elemCheck(intval($theirChamp->Damage) - $before === $damage, "oqk1l75tlz intent $element deals $damage to the defending champion");
    }

    // Link VelTech (q5CXVeHGIf): any Norm/Fire/Water/Wind VelTech Ally Link card in hand or memory.
    global $AllyLink_Cards;
    $veltech = null;
    foreach (array_keys($AllyLink_Cards) as $id) if (PropertyContains(CardSubtypes($id), 'VELTECH')) { $veltech = $id; break; }
    elemCheck($veltech !== null, 'a VelTech Ally Link card exists for q5CXVeHGIf');
    foreach (['EXALTED,FIRE' => true, 'EXALTED,TERA' => false] as $element => $offers) {
        elemLoad();
        $hand = &GetHand(1); $hand = []; AddHand(1, CardID: $veltech);
        $clearQueue();
        global $elementData;
        $saved = $elementData[$veltech]; $elementData[$veltech] = $element;
        try { $onAttackAbilities['q5CXVeHGIf:0'](1); } finally { $elementData[$veltech] = $saved; }
        elemCheck(in_array('MZMAYCHOOSE', $queueTypes(), true) === $offers, "q5CXVeHGIf VelTech card $element " . ($offers ? 'is' : 'is not') . ' offered');
    }

    // Cleric fire discard for power (qmyn2rz308).
    foreach (['EXALTED,FIRE' => true, 'EXALTED,WIND' => false] as $element => $offers) {
        elemLoad();
        $champion()->Counters['_overrides']['classes'] = 'CLERIC';
        $target = AddField(2, CardID: $dummy, Owner: 2, Controller: 2);
        DecisionQueueController::StoreVariable('CombatTarget', 'theirField-' . $target->mzIndex);
        $hand = &GetHand(1); $hand = []; AddHand(1, CardID: $dummy);
        $clearQueue();
        $withElement($element, fn() => $onAttackAbilities['qmyn2rz308:0'](1));
        elemCheck(in_array('MZMAYCHOOSE', $queueTypes(), true) === $offers, "qmyn2rz308 hand card $element " . ($offers ? 'is' : 'is not') . ' offered');
    }

    // Wind Cutter (TgYTZg6TaG): a revealed wind memory card puts it into memory.
    foreach (['EXALTED,WIND' => 2, 'EXALTED,FIRE' => 1] as $element => $memoryCount) {
        elemLoad();
        $mem = &GetMemory(1); $mem = []; AddMemory(1, CardID: $dummy);
        $intent = &GetIntent(1); $intent = []; AddIntent(1, CardID: 'TgYTZg6TaG');
        $withElement($element, fn() => $onHitAbilities['TgYTZg6TaG:0'](1));
        DecisionQueueController::CleanupRemovedCards();
        elemCheck($live('myMemory') === $memoryCount, "TgYTZg6TaG revealing $element leaves $memoryCount memory cards");
    }

    // Merlin discard (THjSE7caau) and Ritai-style discard buff (r7oifozaog).
    foreach (['EXALTED,FIRE' => true, 'EXALTED,WIND' => false] as $element => $targets) {
        elemLoad();
        $champion()->Subcards = ['6R8XmWoKLn'];
        $hand = &GetHand(1); $hand = []; AddHand(1, CardID: $dummy);
        $clearQueue();
        $withElement($element, fn() => $customDQHandlers['THjSE7caau:0:Enter-1'](1, [], 'myHand-0'));
        elemCheck(in_array('MZCHOOSE', $queueTypes(), true) === $targets, "THjSE7caau discarding $element " . ($targets ? 'asks for a target' : 'does nothing more'));
    }
    foreach (['EXALTED,FIRE' => true, 'EXALTED,WIND' => false] as $element => $buffed) {
        elemLoad();
        $unit = AddField(1, CardID: 'r7oifozaog', Owner: 1, Controller: 1);
        $hand = &GetHand(1); $hand = []; AddHand(1, CardID: $dummy);
        DecisionQueueController::StoreVariable('mzID', 'myField-' . $unit->mzIndex);
        $withElement($element, fn() => $customDQHandlers['r7oifozaog:0:OnAttack-1'](1, [], 'myHand-0'));
        elemCheck(in_array('r7oifozaog', $unit->TurnEffects ?? [], true) === $buffed, "r7oifozaog discarding $element " . ($buffed ? 'grants' : 'does not grant') . ' the buff');
    }
    echo "OK $checks checks\n";
} catch (Throwable $e) {
    echo "FAIL " . $e->getMessage() . " @" . $e->getLine() . "\n";
    exit(1);
} finally {
    @unlink($gameDir . '/Gamestate.txt');
    @rmdir($gameDir);
}
