<?php
// HMW_014
// Cost 4 - Wicket - Few Greater Battles to Fight - [Aggression,Heroism] - Power 2 - HP 5
// Text: When a friendly unit attacks a unit that costs more than it: You may exhaust this leader. If you do, draw a card.
// Epic Action: If you control 4 or more resources, deploy this leader.
// DeployText: On Attack: If you control a unit that costs 3 or less, draw a card.

// ── FRONT (undeployed) side ──────────────────────────────────────────────────────────────────────────
// The reactive observer itself is collected in CombatLogic.php's CollectCombatStep1Triggers (that is the
// attack-declaration window) and routed here by the 'HMW_014' DispatchTrigger case in GameLogic.php.
// This continuation is only the "you may exhaust this leader. If you do, draw a card." half.
$customDQHandlers["HMW_014#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    // Pay the cost FIRST and re-check it is still payable — "If you do" gates the draw on the exhaust
    // actually happening, and the board can have moved between the offer and the answer.
    $paid = false;
    foreach (GetLeader(intval($player)) as $l) {
        if (!empty($l->removed)) continue;
        if (($l->CardID ?? '') === 'HMW_014' && empty($l->Deployed) && !empty($l->Ready)) {
            $l->Ready = false; $paid = true; break;
        }
    }
    if (!$paid) return;
    DoDrawCard(intval($player), 1);
};

// ── DEPLOYED (leader unit) side ──────────────────────────────────────────────────────────────────────
// "On Attack: If you control a unit that costs 3 or less, draw a card." No cost, no choice, no exhaust —
// it simply happens. COST is the PRINTED cost, so token units (cost 0) qualify and Wicket himself
// (printed 4) does not. Combat owns the after-action.
$onAttackAbilities["HMW_014:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (SWUObjGone($u)) continue;
        if (intval(CardCost($u->CardID ?? '')) <= 3) { DoDrawCard(intval($player), 1); return; }
    }
};
