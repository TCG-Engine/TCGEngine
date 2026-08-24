<?php
// The swappable "chooser" half of the GA bot seam:
//   GABotLegalActions() -> chooser($actions, $legal) -> one action -> EngineExecuteLoadedAction()
// This file provides the default rule-based chooser. A future model-backed chooser (mirroring
// AzukiRlBotChooseAction's checkpoint-lookup pattern) can be added and registered under a new
// profile name without touching enumeration (BotLegalActions.php) or execution (BotController.php).

if (!isset($GLOBALS['GABotChoosers'])) {
    $GLOBALS['GABotChoosers'] = [];
}

function GABotRegisterChooser($profileName, callable $fn) {
    $GLOBALS['GABotChoosers'][strval($profileName)] = $fn;
}

// Per-game chooser selection, mirroring Azuki's per-seat AzukiRlBotProfile DQ variable. Defaults
// to 'heuristic' — the only profile registered today.
function GABotActiveChooserProfile() {
    $value = class_exists('DecisionQueueController') ? DecisionQueueController::GetVariable('GABotProfile') : null;
    return ($value !== null && $value !== '') ? strval($value) : 'heuristic';
}

function GABotChooseAction(array $actions, array $legal) {
    if (empty($actions)) return null;
    $profile = GABotActiveChooserProfile();
    $chooser = $GLOBALS['GABotChoosers'][$profile] ?? $GLOBALS['GABotChoosers']['heuristic'] ?? null;
    if ($chooser === null) return $actions[0];
    return call_user_func($chooser, $actions, $legal);
}

// Rough total attack power available to $player this turn — sum of CurrentPower across field
// slots the enumerator offered as attack candidates (mode 10002, "myField-" cardID).
function GABotEstimateAttackPower($player, array $actions) {
    $total = 0;
    foreach ($actions as $a) {
        if (($a['mode'] ?? 0) != 10002) continue;
        $cardID = strval($a['cardID'] ?? '');
        if (strpos($cardID, 'myField-') !== 0) continue;
        $idx = intval(substr($cardID, strlen('myField-')));
        $field = GetField($player);
        $obj = $field[$idx] ?? null;
        if ($obj === null) continue;
        $power = function_exists('ObjectCurrentPower') ? ObjectCurrentPower($obj) : intval($obj->CurrentPower ?? 0);
        if ($power > 0) $total += $power;
    }
    return $total;
}

function GABotOpponentChampionHP($player) {
    $opponent = ($player == 1) ? 2 : 1;
    if (!function_exists('FindChampionMZ') || !function_exists('GetZoneObject')) return null;
    // FindChampionMZ/GetZoneObject resolve "myField"/"theirField" relative to the ambient
    // $playerID perspective global, not the $player argument — set it for this lookup regardless
    // of what the caller left it as (see the identical issue found in GACaptureCurrentGameDetail).
    global $playerID;
    $savedPlayerID = $playerID;
    $playerID = $player;
    $mz = FindChampionMZ($opponent);
    $obj = $mz !== null ? GetZoneObject($mz) : null;
    $playerID = $savedPlayerID;
    if ($obj === null) return null;
    $hp = function_exists('ObjectCurrentHP') ? ObjectCurrentHP($obj) : intval($obj->CurrentHP ?? 0);
    return max(0, intval($hp) - intval($obj->Damage ?? 0));
}

