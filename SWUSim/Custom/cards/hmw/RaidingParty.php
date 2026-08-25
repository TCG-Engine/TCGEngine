<?php
// HMW_230
// Cost 5 - Raiding Party - [Cunning] - Power 0 - HP 6 - Tusken
// Text: Raid 6 (This unit gets +6/+0 while attacking.)
//       When Played: If you control another Tusken unit or a Tatooine base, you may exhaust a ground unit.
//
// Raid 6 needs no code — the generator put HMW_230 in $Raid_Cards with value 6, and combat reads it.
// Worth knowing when reading this card: its printed power is 0, so Raid IS its whole offense and it
// deals nothing at all while defending.

// The gate: two independent limbs joined by OR, either of which opens the ability on its own.
function _SWUHmw230GateOpen(int $player, string $selfMzID): bool {
    // "a Tatooine base" — YOUR base. _SWUControlsBaseWithTrait reads this player's base only, which is
    // what keeps an opponent's Tatooine base from opening it.
    if (_SWUControlsBaseWithTrait($player, 'Tatooine')) return true;

    // "ANOTHER Tusken unit" — Raiding Party is itself a Tusken, so the source must be excluded by
    // UniqueID or the gate opens itself every time. _SWUCountFriendlyTraitUnits does exactly that, and
    // deliberately uses TraitContains + GetUnitsInPlay: a GRANTED Tusken trait counts, a per-instance
    // trait loss is honoured, and a DEPLOYED LEADER unit counts as a unit (HMW_018 The Warrior is a
    // Tusken leader, so deploying her opens this gate).
    global $playerID;
    $savedPID = $playerID; $playerID = $player;
    $self = GetZoneObject($selfMzID);
    $playerID = $savedPID;
    $selfUID = SWUObjGone($self) ? 0 : intval($self->UniqueID ?? 0);

    return _SWUCountFriendlyTraitUnits($player, 'Tusken', $selfUID) > 0;
}

// The effect's target pool: every READY unit in a ground arena, on ANY side.
//
// "A ground unit" carries no controller qualifier, so a FRIENDLY unit is a legal target too — this is
// the same collector shape as SHD_201 Principled Outlaw, whose clause is word-for-word identical.
//
// ⚠ ['myGroundArena','theirGroundArena'] is NOT a two-seat hardcode, which is easy to assume and wrong.
// ZoneSearch itself fans "their<Zone>" out across every live opponent at 3+ seats, returning
// seat-addressed p{n}GroundArena-{i} mzIDs (the Twin Suns Phase 3 change; at two seats it stays a plain
// "their…" search, byte-identical). Verified here by running the four-seat sections against BOTH this
// shape and a hand-rolled GetLiveSeatsArray()/SWUForeignMzID loop: identical pools, p2 and p3 both
// present. Going through ZoneSearch is strictly better than hand-rolling the seat loop, because a raw
// arena walk also skips AnyUnitFilter and ZoneSearch's leader-unit type mapping.
//
// READY-only is the house exhaust convention (SHD_201, SEC_069 Nimble Prowess): exhausting an already
// exhausted unit does nothing, so it is not a legal target and must not be offered.
function _SWUHmw230ReadyGroundUnits(int $player): array {
    global $playerID; $playerID = $player;
    $out = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $out[] = $mz;
        }
    }
    return $out;
}

$whenPlayedAbilities["HMW_230:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);

    if (!_SWUHmw230GateOpen(intval($player), $mzID)) return;

    // "a ground unit" carries no controller qualifier, so a FRIENDLY unit is a legal target too — and
    // Raiding Party itself is one whenever something let it enter play ready (HMW_234 Ritual Dragon).
    // Normally it enters exhausted and is filtered out by the ready check above, not by being the source.
    $targets = _SWUHmw230ReadyGroundUnits(intval($player));

    // Nothing ready to exhaust → the "you may" could only fizzle, so it is not offered at all.
    if (empty($targets)) return;

    SWUQueueMayChooseTarget(intval($player), $targets,
        "Exhaust_a_ground_unit?", "Exhaust_a_ground_unit", "EXHAUST_UNIT");
};
