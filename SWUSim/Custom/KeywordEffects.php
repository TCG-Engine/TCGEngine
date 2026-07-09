<?php
// KeywordEffects.php
// Conditional and passively-granted keyword logic for SWUSim.
//
// Each HasConditionalKeyword_X($obj) / GetConditionalKeyword_X_Value($obj) is
// called as the 4th check inside the generated HasKeyword_X / GetKeyword_X_Value
// functions (GeneratedKeywordCode.php).  Add new card cases here as sets are
// implemented — never edit the generated file directly.

// ═════════════════════════════════════════════════════════════════════════════
// KEYWORD SUPPRESSION ("this unit loses <keyword> for this phase")
// A suppressor card tags the target with a TurnEffect equal to its own CardID. That
// bare CardID doubles as the Active Effects UI source (no suffix to parse). The
// generated HasKeyword_X functions call SWUKeywordSuppressed first, so suppression
// overrides innate/granted/conditional keywords. Naturally tiny — "X loses <kw>" is rare.
// ═════════════════════════════════════════════════════════════════════════════

$keywordSuppressors = [
    'SOR_140' => ['SENTINEL'],   // SpecForce Soldier — a unit loses Sentinel for this phase
    'JTL_077' => ['SABOTEUR'],   // In the Heat of Battle — each unit loses Saboteur for this phase
    'LOF_209' => ['HIDDEN'],     // Tusken Tracker — each enemy unit loses Hidden for this phase
    'SEC_185' => ['*'],          // TIE/ln Fighter — a ground unit loses ALL keywords (can't gain) this phase
];

function SWUKeywordSuppressed($obj, string $keyword): bool {
    // SEC_054 Exiled from the Force — "loses all abilities EXCEPT for Grit." Grit survives the ability
    // loss (and is re-granted via HasConditionalKeyword_Grit), so never suppress GRIT for a SEC_054 host.
    if ($keyword === 'GRIT' && is_object($obj) && _SWUUnitHasUpgrade($obj, 'SEC_054')) return false;
    // A unit that has lost all abilities has NO keyword — innate, granted, or gained.
    if (LostAbilities($obj)) return true;
    // ASH_040 Poe Dameron — "All units lose Sentinel." Field-presence: while any Poe is in play (either
    // player), every unit's Sentinel is suppressed.
    if ($keyword === 'SENTINEL' && is_object($obj)
        && (_SWUCountUnitsWithCardID(1, 'ASH_040') > 0 || _SWUCountUnitsWithCardID(2, 'ASH_040') > 0)) return true;
    // ASH_030 Marrok — "While this unit is upgraded, he loses Sentinel (and gains Saboteur)."
    if ($keyword === 'SENTINEL' && is_object($obj) && ($obj->CardID ?? '') === 'ASH_030' && _SWUIsUpgraded($obj)) return true;
    // ASH_068 Domesticated Loth-Cat — "Enemy units lose Ambush and Support." Field-presence suppression:
    // a unit's AMBUSH/SUPPORT is suppressed while an OPPONENT controls a Loth-Cat.
    if (($keyword === 'AMBUSH' || $keyword === 'SUPPORT') && is_object($obj)) {
        $loth_opp = GetOpponent(intval($obj->Controller ?? 0));
        if ($loth_opp > 0 && _SWUCountActiveUnitsWithCardID($loth_opp, 'ASH_068') > 0) return true;
    }
    global $keywordSuppressors;
    if (empty($obj->TurnEffects)) return false;
    foreach ($obj->TurnEffects as $te) {
        if (!isset($keywordSuppressors[$te])) continue;
        if (in_array('*', $keywordSuppressors[$te], true)
            || in_array($keyword, $keywordSuppressors[$te], true)) return true;   // '*' = all keywords
    }
    return false;
}

// CR: a unit that "loses all abilities" has none and CAN'T gain abilities — this gates innate,
// granted, AND would-be-gained abilities at every ability surface (keywords via SWUKeywordSuppressed,
// triggered abilities at their fire points, activated abilities, and constant passives it provides).
// Sources (extend this list as more "lose abilities" cards land):
//   • SOR_138 Force Lightning — TurnEffect "SOR_138" on the chosen unit (this phase).
//   • SHD_072 (upgrade) — "Attached unit loses its current abilities and can't gain abilities."
function LostAbilities($obj): bool {
    if ($obj === null) return false;
    if (!empty($obj->TurnEffects) && in_array('SOR_138', $obj->TurnEffects, true)) return true;
    if (!empty($obj->TurnEffects) && in_array('JTL_244', $obj->TurnEffects, true)) return true; // There Is No Escape
    if (!empty($obj->TurnEffects) && in_array('JTL_018', $obj->TurnEffects, true)) return true; // Kazuda Xiono
    if (!empty($obj->TurnEffects) && in_array('SEC_038', $obj->TurnEffects, true)) return true; // Condemn — loses all other abilities while attacking
    if (!empty($obj->TurnEffects) && in_array('SEC_157_DEF', $obj->TurnEffects, true)) return true; // One Way Out — defender loses all abilities for this attack
    if (!empty($obj->TurnEffects) && in_array('LAW_132', $obj->TurnEffects, true)) return true; // The Tree Remembers — loses all abilities this phase
    foreach (GetUpgradesOnUnit($obj) as $u) {
        if (($u->CardID ?? '') === 'SHD_072') return true;
        // SEC_054 Exiled from the Force — "loses all abilities except for Grit." Full ability loss here;
        // the Grit exception is handled in SWUKeywordSuppressed (GRIT survives + is re-granted).
        if (($u->CardID ?? '') === 'SEC_054') return true;
    }
    // SEC_046 Galen Erso — a non-leader card owned by Galen's opponent, whose name Galen named, loses
    // all abilities while Galen is in play. (An in-play arena object always has Owner set.)
    if (_SWUGalenSuppressesCard(intval($obj->Owner ?? $obj->Controller ?? 0), $obj->CardID ?? '')) return true;
    return false;
}

// SEC_046 Galen Erso — true if $cardID, owned by $owner, is a NON-LEADER card whose title an OPPOSING
// in-play Galen named (so it loses all abilities AND can't gain abilities). Works for cards NOT in play
// (by CardID + owner) — used at every ability surface that keys on a card title rather than an object.
function _SWUGalenSuppressesCard(int $owner, string $cardID): bool {
    if ($owner <= 0 || $cardID === '') return false;
    if (CardType($cardID) === 'Leader') return false;             // "non-leader card" — excludes leaders
    $title = str_replace(' ', '_', (string)(CardTitle($cardID) ?? ''));
    return $title !== '' && _SWUGalenNames($owner, $title);
}

