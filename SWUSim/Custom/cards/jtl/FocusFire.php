<?php
// JTL_129
// Cost 4 - Focus Fire - [Command]
// Text: Choose a unit. Each friendly Vehicle unit in the same arena deals damage equal to its power to that unit.

// ── JTL_129 Focus Fire — each friendly Vehicle in the chosen unit's arena deals its power to it. ──────
$customDQHandlers["JTL_129#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $arena = $obj->Location ?? 'GroundArena';   // 'GroundArena' or 'SpaceArena'
    // Owner (2026-10-07): it is all done AT THE SAME TIME — one simultaneous damage event, each Vehicle a source of its
    // own share (CR 18.2a). So a Shield stops the whole event, not just the first Vehicle's share — EXCEPT the shares
    // ASH_196 Gorian Shard's Corsair makes unpreventable (dealt by a friendly Underworld card), which land regardless.
    $sum = 0; $unprev = 0; $unprevSrc = null;
    foreach (ZoneSearch('my' . $arena, AnyUnitFilter) as $mz) {
        $u = GetZoneObject($mz);
        if ($u === null || !empty($u->removed) || !HasTrait($u->CardID ?? '', 'Vehicle')) continue;
        $p = intval(ObjectCurrentPower($u));
        if ($p <= 0) continue;
        $sum += $p;
        if (_SWUDamageUnpreventable($u)) { $unprev += $p; $unprevSrc ??= $mz; }
    }
    if ($sum <= 0) return;
    // Nothing unpreventable: ONE hit, exactly as it always was.
    if ($unprev === 0) { SWUDealDamageToUnit($lastDecision, $sum, intval($player)); return; }
    // Otherwise the same single event as two shares on the one target, through the divided-damage funnel (all damage
    // applied before any defeat): the unpreventable share sourced by an Underworld Vehicle, the rest unsourced as before.
    // That funnel never offers HMW_185 Ty Yorrick, so it is asked here — owner ruling 2026-10-07: ONE +1 on the event
    // total, riding the unpreventable share.
    $tok = _SWUEncodeDamageSource($unprevSrc);
    $tuid = intval($obj->UniqueID ?? 0);
    $ty = _SWUHmw185Decider(intval($player));
    if ($ty > 0) { _SWUHmw185Defer($ty, "JTL_129#1|" . intval($player) . "|{$tuid}|{$unprev}|" . ($sum - $unprev) . "|{$tok}"); return; }
    _SWUJtl129Shares(intval($player), $tuid, $unprev, $sum - $unprev, $tok);
};

// Resume after Ty's question. parts: dealer seat | target UID | unpreventable share | the rest | source token.
$customDQHandlers["JTL_129#1"] = function($player, $parts, $lastDecision) {
    $unprev = intval($parts[2] ?? 0) + (_SWUHmw185Accepted($lastDecision) ? 1 : 0);
    _SWUJtl129Shares(intval($parts[0] ?? 0), intval($parts[1] ?? 0), $unprev, intval($parts[3] ?? 0), (string)($parts[4] ?? ''));
};

function _SWUJtl129Shares(int $player, int $targetUID, int $unprev, int $rest, string $tok): void {
    global $playerID;
    if ($player <= 0) return;
    $playerID = $player;
    $mz = SWUFindMzByUID($targetUID);
    if ($mz === null) return;                                   // the target left play while Ty's question was pending
    $assign = [];
    if ($unprev > 0) $assign[] = "{$mz}:{$unprev}:{$tok}";
    if ($rest > 0)   $assign[] = "{$mz}:{$rest}";
    if (!empty($assign)) SWUDealSplitDamage($player, implode(',', $assign));
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_129:0"] = function($player, $mzID = '') {
// Focus Fire — "Choose a unit. Each friendly Vehicle unit in the same arena
                          // deals damage equal to its power to that unit."
            global $playerID;
            $playerID = intval($player);
            // A unit is only a legal target if at least one friendly Vehicle shares its arena — otherwise
            // the effect would deal 0 damage, and such a zero-effect selection is disallowed.
            $friendlyVehicleIn = function(string $arenaZone): bool {
                foreach (ZoneSearch($arenaZone, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Vehicle')) return true;
                }
                return false;
            };
            $targets = [];
            if ($friendlyVehicleIn('teamGroundArena')) {
                // ⚠ UNQUALIFIED pool = the WHOLE table, so the own side is 'team*', not 'my*': `their*` is the
                // OPPONENT fan-out and excludes a teammate, so my*+their* leaves a teammate's units in NEITHER
                // list. 'team*' degrades to 'my*' outside a team game (Premier byte-identical).
                $targets = array_merge($targets, ZoneSearch('teamGroundArena', AnyUnitFilter), ZoneSearch('theirGroundArena', AnyUnitFilter));
            }
            if ($friendlyVehicleIn('teamSpaceArena')) {
                $targets = array_merge($targets, ZoneSearch('teamSpaceArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter));
            }
            $targets = array_values($targets);
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Each_friendly_Vehicle_in_that_arena_deals_its_power", "JTL_129#0");
            return;
};
