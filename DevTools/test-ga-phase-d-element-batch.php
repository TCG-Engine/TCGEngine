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
    echo "OK $checks checks\n";
} catch (Throwable $e) {
    echo "FAIL " . $e->getMessage() . " @" . $e->getLine() . "\n";
    exit(1);
} finally {
    @unlink($gameDir . '/Gamestate.txt');
    @rmdir($gameDir);
}