function GABotScoreAttackAction($player, array $action) {
    $cardID = strval($action['cardID'] ?? '');
    if (!preg_match('/^myField-(\d+)/', $cardID, $m)) return PHP_INT_MIN;
    $attacker = GetField($player)[intval($m[1])] ?? null;
    if ($attacker === null || !empty($attacker->removed)) return PHP_INT_MIN;

    $power = max(0, intval(function_exists('ObjectCurrentPower') ? ObjectCurrentPower($attacker) : ($attacker->CurrentPower ?? 0)));
    $hp = max(0, intval(function_exists('ObjectCurrentHP') ? ObjectCurrentHP($attacker) : ($attacker->CurrentHP ?? 0)) - intval($attacker->Damage ?? 0));
    $opponent = ($player === 1) ? 2 : 1;
    $oppChampPower = 0;
    $oppChampHP = GABotOpponentChampionHP($player);
    foreach (GetField($opponent) as $obj) {
        if ($obj === null || !empty($obj->removed) || !PropertyContains(EffectiveCardType($obj), 'CHAMPION')) continue;
        $oppChampPower = max($oppChampPower, intval(ObjectCurrentPower($obj)));
    }

    // Face pressure is valuable, with a large bonus for a plausible lethal. Penalize attacks
    // that likely trade away the attacker into the opposing champion, while still allowing
    // lethal attacks and high-power pressure to override that conservatism.
    $score = $power * 10;
    if ($oppChampHP !== null) {
        if ($power >= $oppChampHP && $power > 0) $score += 10000;
        else $score += min($power, $oppChampHP) * 4;
    }
    if ($oppChampPower > 0 && $hp <= $oppChampPower) $score -= $power * 7;
    if (PropertyContains(EffectiveCardType($attacker), 'CHAMPION')) $score += 3;
    return $score;
}

function GABotChooseActionHeuristic(array $actions, array $legal) {
    $kind = strval($legal['kind'] ?? '');
    $player = intval($legal['playerID'] ?? 0);

    if ($kind === 'decision') {
        // BotLegalActions.php already picked the single safe/conservative response for every
        // decision type it recognizes (pay reserve with first hand card, decline YES/NO, decline
        // optional choices, pick the first candidate for a required target choice) — nothing left
        // to choose between here.
        return $actions[0];
    }

    // Free play. Prefer materializing (developing the board) over attacking on principle — a
    // bigger board this turn tends to matter more than a small early attack — except when an
    // attack looks lethal right now, which always takes priority.
    $materializeActions = array_values(array_filter($actions, fn($a) => ($a['mode'] ?? 0) == 10002 && strpos(strval($a['cardID'] ?? ''), 'myHand-') === 0));
    $attackActions = array_values(array_filter($actions, fn($a) => ($a['mode'] ?? 0) == 10002 && strpos(strval($a['cardID'] ?? ''), 'myField-') === 0));
    $endTurnAction = null;
    foreach ($actions as $a) { if (($a['mode'] ?? 0) == 10001) { $endTurnAction = $a; break; } }

    if (!empty($attackActions)) {
        $oppHP = GABotOpponentChampionHP($player);
        $potentialDamage = GABotEstimateAttackPower($player, $attackActions);
        usort($attackActions, fn($a, $b) => GABotScoreAttackAction($player, $b) <=> GABotScoreAttackAction($player, $a));
        if ($oppHP !== null && $potentialDamage >= $oppHP && $potentialDamage > 0) {
            return $attackActions[0]; // go for lethal with the best individual attacker
        }
        // Favor a strong, survivable pressure attack over another marginal development play.
        if (GABotScoreAttackAction($player, $attackActions[0]) >= 55) return $attackActions[0];
    }

    if (!empty($materializeActions)) {
        // Highest-cost-first is a crude proxy for "biggest impact" — good enough for a first pass.
        usort($materializeActions, function ($a, $b) use ($player) {
            $costA = GABotHandCardMemoryCost($a['cardID'] ?? '', $player);
            $costB = GABotHandCardMemoryCost($b['cardID'] ?? '', $player);
            return $costB <=> $costA;
        });
        return $materializeActions[0];
    }

    if (!empty($attackActions)) {
        return $attackActions[0];
    }

    return $endTurnAction ?? $actions[0];
}

function GABotHandCardMemoryCost($cardID, $player) {
    // $cardID here is an mzID string like "myHand-2!FSM!"; resolve back to the underlying card.
    if (!preg_match('/^myHand-(\d+)/', strval($cardID), $m)) return 0;
    $hand = GetHand($player);
    $obj = $hand[intval($m[1])] ?? null;
    if ($obj === null || !isset($obj->CardID)) return 0;
    return function_exists('CardMemoryCost') ? intval(CardMemoryCost($obj)) : 0;
}

GABotRegisterChooser('heuristic', 'GABotChooseActionHeuristic');
