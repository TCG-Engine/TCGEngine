<?php
// HMW_098 Resonate — Event, cost 1, [Vigilance], Innate.
// "If a friendly non-leader unit shares a Trait with a friendly leader, heal 4 damage from a unit or base."
// The condition is LAW_152 C-3PO's trait-sharing read, team-wide: a friendly leader is any friendly
// undeployed leader in a leader zone or any friendly leader UNIT in play; traits are read object-aware
// (TraitContains) on both sides. The heal is mandatory once the condition holds, any unit or base.

if (!function_exists('_SWUHmw098Condition')) {
    function _SWUHmw098Condition(int $player): bool {
        $seats = array_map('intval', array_merge([$player], SWUTeammatesOf($player)));
        $leaders = [];
        foreach ($seats as $s) {
            $l = SWUGetLeader($s);
            $dep = $l !== null && ((($l->Deployed ?? false) === true) || (($l->Deployed ?? '') === 'true'));
            if ($l !== null && !$dep) $leaders[] = $l;
        }
        $units = SWUFriendlyUnitObjects($player);
        foreach ($units as $u) { if (empty($u->removed) && IsLeaderUnit($u)) $leaders[] = $u; }
        if (empty($leaders)) return false;
        $grantables = ['Rebel', 'Underworld', 'Mandalorian', 'Jedi', 'Force', 'Clone'];
        foreach ($units as $u) {
            if (!empty($u->removed) || IsLeaderUnit($u)) continue;
            $cand = array_filter(array_map('trim', explode(',', (string)(CardTrait($u->CardID ?? '') ?? ''))));
            foreach ($leaders as $L) {
                $lt = array_filter(array_map('trim', explode(',', (string)(CardTrait($L->CardID ?? '') ?? ''))));
                foreach (array_unique(array_merge($cand, $lt, $grantables)) as $t) {
                    if (TraitContains($u, $t) && TraitContains($L, $t)) return true;
                }
            }
        }
        return false;
    }
}

$whenPlayedAbilities["HMW_098:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    if (!_SWUHmw098Condition(intval($player))) return;
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'HEAL_TARGET', 'amount' => 4, 'includeBases' => true,
        'prompt' => 'Heal_4_damage_from_a_unit_or_base',
    ]);
};
