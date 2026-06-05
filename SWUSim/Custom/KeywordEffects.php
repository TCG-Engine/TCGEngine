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
];

function SWUKeywordSuppressed($obj, string $keyword): bool {
    // A unit that has lost all abilities has NO keyword — innate, granted, or gained.
    if (LostAbilities($obj)) return true;
    global $keywordSuppressors;
    if (empty($obj->TurnEffects)) return false;
    foreach ($obj->TurnEffects as $te) {
        if (isset($keywordSuppressors[$te]) && in_array($keyword, $keywordSuppressors[$te], true)) return true;
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
    foreach (GetUpgradesOnUnit($obj) as $u) {
        if (($u->CardID ?? '') === 'SHD_072') return true;
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

// Returns true if $player has at least one unit in play with innate or
// TurnEffect Coordinate.  Checks the generated $Coordinate_Cards table
// directly to avoid recursive calls through HasConditionalKeyword_Coordinate.
function IsCoordinateActive(int $player): bool {
    global $Coordinate_Cards;
    foreach (GetUnitsInPlay($player) as $u) {
        if (isset($Coordinate_Cards[$u->CardID])) return true;
        if (!empty($u->TurnEffects) && in_array('COORDINATE', $u->TurnEffects)) return true;
        foreach (GetUpgradesOnUnit($u) as $upg) {
            if ($upg->CardID === 'TWI_051') return true; // For the Republic
        }
    }
    return false;
}

// Thin wrapper so callers can write HasInitiative() without worrying about the
// intentional typo in the GA-inherited PlayerHasIniative().
function HasInitiative(int $player): bool {
    return PlayerHasIniative($player);
}

// ═════════════════════════════════════════════════════════════════════════════
// AMBUSH
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Ambush($obj) {
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
    if (_SWUYularenGrants($obj, 'GRIT')) return true;
    // JTL_150 Biggs Darklighter (pilot): if the attached unit is a Speeder, it gains Grit.
    if (_SWUUnitHasUpgrade($obj, 'JTL_150') && HasTrait($obj->CardID ?? '', 'Speeder')) return true;
    switch ($obj->CardID) {
        case 'TWI_050': // Luminara Unduli — while Coordinate is active
            return IsCoordinateActive($obj->Controller);
        case 'SEC_029': // Zam Wesell — while she has an upgrade attached
            return count(GetUpgradesOnUnit($obj)) > 0;
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
                return true;
        }
    }
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// OVERWHELM
// ═════════════════════════════════════════════════════════════════════════════

function HasConditionalKeyword_Overwhelm($obj) {
    switch ($obj->CardID) {
        // SOR_130 / SHD_138: "while attacking a damaged/bounty unit" — combat-time only.
        // The combat resolver must re-check directly; return false here.
        case 'SHD_169': // Clan Challengers — while upgraded
            return count(GetUpgradesOnUnit($obj)) > 0;
        case 'TWI_130': // Bo-Katan Kryze — while you have another Mandalorian unit
            return PlayerHasUnitWithTraitInPlay($obj->Controller, 'Mandalorian', $obj->UniqueID);
        case 'JTL_137': // Vonreg's TIE Interceptor — while it has 4 or more power
            return ObjectCurrentPower($obj) >= 4;
    }
    // JTL_150 Biggs Darklighter (pilot): if the attached unit is a Fighter, it gains Overwhelm.
    if (_SWUUnitHasUpgrade($obj, 'JTL_150') && HasTrait($obj->CardID ?? '', 'Fighter')) return true;
    foreach (GetUnitsInPlay($obj->Controller) as $u) {
        if ($u->UniqueID === $obj->UniqueID) continue;
        switch ($u->CardID) {
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
    if (_SWUYularenGrants($obj, 'SENTINEL')) return true;
    switch ($obj->CardID) {
        case 'SOR_048': // Vigilant Honor Guards — while undamaged
            return intval(isset($obj->Damage) ? $obj->Damage : 0) === 0;
        case 'SOR_113': // Homestead Militia (SOR)
        case 'JTL_113': // Homestead Militia (JTL)
            return count(GetResources($obj->Controller)) >= 6;
        case 'SOR_211': // Gamorrean Guards — while you have a Cunning unit
            return PlayerHasUnitWithAspectInPlay($obj->Controller, 'Cunning', $obj->UniqueID);
        case 'SOR_065': // Baze Malbus — while you have initiative
            return HasInitiative($obj->Controller);
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
        case 'SHD_033': // Synara San — while exhausted (Status != 2)
        case 'SHD_165': // Unlicensed Headhunter — while exhausted
            return isset($obj->Status) && intval($obj->Status) !== 2;
    }
    return false;
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
    return false;
}

function HasConditionalKeyword_Plot($obj) {
    return false;
}

// ═════════════════════════════════════════════════════════════════════════════
// RAID  (value keyword)
// ═════════════════════════════════════════════════════════════════════════════

function GetConditionalKeyword_Raid_Value($obj) {
    $amount = 0;
    switch ($obj->CardID) {
        case 'SOR_159': // Partisan Insurgent — Raid 2 while another Aggression unit in play
            if (PlayerHasUnitWithAspectInPlay($obj->Controller, 'Aggression', $obj->UniqueID)) $amount += 2;
            break;
        case 'SHD_168': // Hunting Nexu — Raid 2 while another Aggression unit in play
            if (PlayerHasUnitWithAspectInPlay($obj->Controller, 'Aggression', $obj->UniqueID)) $amount += 2;
            break;
        case 'SOR_131': // Fifth Brother — Raid equal to damage taken
            $amount += intval(isset($obj->Damage) ? $obj->Damage : 0);
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
        }
    }
    return $amount;
}

// ═════════════════════════════════════════════════════════════════════════════
// RESTORE  (value keyword)
// ═════════════════════════════════════════════════════════════════════════════

function GetConditionalKeyword_Restore_Value($obj) {
    $amount = 0;
    if (_SWUYularenGrants($obj, 'RESTORE')) $amount += 1;   // JTL_047 Yularen (Restore 1 to Vehicles)
    switch ($obj->CardID) {
        case 'SOR_112': // Consortium Starviper — Restore 2 while you have initiative
            if (HasInitiative($obj->Controller)) $amount += 2;
            break;
    }
    foreach (GetUnitsInPlay($obj->Controller) as $u) {
        if ($u->UniqueID === $obj->UniqueID) continue;
        switch ($u->CardID) {
            case 'SOR_102': // Home One — other friendly units get +1 Restore
                $amount += 1;
                break;
        }
    }
    foreach (GetUpgradesOnUnit($obj) as $u) {
        switch ($u->CardID) {
            case 'SOR_070': // Devotion — +2 Restore
                $amount += 2;
                break;
            case 'JTL_045': // Hera Syndulla (pilot) — "Attached unit gains Restore 1."
                $amount += 1;
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
