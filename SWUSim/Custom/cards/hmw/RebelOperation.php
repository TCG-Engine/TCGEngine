<?php
// HMW_173 Rebel Operation — Event, cost 4, [Aggression][Heroism], Rebel/Plan.
// "This card costs 1 resource less to play for each friendly Rebel unit and leader. Draw 2 cards."
// The cost modifier lives in GameLogic ($playCostModifiers is initialised after cards/_loader.php).

$whenPlayedAbilities["HMW_173:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DoDrawCard(intval($player), 2);
};

// Friendly Rebel units (team, a deployed leader unit counts once as a unit) + friendly UNDEPLOYED Rebel leaders.
if (!function_exists('_SWUHmw173RebelCount')) {
    function _SWUHmw173RebelCount(int $player): int {
        $n = 0;
        foreach (SWUFriendlyUnitObjects($player) as $u) {
            if (empty($u->removed) && TraitContains($u, 'Rebel')) $n++;
        }
        foreach (array_merge([$player], SWUTeammatesOf($player)) as $s) {
            $l = SWUGetLeader(intval($s));
            if ($l === null) continue;
            $dep = (($l->Deployed ?? false) === true) || (($l->Deployed ?? '') === 'true');
            if (!$dep && HasTrait($l->CardID ?? '', 'Rebel')) $n++;
        }
        return $n;
    }
}
