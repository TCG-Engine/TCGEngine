<?php
// IC27_084
// Cost 9 - Negotiator - Prepare the Troops - [Command,Heroism] - Power 9 - HP 7 - Republic, Vehicle, Capital Ship
// Text: Sentinel / On Attack/When Defeated: Resource the top card of your deck. Then, you may return a friendly resource
// to its owner's hand.

// IC27_084 Negotiator — one ability on two windows (On Attack, When Defeated), resolved by the unit's controller.
// "Resource the top card" = SWUResourceTopOfDeck (exhausted; a no-op on an empty deck). "Then" is unconditional, so the
// return is offered whether or not a card was resourced — and the card just resourced is itself a legal pick.
// "a FRIENDLY resource" spans the TEAM (user ruling 2026-08-26) — SWUFriendlyResourceMzIDs, Credit tokens excluded (they
// sit in the resource zone but are not resources), the LAW_029 Citadel Research Center pool. "its owner's hand" —
// SWUReturnResourceToHand returns to the OWNER (the seat named by the mzID when Owner is unset).
if (!function_exists('_SWUIc27084Resolve')) {
    function _SWUIc27084Resolve(int $player): void {
        global $playerID; $playerID = $player;
        SWUResourceTopOfDeck($player);
        $playerID = $player;
        $pool = SWUFriendlyResourceMzIDs($player, fn($o) => !SWUIsCreditToken($o->CardID ?? ''));
        if (empty($pool)) return;
        SWUQueueMayChooseTarget($player, $pool, "Return_a_friendly_resource_to_its_owner's_hand?",
            "Choose_a_friendly_resource_to_return", "IC27_084#0");
    }
}

$onAttackAbilities["IC27_084:0"] = function($player, $mzID) { _SWUIc27084Resolve(intval($player)); };
$whenDefeatedAbilities["IC27_084:0"] = function($player, $mzID) { _SWUIc27084Resolve(intval($player)); };

$customDQHandlers["IC27_084#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUReturnResourceToHand(intval($player), (string)$lastDecision);
};