// True if an OPPONENT of $targetOwner controls a Galen (still in play) that named $titleEnc (the encoded
// card title). Stale flags (Galen left play) are skipped lazily.
function _SWUGalenNames(int $targetOwner, string $titleEnc): bool {
    $opp = OtherPlayer($targetOwner);                 // 2-player: the one opponent (= Galen's controller)
    foreach (GetGlobalEffects($opp) as $e) {
        $flag = (string)($e->CardID ?? '');
        if (strpos($flag, 'SWU_GALEN|') !== 0) continue;
        $parts = explode('|', $flag);
        if (!_SWUUnitInPlayWithUID($opp, intval($parts[1] ?? 0))) continue;   // Galen gone → ignore
        if (($parts[2] ?? '') === $titleEnc) return true;
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// ZONE HELPERS
// These cover patterns used throughout keyword checks.  They rely on the SWUSim
// zone accessors (ZoneAccessors.php): GetGroundArena($p), GetSpaceArena($p),
// GetResources($p), GetZoneObject($mzID), and the card-dictionary helpers
// CardAspect(), CardType(), TraitContains().
// ═════════════════════════════════════════════════════════════════════════════

function OtherPlayer(int $player): int {
    return $player === 1 ? 2 : 1;
}

// Returns all non-removed unit objects in one arena for a player.
// $arena is 'Ground' or 'Space'.
function GetUnitsInArena(int $player, string $arena): array {
    $zone = $arena === 'Ground' ? GetGroundArena($player) : GetSpaceArena($player);
    $units = [];
    foreach ($zone as $obj) {
        if (!isset($obj->removed) || !$obj->removed) $units[] = $obj;
    }
    return $units;
}

// Returns all non-removed units in both arenas for a player.
function GetUnitsInPlay(int $player): array {
    return array_merge(GetUnitsInArena($player, 'Ground'), GetUnitsInArena($player, 'Space'));
}

// Returns all non-removed, non-captive upgrade objects attached to a unit.
// Subcards may be PHP arrays (deserialized path) or stdClass objects (runtime-added path).
function GetUpgradesOnUnit($obj): array {
    if (!is_array($obj->Subcards ?? null)) return [];
    $result = [];
    foreach ($obj->Subcards as $sub) {
        if (is_array($sub)) {
            if (!empty($sub['IsCaptive']) || !empty($sub['removed'])) continue;
            $result[] = (object)$sub;
        } else {
            if (($sub->IsCaptive ?? false) || ($sub->removed ?? false)) continue;
            $result[] = $sub;
        }
    }
    return $result;
}

function IsLeaderUnit($obj): bool {
    $type = CardType($obj->CardID ?? '');
    // "Leader Unit" covers future API entries that distinguish the two sides.
    // "Leader" cards in an arena zone are deployed leaders acting as units.
    if (strpos($type, 'Leader Unit') !== false) return true;
    $loc = $obj->Location ?? '';
    if (strpos($type, 'Leader') !== false
        && ($loc === 'GroundArena' || $loc === 'SpaceArena')) return true;
    // Derived: a normal unit hosting a leader Pilot subcard from the conversion set
    // (CardLeaderCanDeployAsUpgrade) becomes a Leader Unit per the deployBox text
    // "Attached unit is a leader unit." (e.g. JTL_001 Asajj Ventress).
    // JTL_013 Poe Dameron does NOT use CardLeaderCanDeployAsUpgrade (its attach action
    // uses different text), so it is correctly excluded from this set.
    if (!empty($obj->Subcards) && is_array($obj->Subcards)) {
        foreach ($obj->Subcards as $sub) {
            $isRemoved = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
            if ($isRemoved) continue;
            $isPilot   = is_array($sub) ? ($sub['IsPilot'] ?? false) : ($sub->IsPilot ?? false);
            if (!$isPilot) continue;
            $subCardID = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            if (CardLeaderCanDeployAsUpgrade($subCardID)) return true;
        }
    }
    // ASH_135 The Darksaber — "Attached unit is a leader unit." Same derived-leader status as a leader
    // Pilot subcard above; the host stays a normal Unit for defeat/bounce/return (only its status reads
    // as a leader unit), mirroring the JTL_001 pattern.
    if (_SWUUnitHasUpgrade($obj, 'ASH_135')) return true;
    return false;
}

// Returns true if any of $player's units in play has the given aspect,
// optionally excluding the unit with UniqueID $excludeUID (self-exclusion).
function PlayerHasUnitWithAspectInPlay(int $player, string $aspect, $excludeUID = null): bool {
    foreach (GetUnitsInPlay($player) as $u) {
        if ($excludeUID !== null && $u->UniqueID == $excludeUID) continue;
        $raw = CardAspect($u->CardID);
        if (!$raw) continue;
        if (in_array($aspect, array_map('trim', explode(',', $raw)))) return true;
    }
    return false;
}

// Returns true if any of $player's units in play has the given trait,
// optionally excluding the unit with UniqueID $excludeUID.
function PlayerHasUnitWithTraitInPlay(int $player, string $trait, $excludeUID = null): bool {
    foreach (GetUnitsInPlay($player) as $u) {
        if ($excludeUID !== null && $u->UniqueID == $excludeUID) continue;
        if (TraitContains($u, $trait)) return true;
    }
    return false;
}

// Coordinate (CR 15.a): a Coordinate ability is active "while you control 3 or
// more units" — counting ALL units the player controls, including the unit that
// carries the Coordinate ability itself (TWI_106: "including this one"). This is
// purely a unit-count condition; whether a unit *has* Coordinate is a separate
// question answered by HasKeyword_Coordinate.
function IsCoordinateActive(int $player): bool {
    return count(GetUnitsInPlay($player)) >= 3;
}

// Per-unit Coordinate indicator for the card counters (schema Virtual:
// CoordinateActive / CoordinateInactive). A unit that HAS Coordinate shows the
// "active" icon while its controller meets the 3-unit condition, and the "inactive"
// icon while it has the keyword but the condition isn't met (CR 15.c). A unit
// without Coordinate shows neither. Return 1/0 for the Image counters (ShowZero=false
// hides 0); the two are mutually exclusive so only one icon ever renders.
function ObjectCoordinateActive($obj): int {
    if (!HasKeyword_Coordinate($obj)) return 0;
    $player = intval($obj->Controller ?? $obj->Owner ?? $obj->PlayerID ?? 0);
    return ($player > 0 && IsCoordinateActive($player)) ? 1 : 0;
}
function ObjectCoordinateInactive($obj): int {
    if (!HasKeyword_Coordinate($obj)) return 0;
    $player = intval($obj->Controller ?? $obj->Owner ?? $obj->PlayerID ?? 0);
    return ($player > 0 && IsCoordinateActive($player)) ? 0 : 1;
}

// Per-unit Saboteur indicator for the card counter (schema Virtual: HasSaboteur). 1 when the
// unit has the Saboteur keyword (printed or granted; CR — ignores enemy Sentinels and defeats
// the defender's shields). Static keyword, no board-state condition. Returns 1/0 for the Image
// counter (ShowZero=false hides 0).
function ObjectHasSaboteur($obj): int {
    return ($obj !== null && isset($obj->CardID) && HasKeyword_Saboteur($obj)) ? 1 : 0;
}

// Per-unit Overwhelm indicator for the card counter (schema Virtual: HasOverwhelm). 1 when the
// unit has the Overwhelm keyword (printed or granted; CR — excess attack damage spills to the
// defending player's base). Static keyword, no board-state condition. Returns 1/0 for the Image
// counter (ShowZero=false hides 0).
function ObjectHasOverwhelm($obj): int {
    return ($obj !== null && isset($obj->CardID) && HasKeyword_Overwhelm($obj)) ? 1 : 0;
}

// Per-unit Grit indicator for the card counter (schema Virtual: HasGrit). 1 when the unit has
// the Grit keyword (printed or granted; CR — gets +1/+1 for each damage on it). Shown whenever
// the unit has Grit, regardless of its current damage. Returns 1/0 (ShowZero=false hides 0).
function ObjectHasGrit($obj): int {
    return ($obj !== null && isset($obj->CardID) && HasKeyword_Grit($obj)) ? 1 : 0;
}

// Tech-wall overlay flag (schema Virtual: HasSentinel + Overlay rule). 1 when the unit has the
// Sentinel keyword (printed or granted). Returns 1/0 for the overlay rule (shown when == 1).
function ObjectHasSentinel($obj): int {
    return ($obj !== null && isset($obj->CardID) && HasKeyword_Sentinel($obj)) ? 1 : 0;
}

// Per-unit Hidden indicator for the card counter (schema Virtual: HasHidden). 1 whenever the unit
// has the Hidden keyword (printed or granted), REGARDLESS of whether it's currently unattackable.
// (The separate smoke overlay keeps its own unattackable-only logic — see ObjectHiddenUnattackable.)
function ObjectHasHidden($obj): int {
    return ($obj !== null && isset($obj->CardID) && HasKeyword_Hidden($obj)) ? 1 : 0;
}

// Per-unit Ambush indicator for the card counter (schema Virtual: HasAmbush). 1 when the unit has
// the Ambush keyword (printed or granted). Returns 1/0 (ShowZero=false hides 0).
function ObjectHasAmbush($obj): int {
    return ($obj !== null && isset($obj->CardID) && HasKeyword_Ambush($obj)) ? 1 : 0;
}

// Per-unit Bounty indicator for the card counter (schema Virtual: HasBounty). 1 when the unit has
// the Bounty keyword (printed, granted by an upgrade, or granted by an effect). Returns 1/0.
function ObjectHasBounty($obj): int {
    return ($obj !== null && isset($obj->CardID) && HasKeyword_Bounty($obj)) ? 1 : 0;
}

// Smoke-overlay flag (schema Virtual: HiddenUnattackable + Overlay rule). 1 while a unit is
// unattackable because of Hidden: it has the Hidden keyword AND entered play this phase
// (SWU_PLAYED_UNIT_{uid}, set on a real play and cleared at RegroupPhaseStart). Mirrors
// _SWUHiddenBlocksAttack so the overlay is shown exactly while the unit can't be attacked,
// and vanishes once the phase ends and the flag clears. Self-contained (no CombatLogic dep).
function ObjectHiddenUnattackable($obj): int {
    if ($obj === null || !HasKeyword_Hidden($obj)) return 0;
    return GlobalEffectCount(intval($obj->Controller ?? 0), 'SWU_PLAYED_UNIT_' . intval($obj->UniqueID ?? 0)) > 0 ? 1 : 0;
}

// Thin wrapper so callers can write HasInitiative() without worrying about the
// intentional typo in the GA-inherited PlayerHasIniative().
function HasInitiative(int $player): bool {
    return PlayerHasIniative($player);
}

// ═════════════════════════════════════════════════════════════════════════════
// LOF_105 Oppo Rancisis — keyword-mirroring helpers
// "This unit gains <KW> while another friendly unit has <KW>." Other LOF_105 copies are EXCLUDED from the
// "another unit has it" check so two Rancisis can't mutually mirror into an infinite recursion.
// ═════════════════════════════════════════════════════════════════════════════
function _SWUUnitHasKeyword($u, string $kw): bool {
    switch ($kw) {
        case 'AMBUSH':    return HasKeyword_Ambush($u);
        case 'GRIT':      return HasKeyword_Grit($u);
        case 'HIDDEN':    return HasKeyword_Hidden($u);
        case 'OVERWHELM': return HasKeyword_Overwhelm($u);
        case 'SABOTEUR':  return HasKeyword_Saboteur($u);
        case 'SENTINEL':  return HasKeyword_Sentinel($u);
        case 'SHIELDED':  return HasKeyword_Shielded($u);
        case 'RAID':      return HasKeyword_Raid($u);
        case 'RESTORE':   return HasKeyword_Restore($u);
    }
    return false;
}
function _SWUMirrorAnotherFriendlyHasKeyword($obj, string $kw): bool {
    $self = intval($obj->UniqueID ?? -1);
    foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
        if (intval($u->UniqueID ?? -2) === $self) continue;
        if (($u->CardID ?? '') === 'LOF_105') continue;  // exclude other Rancisis (no mutual mirror)
        if (_SWUUnitHasKeyword($u, $kw)) return true;
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// AMBUSH
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Ambush($obj) {
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'AMBUSH')) return true;
    // SHD_188 4-LOM: each friendly unit named Zuckuss gains Ambush.
    if (CardTitle($obj->CardID ?? '') === 'Zuckuss'
        && _SWUCountUnitsWithCardID(intval($obj->Controller ?? 0), 'SHD_188') > 0) return true;
    // SHD_204 Millennium Falcon — "If you play this unit from your hand, it gains Ambush."
    // The per-UID hand-source flag is set in ActivateCard before entry triggers collect
    // (smuggle/discard/deck plays never set it).
    if (($obj->CardID ?? '') === 'SHD_204'
        && GlobalEffectCount(intval($obj->Controller ?? 0), 'SWU_PLAYED_FROM_HAND_' . intval($obj->UniqueID ?? 0)) > 0) {
        return true;
    }
    // ASH_113 Mandalorian Flagship — "While you control a leader unit, this unit gains Ambush."
    if (($obj->CardID ?? '') === 'ASH_113') {
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && IsLeaderUnit($u)) return true;
        }
    }
    // ASH_098 AT-ST Raider — "While you control another non-unique unit, this unit gains Ambush."
    if (($obj->CardID ?? '') === 'ASH_098') {
        $selfUid098 = intval($obj->UniqueID ?? 0);
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUid098 && !CardUnique($u->CardID ?? '')) return true;
        }
    }
    switch ($obj->CardID) {
        case 'SOR_114': // Escort Skiff — while you have a Cunning unit
            return PlayerHasUnitWithAspectInPlay($obj->Controller, 'Cunning', $obj->UniqueID);
        case 'SOR_249': // Frontier AT-RT — while you have a Vehicle unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Vehicle', $obj->UniqueID);
        case 'TWI_106': // Coruscant Guard — while Coordinate is active
            return IsCoordinateActive($obj->Controller);
        case 'TWI_081': // Droid Commando — while you have a Separatist unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Separatist', $obj->UniqueID);
        case 'TWI_194': // Ahsoka Tano — while you have fewer units than opponent
            return count(GetUnitsInPlay($obj->Controller)) < count(GetUnitsInPlay(OtherPlayer($obj->Controller)));
        case 'LOF_231': // Darth Tyranus — "While the Force is with you, this unit gains Ambush."
            return PlayerHasTheForce(intval($obj->Controller ?? 0));
        case 'LOF_118': // Terentatek — "While an opponent controls a Force unit, this unit gains Ambush."
            return PlayerHasUnitWithTraitInPlay(OtherPlayer(intval($obj->Controller ?? 0)), 'Force');
    }
    foreach (GetUnitsInPlay($obj->Controller) as $u) {
        if ($u->UniqueID === $obj->UniqueID) continue;
        switch ($u->CardID) {
            case 'SOR_079': // Admiral Piett — units costing 6+ gain Ambush
                if (intval(CardCost($obj->CardID)) >= 6 && strpos(CardType($obj->CardID), 'Unit') !== false) return true;
                break;
            case 'SOR_100': // Wedge Antilles — Vehicle units gain Ambush
                if (TraitContains($obj, 'Vehicle')) return true;
                break;
        }
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// GRIT
// ═════════════════════════════════════════════════════════════════════════════

// JTL_047 Admiral Yularen — true if $obj is a friendly Vehicle and the controller controls a JTL_047
// whose chosen keyword (stored per-UID as SWU_YULAREN_<uid>_<KW> on play) is $kw.
function _SWUYularenGrants($obj, string $kw): bool {
    if (!HasTrait($obj->CardID ?? '', 'Vehicle')) return false;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return false;
    foreach (GetUnitsInPlay($ctrl) as $u) {
        if (($u->CardID ?? '') !== 'JTL_047' || !empty($u->removed)) continue;
        if (GlobalEffectCount($ctrl, "SWU_YULAREN_" . intval($u->UniqueID ?? 0) . "_{$kw}") > 0) return true;
    }
    return false;
}

function HasConditionalKeyword_Grit($obj) {
    if (is_object($obj) && _SWUUnitHasUpgrade($obj, 'SEC_054')) return true;   // SEC_054 grants Grit
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'GRIT')) return true;
    if (_SWUYularenGrants($obj, 'GRIT')) return true;
    // JTL_150 Biggs Darklighter (pilot): if the attached unit is a Speeder, it gains Grit.
    if (_SWUUnitHasUpgrade($obj, 'JTL_150') && HasTrait($obj->CardID ?? '', 'Speeder')) return true;
    // LOF_238 Darth Revan's Lightsabers: "If attached unit is a Sith, it gains Grit."
    if (_SWUUnitHasUpgrade($obj, 'LOF_238') && HasTrait($obj->CardID ?? '', 'Sith')) return true;
    switch ($obj->CardID) {
        case 'TWI_050': // Luminara Unduli — while Coordinate is active
            return IsCoordinateActive($obj->Controller);
        case 'SEC_029': // Zam Wesell — while she has an upgrade attached
            return count(GetUpgradesOnUnit($obj)) > 0;
        case 'LOF_050': // Plo Koon — "While the Force is with you, this unit gains Grit."
            return PlayerHasTheForce(intval($obj->Controller ?? 0));
    }
    foreach (GetUnitsInPlay($obj->Controller) as $u) {
        if ($u->UniqueID === $obj->UniqueID) continue;
        switch ($u->CardID) {
            case 'SEC_088': // First Light — all friendly non-leader units gain Grit
                if (!IsLeaderUnit($obj)) return true;
                break;
        }
    }
    foreach (GetUpgradesOnUnit($obj) as $u) {
        switch ($u->CardID) {
            case 'JTL_001': // Asajj Ventress leader-pilot deployBox: "It gains Grit"
            case 'JTL_034': // Interceptor Ace (pilot) — "Attached unit gains Grit."
            case 'JTL_050': // Phantom II attached to The Ghost — "Attached unit ... gains Grit."
            case 'LAW_128': // Veiled Strength — "Attached unit gains Grit."
                return true;
        }
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// OVERWHELM
// ═════════════════════════════════════════════════════════════════════════════

// SEC_104 The Will of the People (upgrade) — attached unit gains: "While this unit is READY, each
// OTHER friendly unit gains Overwhelm, Raid 1, and Restore 1." True (for $obj) when a DIFFERENT friendly
// unit carries SEC_104 and is ready.
function _SWUSEC104AuraActive($obj): bool {
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return false;
    foreach (GetUnitsInPlay($ctrl) as $u) {
        if (!empty($u->removed) || intval($u->UniqueID ?? 0) === intval($obj->UniqueID ?? 0)) continue;
        if (intval($u->Status ?? 0) === 1 && _SWUUnitHasUpgrade($u, 'SEC_104')) return true;
    }
    return false;
}

function HasConditionalKeyword_Overwhelm($obj) {
    if (_SWUUnitHasUpgrade($obj, 'ASH_181')) return true;   // ASH_181 Mark My Words — "Attached unit gains Overwhelm."
    if (_SWUSEC104AuraActive($obj)) return true;   // SEC_104 aura
    // SEC_099 Naboo Royal Starship — each friendly LEADER unit gains Overwhelm.
    if (IsLeaderUnit($obj)) {
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'SEC_099') return true;
        }
    }
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'OVERWHELM')) return true;
    switch ($obj->CardID) {
        // SOR_130 / SHD_138: "while attacking a damaged/bounty unit" — combat-time only.
        // The combat resolver must re-check directly; return false here.
        case 'SHD_169': // Clan Challengers — while upgraded
            return count(GetUpgradesOnUnit($obj)) > 0;
        case 'TWI_130': // Bo-Katan Kryze — while you have another Mandalorian unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Mandalorian', $obj->UniqueID);
        case 'JTL_137': // Vonreg's TIE Interceptor — while it has 4 or more power
            return ObjectCurrentPower($obj) >= 4;
        case 'LOF_007': // Avar Kriss (deployed) — while the Force is with you, gains Overwhelm
            return intval($obj->Controller ?? 0) > 0 && PlayerHasTheForce(intval($obj->Controller));
    }
    // JTL_150 Biggs Darklighter (pilot): if the attached unit is a Fighter, it gains Overwhelm.
    if (_SWUUnitHasUpgrade($obj, 'JTL_150') && HasTrait($obj->CardID ?? '', 'Fighter')) return true;
    foreach (GetUnitsInPlay($obj->Controller) as $u) {
        if ($u->UniqueID === $obj->UniqueID) continue;
        switch ($u->CardID) {
            case 'ASH_007': // Grand Admiral Sloane (deployed) — each OTHER friendly unit gains Overwhelm
                return true;
            case 'SHD_007': // Moff Gideon — units costing 3 or less gain Overwhelm while attacking
                if (intval(CardCost($obj->CardID)) <= 3) return true;
                break;
            case 'JTL_161': // Captain Tarkin — Vehicle units gain Overwhelm
                if (HasTrait($obj->CardID ?? '', 'Vehicle')) return true;
                break;
        }
    }
    foreach (GetUpgradesOnUnit($obj) as $u) {
        switch ($u->CardID) {
            // Add upgrade cards that grant Overwhelm as they are implemented
        }
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// SABOTEUR
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Saboteur($obj) {
    // LAW_233 Galen Erso — "Enemy units gain Raid 1 and Saboteur." A unit gains it while an opponent of
    // its controller controls a LAW_233.
    if (_SWUCountUnitsWithCardID(OtherPlayer(intval($obj->Controller ?? 0)), 'LAW_233') > 0) return true;
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'SABOTEUR')) return true;
    if (_SWULof191HasBuff($obj)) return true; // LOF_191 BD-1: chosen unit gains Saboteur while BD-1 in play
    // SHD_190 Zuckuss: each friendly unit named 4-LOM gains Saboteur.
    if (CardTitle($obj->CardID ?? '') === '4-LOM'
        && _SWUCountUnitsWithCardID(intval($obj->Controller ?? 0), 'SHD_190') > 0) return true;
    // ASH_030 Marrok — "While this unit is upgraded, he loses Sentinel and gains Saboteur."
    if (($obj->CardID ?? '') === 'ASH_030' && _SWUIsUpgraded($obj)) return true;
    switch ($obj->CardID) {
        case 'TWI_243': // Republic Commando — while Coordinate is active
            return IsCoordinateActive($obj->Controller);
        case 'TWI_130': // Bo-Katan Kryze — while you have another Mandalorian unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Mandalorian', $obj->UniqueID);
    }
    foreach (GetUpgradesOnUnit($obj) as $u) {
        switch ($u->CardID) {
            case 'SOR_166': // Infiltrator's Skill
            case 'LOF_215': // Ascension Cable
                return true;
        }
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// SENTINEL
// ═════════════════════════════════════════════════════════════════════════════

// True if $player controls a Resistance card other than the unit with $selfUid — as a friendly unit
// (trait Resistance), an upgrade (trait Resistance, on any unit including self), or the leader (JTL_104).
function _SWUControlsAnotherResistance(int $player, int $selfUid): bool {
    if ($player <= 0) return false;
    foreach (GetLeader($player) as $l) {
        if (empty($l->removed) && HasTrait($l->CardID ?? '', 'Resistance')) return true;
    }
    foreach (GetUnitsInPlay($player) as $u) {
        if (intval($u->UniqueID ?? 0) !== $selfUid && HasTrait($u->CardID ?? '', 'Resistance')) return true;
        foreach (GetUpgradesOnUnit($u) as $up) {
            if (HasTrait($up->CardID ?? '', 'Resistance')) return true; // a Resistance upgrade is "another card"
        }
    }
    return false;
}

function HasConditionalKeyword_Sentinel($obj) {
    // SEC_071 (upgrade) — "While attached unit is exhausted, it gains Sentinel."
    if (_SWUUnitHasUpgrade($obj, 'SEC_071') && intval($obj->Status ?? 1) === 0) return true;
    // ASH_243 Darth Vader — Shielded + "While this unit is ready, he gains Sentinel."
    if (($obj->CardID ?? '') === 'ASH_243' && intval($obj->Status ?? 1) === 1) return true;
    // ASH_066 Luke's Jedi Lightsaber (upgrade) — "If attached unit is Luke Skywalker, he gains Sentinel."
    if (_SWUUnitHasUpgrade($obj, 'ASH_066') && CardTitle($obj->CardID ?? '') === 'Luke Skywalker') return true;
    // ASH_198 Nowhere to Hide (upgrade) — "Attached unit gains Sentinel."
    if (_SWUUnitHasUpgrade($obj, 'ASH_198')) return true;
    // ASH_007 Grand Admiral Sloane (deployed) — each OTHER friendly unit gains Sentinel.
    if (intval($obj->Controller ?? 0) > 0) {
        $self007 = intval($obj->UniqueID ?? 0);
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'ASH_007' && intval($u->UniqueID ?? -1) !== $self007) return true;
        }
    }
    // ASH_120 Warrior of Clan Kryze — "While you control another exhausted unit, this unit gains Sentinel."
    if (($obj->CardID ?? '') === 'ASH_120') {
        $selfUid120 = intval($obj->UniqueID ?? 0);
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUid120 && intval($u->Status ?? 1) === 0) return true;
        }
    }
    // ASH_078 B-Wing Rearguard — "While you control a ground unit, this unit gains Sentinel."
    if (($obj->CardID ?? '') === 'ASH_078') {
        foreach (GetGroundArena(intval($obj->Controller ?? 0)) as $gu) {
            if (empty($gu->removed)) return true;
        }
    }
    // ASH_049 Shin Hati — "While this is the only friendly non-leader ground unit, she gains Sentinel."
    if (($obj->CardID ?? '') === 'ASH_049') {
        $ctrl049 = intval($obj->Controller ?? 0);
        $others = 0;
        foreach (GetGroundArena($ctrl049) as $u) {
            if (empty($u->removed) && !IsLeaderUnit($u) && intval($u->UniqueID ?? 0) !== intval($obj->UniqueID ?? 0)) $others++;
        }
        if ($others === 0) return true;
    }
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'SENTINEL')) return true;
    if (_SWUYularenGrants($obj, 'SENTINEL')) return true;
    switch ($obj->CardID) {
        case 'ASH_079': // Koska Reeves — "While you control a token unit, this unit gains Sentinel."
            foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
                if (empty($u->removed) && strpos(CardType($u->CardID ?? '') ?? '', 'Token') !== false) return true;
            }
            return false;
        case 'LAW_105': // Cinta Kaz — "While this unit is upgraded, she gains Sentinel."
            return _SWUIsUpgraded($obj);
        case 'SOR_048': // Vigilant Honor Guards — while undamaged
        case 'SEC_063': // Rotunda Senate Guards — while undamaged
            return intval(isset($obj->Damage) ? $obj->Damage : 0) === 0;
        case 'SOR_113': // Homestead Militia (SOR)
        case 'JTL_113': // Homestead Militia (JTL)
            return count(GetResources($obj->Controller)) >= 6;
        case 'SEC_079': // Corrupt Politician — Sentinel while you control more units than an opponent
            $sec079Ctrl = intval($obj->Controller ?? 0);
            if ($sec079Ctrl <= 0) return false;
            $sec079Mine = 0; foreach (GetUnitsInPlay($sec079Ctrl) as $u) { if (empty($u->removed)) $sec079Mine++; }
            $sec079Theirs = 0; foreach (GetUnitsInPlay(OtherPlayer($sec079Ctrl)) as $u) { if (empty($u->removed)) $sec079Theirs++; }
            return $sec079Mine > $sec079Theirs;
        case 'SOR_211': // Gamorrean Guards — while you have a Cunning unit
            return PlayerHasUnitWithAspectInPlay($obj->Controller, 'Cunning', $obj->UniqueID);
        case 'SOR_065': // Baze Malbus — while you have initiative
            return HasInitiative($obj->Controller);
        case 'LOF_196': // Jedi Sentinel — "While the Force is with you, this unit gains Sentinel."
            return PlayerHasTheForce(intval($obj->Controller ?? 0));
        case 'LOF_085': // Praetorian Guard — "While you control a unit with 4 or more power, gains Sentinel."
            foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
                if (empty($u->removed) && intval(ObjectCurrentPower($u)) >= 4) return true;
            }
            return false;
        case 'SOR_082': // Emperor's Royal Guard — while you have an Official unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Official');
        case 'SHD_112': // Gamorrean Retainer — while you have a Command unit
            return PlayerHasUnitWithAspectInPlay($obj->Controller, 'Command', $obj->UniqueID);
        case 'SHD_034': // Supercommando Squad — while upgraded
        case 'SHD_247': // Protector of the Throne — while upgraded
            return count(GetUpgradesOnUnit($obj)) > 0;
        case 'SHD_052': { // Sugi — while opponent has an upgraded unit
            $opp = OtherPlayer($obj->Controller);
            foreach (GetUnitsInPlay($opp) as $u) {
                if (count(GetUpgradesOnUnit($u)) > 0) return true;
            }
            return false;
        }
        case 'TWI_043': // Outspoken Representative — while you have a Republic unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Republic', $obj->UniqueID);
        case 'TWI_061': // Infantry of the 212th — while Coordinate is active
            return IsCoordinateActive($obj->Controller);
        case 'TWI_054': // Duchess's Champion — while opponent has Coordinate active
            return IsCoordinateActive(OtherPlayer($obj->Controller));
        case 'JTL_053': // The Ghost — while upgraded
            return count(GetUpgradesOnUnit($obj)) > 0;
        case 'JTL_107': // Bunker Defender — while you control a Vehicle unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Vehicle');
        case 'JTL_104': // Raddus — while you control ANOTHER Resistance card (unit, upgrade, or leader)
            return _SWUControlsAnotherResistance(intval($obj->Controller ?? 0), intval($obj->UniqueID ?? 0));
    }
    // JTL_053 The Ghost aura — each OTHER friendly Spectre unit gains The Ghost's keywords. The Ghost's
    // only keyword is Sentinel (while it is upgraded), so a friendly Spectre unit gains Sentinel while a
    // friendly upgraded The Ghost is in play.
    if (HasTrait($obj->CardID ?? '', 'Spectre') && ($obj->CardID ?? '') !== 'JTL_053') {
        $controller = intval($obj->Controller ?? 0);
        if ($controller > 0) {
            foreach (GetUnitsInPlay($controller) as $u) {
                if (($u->CardID ?? '') === 'JTL_053' && empty($u->removed) && count(GetUpgradesOnUnit($u)) > 0) return true;
            }
        }
    }
    foreach (GetUpgradesOnUnit($obj) as $u) {
        switch ($u->CardID) {
            case 'SOR_057': // Protector
                return true;
            case 'JTL_109': { // Jarek Yeager (pilot) — while you control a ground unit AND a space unit
                $ctrl = intval($obj->Controller ?? 0);
                $hasG = false; foreach (GetGroundArena($ctrl) as $g)  { if (empty($g->removed))  { $hasG = true; break; } }
                $hasS = false; foreach (GetSpaceArena($ctrl)  as $sp) { if (empty($sp->removed)) { $hasS = true; break; } }
                if ($hasG && $hasS) return true;
                break;
            }
        }
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// SHIELDED
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Shielded($obj) {
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'SHIELDED')) return true;
    if (_SWUYularenGrants($obj, 'SHIELDED')) return true;
    switch ($obj->CardID) {
        case 'SHD_212': // Privateer Scyk — while you have a Cunning unit
            return PlayerHasUnitWithAspectInPlay($obj->Controller, 'Cunning', $obj->UniqueID);
        case 'SHD_186': { // Hunter of the Haxion Brood — while opponent has a Bounty unit
            foreach (GetUnitsInPlay(OtherPlayer($obj->Controller)) as $u) {
                if (HasKeyword_Bounty($u)) return true;
            }
            return false;
        }
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// BOUNTY
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Bounty($obj) {
    switch ($obj->CardID) {
        case 'SHD_033': // Synara San — while exhausted (Status 0; 1 = ready)
        case 'SHD_165': // Unlicensed Headhunter — while exhausted
            return isset($obj->Status) && intval($obj->Status) === 0;
    }
    // Upgrade-granted Bounty — the attached unit gains a Bounty ability (the keyword shows the badge;
    // the custom reward is collected on defeat — see the granted-bounty snapshot in
    // CollectWhenDefeatedTriggers + SWUCollectBounty). Shared list: SWUBountyGrantUpgrades().
    foreach (GetUpgradesOnUnit($obj) as $u) {
        if (in_array($u->CardID, SWUBountyGrantUpgrades(), true)) return true;
    }
    return false;
}

// The Bounty-granting upgrades ("Attached unit gains: 'Bounty — …'"). One list drives BOTH the
// badge (HasConditionalKeyword_Bounty above) and the defeat-time reward snapshot (the Subcards scan
// in CollectWhenDefeatedTriggers, GameLogic.php); each ID also needs its reward case in
// SWUCollectBounty. The snapshot param is uniformly "host was unique" (used by SHD_071/123/173).
function SWUBountyGrantUpgrades(): array {
    return ['SHD_068', 'SHD_071', 'SHD_123', 'SHD_125', 'SHD_173', 'SHD_176', 'SHD_221', 'SHD_222', 'SHD_261'];
}

// ═════════════════════════════════════════════════════════════════════════════
// SMUGGLE
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Smuggle($obj) {
    // SHD_248 Tech — all cards in your resources gain Smuggle while Tech is in a ground arena.
    foreach (GetUnitsInArena($obj->Controller, 'Ground') as $u) {
        if ($u->CardID === 'SHD_248') return true;
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// COORDINATE
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Coordinate($obj) {
    foreach (GetUpgradesOnUnit($obj) as $u) {
        if ($u->CardID === 'TWI_051') return true; // For the Republic
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// PILOTING  /  HIDDEN  /  PLOT
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Piloting($obj) {
    return false;
}

function HasConditionalKeyword_Hidden($obj) {
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'HIDDEN')) return true;
    if ($obj === null) return false;
    // ASH_177 Onyx Cinder — "Other friendly units gain Hidden." Granted to any unit whose controller
    // controls another Onyx Cinder. (Onyx Cinder itself has Hidden innately.)
    if (($obj->CardID ?? '') !== 'ASH_177') {
        $ash177Uid = intval($obj->UniqueID ?? -1);
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'ASH_177' && intval($u->UniqueID ?? -2) !== $ash177Uid) return true;
        }
    }
    // LOF_132 Grand Inquisitor: "Other friendly Inquisitor units gain Hidden."
    if (HasTrait($obj->CardID ?? '', 'Inquisitor')) {
        $controller = intval($obj->Controller ?? 0);
        $selfUid    = intval($obj->UniqueID ?? -1);
        foreach (GetUnitsInPlay($controller) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'LOF_132' && intval($u->UniqueID ?? -2) !== $selfUid) return true;
        }
    }
    // SEC_203 Tala Durith: "Each other friendly unit gains Hidden."
    $sec203Ctrl = intval($obj->Controller ?? 0);
    $sec203Self = intval($obj->UniqueID ?? -1);
    foreach (GetUnitsInPlay($sec203Ctrl) as $u) {
        if (empty($u->removed) && ($u->CardID ?? '') === 'SEC_203' && intval($u->UniqueID ?? -2) !== $sec203Self) return true;
    }
    return false;
}

