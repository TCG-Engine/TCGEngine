<?php
// IC27_076
// Cost 4 - Clone Commander Cody - Loyal to the Republic - [Command,Heroism] - Power 4 - HP 4 - Republic, Clone, Trooper
// Text: When another friendly Republic unit enters play: Give an Experience token to it.

// IC27_076 Clone Commander Cody — an "enters play" observer, so it hangs off EVERY way a unit enters play, not the play
// path alone: CollectEntryTriggers (played from anywhere, deployed leaders, Smuggle, freed captives), _SWUCreateOneToken
// (created token units) and DoRescueUnit (CR 8.34.3: a rescued unit is considered to enter play). Applied inline at
// entry, like ASH_041 Outcast / HMW_225 Boba Fett, so the entering unit already carries the token into its own entry
// triggers (Ambush, When Played).
// "friendly" spans the TEAM (user ruling 2026-08-26): every ability-active Cody controlled by a seat on the entering
// unit's team gives one (Cody is unique, but a teammate can hold another copy). "another" excludes Cody itself by UID.
if (!function_exists('_SWUIc27076OnUnitEntered')) {
    function _SWUIc27076OnUnitEntered($entered): void {
        if ($entered === null || !empty($entered->removed)) return;
        if (!HasTrait((string)($entered->CardID ?? ''), 'Republic')) return;
        $uid  = intval($entered->UniqueID ?? 0);
        $ctrl = intval($entered->Controller ?? 0);
        if ($uid <= 0 || $ctrl <= 0) return;
        global $playerID;
        $saved = $playerID;
        foreach (GetLiveSeatsArray() as $seat) {
            if (SWUTeamOf(intval($seat)) !== SWUTeamOf($ctrl)) continue;
            foreach (GetUnitsInPlay(intval($seat)) as $u) {
                if (!empty($u->removed) || ($u->CardID ?? '') !== 'IC27_076' || LostAbilities($u)) continue;
                if (intval($u->UniqueID ?? 0) === $uid) continue;   // "another"
                $playerID = intval($seat);                          // the giver's frame
                $mz = SWUFindMzByUID($uid);
                if ($mz !== null && $mz !== '') DoGiveExperienceToken(intval($seat), $mz);
            }
        }
        $playerID = $saved;
    }
}
