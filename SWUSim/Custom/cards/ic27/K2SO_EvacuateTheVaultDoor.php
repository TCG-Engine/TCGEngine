<?php
// IC27_124
// Cost 9 - K-2SO - Evacuate The Vault Door - [Aggression,Heroism] - Power 10 - HP 5 - Rebel, Droid
// Text: When Defeated: Deal 5 damage to each enemy unit and each enemy base.

// IC27_124 K-2SO — ONE simultaneous event over every enemy unit and every enemy base, judged from the unit's controller
// (who resolves the When Defeated). The units go through SWUDealSplitDamage (all damage first, then one defeat sweep —
// the ASH_112 Luke Skywalker funnel) and the bases through OpponentsOf(), inside a SWUSimultaneousBaseDamage bracket so
// two enemy bases reaching 0 together is judged once (CR 6.3). 'their*' is the opponent fan-out at every seat count and
// excludes a Team Suns teammate, as OpponentsOf() does.
// HMW_185 Ty Yorrick — the blast is ONE friendly ability, so it gets ONE "+1?" question (owner ruling 2026-10-07, as
// JTL_131 Turbolaser Salvo): accepted, every unit and every base takes 6. The base funnel's own per-base question is
// suppressed ($skipTyOffer), or a base would be asked a second time.
if (!function_exists('_SWUIc27124Blast')) {
    function _SWUIc27124Blast(int $player, int $amount): void {
        global $playerID; $playerID = $player;
        $assign = [];
        foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
            foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                if (!SWUObjGone(GetZoneObject($mz))) $assign[] = $mz . ':' . $amount;
            }
        }
        SWUSimultaneousBaseDamage(function () use ($player, $amount, $assign) {
            if (!empty($assign)) SWUDealSplitDamage($player, implode(',', $assign));
            foreach (OpponentsOf($player) as $o) SWUDealDamageToBase($amount, $o, $player, false, true);
        });
    }
}

$whenDefeatedAbilities["IC27_124:0"] = function($player, $mzID) {
    $ty = _SWUHmw185Decider(intval($player));
    if ($ty > 0) { _SWUHmw185Defer($ty, "IC27_124#0|" . intval($player)); return; }
    _SWUIc27124Blast(intval($player), 5);
};

// Resume after Ty's question. parts: the K-2SO controller.
$customDQHandlers["IC27_124#0"] = function($player, $parts, $lastDecision) {
    _SWUIc27124Blast(intval($parts[0] ?? $player), 5 + (_SWUHmw185Accepted($lastDecision) ? 1 : 0));
};