function HasConditionalKeyword_Plot($obj) {
    return false;
}

// Display flag for the resource-zone Plot icon (schema Virtual: HasPlot). 1 when a card
// in the resource zone has the Plot keyword (CR 19) — it can be played from resources when
// you deploy your leader. Returns 1/0 for the Image counter (ShowZero=false hides 0).
function ResourceHasPlot($obj): int {
    if ($obj === null || !isset($obj->CardID)) return 0;
    return HasKeyword_Plot($obj) ? 1 : 0;
}

// ═════════════════════════════════════════════════════════════════════════════
// SUPPORT  (ASH) — "When you play this unit, you may attack with another unit. It
// gains this unit's other abilities for this attack." Detection is innate-only for
// now; ASH_008 (granted Support via discard-pile condition) is handled separately
// if/when implemented. No card grants Support conditionally to a field unit yet.
// ═════════════════════════════════════════════════════════════════════════════
function HasConditionalKeyword_Support($obj) {
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// RAID  (value keyword)
// ═════════════════════════════════════════════════════════════════════════════

function GetConditionalKeyword_Raid_Value($obj) {
    $amount = 0;
    // ASH_093 Captain Pellaeon — "While a leader unit has been defeated this phase, this unit gains Raid 3."
    if (($obj->CardID ?? '') === 'ASH_093' && GlobalEffectCount(intval($obj->Controller ?? 0), 'SWU_LEADER_DEFEATED_PHASE') > 0) $amount += 3;
    // ASH_105 Bo-Katan Kryze — "While you control another Mandalorian unit, this unit gains Raid 2."
    if (($obj->CardID ?? '') === 'ASH_105') {
        $selfUid105 = intval($obj->UniqueID ?? 0);
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUid105 && _SWUUnitHasTrait($u, 'Mandalorian')) { $amount += 2; break; }
        }
    }
    if (_SWUSEC104AuraActive($obj)) $amount += 1;   // SEC_104 aura — Raid 1
    // LAW_233 Galen Erso — "Enemy units gain Raid 1 and Saboteur." (Raid half.)
    if (_SWUCountUnitsWithCardID(OtherPlayer(intval($obj->Controller ?? 0)), 'LAW_233') > 0) $amount += 1;
    // SEC_140 Hondo Ohnaka — each OTHER friendly unit gains Raid 1.
    // SEC_099 Naboo Royal Starship — each friendly LEADER unit gains Raid 2.
    foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== intval($obj->UniqueID ?? 0) && ($u->CardID ?? '') === 'SEC_140') { $amount += 1; break; }
    }
    if (IsLeaderUnit($obj)) {
        foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
            if (empty($u->removed) && ($u->CardID ?? '') === 'SEC_099') { $amount += 2; break; }
        }
    }
    // SEC_201 Anakin Skywalker — Raid 2 while you control Padmé Amidala (leader or unit).
    if (($obj->CardID ?? '') === 'SEC_201' && _SWUControlsTitle(intval($obj->Controller ?? 0), ['Padmé Amidala'])) $amount += 2;
    // LOF_105 Oppo Rancisis — "gains Raid 2 while another friendly unit has Raid."
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'RAID')) $amount += 2;
    // SEC_155 Alexsandr Kallus — each OTHER friendly unique unit gains Raid 2 while you have the initiative.
    if (CardUnique($obj->CardID ?? '')) {
        $kctrl = intval($obj->Controller ?? 0);
        if ($kctrl > 0 && PlayerHasIniative($kctrl)) {
            foreach (GetUnitsInPlay($kctrl) as $ku) {
                if (!empty($ku->removed) || intval($ku->UniqueID ?? 0) === intval($obj->UniqueID ?? 0)) continue;
                if (($ku->CardID ?? '') === 'SEC_155') { $amount += 2; break; }
            }
        }
    }
    // SEC_010 Dedra Meero (deployed) — Raid 2 while you have more cards in hand than an opponent.
    if (($obj->CardID ?? '') === 'SEC_010') {
        $dctrl = intval($obj->Controller ?? 0);
        if ($dctrl > 0) {
            $myH = 0; foreach (GetHand($dctrl) as $h) if (empty($h->removed)) $myH++;
            $opH = 0; foreach (GetHand(OtherPlayer($dctrl)) as $h) if (empty($h->removed)) $opH++;
            if ($myH > $opH) $amount += 2;
        }
    }
    switch ($obj->CardID) {
        case 'SEC_171': // Punishing One — Raid 1 for each damaged enemy unit
            foreach (GetUnitsInPlay(OtherPlayer(intval($obj->Controller ?? 0))) as $eu) {
                if (empty($eu->removed) && intval($eu->Damage ?? 0) > 0) $amount += 1;
            }
            break;
        case 'SEC_134': // Hunting Assassin Droid — Raid 2 while an enemy unit is damaged
            foreach (GetUnitsInPlay(OtherPlayer(intval($obj->Controller ?? 0))) as $eu) {
                if (empty($eu->removed) && intval($eu->Damage ?? 0) > 0) { $amount += 2; break; }
            }
            break;
        case 'SEC_249': // High Command Councilor — Raid 2 while you control another Official unit
            foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $fu) {
                if (empty($fu->removed) && intval($fu->UniqueID ?? 0) !== intval($obj->UniqueID ?? 0)
                    && HasTrait($fu->CardID ?? '', 'Official')) { $amount += 2; break; }
            }
            break;
        case 'SOR_159': // Partisan Insurgent — Raid 2 while another Aggression unit in play
            if (PlayerHasUnitWithAspectInPlay($obj->Controller, 'Aggression', $obj->UniqueID)) $amount += 2;
            break;
        case 'SHD_168': // Hunting Nexu — Raid 2 while another Aggression unit in play
            if (PlayerHasUnitWithAspectInPlay($obj->Controller, 'Aggression', $obj->UniqueID)) $amount += 2;
            break;
        case 'SOR_131': // Fifth Brother — Raid equal to damage taken
            $amount += intval(isset($obj->Damage) ? $obj->Damage : 0);
            break;
        case 'LOF_162': // Hunting Nexu — Raid 2 while you control another Aggression unit
            if (PlayerHasUnitWithAspectInPlay($obj->Controller, 'Aggression', $obj->UniqueID)) $amount += 2;
            break;
        case 'LOF_212': // Life Wind Sage — Raid 2 while an enemy unit is exhausted
            foreach (GetUnitsInPlay(OtherPlayer(intval($obj->Controller ?? 0))) as $eu) {
                if (empty($eu->removed) && intval($eu->Status ?? 0) !== 1) { $amount += 2; break; }
            }
            break;
        case 'SOR_188': // Chopper — Raid 1 while you control ANOTHER Spectre unit
            foreach (GetUnitsInPlay($obj->Controller) as $u) {
                if ($u->UniqueID === $obj->UniqueID) continue;
                if (HasTrait($u->CardID, 'Spectre')) { $amount += 1; break; }
            }
            break;
        case 'JTL_081': // First Order TIE Fighter — Raid 1 while you control a token unit
            foreach (GetUnitsInPlay($obj->Controller) as $u) {
                if (EffectiveCardType($u) === 'Token Unit') { $amount += 1; break; }
            }
            break;
        case 'JTL_137': // Vonreg's TIE Interceptor — Raid 1 while it has 6 or more power
            if (ObjectCurrentPower($obj) >= 6) $amount += 1;
            break;
        case 'JTL_257': // Flanking Fang Fighter — Raid 2 while you control ANOTHER Fighter unit
            foreach (GetUnitsInPlay($obj->Controller) as $u) {
                if ($u->UniqueID === $obj->UniqueID) continue;
                if (HasTrait($u->CardID, 'Fighter')) { $amount += 2; break; }
            }
            break;
    }
    foreach (GetUnitsInPlay($obj->Controller) as $u) {
        if ($u->UniqueID === $obj->UniqueID) continue;
        switch ($u->CardID) {
            case 'SOR_012': // IG-88 Leader Unit — all other friendly units get +1 Raid
                $amount += 1;
                break;
            case 'SOR_144': // Red Three — each other friendly Heroism unit gains Raid 1
                if (strpos(CardAspect($obj->CardID) ?? '', 'Heroism') !== false) $amount += 1;
                break;
            case 'JTL_134': // General Hux — each other friendly First Order unit gains Raid 1
                if (HasTrait($obj->CardID, 'First Order')) $amount += 1;
                break;
        }
    }
    foreach (GetUpgradesOnUnit($obj) as $u) {
        switch ($u->CardID) {
            case 'JTL_211': // Independent Smuggler (pilot) — "Attached unit gains Raid 1."
                $amount += 1;
                break;
            case 'LOF_261': // Constructed Lightsaber — "If attached unit is a Villainy unit, it gains Raid 2."
                if (strpos(CardAspect($obj->CardID) ?? '', 'Villainy') !== false) $amount += 2;
                break;
        }
    }
    // LOF_169 Invasion Control Ship: "Friendly Droid units gain Raid 2." (field-source grant.)
    if (HasTrait($obj->CardID ?? '', 'Droid')
        && _SWUCountUnitsWithCardID(intval($obj->Controller ?? 0), 'LOF_169') > 0) {
        $amount += 2;
    }
    // LOF_186 Marchion Ro — "Each friendly unit's Raid is doubled." Doubles the GRAND total. The generated
    // GetKeyword_Raid_Value computes base_max (printed/TE/granted) then adds this conditional amount, so to
    // make the final value 2×(base_max + amount) the conditional must contribute (base_max + amount) extra.
    if (_SWUCountUnitsWithCardID(intval($obj->Controller ?? 0), 'LOF_186') > 0) {
        global $Raid_Cards;
        $base = intval($Raid_Cards[$obj->CardID] ?? 0);
        $base = max($base, SWUTurnEffectKeywordValue($obj, 'RAID'));
        if (HasGrantedKeyword($obj, 'RAID')) $base = max($base, 1);
        if ($base + $amount > 0) $amount += $base + $amount;
    }
    return $amount;
}

