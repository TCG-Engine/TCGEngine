<?php
// Retained loaded-state checks for Phase E Norm clauses that gameplay fixtures cannot reach cheaply:
// Dodge Roll's non-combat window, Crystallized Destiny's prevention and its 7+ tax.
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_phase_e_norm_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function nmCheck($ok, $label) {
    global $checks;
    if (!$ok) { echo "MISS $label\n"; if (getenv('GA_CHECK_CONTINUE') !== '1') throw new RuntimeException($label); return; }
    ++$checks;
    echo "PASS $label\n";
}
function nmLoad() {
    global $gameDir, $playerID;
    $playerID = 1;
    copy('Tests/Integration/GrandArchiveSim/slipstream-target-cost-ordinary/initial_gamestate.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $seat) { $q = &GetDecisionQueue($seat); $q = []; }
    foreach (['CombatAttacker', 'CombatAttackerPlayer', 'CombatTarget'] as $var) DecisionQueueController::ClearVariable($var);
}
// Seat 2's attacker is attacking seat 1's target, stored in the attacker's frame the way the combat flow stores it.
function nmCombat($attacker, $target) {
    DecisionQueueController::StoreVariable('CombatAttackerPlayer', '2');
    StoreCombatAttackerState('myField-' . $attacker->mzIndex, 2);
    StoreCombatTargetState('theirField-' . $target->mzIndex, 2);
}
$ally = 'em6eEh9q8y';
try {
    // ---- Dodge Roll: prevents the next 3 NON-combat damage; combat damage is not prevented; a spell cast during combat is not combat damage. ----
    foreach (['no combat' => false, 'spell during combat' => true] as $label => $inCombat) {
        nmLoad();
        global $playerID;
        $playerID = 1;
        $target = AddField(1, CardID: $ally, Owner: 1, Controller: 1);
        $attacker = AddField(2, CardID: $ally, Owner: 2, Controller: 2);
        $bystander = AddField(2, CardID: $ally, Owner: 2, Controller: 2);
        $target->TurnEffects = ['PREVENT_NONCOMBAT_3'];
        if ($inCombat) {
            nmCombat($attacker, $target);
        }
        OnDealDamage(2, 'theirField-' . $bystander->mzIndex, 'myField-' . $target->mzIndex, 2);
        nmCheck(intval($target->Damage) === 0, "Dodge Roll ($label): 2 spell damage is fully prevented");
        OnDealDamage(2, 'theirField-' . $bystander->mzIndex, 'myField-' . $target->mzIndex, 3);
        nmCheck(intval($target->Damage) === 2, "Dodge Roll ($label): 3 more spell damage, only the 1 remaining prevented, 2 gets through");
        nmCheck(!preg_grep('/^PREVENT_NONCOMBAT_/', $target->TurnEffects ?? []), "Dodge Roll ($label): the effect is used up");
    }
    nmLoad();
    $playerID = 1;
    $target = AddField(1, CardID: $ally, Owner: 1, Controller: 1);
    $attacker = AddField(2, CardID: $ally, Owner: 2, Controller: 2);
    $target->TurnEffects = ['PREVENT_NONCOMBAT_3'];
    nmCombat($attacker, $target);
    OnDealDamage(2, 'theirField-' . $attacker->mzIndex, 'myField-' . $target->mzIndex, 2);
    nmCheck(intval($target->Damage) === 2, 'Dodge Roll: combat damage from the attacker is not prevented');
    // ---- Crystallized Destiny: the next damage to the champion is prevented; 7 or more prevented taxes the OPPONENT's cards this turn. ----
    foreach ([1, 2] as $owner) {
        $other = 3 - $owner;
        foreach ([3, 6, 7, 12] as $dmg) {
            nmLoad();
            $playerID = $owner;
            $champMZ = FindChampionMZ($owner);
            $champ = GetZoneObject($champMZ);
            $champ->TurnEffects = ['CRYSTALLIZED_DESTINY'];
            $ownerCard = AddHand($owner, CardID: $ally);
            $otherCard = AddHand($other, CardID: $ally);
            $base = intval(CardCost_reserve($ally));
            $beforeOther = CalculateActivationReserveCost($other, $otherCard);
            OnDealDamage($other, ($owner === 1 ? 'theirField-0' : 'myField-0'), $champMZ, $dmg);
            nmCheck(intval($champ->Damage) === 0, "Crystallized Destiny seat $owner: $dmg damage is prevented");
            nmCheck(!in_array('CRYSTALLIZED_DESTINY', $champ->TurnEffects ?? [], true), "Crystallized Destiny seat $owner: the effect is spent after one damage event ($dmg)");
            $taxed = CalculateActivationReserveCost($other, $otherCard) - $beforeOther;
            nmCheck($taxed === ($dmg >= 7 ? 2 : 0), "Crystallized Destiny seat $owner: the opponent's card costs " . ($dmg >= 7 ? '2 more' : 'the same') . " after $dmg prevented (was +$taxed)");
            $ownTaxed = CalculateActivationReserveCost($owner, $ownerCard) - $base;
            nmCheck($ownTaxed === 0, "Crystallized Destiny seat $owner: the controller's own cards are not taxed after $dmg prevented (was +$ownTaxed)");
        }
        // The second damage event is not prevented.
        nmLoad();
        $playerID = $owner;
        $champMZ = FindChampionMZ($owner);
        $champ = GetZoneObject($champMZ);
        $champ->TurnEffects = ['CRYSTALLIZED_DESTINY'];
        OnDealDamage($other, ($owner === 1 ? 'theirField-0' : 'myField-0'), $champMZ, 2);
        OnDealDamage($other, ($owner === 1 ? 'theirField-0' : 'myField-0'), $champMZ, 5);
        nmCheck(intval($champ->Damage) === 5, "Crystallized Destiny seat $owner: only the next damage is prevented");
    }
    echo "OK $checks checks\n";
} catch (Throwable $e) {
    echo 'FAIL ' . $e->getMessage() . ' @' . $e->getLine() . "\n";
    exit(1);
} finally {
    @unlink($gameDir . '/Gamestate.txt');
    @rmdir($gameDir);
}
