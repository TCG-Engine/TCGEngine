<?php
// JTL_039
// Cost 6 - Chimaera - Reinforcing the Center - [Vigilance,Villainy] - Power 5 - HP 6
// Text: When Played: You may use a "When Defeated" ability on another friendly unit. / When Defeated: Create 2 TIE Fighter tokens.

// ── JTL_039 Chimaera — When Played: You may use a "When Defeated" ability on another friendly unit. ──
// Offers any other friendly unit that has a When-Defeated ability; the chosen one's ability fires
// (the unit stays in play). Reuses the SWUUseWhenDefeatedAbility primitive (Phase 23).
$whenPlayedAbilities["JTL_039:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = $mzID;
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        if ($mz === $self) continue;
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        // A unit qualifies on its INNATE When Defeated or on any it has GAINED (attached upgrade,
        // phase effect, or a field-presence granter) — see _SWUGrantedWhenDefeatedTypes().
        if (!HasWhenDefeatedAbility($o->CardID)
            && empty(_SWUGrantedWhenDefeatedTypes(intval($player), $mz))) continue;
        $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Use_a_When_Defeated_ability_on_another_friendly_unit",
        "Use_a_When_Defeated_ability_on_another_friendly_unit", "JTL_039#0");
};

$customDQHandlers["JTL_039#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    // The chosen unit may hold MORE than one "When Defeated" — its innate one plus any it gained.
    // Innate resolves under the generic 'WhenDefeated' window (grantedType null); each granted one
    // dispatches under its own per-card trigger type.
    $granted = _SWUGrantedWhenDefeatedTypes(intval($player), $lastDecision);
    $options = [];
    if (HasWhenDefeatedAbility($obj->CardID)) $options[] = null;
    foreach ($granted as $g) $options[] = $g;
    if (empty($options)) return;
    if (count($options) === 1) {
        SWUUseWhenDefeatedAbility(intval($player), $obj->CardID, $lastDecision, $options[0]);
        return;
    }
    // More than one available — let the controller pick which ability to use.
    SWUQueueChooseWhenDefeatedAbility(intval($player), $obj->CardID, $lastDecision, $options);
};

// JTL_039 continuation — the controller picked WHICH When Defeated ability to use on the chosen
// unit (label = the CardID supplying it; the host's own CardID means its innate one).
$customDQHandlers["JTL_039#1"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $owner      = intval($parts[0] ?? $player);
    $hostCardID = $parts[1] ?? '';
    $mzID       = $parts[2] ?? '';
    if ($hostCardID === '' || $mzID === '') return;
    SWUUseWhenDefeatedAbility($owner, $hostCardID, $mzID, ($lastDecision === $hostCardID) ? null : $lastDecision);
};

// JTL_039 — When Defeated: Create 2 TIE Fighter tokens.
$whenDefeatedAbilities["JTL_039:0"] = function($player, $mzID) {
    SWUCreateUnitTokens(intval($player), 'JTL_T01', 2);
};
