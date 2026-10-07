<?php
// HMW_114
// Cost 2 - Breach - [Command][Villainy] - Event - Traits: Tactic
// Text: A friendly unit deals damage equal to its power to an enemy unit in its arena.
//       If the friendly unit has Overwhelm, deal excess damage to an enemy base.
// (The mock text reads "deal deal excess damage" — a duplication typo in the card text.)
//
// Three steps, because the SECOND target pool depends on the FIRST pick (the enemy must share the
// dealer's arena). Mirrors LAW_168 Haymaker, the existing "that unit deals damage equal to its power
// to an enemy unit in the same arena" card.
//
// The effect is MANDATORY (no "may"), so both picks are SWUQueueChooseTarget — which auto-resolves via
// PASSPARAMETER when only one target is legal.
//
// STEP 0 — choose the dealer. Two exclusions keep zero-effect selections out of the offer, per the
// house rule that a selection which cannot change the game state is not offered (the JTL_129 Focus
// Fire precedent):
//   • a friendly unit with NO enemy in its own arena has nothing to hit;
//   • a 0-power friendly unit would deal 0 damage.
$whenPlayedAbilities["HMW_114:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $dealers = [];
    // ⚠ UNQUALIFIED pool = the WHOLE table, so the own-side zones are 'team*', not 'my*': in a
    // team game `their*` is the OPPONENT fan-out and excludes a teammate, so my*+their* leaves a
    // teammate's units in NEITHER list. 'team*' degrades to 'my*' outside a team game, leaving
    // Premier byte-identical. Same defect as SWUAllUnits() documents for the helper form.
    foreach ([['teamGroundArena', 'theirGroundArena'], ['teamSpaceArena', 'theirSpaceArena']] as [$mine, $theirs]) {
        if (empty(ZoneSearch($theirs, AnyUnitFilter))) continue;   // no enemy in this arena
        foreach (ZoneSearch($mine, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            if (intval(ObjectCurrentPower($o)) <= 0) continue;     // 0 power → nothing to deal
            $dealers[] = $mz;
        }
    }
    if (empty($dealers)) return;
    SWUQueueChooseTarget(intval($player), $dealers,
        'Choose_a_friendly_unit_to_deal_damage_equal_to_its_power', 'HMW_114#0');
};

// STEP 1 — the dealer is known; offer the enemy units in ITS arena only.
// Power and the Overwhelm flag are read here and ride the continuation's Param, so they survive the
// request boundary the next decision creates (an in-memory global would be empty in the next request).
$customDQHandlers["HMW_114#0"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $dealer = GetZoneObject($lastDecision);
    if (SWUObjGone($dealer)) return;
    $power = intval(ObjectCurrentPower($dealer));                  // CURRENT power, incl. upgrades/buffs
    if ($power <= 0) return;
    $hasOverwhelm = HasKeyword_Overwhelm($dealer) ? 1 : 0;
    $enemyZone = (strpos((string)$lastDecision, 'Space') !== false) ? 'theirSpaceArena' : 'theirGroundArena';
    $enemies = ZoneSearch($enemyZone, AnyUnitFilter);
    if (empty($enemies)) return;
    SWUQueueChooseTarget(intval($player), $enemies,
        "Deal_{$power}_damage_to_an_enemy_unit_in_that_arena", "HMW_114#1|{$power}|{$hasOverwhelm}|" . _SWUEncodeDamageSource((string)$lastDecision));
};

// STEP 2 — deal the damage, then the Overwhelm rider.
//
// ⚠ The excess is derived from what the damage ACTUALLY did, never from power-minus-printed-HP. A
// Shield token absorbs the whole instance, so the target survives untouched and there is no excess to
// spill — computing it arithmetically would hand the base 3 damage off an attack that dealt none.
// Surviving the hit for ANY reason means the damage was not lethal, hence no excess by definition, so
// "did it leave play?" is the whole test.
$customDQHandlers["HMW_114#1"] = function ($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $power        = intval($parts[0] ?? 0);
    $hasOverwhelm = intval($parts[1] ?? 0) === 1;
    $target = GetZoneObject($lastDecision);
    if (SWUObjGone($target) || $power <= 0) return;

    $uid          = intval($target->UniqueID ?? 0);
    $remainBefore = intval(ObjectCurrentHP($target)) - intval($target->Damage ?? 0);
    // ⚠ Overwhelm spills into the base of the seat whose unit just died — a DETERMINED seat, named by the
    // mzID we already hold. OtherPlayer() sent it to seat 2, so above two seats killing a seat-4 unit
    // splashed a bystander's base while seat 4 took nothing.
    $baseSeat     = SWUMzOwner((string)$lastDecision, intval($player));

    if ($uid > 0) SetSWUVar("SWU_TY_DEALT_UID_{$uid}", '');
    $tyDecider = _SWUHmw185Decider(intval($player));
    // The dealer is the damage's SOURCE (CR 18.2a) — ASH_196 Gorian's unpreventable, SEC_050, LOF_108, HMW_185 read it (2026-10-07).
    SWUDealDamageToUnit((string)$lastDecision, $power, intval($player), _SWUDecodeDamageSource((string)($parts[2] ?? '')));

    if (!$hasOverwhelm) return;
    $playerID = intval($player);
    $rider = "HMW_114#2|{$uid}|{$remainBefore}|{$power}|{$baseSeat}";
    // HMW_185 Ty Yorrick DEFERS the unit damage behind a "+1?" question (on Ty's controller's queue), so the target is
    // still alive right now. The rider must run AFTER that question resolves, on the same queue — checking now saw
    // a living target and dropped the excess.
    if ($tyDecider > 0 && $power > 0) {
        DecisionQueueController::AddDecision($tyDecider, 'CUSTOM', $rider, 1, dontSkipOnPass: 1);
        return;
    }
    $customDQHandlers = $GLOBALS['customDQHandlers'];
    ($customDQHandlers['HMW_114#2'])(intval($player), explode('|', substr($rider, strlen('HMW_114#2|'))), '');
};

// The Overwhelm rider: "If the friendly unit has Overwhelm, deal excess damage to an enemy base."
// $parts: [target UID, its remaining HP before the hit, Breach's damage, the base's seat].
// ⚠ The excess is derived from what the damage ACTUALLY did. A target that survived (a Shield absorbed it, or it was
// not lethal) leaves no excess. The amount dealt is Breach's power, or what Ty Yorrick turned it into (+1).
$customDQHandlers["HMW_114#2"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $uid          = intval($parts[0] ?? 0);
    $remainBefore = intval($parts[1] ?? 0);
    $power        = intval($parts[2] ?? 0);
    $baseSeat     = intval($parts[3] ?? 0);
    $dealer       = intval($player);
    $playerID = $dealer;
    if ($uid > 0 && SWUFindMzByUID($uid) !== null) return;         // survived → prevented or non-lethal
    $tyDealt = ($uid > 0) ? GetSWUVar("SWU_TY_DEALT_UID_{$uid}", '') : '';
    $dealt   = ($tyDealt !== '') ? intval($tyDealt) : $power;
    $excess  = max(0, $dealt - $remainBefore);
    if ($excess > 0 && $baseSeat > 0) SWUDealDamageToBase($excess, $baseSeat, $dealer);
};
