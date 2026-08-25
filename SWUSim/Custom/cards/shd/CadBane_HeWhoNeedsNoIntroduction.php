<?php
// SHD_014
// Cost 6 - Cad Bane - He Who Needs No Introduction - [Cunning,Villainy] - Power 2 - HP 8
// Text: When you play an Underworld card: You may exhaust this leader. If you do, an opponent chooses a unit they control. Deal 1 damage to it.
// DeployText: Raid 2 (This unit gets +2/+0 while attacking.) / When you play an Underworld card: You may choose an opponent. They choose a unit they control. Deal 2 damage to it. Use this ability only once each round.
// Epic Action: If you control 6 or more resources, deploy this leader.

// Every live opponent of $player who controls at least one unit. THE gate for this card, used in both
// directions: it decides whether the reaction is offered at all, and it is the menu of opponents the
// controller may pick from — so a pick can never fizzle against an empty board.
// ⚠ Was OtherPlayer($player), the legacy `$player === 1 ? 2 : 1`. At four seats that names one seat and
//   ignores the rest, which is the reported "the ping always went to Player 1" bug.
if (!function_exists('_SWUShd014OpponentsWithUnits')) {
    function _SWUShd014OpponentsWithUnits(int $player): array {
        global $playerID;
        $saved = $playerID;
        $out = [];
        foreach (OpponentsOf(intval($player)) as $opp) {
            $playerID = $opp;   // resolve "my…" as THAT seat's own board
            $has = false;
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed)) { $has = true; break 2; }
                }
            }
            if ($has) $out[] = $opp;
        }
        $playerID = $saved;
        return $out;
    }
}

// Which side of Cad Bane can react right now, and therefore what the reaction costs and deals:
//   'deployed' → free, 2 damage, but only ONCE EACH ROUND (the leader unit's NumUses budget)
//   'front'    → costs an exhaust of the ready undeployed leader, 1 damage, no per-round cap
//   null       → neither (deployed but already used this round, or front but already exhausted)
// ⚠ The two sides are SEPARATE ability sets that happen to share a trigger. The deployed one was never
//   implemented at all: the collection site gated on _SWULeaderReadyUndeployed, so a deployed Cad Bane
//   simply never fired. Keeping both behind one mode function is what stops them drifting again.
if (!function_exists('_SWUShd014Mode')) {
    function _SWUShd014Mode(int $player): ?string {
        if (_SWULeaderDeployed($player, 'SHD_014')) {
            return SWUHasUseAvailable(SWUGetLeader($player)) ? 'deployed' : null;
        }
        return _SWULeaderReadyUndeployed($player, 'SHD_014') ? 'front' : null;
    }
}

// The picked opponent now chooses one of THEIR OWN units; $parts[1] is how much it then takes (1 from
// the front side, 2 from the deployed one). The choice is queued on THAT SEAT's queue — the whole point
// of the fix, since a chooser derived from OtherPlayer() puts it in front of the wrong player.
$customDQHandlers["SHD_014#OPP"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($player);
    $amount = max(1, intval($parts[0] ?? 1));
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $playerID = $opp;   // the opponent chooses among THEIR own units ("my..." from their frame)
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $targets[] = $mz; }
    }
    if (empty($targets)) return;
    DecisionQueueController::AddDecision($opp, 'MZCHOOSE', implode('&', $targets), 1, tooltip:"Choose_a_unit_you_control");
    DecisionQueueController::AddDecision($opp, 'CUSTOM', "SHD_014#0|{$caster}|{$amount}", 1);
    // leave $playerID = $opp so MZCountChoices resolves the relative mzIDs under the opponent
};

$customDQHandlers["SHD_014#exhaust"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $mode = _SWUShd014Mode(intval($player));
    if ($mode === null) return;                 // exhausted / already used since the offer was queued
    $eligible = _SWUShd014OpponentsWithUnits(intval($player));
    if (empty($eligible)) return;               // nothing to hit — do not spend the cost
    // Pay ONLY here, on an accepted "you may" with a legal target: a cost taken at offer time would
    // charge the player for declining (the deployed side's once-per-round is the visible half of that).
    if ($mode === 'deployed') {
        SWUConsumeUse(SWUGetLeader(intval($player)));   // "use this ability only once each round"
    } else {
        $leaderArr = &GetLeader(intval($player));
        foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'SHD_014' && empty($l->removed)) { $l->Ready = false; break; } }  // exhaust the leader (cost)
        unset($l);
    }
    $amount = ($mode === 'deployed') ? 2 : 1;
    $playerID = intval($player);
    // "An opponent chooses a unit they control" — the FRONT text does not spell out who names the
    // opponent, but the DEPLOYED half of this same card says "You may CHOOSE an opponent", so the card
    // settles its own wording: Cad Bane's controller picks. Auto-resolves silently at two seats.
    SWUQueueChooseOpponent(intval($player), "SHD_014#OPP|{$amount}", "Which_opponent_chooses_a_unit?", $eligible);
};

$customDQHandlers["SHD_014#0"] = function($opp, $parts, $lastDecision) {
    global $playerID;
    $caster = intval($parts[0] ?? 0);
    $amount = max(1, intval($parts[1] ?? 1));
    if ($caster <= 0) return;                    // the caster ALWAYS rides the param; no seat guessing
    if (SWUDecisionDeclined($lastDecision)) return;
    $playerID = intval($opp);                        // resolve the chosen mzID in the opponent's frame → UID
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    $playerID = $caster;
    $mz = SWUFindMzByUID($uid);                       // UID is frame-independent
    if ($mz !== null) SWUDealDamageToUnit($mz, $amount, $caster);
};

function Shd014UnderworldReaction($player): void
{
  global $playerID;
  $playerID = intval($player);
  $mode = _SWUShd014Mode(intval($player));
  if ($mode === null)
    return;
  // ⚠ EVERY opponent, not just OtherPlayer()'s one: with seat 2 empty but seat 3 holding a unit, the
  // old check saw an empty board and silently never offered the reaction at all.
  if (empty(_SWUShd014OpponentsWithUnits(intval($player))))
    return;   // no enemy unit anywhere to damage → don't offer a reaction that could only fizzle
  $tip = $mode === 'deployed'
    ? "Deal_2_to_a_unit_an_opponent_chooses?"
    : "Exhaust_Cad_Bane_to_deal_1_to_an_opponent's_unit?";
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: $tip);
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_014#exhaust", 1);
}