// ═════════════════════════════════════════════════════════════════════════════
// RESTORE  (value keyword)
// ═════════════════════════════════════════════════════════════════════════════

function GetConditionalKeyword_Restore_Value($obj) {
    $amount = 0;
    // ASH_114 Sabine's Lightsaber (upgrade) — "If attached unit is Sabine Wren or a Force unit, it gains Restore 2."
    if (_SWUUnitHasUpgrade($obj, 'ASH_114')
        && (CardTitle($obj->CardID ?? '') === 'Sabine Wren' || HasTrait($obj->CardID ?? '', 'Force'))) $amount += 2;
    // ASH_122 Consortium StarViper — "While you have the initiative, this unit gains Restore 2."
    if (($obj->CardID ?? '') === 'ASH_122' && HasInitiative(intval($obj->Controller ?? 0))) $amount += 2;
    // ASH_057 Lothal E-Wing — "While an enemy unit is upgraded, this unit gains Restore 2."
    if (($obj->CardID ?? '') === 'ASH_057') {
        foreach (GetUnitsInPlay(OtherPlayer(intval($obj->Controller ?? 0))) as $eu) {
            if (empty($eu->removed) && _SWUIsUpgraded($eu)) { $amount += 2; break; }
        }
    }
    if (_SWUYularenGrants($obj, 'RESTORE')) $amount += 1;   // JTL_047 Yularen (Restore 1 to Vehicles)
    if (_SWUSEC104AuraActive($obj)) $amount += 1;           // SEC_104 aura — Restore 1
    // LOF_105 Oppo Rancisis — "gains Restore 2 while another friendly unit has Restore."
    if (($obj->CardID ?? '') === 'LOF_105' && _SWUMirrorAnotherFriendlyHasKeyword($obj, 'RESTORE')) $amount += 2;
    switch ($obj->CardID) {
        case 'SOR_112': // Consortium Starviper — Restore 2 while you have initiative
            if (HasInitiative($obj->Controller)) $amount += 2;
            break;
        case 'SEC_116': // Nubian Star Skiff — Restore 2 while you control an Official unit
            foreach (GetUnitsInPlay(intval($obj->Controller ?? 0)) as $u) {
                if (empty($u->removed) && HasTrait($u->CardID ?? '', 'Official')) { $amount += 2; break; }
            }
            break;
    }
    foreach (GetUnitsInPlay($obj->Controller) as $u) {
        if ($u->UniqueID === $obj->UniqueID) continue;
        switch ($u->CardID) {
            case 'SOR_102': // Home One — other friendly units get +1 Restore
                $amount += 1;
                break;
            case 'SEC_047': // Defiant — each other friendly unit gains Restore 1
                $amount += 1;
                break;
        }
    }
    foreach (GetUpgradesOnUnit($obj) as $u) {
        switch ($u->CardID) {
            case 'LOF_053': // Heirloom Lightsaber — "If attached unit is a Force unit, it gains Restore 1."
                if (_SWUUnitHasTrait($obj, 'Force')) $amount += 1;
                break;
            case 'SOR_070': // Devotion — +2 Restore
                $amount += 2;
                break;
            case 'JTL_045': // Hera Syndulla (pilot) — "Attached unit gains Restore 1."
                $amount += 1;
                break;
            case 'LOF_261': // Constructed Lightsaber — "If attached unit is a Heroism unit, it gains Restore 2."
                if (strpos(CardAspect($obj->CardID) ?? '', 'Heroism') !== false) $amount += 2;
                break;
        }
    }
    return $amount;
}

// ═════════════════════════════════════════════════════════════════════════════
// EXPLOIT  (value keyword)
// ═════════════════════════════════════════════════════════════════════════════

function GetConditionalKeyword_Exploit_Value($obj) {
    return 0;
}
