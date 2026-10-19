<?php
// Retained loaded-state checks for Phase D combat readers that must normalise the attacker-relative CombatAttacker/CombatTarget to the acting player's frame.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_d_combat_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function cbCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function cbLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
    foreach (['CombatAttacker', 'CombatAttackerPlayer', 'CombatTarget'] as $var) DecisionQueueController::StoreVariable($var, '-');
}
function cbQueue($seat) { $q = &GetDecisionQueue($seat); return $q; }
function cbOptions($seat) {
    $options = [];
    foreach (cbQueue($seat) as $d) if (in_array($d->Type, ['MZCHOOSE', 'MZMAYCHOOSE'], true)) $options = array_merge($options, explode('&', $d->Param ?? ''));
    return $options;
}
$ally = 'em6eEh9q8y';
try {
    // ---- Flamebreak Chorus: a defending ally that is the combat target wakes up and gains its level bonus. ----
    foreach ([1, 2] as $attacker) {
        $defender = 3 - $attacker;
        foreach (['target' => true, 'other ally' => false] as $label => $isTarget) {
            cbLoad();
            global $playerID;
            $playerID = $defender;
            $chosen = AddField($defender, CardID: $ally, Status: 1, Owner: $defender, Controller: $defender);
            $other = AddField($defender, CardID: $ally, Status: 1, Owner: $defender, Controller: $defender);
            $targetObj = $isTarget ? $chosen : $other;
            DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($attacker));
            DecisionQueueController::StoreVariable('CombatAttacker', 'myField-0');
            DecisionQueueController::StoreVariable('CombatTarget', 'theirField-' . $targetObj->mzIndex);
            $customDQHandlers['yky280mtts:0:CardActivated-1']($defender, [], 'myField-' . $chosen->mzIndex);
            $woke = intval($chosen->Status) === 2;
            cbCheck($woke === $isTarget, "Flamebreak Chorus attacker seat $attacker, chosen ally is $label: " . ($isTarget ? 'wakes' : 'stays rested'));
            cbCheck(in_array('yky280mtts_POWER', $chosen->TurnEffects ?? [], true), "Flamebreak Chorus attacker seat $attacker ($label) still gives +2 POWER");
        }
    }

    // ---- Enervating Decay: only the attacking ALLY is a legal target, whoever casts it. ----
    foreach ([1, 2] as $attacker) {
        foreach (['defender' => 3 - $attacker, 'attacker' => $attacker] as $role => $caster) {
            cbLoad();
            global $playerID;
            $playerID = $caster;
            $atk = AddField($attacker, CardID: $ally, Owner: $attacker, Controller: $attacker);
            $bystanders = [];
            foreach ([1, 2] as $seat) $bystanders[$seat] = AddField($seat, CardID: $ally, Owner: $seat, Controller: $seat);
            DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($attacker));
            DecisionQueueController::StoreVariable('CombatAttacker', 'myField-' . $atk->mzIndex);
            DecisionQueueController::StoreVariable('CombatTarget', 'theirField-0');
            $expected = ($caster === $attacker ? 'myField-' : 'theirField-') . $atk->mzIndex;
            $playCardAbilities['jh9s424gjr:0']($caster);
            cbCheck(cbOptions($caster) === [$expected], "Enervating Decay attacker seat $attacker cast by $role offers exactly the attacking ally");
            cbCheck((isset($cardActivatedPrereqs['jh9s424gjr:0']) ? $cardActivatedPrereqs['jh9s424gjr:0']($caster, 'myHand-0') : true) === true, "Enervating Decay attacker seat $attacker cast by $role is activatable");
        }
        // No combat at all, or the attacker is a champion: nothing legal.
        foreach (['no combat' => false, 'champion attacker' => true] as $label => $champion) {
            cbLoad();
            global $playerID;
            $caster = 3 - $attacker;
            $playerID = $caster;
            AddField($attacker, CardID: $ally, Owner: $attacker, Controller: $attacker);
            AddField($caster, CardID: $ally, Owner: $caster, Controller: $caster);
            if ($champion) {
                foreach (GetField($attacker) as $o) if (PropertyContains(CardType($o->CardID), 'CHAMPION')) { $champIndex = $o->mzIndex; break; }
                DecisionQueueController::StoreVariable('CombatAttackerPlayer', strval($attacker));
                DecisionQueueController::StoreVariable('CombatAttacker', 'myField-' . $champIndex);
            }
            $playCardAbilities['jh9s424gjr:0']($caster);
            cbCheck(cbOptions($caster) === [], "Enervating Decay with $label offers no target");
            cbCheck((isset($cardActivatedPrereqs['jh9s424gjr:0']) ? $cardActivatedPrereqs['jh9s424gjr:0']($caster, 'myHand-0') : true) === false, "Enervating Decay with $label is not activatable");
        }
    }
    echo "OK $checks checks\n";
} catch (Throwable $e) {
    echo 'FAIL ' . $e->getMessage() . ' @' . $e->getLine() . "\n";
    exit(1);
} finally {
    @unlink($gameDir . '/Gamestate.txt');
    @rmdir($gameDir);
}
