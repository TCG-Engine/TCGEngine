<?php
// HMW_104
// Cost 1 - Garnac, Let the Hunt Begin! - [Command][Villainy] - Unit (Ground) 3/1 - Trait: Underworld - unique
// Text: While an opponent controls a Unique unit, this unit gains Hidden.
//       When Attack Ends: You may attack with another unit.
//
// ── Clause 1: conditional Hidden ────────────────────────────────────────────────────────────────────────
// A self-conditional keyword grant, read live in HasConditionalKeyword_Hidden (the HMW_176 Village
// Troublemaker slot), so it switches off the moment the condition does. Hidden itself (can't be attacked
// the phase it was played/deployed/created) is enforced generically by _SWUHiddenBlocksAttack.
//   • "an OPPONENT" is existential over every opponent of Garnac's CONTROLLER (OpponentsOf: all live
//     seats at Twin Suns, the enemy team at Team Suns — a teammate is never an opponent). Strictly
//     beneficial and never referenced again, so there is nothing to choose (the SEC_010 auto-resolve rule).
//   • "CONTROLS" is control, not ownership — the opponent's live arena, stolen units included.
//   • "Unique" is the card's unique flag, EXCEPT a TWI_116 Clone copy, which is not unique (the same rule
//     SWUEnforceUniqueness applies). A deployed leader unit is unique; a token never is.
//   • Garnac is himself unique but is never "an opponent's" unit, so he cannot satisfy his own condition.
//
// ── Clause 2: When Attack Ends: you may attack with another unit ───────────────────────────────────────
// SWUQueueAnotherAttack — ready friendly units in either arena, excluding Garnac by UniqueID, as a
// MAY-choose, then CHAINED_ATTACK. No survival rider, so it fires even when Garnac dies in the attack
// (CR 7.6.16.c; the TS26_04 Padmé ruling on the identical clause). On that path CollectAfterAttackTriggers
// dispatches with an EMPTY mzID, so there is no stale positional lookup: the UID resolves to 0, and a dead
// Garnac is not in the pool anyway.
if (!function_exists('_SWUHmw104OpponentControlsUnique')) {
    function _SWUHmw104OpponentControlsUnique($obj): bool {
        $ctrl = intval($obj->Controller ?? 0);
        if ($ctrl <= 0) return false;
        foreach (OpponentsOf($ctrl) as $opp) {
            foreach (GetUnitsInPlay(intval($opp)) as $u) {
                if (!empty($u->removed) || !empty($u->IsClone)) continue;
                if (CardUnique($u->CardID ?? '')) return true;
            }
        }
        return false;
    }
}

$onAttackEndAbilities["HMW_104:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = ($mzID !== '' && $mzID !== null) ? GetZoneObject($mzID) : null;
    $selfUID = SWUObjGone($self) ? 0 : intval($self->UniqueID ?? 0);
    SWUQueueAnotherAttack(intval($player), false, true, 0, $selfUID);
};
