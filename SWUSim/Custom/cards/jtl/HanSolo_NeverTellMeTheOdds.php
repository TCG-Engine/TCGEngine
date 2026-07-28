<?php
// JTL_017
// Cost 5 - Han Solo - Never Tell Me the Odds - [Cunning,Heroism] - Power 3 - HP 7 - Upgrade Power 3 - Upgrade HP 4
// Text: Action [Exhaust]: Reveal the top card of your deck, then attack with a unit. If the revealed card and that unit have different odd costs, that unit gets +1/+0 for this attack.
// DeployText: / Attached unit is a leader unit. / When deployed as an upgrade: For each friendly unit or upgrade that has an odd cost, ready a resource. /
// Epic Action: If you control 5 or more resources, choose one: / Deploy this leader. / Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.

// ── JTL_017 Han Solo (leader action: reveal top, attack; +1/+0 if different odd costs) ──────────────
// $parts[0] = the revealed card's cost. $lastDecision = the chosen attacker. If the revealed cost and
// the attacker's cost are BOTH odd and DIFFERENT, grant +1/+0 for this attack; then begin the attack.
$customDQHandlers["JTL_017#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) {
        SWUAfterAction(intval($player));
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $revealedCost = intval($parts[0] ?? -1);
    $obj = GetZoneObject($lastDecision);
    $unitCost = ($obj !== null) ? intval(CardCost($obj->CardID)) : -1;
    $bothOdd  = ($revealedCost % 2 !== 0) && ($unitCost % 2 !== 0) && $revealedCost >= 0 && $unitCost >= 0;
    if ($bothOdd && $revealedCost !== $unitCost) {
        SWUAddAttackPowerBonus($lastDecision, 1);
    }
    BeginSWUAttack(intval($player), $lastDecision);
};

// JTL_017 Han Solo — When deployed as an upgrade: For each friendly unit or upgrade that has an odd
// cost, ready a resource. (Counts friendly units in play + their non-token upgrades, incl. the
// just-attached leader-pilot itself.)
$whenPlayedAsUpgradeAbilities["JTL_017:0"] = function($player, $mzID) {
    $cnt = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        if (intval(CardCost($u->CardID ?? '')) % 2 === 1) $cnt++;
        foreach (GetUpgradesOnUnit($u) as $up) {
            if (intval(CardCost($up->CardID ?? '')) % 2 === 1) $cnt++;
        }
    }
    if ($cnt > 0) SWUReadyResources(intval($player), $cnt);
};

// JTL_017 Han Solo — Leader Action [Exhaust]: Reveal the top card of your deck, then attack with a
// unit. If the revealed card and that unit have different odd costs, that unit gets +1/+0 for this
// attack. Reveal here (read top cost), then choose the attacker; the continuation evaluates the
// odd-cost condition and begins the attack.
$leaderAbilities["JTL_017"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $deck = GetDeck($player);
    $revealedCost = -1;
    foreach ($deck as $c) {
        if (!empty($c->removed)) continue;
        $revealedCost = intval(CardCost($c->CardID));
        AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . GameLogCardRef($c->CardID));
        break;
    }
    $attackers = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }));
    if (empty($attackers)) { SWUAfterAction($player); return; } // no ready unit to attack → fizzle
    SWUQueueChooseTarget($player, $attackers,
        "Attack_with_a_unit_(+1/+0_if_different_odd_costs)", "JTL_017#0|" . $revealedCost);
};
