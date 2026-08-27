<?php
// HMW_263
// Cost 6 - Wrecker, Wrecking the Empire - [Heroism] - Power 6 - HP 6 - Clone
// Text: When Played: Each player chooses a unit they control. Deal 3 damage to each chosen unit.

// ─── HMW_263 Wrecker, Wrecking the Empire ────────────────────────────────────
// Structurally LAW_099 Governor's Shuttle ("Each player chooses a unit they control. Defeat those
// units.") with damage instead of a defeat; LOF_177 Time of Crisis is the same sentence INVERTED
// ("...deal 3 damage to each unit NOT chosen"). Reusing LAW_099's chain rather than re-deriving it.
//
// "EACH PLAYER chooses" is a LOOP over every LIVE seat, caster first — never "you and an opponent".
// A two-seat chain would silently skip seats 3 and 4 in Twin Suns and their units would take nothing.
//
// The walk is a QUEUED CHAIN, not a loop, because each pick is interactive. Everything the chain needs
// — the caster, the UIDs chosen so far, and the seats still to ask — rides the continuation's own Param:
// a positional mzID would be stale by the time the next seat answers, and an in-memory accumulator would
// be EMPTY across the request boundary (this chain spans one request per answering seat in production).
//
// "A unit THEY CONTROL" scopes each seat's pool to its own board (control, not ownership — a stolen unit
// belongs to the thief's pool), and carries NO "non-leader" restriction — contrast TWI_238 Merciless
// Contest, one sentence away in the same family, which does print it. So a deployed leader unit is a
// legal choice. It also carries no "another", and Wrecker is already in play when its own When Played
// resolves, so Wrecker itself is a legal choice for its controller.
//
// A seat controlling nothing is skipped silently; a seat with exactly one unit auto-resolves through
// SWUQueueChooseTarget's PASSPARAMETER shortcut. The choose is MANDATORY — no "may", no "up to".

if (!function_exists('_SWUHmw263Ask')) {
    function _SWUHmw263Ask(int $caster, array $remaining, array $uids): void {
        global $playerID;
        while (!empty($remaining)) {
            $seat = intval(array_shift($remaining));
            $playerID = $seat;                   // that seat's OWN units, resolved in its own frame
            $units = SWUAllUnits('my');
            if (empty($units)) continue;         // controls nothing → nothing to choose
            SWUQueueChooseTarget($seat, $units, "Choose_a_unit_you_control_to_take_3_damage",
                "HMW_263#PICK|{$caster}|" . implode(',', $uids) . '|' . implode(',', $remaining));
            return;                              // resumes in HMW_263#PICK once this seat answers
        }
        _SWUHmw263Damage($caster, $uids);        // everyone has been asked
    }
}

// Deal the 3 to every chosen unit at ONE point, once all seats have picked — that is what makes the
// choices simultaneous, and it is why nothing is damaged during the walk above.
// Each unit is re-resolved BY UID under the caster's frame: the mzIDs minted when each seat chose are
// long stale, and a chosen unit that dies to its own 3 compacts its arena underneath the next lookup.
// Damage goes through SWUDealDamageToUnit so it runs the full ability-damage chain — Shields, "can't be
// damaged" immunity, the reduction cards, the animation, and the "when this unit is dealt damage"
// observers (HMW_211 Tech among them).
// ⚠ The window: "deal 3 damage to each chosen unit" is ONE ability dealing damage simultaneously (cf.
// the official Rancor Keeper ruling, 07/21/2026: "All damage dealt by Rancor Keeper's ability is dealt
// simultaneously"), so two chosen units that die here die in the SAME batch. SWUSimulDefeatBegin/End
// freeze the per-seat observer counts across the loop so a "when a unit is defeated" observer that is
// itself one of the victims still sees its co-victim — the LOF_130 / ASH_052 same-batch family.
if (!function_exists('_SWUHmw263Damage')) {
    function _SWUHmw263Damage(int $caster, array $uids): void {
        global $playerID; $playerID = $caster;
        SWUSimulDefeatBegin();
        foreach ($uids as $uid) {
            $uid = intval($uid);
            if ($uid <= 0) continue;
            $mz = SWUFindMzByUID($uid);
            if ($mz !== null) SWUDealDamageToUnit($mz, 3, $caster);
        }
        SWUSimulDefeatEnd();
    }
}

$whenPlayedAbilities["HMW_263:0"] = function($player, $mzID) {
    _SWUHmw263Ask(intval($player), SWUSeatsInPlayerOrder(intval($player)), []);
};

$customDQHandlers["HMW_263#PICK"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster    = intval($parts[0] ?? $player);
    $uids      = array_values(array_filter(explode(',', (string)($parts[1] ?? '')), fn($v) => $v !== ''));
    $remaining = array_values(array_filter(explode(',', (string)($parts[2] ?? '')), fn($v) => $v !== ''));
    if (!SWUDecisionDeclined($lastDecision)) {
        $playerID = intval($player);             // the mzID was minted in THIS seat's frame
        $o = GetZoneObject($lastDecision);
        if ($o !== null && empty($o->removed)) $uids[] = intval($o->UniqueID ?? 0);
    }
    _SWUHmw263Ask($caster, $remaining, $uids);
};
