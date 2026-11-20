<?php
// IC27_113
// Cost 2 - Ezra Bridger - Down With The Freedom - [Aggression,Heroism] - Unit (Ground) 1/3 (unique)
//   Traits: Force, Jedi, Rebel, Spectre
// Text: Raid 2 (This unit gets +2/+0 while attacking.)
//       On Attack: Give another friendly Heroism unit Raid 2 for this phase.
//
// Raid 2 is printed ($Raid_Cards). The On Attack is MANDATORY (no "you may") and the pool is "another friendly
// Heroism unit": the team's units (a Team Suns teammate's included), any arena, leader units and tokens included,
// Ezra excluded by UniqueID; Heroism is an ASPECT. One legal unit auto-resolves; none fizzles.
// ⚠ Routed through an intermediate CUSTOM: a mandatory pick queued straight from an On Attack closure is silently
// skipped when OnAttackTrigger's restored $playerID is not the attacker (it restores before MZCountChoices runs) —
// the IC27_104 shape. Measured 2026-10-30: queuing it in the closure is green on every board in this card's tests,
// because there the saved $playerID already IS the attacker; the routing guards the boards where it is not.
// The grant is the registered GRANT_KEYWORD_VALUE row 'IC27_113' (Raid 2, phase). Raid stacks — every instance is
// summed (user ruling 2026-09-10, CR 7.5.8.b) — so each grant is its own token, IC27_113-<n>: AddTurnEffect drops an
// identical token, and a second grant to the same unit (Ezra readied and attacking again) must be a second Raid 2.
$onAttackAbilities["IC27_113:0"] = function($player, $mzID) {
    $uid = SWUObjUID(GetZoneObject($mzID));
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "IC27_113#0|{$uid}", 1);
};

$customDQHandlers["IC27_113#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'IC27_113#1', 'side' => 'friendly',
        'excludeUID' => intval($parts[0] ?? 0),
        'extraFilter' => fn($o) => strpos((string)CardAspect($o->CardID), 'Heroism') !== false,
        'prompt' => "Choose_another_friendly_Heroism_unit_to_gain_Raid_2_for_this_phase",
    ]);
};

$customDQHandlers["IC27_113#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if ($o === null || !empty($o->removed)) return;
    $n = 0;
    foreach ((array)($o->TurnEffects ?? []) as $te) if (strpos((string)$te, 'IC27_113') === 0) $n++;
    AddTurnEffect($lastDecision, 'IC27_113-' . ($n + 1));
};
