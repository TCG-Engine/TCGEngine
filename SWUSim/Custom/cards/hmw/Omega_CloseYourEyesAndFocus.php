<?php
// HMW_006
// Cost 5 - Omega - Close Your Eyes and Focus - [Vigilance,Heroism] - Leader (deployed 2/7 Ground)
// Traits: Clone - Unique
// Text:       Action [1 resource, Exhaust]: Attack with a Heroism unit. It gains Grit for this attack.
//             Epic Action: If you control 5 or more resources, deploy this leader.
// DeployText: Other friendly Heroism units gains Grit.
//
// Epic deploy needs no code: SWUDeployLeader gates on the printed cost (5).
// "Heroism" is an ASPECT, not a trait — read off the unit's aspect icons (SWUCardAspectIcons), which for a
// TWI_116 Clone copy is the copied card's, because the in-play CardID IS the copy.
//
// FRONT — TWI_172 Grim Resolve's shape ("Attack with a non-leader unit. It gains Grit for this attack."):
// an attack-duration GRIT grant (SWU_DUR_ATTACK, so it expires when the attack ends — even mid-phase),
// then BeginSWUAttack, which owns the Action's close once the attack starts.
//   • "Attack with a Heroism unit" names no "even if it's exhausted", so the pool is READY units — and
//     units YOU control: you can only attack with your own, so the front is self-only in every format.
//   • No "may": a mandatory pick (a lone unit auto-resolves). The cost ([1 resource, Exhaust]) is
//     state-changing, so with no ready Heroism unit the Action is still legal and resolves to nothing.
//
// DEPLOYED — "Other friendly Heroism units gain Grit": a continuous grant, read live in
// HasConditionalKeyword_Grit (KeywordEffects.php) through _SWUHmw006GrantsGrit below. Recomputed on every
// read, so it ends the moment Omega leaves play or loses her abilities, and it needs no cleanup.
//   • "friendly" is the TEAM in Team Suns — a teammate's Heroism unit gains it too;
//   • "Other" — never Omega herself (she is Heroism).

function _SWUHmw006IsHeroism($obj): bool {
    return !SWUObjGone($obj) && in_array('Heroism', SWUCardAspectIcons((string)($obj->CardID ?? '')), true);
}

// ── FRONT: Action [1 resource, Exhaust] ─────────────────────────────────────────────────────────────
function _SWUHmw006Attackers(int $player): array {
    return _SWUCollectUnitTargets(intval($player), ['side' => 'my', 'extraFilter' => fn($u) =>
        intval($u->Status ?? 0) === 1 && _SWUHmw006IsHeroism($u) && !_SWUUnitHardCantAttack($u)]);
}

$leaderActionResourceCosts["HMW_006"] = 1;
$leaderAbilities["HMW_006"] = function (int $player): void {
    global $playerID; $playerID = $player;
    $pool = _SWUHmw006Attackers($player);
    if (empty($pool)) { SWUAfterAction($player); return; }   // soft pass — the cost is already paid
    SWUQueueChooseTarget($player, $pool,
        "Attack_with_a_Heroism_unit_(it_gains_Grit_for_this_attack)", 'HMW_006#0');
};

$customDQHandlers["HMW_006#0"] = function ($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || SWUObjGone(GetZoneObject((string)$lastDecision))) {
        SWUAfterAction(intval($player));
        return;
    }
    AddTurnEffect((string)$lastDecision, SWUMakeTurnEffect('GRIT', [], SWU_DUR_ATTACK));
    BeginSWUAttack(intval($player), (string)$lastDecision);   // the attack owns the close from here
};

// ── DEPLOYED: "Other friendly Heroism units gain Grit." ─────────────────────────────────────────────
// True when $obj is a Heroism unit and a deployed Omega with her abilities active sits on its team.
function _SWUHmw006GrantsGrit($obj): bool {
    if (SWUObjGone($obj) || ($obj->CardID ?? '') === 'HMW_006') return false;   // "Other"
    if (!_SWUHmw006IsHeroism($obj)) return false;
    $ctrl = intval($obj->Controller ?? 0);
    if ($ctrl <= 0) return false;
    foreach (array_merge([$ctrl], SWUTeammatesOf($ctrl)) as $seat) {          // "friendly" = the team
        foreach (GetUnitsInPlay($seat) as $u) {
            if (($u->CardID ?? '') === 'HMW_006' && empty($u->removed) && !LostAbilities($u)) return true;
        }
    }
    return false;
}
