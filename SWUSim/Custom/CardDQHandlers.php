<?php
// SWU card-specific DQ handlers and trigger ability closures.
// This file owns all ability arrays. The generator produces Has*Ability() checks;
// implementations live here and survive generator runs.

global $whenPlayedUsingSmuggleAbilities, $whenPlayedAsUpgradeAbilities;
global $whenPlayedAbilities, $whenDefeatedAbilities;
global $onAttackAbilities, $onDefenseAbilities, $onAttackEndAbilities, $onAttackEndFromUpgradeAbilities, $onAttackedFromUpgradeAbilities, $onDefenseFromUpgradeAbilities;
global $unitAbilities, $unitActionResourceCosts, $unitActionCostKind;
global $onAttachedAbilities;

$whenPlayedUsingSmuggleAbilities = [];
$whenPlayedAsUpgradeAbilities = [];
$whenPlayedAbilities = [];
$whenDefeatedAbilities = [];
$onAttackAbilities = [];
$onDefenseAbilities = [];
$onAttackEndAbilities = [];
$onAttachedAbilities = [];

// ── Batch 4.3: shield / heal triggers ───────────────────────────────────────
// Shared helper: all non-removed units of either player matching $pred(object),
// excluding the unit with UniqueID $excludeUID ("another …"). Caller sets $playerID.
function _SWUCollectUnits(int $excludeUID, callable $pred): array
{
  $out = [];
  foreach (array_merge(
    ZoneSearch('myGroundArena', AnyUnitFilter),
    ZoneSearch('mySpaceArena', AnyUnitFilter),
    ZoneSearch('theirGroundArena', AnyUnitFilter),
    ZoneSearch('theirSpaceArena', AnyUnitFilter)
  ) as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o))
      continue;
    if (intval($o->UniqueID ?? -2) === $excludeUID)
      continue;
    if ($pred($o))
      $out[] = $mz;
  }
  return $out;
}

$customDQHandlers["DEFEAT_CREDIT_TOKEN"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
    return;
  global $playerID;
  $playerID = intval($player);
  SWUDefeatCreditToken($lastDecision);
};


// ── "Exhaust any number of units with a combined {metric} ≤ budget" ── ONE weighted multi-select ────
// $metric ∈ {power, cost}. The player gets a single modal over every ready unit that fits the budget on
// its own, with a live "N of B <metric> left" counter; anything that no longer fits greys out as picks
// are made, and one Confirm resolves the lot. $loseAbil: if 1 and the player controls a Force unit, each
// picked unit loses its abilities this phase (LOF_202 Mind Trick's Force rider).
// Callers: LOF_201 Qui-Gon Jinn's Lightsaber (cost 6), LOF_202 Mind Trick (power 4).
// This replaced a re-offered one-at-a-time MZMAYCHOOSE loop — see SWUQueueBudgetMultiChoose.
function _SWUCombinedBudgetOffer(int $player, int $budget, string $metric, int $loseAbil): void
{
  $weights = _SWUBudgetExhaustWeights($player, $metric);
  if (empty($weights))
    return;
  SWUQueueBudgetMultiChoose(
    $player,
    $weights,
    $budget,
    $metric,
    "Exhaust_any_number_of_units_with_{$budget}_or_less_combined_{$metric}",
    "SWU_BUDGET_EXHAUST|{$budget}|{$metric}|{$loseAbil}"
  );
}

// Every READY unit's weight under $metric, keyed by mzID — measured fresh, so it serves both the offer
// and the answer-time re-validation. Only ready units are included: an exhausted one cannot be exhausted.
function _SWUBudgetExhaustWeights(int $player, string $metric): array
{
  global $playerID;
  $playerID = $player;
  $out = [];
  foreach (SWUAllUnits() as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o))
      continue;
    if (intval($o->Status ?? 0) !== 1)
      continue; // only ready units can be exhausted
    $out[$mz] = ($metric === 'cost') ? intval(CardCost($o->CardID ?? '')) : intval(ObjectCurrentPower($o));
  }
  return $out;
}

$customDQHandlers["SWU_BUDGET_EXHAUST"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $budget = intval($parts[0] ?? 0);
  $metric = $parts[1] ?? 'power';
  $loseAbil = intval($parts[2] ?? 0);
  // Re-measure and re-cap server-side — the modal's budget is UX, and a scripted answer never touches it.
  $picked = SWUFilterBudgetAnswer($lastDecision, _SWUBudgetExhaustWeights(intval($player), $metric), $budget);
  foreach ($picked as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o))
      continue;
    $o->Status = 0; // exhaust
    if ($loseAbil && PlayerHasUnitWithTraitInPlay(intval($player), 'Force', -1)) {
      AddTurnEffect($mz, 'SOR_138'); // loses all abilities this phase (registry LOSE_ABILITIES)
    }
  }
};

// ── Reactive draw hook: "When an opponent draws 1+ cards during the action phase, you may give an
// Experience token to a unit." (JTL_111 Seasoned Fleet Admiral. SHD_184 Bazine Netal was wrongly
// listed here — her real ability is a When Played, see CardDQHandlers SHD_184.) For each such unit
// the drawing player's OPPONENT controls, offer one may-give. ────────────────────────────────────────
function _SWUOnPlayerDrew(int $drawingPlayer, int $count): void
{
  if ($count < 1)
    return;
  // ASH_169 Axe Woves — "When you draw 1 or more cards (including during the regroup phase): give an
  // Advantage token to this unit." Fires in ANY phase (before the MAIN gate). Non-interactive.
  {
    global $playerID;
    $savedDraw = $playerID;
    $playerID = intval($drawingPlayer);
    foreach (GetUnitsInPlay(intval($drawingPlayer)) as $u) {
      if (empty($u->removed) && ($u->CardID ?? '') === 'ASH_169') {
        $mz169 = SWUFindMzByUID(intval($u->UniqueID ?? 0));
        if ($mz169 !== null)
          DoGiveAdvantageToken(intval($drawingPlayer), $mz169);
      }
    }
    $playerID = $savedDraw;
  }
  if (GetCurrentPhase() !== 'MAIN')
    return;          // action phase only (for the JTL_111 etc. reactions below)
  global $playerID;
  $savedPID = $playerID;
  // ⚠ EVERY opponent of the drawing player, not OtherPlayer($drawingPlayer) — that is the two-seat
  // hardcode (it answers 2 for seat 1 and 1 for EVERYONE else), so at four seats only one of the
  // drawing player's opponents was ever considered and a JTL_111 on seat 3 or 4 never reacted at all.
  // Fixed 2026-08-27; guarded by TwinSuns_AdmiralOnAFarSeatStillReacts, whose Admiral sits on SEAT 3
  // precisely because a seat-1 Admiral gets the right answer out of the broken code.
  // OpponentsOf() is also team-aware, so in Team Suns a teammate's draw correctly arms nobody.
  foreach (OpponentsOf($drawingPlayer) as $reactor) {   // the would-be JTL_111 controllers
    $playerID = $reactor;
    foreach (GetUnitsInPlay($reactor) as $u) {
      if (!empty($u->removed))
        continue;
      if (($u->CardID ?? '') !== 'JTL_111')
        continue;
      $targets = array_values(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena', AnyUnitFilter),
        ZoneSearch('theirGroundArena', AnyUnitFilter),
        ZoneSearch('theirSpaceArena', AnyUnitFilter)
      ));
      if (!empty($targets)) {
        SWUQueueMayChooseTarget(
          $reactor,
          $targets,
          "Give_an_Experience_token_to_a_unit",
          "Choose_a_unit",
          "GIVE_EXPERIENCE|1"
        );
      }
    }
  }
  // SEC_159 Chairman Papanoida — "When A player draws 1+ during the action phase" (ANY player, incl.
  // the controller). Each controller of SEC_159 may disclose AggressionAggression → create a Spy token.
  foreach (GetLiveSeatsArray() as $p) {
    $playerID = $p;
    foreach (GetUnitsInPlay($p) as $u) {
      if (!empty($u->removed) || ($u->CardID ?? '') !== 'SEC_159')
        continue;
      SWUQueueDisclose(
        $p,
        ['Aggression', 'Aggression'],
        "SEC_159#0",
        "Disclose_AggressionAggression_to_create_a_Spy_token"
      );
    }
  }
  // LAW_052 The Mandalorian — "When YOU draw 1+ cards during the action phase: Give a Shield token to
  // this unit." The DRAWING player's own LAW_052 units each gain a Shield (automatic).
  $playerID = $drawingPlayer;
  foreach (GetUnitsInPlay($drawingPlayer) as $u) {
    if (!empty($u->removed) || ($u->CardID ?? '') !== 'LAW_052')
      continue;
    DoGiveShieldToken($drawingPlayer, $u->GetMzID());
  }
  // HMW_169 Crosshair — "When an OPPONENT draws 1 or more cards during the action phase: Deal 2 damage to
  // their base." Per draw EVENT (this hook is called once per draw with a count), and evaluated against
  // EVERY opponent of the drawing player.
  // ⚠ Deliberately not the `$reactor = OtherPlayer($drawingPlayer)` shape used by JTL_111 above — that is
  // the two-seat hardcode, and at four seats it considers only one of the drawing player's opponents.
  _SWUHmw169CheckDraw($drawingPlayer);
  $playerID = $savedPID;
}



// Whether a candidate On-Attack ability ($abilityCardID, on host unit $hostUnit) is CURRENTLY active for
// $player — i.e. whether it would actually fire if the unit attacked now. Most On-Attack abilities are
// unconditional (return true); the exceptions gate on a condition the ability's own handler checks with an
// early `return`, which a structural key-count can't see. Consulted by JTL_174 Hotshot Maneuver's "for
// each of its On Attack abilities" count so a Coordinate ability with Coordinate inactive, or a Force-host
// upgrade grant on a non-Force host, is NOT counted (matching the ruling). Extend this switch when adding
// another conditional On-Attack ability whose count must respect its activation condition.
function _SWUOnAttackAbilityActive(string $abilityCardID, $hostUnit, int $player): bool
{
  switch ($abilityCardID) {
    // "Coordinate - On Attack: …" — active only while the controller has Coordinate.
    case 'TWI_192': // Padmé Amidala
    case 'TWI_096': // Aayla Secura
      return IsCoordinateActive($player);
    // Jedi Lightsaber grants its On-Attack only while attached to a Force unit.
    case 'SOR_054':
      return TraitContains($hostUnit, 'Force');
    default:
      return true; // unconditional On-Attack ability
  }
}

// _SWUJtl071Heal moved to cards/jtl/Cr90ReliefRunner.php (single-card helper).
// ── JTL_062 Silver Angel — reactive: When 1+ damage is healed from this unit, the controller may deal
// 1 to a space unit. Fired from OnHealUnit (CombatLogic) when a unit is healed. ──────────────────────
function _SWUOnUnitHealed($obj, int $amount = 0): void
{
  global $playerID;
  if (SWUObjGone($obj) || LostAbilities($obj))
    return;
  $controller = intval($obj->Controller ?? 0);
  if ($controller <= 0)
    return;
  // JTL_062 Silver Angel — when healed: you may deal 1 to a space unit.
  if (($obj->CardID ?? '') === 'JTL_062') {
    $playerID = $controller;
    $targets = SWUAllUnits(null, SpaceArena);
    if (empty($targets))
      return;
    SWUQueueMayChooseTarget(
      $controller,
      $targets,
      "You_may_deal_1_to_a_space_unit",
      "Deal_1_to_a_space_unit",
      "DEAL_UNIT_DAMAGE|1"
    );
    return;
  }
  // LAW_047 Baze Malbus — when 1+ damage is healed from this unit: you may deal THAT MUCH to a unit.
  if (($obj->CardID ?? '') === 'LAW_047' && $amount > 0) {
    SWUOfferUnitTarget($controller, '', ['continuation'=>'DEAL_UNIT_DAMAGE','amount'=>$amount,'may'=>true,
        'question'=>"Deal_{$amount}_to_a_unit?",'prompt'=>"Deal_{$amount}_damage_to_a_unit"]);
    return;
  }
}// ── Defeat-replacement resolution (JTL_049 L3-37): the controller chose YES → pick a friendly pilot-less
// Vehicle and attach the would-be-defeated unit to it; NO (or no target) → the defeat happens for real.
$customDQHandlers["DEFEAT_REPLACE"] = function ($player, $parts, $lastDecision) {
  $ctrl = intval($parts[0] ?? $player);
  $uid = intval($parts[1] ?? 0);
  global $playerID;
  $playerID = $ctrl;
  $mz = SWUFindMzByUID($uid);
  if ($mz === null)
    return;
  if ($lastDecision !== "YES") {
    SWUDefeatUnit($ctrl, $mz, true);
    return;
  }
  $vehicles = _SWUPilotlessFriendlyVehicleMzs($ctrl, $uid);
  if (empty($vehicles)) {
    SWUDefeatUnit($ctrl, $mz, true);
    return;
  }
  SWUQueueChooseTarget($ctrl, $vehicles, "Attach_to_a_friendly_Vehicle", "DEFEAT_REPLACE_ATTACH|{$uid}");
};

$customDQHandlers["DEFEAT_REPLACE_ATTACH"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  $uid = intval($parts[0] ?? 0);
  global $playerID;
  $playerID = intval($player);
  $mz = SWUFindMzByUID($uid);
  if ($mz === null)
    return;
  SWUMoveUnitToUpgrade($mz, $lastDecision, true);
};

// JTL_094 Luke (pilot UPGRADE defeat-replacement): YES → rebuild him as an exhausted ground unit from
// the snapshot (host is already gone), preserving UID + any carried captives; NO → discard him.
$customDQHandlers["DEFEAT_REPLACE_UPG"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $uid = intval($parts[1] ?? 0);
  // The rebuild data rides this decision's own Param (serialized with the gamestate) — the player
  // answers in a LATER request, so an in-memory snapshot would be gone by now.
  $e = _SWUDecodeReplacementSnapshot((string) ($parts[2] ?? ''));
  if ($e === null)
    return;
  $cardID = $e['cardID'];
  $owner = intval($e['owner'] ?? $player);
  if ($lastDecision !== "YES") {
    SWUAddToDiscard($owner, $cardID, 'PLAY');
    return;
  }
  $caps = is_array($e['captives'] ?? null) ? array_values($e['captives']) : [];
  $saved = $playerID;
  $playerID = $owner;
  AddGroundArena(
    $owner,
    CardID: $cardID,
    Status: 0,
    Owner: $owner,
    Damage: 0,
    Controller: intval($e['controller'] ?? $owner),
    Subcards: $caps,
    UniqueID: $uid
  );
  $playerID = $saved;
};
// HMW_060 Vice Admiral Rampart — "If an upgrade on your base would be defeated, you may defeat this unit
// instead." YES → defeat Rampart, the saved base upgrade stays. NO → defeat the base upgrade for real
// ($skipReplacement so it doesn't re-offer). If Rampart has left play since the offer, the upgrade is
// defeated regardless.
$customDQHandlers["RAMPART_SAVE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $ctrl = intval($parts[0] ?? $player);
  $uid  = intval($parts[1] ?? 0);
  // Rides the decision's own Param — see DEFEAT_REPLACE_UPG above.
  $e = _SWUDecodeReplacementSnapshot((string) ($parts[2] ?? ''));
  if ($e === null)
    return;
  $playerID = $ctrl;
  $hostMz = (string) ($e['hostMz'] ?? '');
  $idx = _SWUBaseUpgradeIndexByUID($ctrl, $hostMz, $uid);

  // Find Rampart (unique) among the base controller's units.
  $rampartMz = null;
  foreach (GetUnitsInPlay($ctrl) as $u) {
    if (empty($u->removed) && ($u->CardID ?? '') === 'HMW_060') { $rampartMz = $u->GetMzID(); break; }
  }

  if (!SWUDecisionDeclined($lastDecision) && $rampartMz !== null) {
    SWUDefeatUnit($ctrl, $rampartMz);           // "defeat this unit instead" — the base upgrade is saved
  } elseif ($idx >= 0) {
    SWUDefeatUpgrade($ctrl, $hostMz, $idx, false, true);  // declined (or no Rampart) → defeat the upgrade
  }
};
// Universal "deal N damage to the chosen base" — param DEAL_BASE_DAMAGE|N, chosen mzID = myBase-0/theirBase-0.
// No-ops on a '-' decline (used with MZMAYCHOOSE). Reference: SOR_143 Fighters for Freedom.
$customDQHandlers["DEAL_BASE_DAMAGE"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $amount = intval($parts[0] ?? 1);
  $tp = SWUMzOwner((string) $lastDecision, intval($player));   // Twin Suns: base owner from the mzID
  SWUDealDamageToBase($amount, $tp);
};
// SOR_045 Yoda, the TWO-SEAT branch only. ⚠ NOT a seat bug: its sole caller gates on
// SeatCountForGame() <= 2 and takes a per-seat YES/NO path above that, so OtherPlayer() here is only
// ever reached in a game where it is correct by definition. Flagged by the Twin Suns clause scan
// (2026-08-27) because the guard lives in the CALLER, not in this handler — left as-is deliberately.
$customDQHandlers["YODA_DRAW"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $opp = OtherPlayer(intval($player));
  if ($lastDecision === 'You' || $lastDecision === 'Both')
    DoDrawCard(intval($player), 1);
  if ($lastDecision === 'Opponent' || $lastDecision === 'Both')
    DoDrawCard($opp, 1);
};
$customDQHandlers["YODA_DRAW_ONE"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision !== 'YES')
    return;
  DoDrawCard(intval($parts[0] ?? $player), 1);
};

// ── SOR_217 Shoot First ─────────────────────────────────────────────────────
// Event effect lives in cards/sor/ShootFirst.php ($whenPlayedAbilities). Only the DQ step that
// receives the chosen attacker and launches the attack needs a handler here.

// Receives the chosen attacker mzID from $lastDecision; applies Shoot First's TWO effects then
// starts the attack via BeginSWUAttack. "Shoot First" is colloquially just the deal-first ordering,
// but the SOR_217 card ALSO grants +1/+0 — so we split it: a SOR_217 STAT_BUFF (the +1/+0, shown in
// Active Effects with provenance, folded into ObjectCurrentPower) PLUS the SHOOT_FIRST marker (the
// "deals combat damage before the defender" ordering, read in SWUCombatDamage).
$customDQHandlers["SHOOT_FIRST_ATTACK"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $savedPID = $playerID;
  $playerID = $player;

  $attackerMzID = $lastDecision ?? '';
  if (empty($attackerMzID) || !str_contains($attackerMzID, '-')) {
    $playerID = $savedPID;
    SWUAfterAction($player);
    return;
  }

  $attacker = GetZoneObject($attackerMzID);
  if (SWUObjGone($attacker)) {
    $playerID = $savedPID;
    SWUAfterAction($player);
    return;
  }

  SWUApplyPhaseBuff($attackerMzID, 1, 0, 'SOR_217');   // +1/+0 (registry STAT_BUFF, provenance)
  AddTurnEffect($attackerMzID, 'SHOOT_FIRST');          // deals combat damage before the defender
  BeginSWUAttack($player, $attackerMzID);

  $playerID = $savedPID;
};

// Universal: the deciding player discards the card they chose ($lastDecision = "myHand-N") from
// their OWN hand to their discard (From=HAND). Queued per choice by SWUDiscardCards. Used by any
// "discard N cards" effect (SHD_181 Pillage, SOR_175 Forced Surrender, …). The optional $parts[0]
// is the discarding player (it equals the decision player, so it's redundant but kept explicit).
$customDQHandlers["DISCARD_FROM_OWN_HAND"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $targetPlayer = intval($parts[0] ?? $player);
  $savedPID = $playerID;
  $playerID = $targetPlayer;

  $obj = GetZoneObject($lastDecision);
  if ($obj !== null && !($obj->removed ?? false)) {
    $obj->Remove();
    SWUAddToDiscard($targetPlayer, $obj->CardID, 'HAND'); // sets the LAW_179/LAW_076 counters
  }
  $playerID = $savedPID;
};

// Fires the SEC_016 Padmé "when you discard 1+ from your hand" reaction ONCE after a choice-based
// SWUDiscardCards batch completes (queued after the per-card DISCARD_FROM_OWN_HAND decisions). The
// auto-discard-all branch fires the reaction inline instead. Collective: one "discard N" event → one
// trigger.
$customDQHandlers["SEC016_BATCH_REACT"] = function ($player, $parts, $lastDecision) {
  $p = intval($parts[0] ?? $player);
  if (function_exists('_SWUSec016React'))
    _SWUSec016React($p);
};

// ── SHD_135 Kylo's TIE Silencer — On Discard ────────────────────────────────
// "Action: If this unit was discarded from your hand or deck this phase, play
//  it from your discard pile (paying its cost)."
// Tags the discard entry with TPP so SWUPlayFromDiscard allows it at cost.
global $cardDiscardedHandlers;// ── JTL_100 Poe Dameron (unit) — "When played as a unit" ─────────────────────
// "Create an X-Wing token. You may attach this unit as an upgrade to a friendly
//  Vehicle unit without a Pilot on it."
//
// Fires ONLY when JTL_100 enters play as a UNIT (CollectEntryTriggers path).
// When played as a PILOT (upgrade), HasWhenPlayedAsUpgradeAbility(JTL_100)=true
// triggers the no-op $whenPlayedAsUpgradeAbilities["JTL_100:0"] below, which
// returns before falling back to this handler — token does NOT fire on pilot play.
//
// The X-Wing token (JTL_T02, Space, 2/2) is created unconditionally.
// The free-attach is optional (MZMAYCHOOSE): target = friendly Vehicles with
// SWUVehiclePilotCount===0 (strict "0 pilots" per card text; no affordability check).
// On accept → JTL_100 is removed from the arena (without being defeated or discarded)
//             and attached as a Pilot subcard on the chosen Vehicle.
// On decline (AnswerDecision:-) → JTL_100 stays as a unit; token still exists.

// ── Pilot-leader "When deployed as an upgrade:" abilities (JTL_003/006/009/017) ───────────────────────
// These fire when the leader deploys as a Pilot (the Pilot branch of SWUDeployLeader →
// _SWUFinalizeUpgradeAttach → CollectWhenPlayedAsUpgradeTriggers). $mzID is the HOST Vehicle's mzID.
// ── _SWUFinalizeUpgradeAttach — shared pay+attach finisher ───────────────────
// Called by ATTACH_UPGRADE (direct ignoreCost path) and the DROID_PAY ATTACH_UPGRADE
// continuation (after Droid exhaustion). $prepaid = number of Droids already exhausted
// toward the upgrade cost. Pays the real-resource remainder, removes the upgrade from
// hand, attaches it as a Subcard, marks the SOR_061 Guardian charge if applicable, and
// collects WhenPlayedAsUpgrade triggers.
// On payment failure: upgrade stays in hand (rollback is natural — nothing removed
// yet), emits a flash message, and returns immediately.
// NOTE: $playerID must be set correctly by the caller before invoking this.

// Consume the once-per-round/phase UPGRADE cost-discount "used" flags for an upgrade just attached to
// $hostObj: SOR_061 Guardian of the Whills, SEC_064 Congress of Malastare, ASH_075 Pit Droid Team. Shared
// by the normal attach path (_SWUFinalizeUpgradeAttach — called only when the discount was actually
// charged, i.e. !$ignoreCost) AND the smuggled-upgrade path (SMUGGLE_ATTACH — the discount was paid at the
// up-front Smuggle peek). Host-conditional flags (Guardian, and ASH_075's "another friendly") are
// evaluated against the REAL chosen host, so a Smuggle peek's best-case Guardian discount is only spent
// when the upgrade actually lands on the Guardian.
function _SWUConsumeUpgradeUsedFlags($player, $hostObj, $cardID): void
{
  $hostUid = intval($hostObj->UniqueID ?? 0);
  // SOR_061 Guardian of the Whills — spend the per-round charge only if the host IS the Guardian (unused
  // charge). Guard: only for a printed cost ≥ 1 (the −1 actually mattered).
  if (
    in_array($hostObj->CardID ?? '', ['SOR_061', 'LOF_058'], true)
    && intval(CardCost($cardID)) >= 1
    && GlobalEffectCount($player, 'SWU_GUARDIAN_UPG_USED_' . $hostUid) <= 0
  ) {
    AddGlobalEffects($player, 'SWU_GUARDIAN_UPG_USED_' . $hostUid);
  }
  // SEC_064 Congress of Malastare — mark the once-per-phase "first upgrade -1" used (any host).
  if (
    intval(CardCost($cardID)) >= 1
    && GlobalEffectCount($player, 'SWU_SEC064_USED') <= 0
    && _SWUCountUnitsWithCardID($player, 'SEC_064') > 0
  ) {
    AddGlobalEffects($player, 'SWU_SEC064_USED');
  }
  // ASH_075 Pit Droid Team — mark the once-per-phase "first upgrade on ANOTHER friendly unit" used. Set on
  // ANY qualifying play (host is a friendly unit other than a Pit Droid), NOT only while controlling one, so
  // an upgrade played on another friendly BEFORE Pit Droid entered still counts (mirrors the PeliMotto/JTL_260
  // per-phase flag). The discount itself (cost pipeline) additionally requires controlling a Pit Droid.
  if (
    GlobalEffectCount($player, 'SWU_ASH075_USED') <= 0
    && $hostObj !== null && intval($hostObj->Controller ?? 0) === intval($player)
    && ($hostObj->CardID ?? '') !== 'ASH_075'
  ) {
    AddGlobalEffects($player, 'SWU_ASH075_USED');
  }
}

// Fire a host unit's "when a Pilot attaches to this unit" reactions for the HOST'S controller — NOT the
// player who caused the attach. The distinction matters when a pilot attaches onto an ENEMY host (Sidon
// Ithano JTL_213 attaching as a pilot onto an enemy Red Leader / Razor Crest); resolve the
// reaction for the host's owner (the X-Wing token / return goes to them). Shared by _SWUFinalizeUpgradeAttach
// (normal pilot play) and SWUMoveUnitToUpgrade (unit→pilot conversion, e.g. Sidon). Returns count fired.
function _SWUFireHostPilotAttachReactions(?object $hostObj, bool $isPilot, string $cardID): int
{
  if ($hostObj === null)
    return 0;
  if (!($isPilot || HasTrait($cardID, 'Pilot')))
    return 0;
  $hostController = intval($hostObj->Controller ?? ($hostObj->Owner ?? 0));
  $fired = 0;
  // JTL_101 Red Leader — "When a Pilot upgrade attaches to this unit: Create an X-Wing token."
  if (($hostObj->CardID ?? '') === 'JTL_101') {
    SWUCreateUnitToken($hostController, 'JTL_T02');
    $fired++;
  }
  // JTL_223 Razor Crest — "When a Pilot attaches to this unit: You may return a non-leader unit that costs
  // 2 or less, or an exhausted non-leader unit that costs 4 or less, to its owner's hand."
  if (($hostObj->CardID ?? '') === 'JTL_223') {
    AddTrigger($hostController, 'JTL_223', 'JTL_223', '');
    $fired++;
  }
  return $fired;
}

function _SWUFinalizeUpgradeAttach(
  int $player,
  string $cardID,
  string $upgradeMz,
  string $hostMz,
  int $prepaid,
  bool $ignoreCost,
  bool $isPilot = false,
  bool $suppressAfterAction = false,  // SEC_003 Lama Su: caller owns the After Action (deal 1 / combat)
  int $discount = 0,                  // LOF_018 Anakin: "ignoring aspect penalties" — waive the surcharge
  bool $altPayOffered = false,        // true from ATTACH_UPGRADE: the Credit/Droid choice was already shown
  ?int $owner = null,                 // foreign play (SEC_205 milled Pilot): OWNER stays the opponent
  string $grantTE = ''                // marker the playing effect stamps on the attached upgrade (SHD_194)
): int {
  // Re-resolve the host — it must still exist (could have been removed between
  // queuing the Droid-choice and resolution, e.g. opponent removal response).
  $hostObj = GetZoneObject($hostMz);
  if (SWUObjGone($hostObj)) {
    SetFlashMessage("Target no longer in play.");
    return 0;
  }

  // Retrieve the upgrade hand object for cost computation and removal.
  $upgradeHandObj = ($upgradeMz !== '') ? GetZoneObject($upgradeMz) : null;
  $upgradeForCost = $upgradeHandObj ?? (object) ['CardID' => $cardID];

  if ($ignoreCost) {
    // Free play (e.g. SOR_246 top-deck branch): skip payment entirely.
    // Record 0 paid so TWI_210/LAW_231-style consumers see the correct value.
    $GLOBALS['gLastPlayResourcesPaid'] = 0;
  } else {
    // Pilots pay the Piloting cost (CardPilotingCost + aspect penalty).
    // Normal upgrades pay the host-specific play cost (with host discounts).
    $hostCost = $isPilot
      ? SWUComputePilotCost($player, $upgradeForCost, $hostObj)
      : SWUComputePlayCost($player, $upgradeForCost, $hostObj);
    // Waive an explicit discount (LOF_018 Anakin plays a Villainy upgrade "ignoring aspect penalties";
    // the penalty is passed as $discount so the surcharge is cancelled on this deferred payment path,
    // mirroring the event path's ActivateCard($discount)). Pilots use SWU_PILOT_DISCOUNT instead → 0 here.
    if ($discount > 0)
      $hostCost = max(0, $hostCost - $discount);
    $paid = SWUPayCost($player, $hostCost, $prepaid, true, $altPayOffered);
    if (!$paid) {
      SetFlashMessage("Not enough ready resources (need " . max(0, $hostCost - $prepaid) . ").");
      return 0;
      // Upgrade remains in hand — rollback is natural (nothing was removed yet).
    }
    // ASH_212 Peli Motto — a real Upgrade card charges its cost HERE (not in ActivateCard, which excludes
    // upgrades), so mark the "first non-unit played this phase" flag now that the (possibly-waived) cost is
    // locked in. Cleared at RegroupPhaseStart.
    // ⚠ PILOTS COUNT (USER RULING 2026-08-18). A Piloting unit played AS AN UPGRADE is a non-unit play, so
    // it both receives the waiver (SWUAspectPenalty's $asPilot) and CONSUMES the once-per-phase slot. This
    // used to read `!$isPilot` with the comment "Pilots are UNIT cards → don't count"; that is overturned.
    // A pilot's printed CardType is Unit, so the Upgrade type-check cannot see it — hence the explicit
    // $isPilot arm rather than a widened type test.
    if (
      GlobalEffectCount($player, 'SWU_ASH212_USED') <= 0
      && ($isPilot || stripos(CardType($upgradeForCost->CardID ?? '') ?? '', 'Upgrade') !== false)
    ) {
      AddGlobalEffects($player, 'SWU_ASH212_USED');
    }
    // Consume the one-shot Piloting discount now that the pilot has been paid for (the reduction was
    // already folded into $hostCost above). Clear ALL instances — a discount may be multiple (e.g.
    // LOF_188's −4 pre-loads 4) and applies wholly to this one play. No-op if none pending.
    if ($isPilot && GlobalEffectCount($player, 'SWU_PILOT_DISCOUNT') > 0) {
      SWUClearGlobalEffectsByPrefix($player, 'SWU_PILOT_DISCOUNT');
    }
  }

  // Remove the upgrade from hand on successful payment.
  if ($upgradeHandObj !== null && empty($upgradeHandObj->removed)) {
    $upgradeHandObj->Remove();
  }

  // Attach the upgrade as a Subcard on the chosen host.
  if (!is_array($hostObj->Subcards))
    $hostObj->Subcards = [];
  // Owner defaults to the caster. A card played out of an OPPONENT's discard (SEC_205 Obi-Wan's milled
  // Pilot) is still THEIR card: owner = opponent, controller = caster — the same split ActivateCard's
  // $owner param applies on the unit route, so the upgrade goes back to its real owner when defeated.
  $pilotSub = (object) [
    'CardID' => $cardID,
    'Owner' => $owner ?? $player,
    'Controller' => $player,
    // The marker the playing effect asked for, delivered via the ATTACH_UPGRADE param rather than the
    // $gPlayGrantTurnEffect global — the global is already NULL by the time this async handler runs.
    'TurnEffects' => ($grantTE !== '') ? [$grantTE] : [],
    'IsPilot' => $isPilot,
  ];
  // EVERY attached upgrade gets a UniqueID. It used to be Pilots only (so an ejected Pilot keeps its
  // identity across the upgrade↔unit transition), which left every other upgrade identifiable by CardID
  // alone — ambiguous the moment a host carries two copies, and no way for a delayed effect to name the
  // exact subcard it created. SHD_194's "return IT to its owner's hand" needs precisely that.
  $pilotSub->UniqueID = NextUniqueID();
  // The "played this phase" marker stays PILOT-ONLY: it means "a UNIT you played this phase" (Luke
  // SOR_005), which a plain upgrade is not.
  if ($isPilot) {
    AddGlobalEffects($player, 'SWU_PLAYED_UNIT_' . $pilotSub->UniqueID);
  }
  // LOF_056 Size Matters Not — stamp an attach-order UID so its "printed value is considered to be 5"
  // replacement can be timestamp-compared against LAW_036 Obi-Wan's entry UID (most recent wins). A
  // seated SMN (no stamp → 0) is treated as older than any played source, per _SWUSizeOverride.
  if (!$isPilot && $cardID === 'LOF_056')
    $pilotSub->UniqueID = NextUniqueID();
  $hostObj->Subcards[] = $pilotSub;

  // CR 8.19.1.b / 29.3 — uniqueness for upgrades: if this player already controlled a copy of this
  // unique upgrade, auto-defeat the older copy now (immediately, before the just-played copy's
  // When-Played triggers collect below), keeping the one just attached. No-op for non-unique upgrades.
  SWUEnforceUpgradeUniqueness(intval($player), $cardID, $pilotSub);

  // Spend the upgrade cost-discount "used" flags (SOR_061 Guardian / SEC_064 / ASH_075). The discounts
  // were applied at cost time in SWUComputePlayCost with the $host param; spend the charge only on a real
  // (non-free) play. Host-conditional — evaluated against the chosen host. Shared with SMUGGLE_ATTACH.
  if (!$ignoreCost)
    _SWUConsumeUpgradeUsedFlags($player, $hostObj, $cardID);

  // JTL_202 Black Squadron Scout Wing — host reaction: "When you play an upgrade on this unit, you may
  // attack with it (+1/+0 this attack)." Queued onto the same trigger bag so it rides the flush.
  if (($hostObj->CardID ?? '') === 'JTL_202' && intval($hostObj->Status) === 1) {
    AddTrigger($player, 'JTL_202', 'JTL_202', $hostMz);
  }
  // Host "when a Pilot attaches to this unit" reactions (JTL_101 Red Leader X-Wing token / JTL_223 Razor
  // Crest return). Fire for the HOST'S controller — matters when the pilot attaches onto an ENEMY host.
  _SWUFireHostPilotAttachReactions($hostObj, $isPilot, $cardID);
  $triggered = CollectWhenPlayedAsUpgradeTriggers($player, $cardID, $hostMz);
  $triggered += CollectOnAttachedTriggers($player, $cardID, $hostMz);
  // ASH_208 Sabine Wren — "When 1 or more upgrades attach to this unit: may exhaust a ground unit."
  $hostObj208 = GetZoneObject($hostMz);
  if (
    $hostObj208 !== null && ($hostObj208->CardID ?? '') === 'ASH_208'
    && _SWUAsh208OnUpgradeAttach($player, $hostObj208)
  ) {
    $triggered++;
  }
  // SBA — no remaining HP: a stat-reducing upgrade (e.g. LAW_127 Kill Switch's -1/-1) can drop the host's
  // current HP to <= 0 with no damage marked. A unit at 0-or-less remaining HP is defeated immediately.
  SWUCheckShrinkDefeats();

  if ($triggered === 0) {
    DecisionQueueController::CleanupRemovedCards();
    if (!$suppressAfterAction)
      SWUAfterAction($player);
  }
  return $triggered;
}

// ── TWI_040 A Fine Addition — "play an upgrade from your hand or from any player's discard pile" ──────
// Returns the valid hosts for a candidate card (aspect penalty waived for the affordability gate), or
// null if it isn't a playable upgrade/pilot right now (wrong type, no legal host, or unaffordable).
// $isPilotOut receives whether the card is played as a Pilot (a Piloting Unit) vs a regular Upgrade.
function _SWUTwi040HostsFor(int $player, string $cid, &$isPilotOut = null): ?array
{
  $isPilot = (CardPilotingCost($cid) !== null);                 // Piloting card → played as a Pilot upgrade
  $isUpg = (strpos(CardType($cid) ?? '', 'Upgrade') !== false); // regular Upgrade
  $isPilotOut = $isPilot;
  if (!$isPilot && !$isUpg)
    return null;
  $GLOBALS['gTwi040IgnoreAspect'] = true;                        // "ignoring its aspect penalty"
  if ($isPilot) {
    $hosts = SWUGetPilotValidTargets($player, $cid);          // friendly Vehicles w/ capacity + affordable
  } else {
    $hosts = SWUGetUpgradeValidTargets($player, $cid);        // friendly units matching the upgrade's restriction
    $cost = SWUComputePlayCost($player, (object) ['CardID' => $cid]); // base cost (host discounts ignored for the gate)
    if (SWUTotalPaymentCapacity($player) < $cost)
      $hosts = [];
  }
  $GLOBALS['gTwi040IgnoreAspect'] = false;
  return empty($hosts) ? null : array_values($hosts);
}

// Collect controller-relative mzIDs of every playable upgrade/pilot in the player's hand + both discard
// piles ("your hand or any player's discard pile").
function _SWUTwi040Candidates(int $player): array
{
  global $playerID;
  $saved = $playerID;
  $playerID = $player;
  $zones = array_merge(ZoneSearch('myHand'), ZoneSearch('myDiscard'), ZoneSearch('theirDiscard'));
  $out = [];
  foreach ($zones as $mz) {
    $o = GetZoneObject($mz);
    if (SWUObjGone($o))
      continue;
    $cid = $o->CardID ?? '';
    if ($cid !== '' && _SWUTwi040HostsFor($player, $cid) !== null)
      $out[] = $mz;
  }
  $playerID = $saved;
  return $out;
}// ── ATTACH_UPGRADE — core upgrade attach handler ─────────────────────────────
// Receives the chosen target mzID from MZCHOOSE ($lastDecision), the cardID
// from $parts[0], and the upgrade's hand mzID from $parts[1].
// Pays cost AFTER host selection (host-specific, exact — no claw-back), removes
// the upgrade from hand, attaches it as a Subcard on the host, and collects
// WhenPlayedAsUpgrade / WhenPlayed triggers.
// On payment failure: upgrade stays in hand (natural rollback), action abandoned.
// If SEC_122 Vuutun Palaa is in play, the Droid alt-pay step is offered before
// finalizing payment (routes through SWUOfferDroidPayment → DROID_PAY ATTACH_UPGRADE).
$customDQHandlers["ATTACH_UPGRADE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $savedPID = $playerID;
  $playerID = intval($player);
  $cardID = $parts[0] ?? '';
  $upgradeMz = $parts[1] ?? '';  // hand mzID of the upgrade card
  $ignoreCost = !empty($parts[2]);  // 1 = free play (e.g. SOR_246 top-deck free branch)
  $isPilot = !empty($parts[3]);  // 1 = pilot path (SWUComputePilotCost instead of play cost)
  $discount = intval($parts[4] ?? 0);  // LOF_018 Anakin: aspect-penalty waiver on the upgrade play
  // 6th field: OWNER for a foreign play (SEC_205 milled Pilot). Empty/absent → owner = caster.
  $ownerFld = $parts[5] ?? '';
  $owner = ($ownerFld === '') ? null : intval($ownerFld);
  $grantTE = (string) ($parts[6] ?? '');   // 7th field: marker to stamp on the attached upgrade
  $hostMz = $lastDecision ?? '';  // chosen host mzID from preceding MZCHOOSE
  if ($cardID === '' || $hostMz === '') {
    $playerID = $savedPID;
    return;
  }

  $hostObj = GetZoneObject($hostMz);
  if (SWUObjGone($hostObj)) {
    $playerID = $savedPID;
    return;
  }

  // If SEC_122 is in play and there is a non-zero cost with ready Droids, offer
  // the Droid alt-pay step via the central function.
  if (!$ignoreCost) {
    $upgradeHandObj = ($upgradeMz !== '') ? GetZoneObject($upgradeMz) : null;
    $upgradeForCost = $upgradeHandObj ?? (object) ['CardID' => $cardID];
    // Pilots pay the Piloting cost (CardPilotingCost + aspect penalty); normal upgrades
    // pay the host-specific play cost (CardCost + aspect penalty + host discounts).
    $hostCost = $isPilot
      ? SWUComputePilotCost(intval($player), $upgradeForCost, $hostObj)
      : SWUComputePlayCost(intval($player), $upgradeForCost, $hostObj);
    // LOF_018 Anakin — waive the aspect-penalty surcharge on the offered cost so the Droid/Credit cap
    // is against the discounted amount (the actual payment is discounted in _SWUFinalizeUpgradeAttach).
    if ($discount > 0)
      $hostCost = max(0, $hostCost - $discount);
    // Encode $isPilot (4th field) + $discount (5th field) so the DROID_PAY continuation can rebuild them.
    // 7th field: $grantTE. ⚠ This branch does NOT re-queue this handler — it routes through
    // SWUDispatchDroidContinuation's own 'ATTACH_UPGRADE' case (which calls _SWUFinalizeUpgradeAttach
    // directly), and it runs even at cost 0. Any field not in $droidArgs is silently dropped there;
    // that is exactly how SHD_194's return-to-hand marker vanished between this handler and the attach.
    $droidArgs = "{$cardID}|{$upgradeMz}|{$hostMz}|" . ($isPilot ? '1' : '0') . "|{$discount}|"
                 . ($owner === null ? '' : $owner) . "|" . $grantTE;
    SWUOfferAltPayment(intval($player), $hostCost, 'ATTACH_UPGRADE', $droidArgs, 0);
    $playerID = $savedPID;
    return;
  }

  // ignoreCost path — finalize directly without SEC_122 check.
  _SWUFinalizeUpgradeAttach(intval($player), $cardID, $upgradeMz, $hostMz, 0, $ignoreCost, $isPilot, false, $discount, false, $owner, $grantTE);
  $playerID = $savedPID;
};

// ── Piloting play helpers ─────────────────────────────────────────────────────
// SWUQueuePilotVehiclePick: routes a pilot onto a Vehicle target. Called when the
// player has already committed to the Pilot branch (either via OPTIONCHOOSE or the
// pilot-only short-circuit). $vehicles = array of host mzIDs from
// SWUGetPilotValidTargets. _SWUFinalizeUpgradeAttach uses SWUComputePilotCost
// (isPilot=true) instead of SWUComputePlayCost.
//
// If count($vehicles) === 1: auto-attaches to the sole valid Vehicle (skips MZCHOOSE),
//   routing through SWUOfferDroidPayment → _SWUFinalizeUpgradeAttach so Droid alt-pay
//   is still offered when applicable. $playerID is set before calling SWUOfferDroidPayment
//   because that function may queue MZMULTICHOOSE (Droids) and must leave $playerID = $player.
//
// If count($vehicles) >= 2: queues the MZCHOOSE picker as before.
// CRITICAL: $playerID must be left = $player on return so MZCountChoices can
// resolve the relative mzIDs in the MZCHOOSE param immediately after this returns.
function SWUQueuePilotVehiclePick(int $player, string $mzID, string $cardID, array $vehicles,
  ?int $owner = null): void
{
  $ownerFld = ($owner === null) ? '' : (string) $owner;
  global $playerID;
  $playerID = $player;

  // LOF_012 Rey: a Force Pilot played AS AN UPGRADE is a "non-unit Force card" played this phase — the
  // generic upgrade-play path sets this for type-Upgrade cards, but pilots route here instead, so mirror
  // it. (Any caller that plays a pilot as an upgrade — the Unit/Pilot choice, pilot-only, Anakin LOF_018,
  // Wedge JTL_008 — is a valid "played a Force non-unit card".)
  if (HasTrait($cardID, 'Force')) {
    AddGlobalEffects($player, 'SWU_PLAYED_NONUNIT_FORCE');
  }

  if (count($vehicles) === 1) {
    // Auto-attach to the only valid Vehicle — no picker needed.
    // Route through SWUOfferDroidPayment so SEC_122 Droid alt-pay is offered if applicable.
    // Args format: "{cardID}|{upgradeMz}|{hostMz}|{isPilot}" (isPilot=1).
    $hostMz = $vehicles[0];
    $droidArgs = "{$cardID}|{$mzID}|{$hostMz}|1|0|{$ownerFld}";
    // Compute the pilot cost for the Droid-offer threshold.
    $upgradeObj = GetZoneObject($mzID);
    $upgradeForCost = $upgradeObj ?? (object) ['CardID' => $cardID];
    $pilotCost = SWUComputePilotCost($player, $upgradeForCost, GetZoneObject($hostMz));
    SWUOfferAltPayment($player, $pilotCost, 'ATTACH_UPGRADE', $droidArgs, 1);
    // $playerID left = $player by SWUOfferDroidPayment (it sets it before any MZMULTICHOOSE).
    return;
  }

  // 2+ vehicles: show the MZCHOOSE picker.
  DecisionQueueController::AddDecision(
    $player,
    "MZCHOOSE",
    implode("&", $vehicles),
    1,
    tooltip: "Choose_a_Vehicle_to_pilot"
  );
  // $parts[3] = "1" → pilot path in ATTACH_UPGRADE; $parts[2] = "0" = not ignoreCost.
  DecisionQueueController::AddDecision(
    $player,
    "CUSTOM",
    "ATTACH_UPGRADE|{$cardID}|{$mzID}|0|1|0|{$ownerFld}",
    1
  );
}

// PILOT_PLAY_CHOICE — receives the OPTIONCHOOSE "Unit" or "Pilot" answer.
// $parts[0] = hand mzID of the pilot card, $parts[1] = cardID.
// "Pilot" → queue the Vehicle pick; "Unit" → continue as a normal unit play.
$customDQHandlers["PILOT_PLAY_CHOICE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $mzID = $parts[0] ?? '';
  $cardID = $parts[1] ?? '';
  $discount = intval($parts[2] ?? 0); // pre-set as SWU_PILOT_DISCOUNT by SWUBeginPlayCard's pilot branch
  if ($lastDecision === 'Pilot') {
    $vehicles = SWUGetPilotValidTargets(intval($player), $cardID);
    if (!empty($vehicles)) {
      SWUQueuePilotVehiclePick(intval($player), $mzID, $cardID, $vehicles); // discount consumed at charge
      return;
    }
  }
  // "Unit" (or no Vehicle left): the pilot discount doesn't apply — drop it and re-enter the FULL
  // unit-play path (incl. Exploit) with the discount applied to the unit cost instead.
  // Do NOT call SWUContinuePlayAfterExploit here — that skips the Exploit step.
  // $playerID is already set above; the helper does NOT restore it (see its comment),
  // so $playerID remains = $player on return (correct for any queued MZMULTICHOOSE).
  for ($k = 0; $k < $discount; $k++)
    RemoveGlobalEffect(intval($player), 'SWU_PILOT_DISCOUNT');
  _SWUBeginPlayCardUnitPath(intval($player), $mzID, $discount);
};

// FOREIGN_PILOT_PLAY_CHOICE — the Unit-vs-Pilot answer for a card played out of an OPPONENT's discard
// (SEC_205 Obi-Wan: "you may play THAT CARD… ignoring its aspect penalties" — a card, not a unit, so the
// Piloting route is available too). $parts = discardIdx | cardID | modifier | opponent.
// The pilot cost's aspect-penalty waiver is already pre-loaded as SWU_PILOT_DISCOUNT by the caller;
// _SWUFinalizeUpgradeAttach clears it after a successful pilot payment, and the Unit branch drops it here.
$customDQHandlers["FOREIGN_PILOT_PLAY_CHOICE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $discardIdx = intval($parts[0] ?? 0);
  $cardID     = $parts[1] ?? '';
  $modifier   = $parts[2] ?? '';
  $opponent   = intval($parts[3] ?? 0);

  if ($lastDecision === 'Pilot') {
    $vehicles = SWUGetPilotValidTargets(intval($player), $cardID);
    if (!empty($vehicles)) {
      AddGlobalEffects(intval($player), 'SWU_CARDS_PLAYED');
      // Seat-correct pile mzID: the sibling site in SWUPlayFromOpponentDiscard was fixed the same way.
      // A literal "theirDiscard-N" resolves to SEAT 2 above two seats, so the Pilot branch of the fork
      // would attach a completely different card than the Unit branch of the same prompt.
      SWUQueuePilotVehiclePick(intval($player), SWUForeignDiscardMzID(intval($player), $opponent, $discardIdx), $cardID, $vehicles, $opponent);
      return;
    }
    // Every Vehicle disappeared while the prompt was open — fall through to the unit play.
  }
  SWUClearGlobalEffectsByPrefix(intval($player), 'SWU_PILOT_DISCOUNT');
  AddGlobalEffects(intval($player), 'SWU_CARDS_PLAYED');
  _SWUForeignDiscardPlayAsUnit(intval($player), $discardIdx, $cardID, $modifier, $opponent);
};

// OWN_DISCARD_PILOT_CHOICE — the Unit-vs-Pilot answer for a Piloting card played out of YOUR OWN
// discard under a TPF/TPP permission (SHD_115 Cobb Vanth: "you may play THAT CARD from your discard
// pile" — a card, not a unit, so the Piloting route is available; official ruling 2025-03-06).
// $parts = actualDiscardIdx | cardID | modifier | playDiscount.
// For a TPF (free) play the caller has already pre-loaded the whole pilot cost as SWU_PILOT_DISCOUNT
// stacks, so the Pilot route is free too; _SWUFinalizeUpgradeAttach clears them after a successful
// pilot payment, and the Unit branch drops them here.
$customDQHandlers["OWN_DISCARD_PILOT_CHOICE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $discardIdx = intval($parts[0] ?? 0);
  $cardID     = $parts[1] ?? '';
  $modifier   = $parts[2] ?? '';
  $discount   = intval($parts[3] ?? 0);

  if ($lastDecision === 'Pilot') {
    $vehicles = SWUGetPilotValidTargets(intval($player), $cardID);
    if (!empty($vehicles)) {
      AddGlobalEffects(intval($player), 'SWU_CARDS_PLAYED');
      SWUQueuePilotVehiclePick(intval($player), "myDiscard-{$discardIdx}", $cardID, $vehicles);
      return;
    }
    // Every Vehicle disappeared while the prompt was open — fall through to the unit play.
  }
  SWUClearGlobalEffectsByPrefix(intval($player), 'SWU_PILOT_DISCOUNT');
  _SWUOwnDiscardPlayAsUnit(intval($player), $discardIdx, $cardID, $modifier, $discount);
};

// ── Leader deploy-as-Pilot choice handlers ───────────────────────────────────

// LEADER_DEPLOY_CHOICE — receives the OPTIONCHOOSE "Unit" or "Pilot" answer.
// $parts[0] = the leader's CardID (e.g. "JTL_001").
// "Unit"  → call SWUDeployLeader($player, 'Unit') — skips the choose-one gate (no
//            eligible Vehicle left / player chose Unit) via the normal Unit path.
// "Pilot" → re-read eligible Vehicles, auto-attach if exactly one, else queue MZCHOOSE
//            then LEADER_DEPLOY_PILOT to finish the attach.
$customDQHandlers["LEADER_DEPLOY_CHOICE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $leaderCardID = $parts[0] ?? '';
  // ⚠ Twin Suns: WHICH leader is deploying. SWUDeployLeader's 4th arg defaults to 0, and every
  // re-entry below used to take that default — so deploying a seat's SECOND leader (index 1) actually
  // deployed its FIRST, and when the first was already deployed the guard at the top of
  // SWUDeployLeader turned the whole action into a silent no-op ("Vonreg was undeployable",
  // reported 2026-08-25). Premier has one leader, so index 0 was always right and this never showed.
  $leaderIndex = intval($parts[1] ?? 0);

  if ($lastDecision === 'Pilot') {
    $vehicles = SWUGetLeaderPilotVehicles(intval($player));
    if (!empty($vehicles)) {
      if (count($vehicles) === 1) {
        // Auto-attach to the single eligible Vehicle — no picker needed.
        SWUDeployLeader(intval($player), 'Pilot', $vehicles[0], $leaderIndex);
      } else {
        // 2+ vehicles: let the player pick.
        DecisionQueueController::AddDecision(
          $player,
          "MZCHOOSE",
          implode("&", $vehicles),
          1,
          tooltip: "Choose_a_Vehicle_to_deploy_onto"
        );
        DecisionQueueController::AddDecision(
          $player,
          "CUSTOM",
          "LEADER_DEPLOY_PILOT|{$leaderCardID}|{$leaderIndex}",
          1
        );
      }
      return;
    }
    // No eligible Vehicle any more (removed between queue and resolution): fall through to Unit.
  }

  // "Unit" or fallback: deploy normally as a unit.
  // 'UnitDirect' bypasses the choose-one gate so we don't re-offer when vehicles still exist.
  SWUDeployLeader(intval($player), 'UnitDirect', '', $leaderIndex);
};

// LEADER_DEPLOY_PILOT — receives the MZCHOOSE host mzID, finalizes the Pilot attach.
// $parts[0] = leaderCardID (informational; SWUDeployLeader re-resolves from the leader zone).
$customDQHandlers["LEADER_DEPLOY_PILOT"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $hostMz = $lastDecision ?? '';
  if ($hostMz === '' || $hostMz === '-')
    return;
  SWUDeployLeader(intval($player), 'Pilot', $hostMz, intval($parts[1] ?? 0));
};
// ── Implemented When Played abilities ───────────────────────────────────────// Twin Suns plan counter (Phase 4): put the chosen hand card on the bottom of the deck.
$customDQHandlers["SWU_PLAN_BOTTOM"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $mz = (string) $lastDecision;
  if ($mz === '' || $mz === '-' || $mz === 'PASS')
    return;
  $o = GetZoneObject($mz);
  if (SWUObjGone($o))
    return;
  $cardID = $o->CardID;
  $o->removed = true;
  DecisionQueueController::CleanupRemovedCards();
  _topDeckPutRemainingToBottom(intval($player), [$cardID]);
};

// SOR_215 — Snapshot Reflexes: "When Played: You may attack with the attached unit."
// $mzID is the host unit's arena mzID (e.g. "myGroundArena-0").
$whenPlayedAbilities["SOR_215:0"] = function ($player, $mzID) {
  // Attached to an ENEMY unit (legal per CR 2.e), "you may attack with attached unit" can only
  // fizzle — you cannot attack with an opponent's unit. Fizzle-only optional → no prompt
  // (user ruling 2026-08-13, the Blue Leader pay-2 family).
  global $playerID; $saved = $playerID; $playerID = intval($player);
  $host = GetZoneObject($mzID);
  $playerID = $saved;
  if (SWUObjGone($host) || intval($host->Controller ?? 0) !== intval($player)) return;
  DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Attack_with_attached_unit?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_215#0|{$player}|{$mzID}", 1);
};

// SOR_215: player answered the "Attack with attached unit?" YESNO.
// $parts[0] = player, $parts[1] = host unit mzID.
global $customDQHandlers;
// SOR_167 Force Throw — pick a player; THAT player discards a card of their choice; then if the CASTER
// controls a Force unit, the caster may deal damage to a unit equal to the discarded card's cost.
// Discard the chosen hand card ($mz, relative to $discarder), then queue the caster's optional damage.
function _SWUForceThrowDiscard(int $discarder, int $caster, string $mz): void
{
  global $playerID;
  $playerID = $discarder;
  $o = GetZoneObject($mz);
  if (SWUObjGone($o))
    return;
  $cost = intval(CardCost($o->CardID));
  $cardID = $o->CardID;
  $o->Remove();
  SWUAddToDiscard($discarder, $cardID, 'HAND');
  DecisionQueueController::CleanupRemovedCards();
  AddGameLogEntry('DISCARD', "P{$discarder} discarded " . GameLogCardRef($cardID));
  if ($cost > 0 && _SWUControlsForceUnit($caster)) {
    SWUOfferUnitTarget($caster, '', ['continuation'=>'DEAL_UNIT_DAMAGE','amount'=>$cost,'may'=>true,
        'question'=>"You_may_deal_{$cost}_damage_to_a_unit",'prompt'=>"Deal_{$cost}_damage_to_a_unit"]);
  }
}// ── SOR_223 Don't Get Cocky — iterative reveal-until-stop ────────────────────
// Reveal the top card of $player's deck (public), removing it from the deck and returning its CardID
// (null if the deck is empty). Revealed cards are held by CardID in the loop param and returned to the
// deck bottom at resolution.
function _SOR223RevealTop(int $player): ?string
{
  $deck = &GetDeck($player);
  if (count($deck) === 0)
    return null;
  $card = array_shift($deck);
  foreach ($deck as $i => $c) {
    $c->mzIndex = $i;
  }
  $cid = $card->CardID;
  AddGameLogEntry('REVEAL', "P{$player} revealed " . GameLogCardRef($cid));
  return $cid;
}

// Resolve: if combined cost ≤ 7 (and > 0) deal it to the chosen unit; return revealed cards to the
// bottom of the deck in random order.
function _SOR223Resolve(int $player, int $targetUID, array $revealed): void
{
  global $playerID;
  $playerID = intval($player);
  $total = 0;
  foreach ($revealed as $cid)
    $total += intval(CardCost($cid));
  if ($total > 0 && $total <= 7) {
    $mz = SWUFindMzByUID($targetUID);
    if ($mz !== null)
      SWUDealDamageToUnit($mz, $total, $player);
  }
  if (!empty($revealed))
    _topDeckPutRemainingToBottom($player, $revealed);  // shuffles → bottom
}
// ── "Choose two, in any order" modal (SOR_058/107/155/203) ───────────────────
// Resolve ONE chosen mode (queues that mode's own effect decisions at block 1).
function _SWUModalResolveMode(int $player, string $cardID, string $label): void
{
  global $playerID;
  $playerID = intval($player);
  if ($cardID === 'SOR_058') {                         // Vigilance
    switch ($label) {
      case 'Discard6':                              // discard 6 from an opponent's deck
        for ($i = 0; $i < 6; $i++)
          SWUMillTopCard(OtherPlayer($player));
        return;
      case 'Heal5':                                 // heal 5 from a base
        SWUOfferBaseTarget($player, ['continuation'=>'HEAL_TARGET','amount'=>5,'prompt'=>"Choose_a_base_to_heal"]);
        return;
      case 'Defeat': {                              // defeat a unit with ≤3 remaining HP
        SWUOfferUnitTarget($player, '', [
            'continuation' => 'DEFEAT_UNIT',
            'extraFilter' => fn($o) => (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0)) <= 3,
            'prompt' => "Defeat_a_unit_with_3_or_less_remaining_HP",
        ]);
        return;
      }
      case 'Shield':                                // give a Shield to a unit
        GiveTokenUpgrade($player, '', ['token'=>'SHIELD','friendlyOnly'=>false,'prompt'=>"Give_a_Shield_to_a_unit"]);
        return;
    }
  }
  if ($cardID === 'SOR_107') {                          // Command
    switch ($label) {
      case 'Experience':                            // give 2 Experience tokens to a unit
        GiveTokenUpgrade($player, '', ['token'=>'EXPERIENCE','amount'=>2,'friendlyOnly'=>false,'prompt'=>"Give_2_Experience_to_a_unit"]);
        return;
      case 'PowerStrike': {                         // a friendly unit deals its power to a non-unique enemy
        $friendly = SWUAllUnits('my');
        SWUQueueChooseTarget($player, array_values($friendly), "Choose_the_friendly_unit", "SOR_107#0");
        return;
      }
      case 'Resource': {                            // put this event into play as a resource
        $mz = _SWUFindDiscardMzID($player, 'SOR_107');
        if ($mz !== null)
          SWURampResourceExhausted($player, $mz); // enters exhausted (no "ready" wording)
        return;
      }
      case 'Return':                                // return a unit from your discard to hand
        SWUQueueChooseTarget(
          $player,
          array_values(ZoneSearch("myDiscard", AnyUnitFilter)),
          "Return_a_unit_from_your_discard",
          "RETURN_DISCARD_UNIT"
        );
        return;
    }
  }
  if ($cardID === 'SOR_155') {                          // Aggression
    switch ($label) {
      case 'Draw':
        DoDrawCard($player, 1);
        return;
      case 'DefeatUpgrades':                        // defeat up to 2 upgrades (possibly on
        // DIFFERENT units — two chained "may defeat 1")
        SWUQueueDefeatUpgrade(
          $player,
          "Defeat_an_upgrade_(1_of_2)",
          may: true,
          max: 1,
          min: 0,
          thenHandler: 'SOR_155#0'
        );
        return;
      case 'Ready': {                               // ready a unit with ≤3 power
        SWUOfferUnitTarget($player, '', ['continuation'=>'READY_UNIT',
            'extraFilter'=>fn($o)=>intval(ObjectCurrentPower($o)) <= 3,
            'prompt'=>"Ready_a_unit_with_3_or_less_power"]);
        return;
      }
      case 'Deal4':                                 // deal 4 to a unit
        SWUOfferUnitTarget($player, '', ['continuation'=>'DEAL_UNIT_DAMAGE','amount'=>4,'prompt'=>"Deal_4_to_a_unit"]);
        return;
    }
  }
  if ($cardID === 'SOR_203') {                          // Cunning
    switch ($label) {
      case 'ReturnUnit': {                          // return a non-leader unit with ≤4 power to hand
        $targets = [];
        foreach (SWUAllUnits() as $mz) {
          $o = GetZoneObject($mz);
          if ($o !== null && empty($o->removed) && !IsLeaderUnit($o) && intval(ObjectCurrentPower($o)) <= 4)
            $targets[] = $mz;
        }
        SWUQueueChooseTarget($player, array_values($targets), "Return_a_non-leader_unit_with_4_or_less_power", "BOUNCE_UNIT");
        return;
      }
      case 'BuffUnit':                              // give a unit +4/+0 this phase
        SWUOfferUnitTarget($player, '', ['continuation'=>'APPLY_PHASE_BUFF|4|0|SOR_203', 'prompt'=>"Give_a_unit_+4/+0_this_phase"]);
        return;
      case 'Exhaust': {                             // exhaust up to 2 units
        $units = array_values(SWUAllUnits());
        if (empty($units))
          return;
        $max = min(2, count($units));
        DecisionQueueController::AddDecision(
          $player,
          "MZMULTICHOOSE",
          "0|{$max}|" . implode("&", $units),
          1,
          tooltip: "Exhaust_up_to_2_units"
        );
        DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_203#0", 1, dontSkipOnPass: 1);
        return;
      }
      case 'Discard':                               // an opponent discards a random card
        _SWUOpponentDiscardRandom($player);
        return;
    }
  }
  if ($cardID === 'SHD_153') {                          // Poe Dameron (On Attack modal)
    switch ($label) {
      case 'Deal2':                                 // deal 2 to a unit or base
        SWUOfferUnitTarget($player, '', [
            'continuation' => 'DEAL_TARGET', 'amount' => 2, 'includeBases' => true,
            'prompt' => "Deal_2_to_a_unit_or_base",
        ]);
        return;
      case 'DefeatUpgrade':                         // defeat an upgrade
        SWUQueueDefeatUpgrade($player, "Defeat_an_upgrade", may: false, max: 1, min: 1);
        return;
      case 'OppDiscard': {                          // an opponent discards a card from their hand
        // "AN opponent" — a real pick, restricted to opponents actually holding a card (auto-resolves
        // at one, so 2-player shows no prompt). Was untargeted, i.e. OtherPlayer($player).
        $oppd = SWUOpponentsWithCards(intval($player));
        if (empty($oppd)) return;
        SWUQueueChooseOpponent(intval($player), "MODAL_OPP_DISCARD", "Which_opponent_discards_a_card?", $oppd);
        return;
      }
    }
  }
}
$customDQHandlers["MODAL_OPP_DISCARD"] = function($player, $parts, $lastDecision) {
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    SWUDiscardCards(intval($player), 1, $opp);
};

// "$targetPlayer discards one random card from their own hand" (any player — LOF_227).
function _SWUPlayerDiscardRandom(int $targetPlayer): void
{
  global $playerID;
  $playerID = intval($targetPlayer);
  $hand = &GetHand($targetPlayer);
  $liveIdx = [];
  foreach ($hand as $i => $c) {
    if (empty($c->removed))
      $liveIdx[] = $i;
  }
  if (empty($liveIdx))
    return;
  $pick = $liveIdx[array_rand($liveIdx)];
  $cid = $hand[$pick]->CardID;
  $hand[$pick]->Remove();
  SWUAddToDiscard($targetPlayer, $cid, 'HAND');
  DecisionQueueController::CleanupRemovedCards();
  AddGameLogEntry('DISCARD', "P{$targetPlayer} discarded " . GameLogCardRef($cid) . ' at random');
}

// "$player's opponent discards one random card from hand" (SOR_203 mode; mirrors SOR_190).
function _SWUOpponentDiscardRandom(int $player): void
{
  global $playerID;
  $playerID = intval($player);
  $opp = OtherPlayer($player);
  $hand = &GetHand($opp);
  $liveIdx = [];
  foreach ($hand as $i => $c) {
    if (empty($c->removed))
      $liveIdx[] = $i;
  }
  if (empty($liveIdx))
    return;
  $pick = $liveIdx[array_rand($liveIdx)];
  $cid = $hand[$pick]->CardID;
  $hand[$pick]->Remove();
  SWUAddToDiscard($opp, $cid, 'HAND');
  DecisionQueueController::CleanupRemovedCards();
  AddGameLogEntry('DISCARD', "P{$opp} discarded " . GameLogCardRef($cid) . ' at random');
}

// Universal: return the chosen discard-pile unit to its owner's hand.
$customDQHandlers["RETURN_DISCARD_UNIT"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};$customDQHandlers["MODAL_CHOOSE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $cardID = $parts[0] ?? '';
  $picksLeft = intval($parts[1] ?? 0);
  $block = intval($parts[2] ?? 1);
  $labels = (($parts[3] ?? '') !== '') ? explode(",", $parts[3]) : [];
  if (SWUDecisionDeclined($lastDecision))
    return;
  _SWUModalResolveMode(intval($player), $cardID, $lastDecision);   // queues this mode's effects (block 1)
  if ($picksLeft - 1 > 0) {                                        // next picker at a higher block
    $remaining = array_values(array_filter($labels, fn($l) => $l !== $lastDecision));
    SWUQueueModalChoose(intval($player), $cardID, $remaining, $picksLeft - 1, $block + 1);
  }
};// Universal: defeat the unit at $lastDecision. Queued by PASSPARAMETER/MZCHOOSE +
// CUSTOM DEFEAT_UNIT (SOR_077 Takedown, SOR_078 Vanquish). No SWUAfterAction here —
// event flow handles cleanup via FINISH_PLAY_CARD.
$customDQHandlers["DEFEAT_UNIT"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
    return;
  global $playerID;
  $playerID = intval($player);
  SWUDefeatUnit(intval($player), $lastDecision);
};
// ── LAW Unit When-Played abilities (Phase 2) ──────────────────────────────────────────────────────
// ── LAW Unit On-Attack / On-Attack-End abilities (Phase 3) ────────────────────────────────────────
// ── ASH (Support set) — card abilities that double as Support-lent abilities ──
// These register in the normal On-Attack / On-Attack-End registries; the Support mechanism grafts them
// onto a supported attacker (SupportOnAttack/SupportOnAttackEnd → the same closures, $mzID = the lent
// attacker). "this unit" / "the defender" therefore resolve correctly for both own and lent attacks.

// True if $obj has one or more keywords (innate / granted / conditional). Checks the full SWU keyword set.
function _SWUUnitHasAnyKeyword($obj): bool
{
  if ($obj === null)
    return false;
  foreach (['Sentinel', 'Ambush', 'Overwhelm', 'Grit', 'Saboteur', 'Shielded', 'Bounty', 'Hidden', 'Smuggle', 'Plot', 'Piloting', 'Support'] as $kw) {
    $fn = "HasKeyword_{$kw}";
    if (function_exists($fn) && $fn($obj))
      return true;
  }
  foreach (['Raid', 'Restore', 'Exploit'] as $vkw) {
    $fn = "GetKeyword_{$vkw}_Value";
    if (function_exists($fn)) {
      $v = $fn($obj);
      if ($v !== null && intval($v) > 0)
        return true;
    }
  }
  return false;
}

// ASH_128 Bothan-5 — reactive (a friendly non-Vehicle unit was defeated): may capture that just-defeated
// unit from your discard pile onto Bothan-5. $captorUID = Bothan-5's UID, $cardID = the defeated card.
function Ash128Trigger($player, $captorUID, $cardID): void
{
  global $playerID;
  $playerID = intval($player);
  if ($captorUID <= 0 || $cardID === '' || SWUFindMzByUID($captorUID) === null)
    return;
  DecisionQueueController::AddDecision(
    intval($player),
    "YESNO",
    "-",
    1,
    tooltip: "Capture_" . GameLogCardRef($cardID) . "_from_your_discard_with_Bothan-5?"
  );
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_128#0|{$captorUID}|{$cardID}", 1);
}

// ASH_232 Full of Surprises — after returning the ≤2 upgrade, give a Shield token to a unit.
function _SWUAsh232GiveShield(int $player): void
{
  GiveTokenUpgrade($player, '', ['token'=>'SHIELD','friendlyOnly'=>false,'prompt'=>"Give_a_Shield_token_to_a_unit"]);
}
// TS26_35 Ahsoka's Lightsabers (upgrade) — grants "On Attack/When Defeated: you may give a Shield to an
// enemy unit. If you do, the next event you play this phase costs 2 less." Both windows share the offer;
// the When-Defeated half dispatches via DispatchTrigger 'TS26_35' (subcard scan).
function _SWUTs26035Offer(int $player): void
{
  global $playerID;
  $playerID = intval($player);
  $enemy = SWUAllUnits('their');
  if (empty($enemy))
    return;
  SWUQueueMayChooseTarget($player, $enemy, "Give_a_Shield_to_an_enemy_unit_(next_event_-2)?", "Choose_an_enemy_unit", "TS26_35#0");
}

function _SWUAsh053Finish(int $player, int $count): void
{
  global $playerID;
  $playerID = $player;
  SWUCreateUnitTokens($player, 'ASH_T01', $count);
}// ── LAW Phase 6 — Upgrade-granted abilities ───────────────────────────────────────────────────────


// Universal "an opponent chooses one of THEIR units to defeat" (SOR_040 Avenger, SOR_041 Power of the
// Dark Side). $parts[0] = '1' to restrict to non-leader units, '0' for any unit. Run via an
// intermediate CUSTOM (not inline from a trigger closure) so the cross-player $playerID survives —
// see SWUOpponentChoosesOwnUnit.
// ⚠ "AN opponent" means OF YOUR CHOICE — the CASTER picks which opponent, then THAT opponent picks
// which of their units. OFFICIAL RULING (Avenger, 03/01/2024): "If there are multiple opponents, the
// controlling player chooses which one will be 'an opponent.'" This used to call
// SWUOpponentChoosesOwnUnit with no seat, which fell through to OtherPlayer($caster) — not a choice at
// all above two seats, and (because OtherPlayer is literally `$p === 1 ? 2 : 1`) it aimed every seat-3
// and seat-4 caster at PLAYER 1.
//
// ⚠ This handler is for "AN opponent" ONLY. "EACH player" / "each opponent" is a LOOP — see
// EACH_OPPONENT_DEFEATS_OWN_UNIT (TWI_238 Merciless Contest). The two read almost identically in the
// printed text and take opposite implementations.
$customDQHandlers["OPP_DEFEAT_OWN_UNIT"] = function ($player, $parts, $lastDecision) {
  $nonLeader = (($parts[0] ?? '1') === '1');
  // Filter to opponents who can actually make the demanded choice (ELIGIBILITY = WHO ACTS). Gate on
  // non-empty BEFORE prompting so a card with no legal opponent fizzles silently.
  $eligible = SWUOpponentsWithOwnUnit(intval($player), $nonLeader);
  if (empty($eligible)) return;
  // At one eligible opponent SWUQueueChooseOpponent auto-resolves via PASSPARAMETER, so 2-player play
  // is byte-identical — no new prompt.
  SWUQueueChooseOpponent(intval($player), "OPP_DEFEAT_PICKED|" . ($nonLeader ? '1' : '0'),
                         'Choose_an_opponent', $eligible);
};

// Continuation of OPP_DEFEAT_OWN_UNIT: the caster has named the seat; now THAT opponent picks which of
// their own units is defeated.
$customDQHandlers["OPP_DEFEAT_PICKED"] = function ($player, $parts, $lastDecision) {
  $opp = SWUPickedOpponent($lastDecision);
  if ($opp <= 0 || $opp === intval($player)) return;
  $nonLeader = (($parts[0] ?? '1') === '1');
  $tip = $nonLeader ? 'Choose_a_non-leader_unit_to_defeat' : 'Choose_a_unit_to_defeat';
  // The opponent CHOOSES the target, but the defeat is caused by the CASTER's card ability — so it
  // must be attributed to the caster for "can't be defeated by ENEMY card abilities" to apply.
  SWUOpponentChoosesOwnUnit(intval($player), $nonLeader, $tip,
                            "ENEMY_SOURCED_DEFEAT|" . intval($player), $opp);
};

// "EACH opponent chooses a unit they control. Defeat those units." — a LOOP, one resolution per seat,
// NOT a pick. 2-player: the single opponent, byte-identical to the old shared handler.
$customDQHandlers["EACH_OPPONENT_DEFEATS_OWN_UNIT"] = function ($player, $parts, $lastDecision) {
  $nonLeader = (($parts[0] ?? '1') === '1');
  $tip = $nonLeader ? 'Choose_a_non-leader_unit_to_defeat' : 'Choose_a_unit_to_defeat';
  foreach (OpponentsOf(intval($player)) as $opp) {
    SWUOpponentChoosesOwnUnit(intval($player), $nonLeader, $tip,
                              "ENEMY_SOURCED_DEFEAT|" . intval($player), $opp);
  }
};

// Defeat a unit the OPPONENT chose among their own, attributing the defeat to the CASTER (the ability's
// controller) rather than the chooser. $chooser = the player who answered the pick (owns the unit);
// $parts[0] = the caster. The chooser-frame mzID ('my…Arena-N') is translated to the caster's frame
// ('their…Arena-N') — same physical unit — so SWUDefeatUnit sees actor ≠ controller and correctly
// evaluates the enemy-ability defeat immunity.
$customDQHandlers["ENEMY_SOURCED_DEFEAT"] = function ($chooser, $parts, $lastDecision) {
  if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
    return;
  $caster = intval($parts[0] ?? OtherPlayer(intval($chooser)));
  // ⚠ FRAME TRANSLATION, and it is NOT a my->their string swap. $lastDecision was minted in the
  // CHOOSER's frame ('myGroundArena-N'). At two seats 'theirGroundArena-N' names that same unit, but at
  // 3+ seats 'their...' is ambiguous — GetZoneObject resolves it to the caster's FIRST opponent, so a
  // seat-4 chooser's pick silently defeated SEAT 2's unit instead. SWUForeignMzID emits the
  // seat-addressed 'p{n}...' form above two seats and the historical 'their...' at two.
  $zone = (strpos((string)$lastDecision, 'SpaceArena') !== false) ? 'SpaceArena' : 'GroundArena';
  $idx  = (int)substr((string)$lastDecision, strrpos((string)$lastDecision, '-') + 1);
  $casterMz = SWUForeignMzID($caster, intval($chooser), $zone, $idx);
  global $playerID;
  $playerID = $caster;
  SWUDefeatUnit($caster, $casterMz);
};
// Universal: deal the MZSPLITASSIGN result ($lastDecision) simultaneously (apply-all then sweep).
// Shared by any "deal N divided among units" card (SOR_135 Palpatine, SOR_092 Overwhelming Barrage).
$customDQHandlers["SPLIT_DAMAGE"] = function ($player, $parts, $lastDecision) {
  // $parts[0] = the source token written by SWUOfferSplitDamage — the unit that DEALS the damage when
  // the ability names one (CR 9.12). Empty for a source-less "deal N divided among enemy units".
  SWUDealSplitDamage(intval($player), (string) $lastDecision, (string) ($parts[0] ?? ''));
};

// Goldfish ⚗ Practice menu — resolve the "defeat/bounce one of your units" MZCHOOSE. $lastDecision
// is the chosen my-unit mzID. Goldfish-gated (belt-and-suspenders with the CustomInput case).
$customDQHandlers["GfDefeat"] = function ($player, $parts, $lastDecision) {
  if (!function_exists('SWUGameMode') || SWUGameMode() !== 'goldfish')
    return;
  if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS')
    return;
  SWUDefeatUnit(1, (string) $lastDecision);
};
$customDQHandlers["GfBounce"] = function ($player, $parts, $lastDecision) {
  if (!function_exists('SWUGameMode') || SWUGameMode() !== 'goldfish')
    return;
  if ($lastDecision === null || $lastDecision === '' || $lastDecision === '-' || $lastDecision === 'PASS')
    return;
  SWUBounceUnit(1, (string) $lastDecision);
};

// ── Unit activated abilities ("Action [...]") ───────────────────────────────
// $unitAbilities[providerCardID] = function($player, $hostMzID). The provider is the
// unit's own CardID, or an attached upgrade's CardID (SWUGetUnitActionProvider).
// $unitActionResourceCosts[providerCardID] = resource cost (omit = 0). SWUUnitAction
// pays the Exhaust cost and dispatches; the handler ends with SWU_AFTER_ACTION.
$unitAbilities = [];
$unitActionResourceCosts = [];
// Base cost-kind per provider: 'exhaust' (default — requires ready, exhausts the unit) or
// 'defeat' (no ready requirement; the unit is defeated to pay, e.g. SOR_110 Frontline Shuttle).
$unitActionCostKind = [];
// Any-player-usable unit actions (LAW_156 Hunter For Hire: "Any player may use this ability."). Surfaced
// on the OPPONENT's units too (SWUComputeActionsData); SWUUnitAction has no controller gate.
$anyPlayerUnitActions = [];



// LAW_015 Jabba the Hutt (deployed leader unit) — "Action: Play an Underworld unit from your hand. If
// you defeated a Credit while paying its cost, that unit gains Ambush for this phase." No exhaust / no
// resource cost (the unit's own play cost is the limiter). The play runs through the full SWUBeginPlayCard
// ceremony so the Credit alt-payment is offered; if a Credit is defeated (CREDIT_PAY), the SWU_JABBA015
// flags grant Ambush at entry (ActivateCard), mirroring LOF_180. Front side is in LeaderAbilities.php.
function _SWULaw015PlayableUnderworldUnits(int $player): array
{
  global $playerID;
  $playerID = $player;
  $budget = SWUTotalPaymentCapacity($player); // ready resources + Credits + SEC_122 Droids (CR 3.13)
  $hand = GetHand($player);
  $out = [];
  for ($i = 0; $i < count($hand); $i++) {
    $c = $hand[$i];
    if (SWUObjGone($c))
      continue;
    $cid = $c->CardID;
    if (CardType($cid) !== 'Unit' || !HasTrait($cid, 'Underworld'))
      continue;
    if (_SWUCantPlayFromHand($cid))
      continue;
    if (SWUComputePlayCost($player, $c) <= $budget)
      $out[] = "myHand-{$i}";
  }
  return $out;
}

function _SWUPlayForeignResourceFree(int $caster, int $opp, string $resMz, string $cardID, string $type): bool
{
  global $playerID;
  $playerID = $caster;
  $o = GetZoneObject($resMz);
  if (SWUObjGone($o))
    return false;
  // Delegate to the single play path (LAW_066 is a FREE play → ignoreCost, prepaid 0). ActivateCard
  // removes the source resource, adds the cards-played counter, logs the play, routes an event to the
  // OWNER's ($opp) discard and a unit to Owner=$opp/Controller=$caster, and applies every play-time effect
  // (Saw Gerrera / Adi Gallia / Relentless / TWI_210 / telemetry) the old branches skipped. Do NOT
  // pre-remove $o — ActivateCard reads it via GetZoneObject.
  SWUWithNestedActionFrame(fn() => ActivateCard($caster, $resMz, 1, 0, 0, $opp));
  return true;
}

// LAW_215 Vermillion — When Attack Ends (if survived — the OnAttackEnd seam auto-gates): reveal the top
// card of a deck, then choose a player; they may play it for FREE; if they do, a DIFFERENT player creates
// Credit tokens equal to that card's cost. (2-player: "a different player" auto-resolves to the single
// other player — Twin Suns: the active player picks; see [[project-twin-suns-format]].)
// V = Vermillion's controller, D = chosen deck's owner, P = chosen player who may play, OtherPlayer(P) =
// the "different player" who gets the Credits. The played card is owned by D, controlled by P (foreign-
// owned free play, mirror of LAW_066). No SWUAfterAction — the combat resume owns the action's close.
function _SWUVermillionReveal(int $V, int $D): void
{
  global $playerID;
  $playerID = $D;
  $idx = _SWUTopDeckFrontIdx($D);
  if ($idx === -1)
    return;
  $cardID = GetDeck($D)[$idx]->CardID ?? '';
  if ($cardID === '')
    return;
  DoRevealCard($D, "myDeck-{$idx}");   // public reveal of the deck-top
  $playerID = $V;
  DecisionQueueController::AddDecision(
    $V,
    "OPTIONCHOOSE",
    SWUPlayerPickerLabels($V),
    1,
    tooltip: "Choose_a_player_to_play_" . str_replace(' ', '_', CardTitle($cardID)) . "_for_free"
  );
  DecisionQueueController::AddDecision($V, "CUSTOM", "LAW_215#1|{$V}|{$D}|{$cardID}", 1);
}
// SOR_093 Alliance Dispatcher / TWI_120 Strategic Acumen moved to their card files;
// shared logic is SWUPlayFromHandWithDiscount() in CardHelpers.php.

// ── LOF Action [Exhaust] unit abilities (Phase 11) ───────────────────────────────────────────────────// Plays the chosen Imperial ($lastDecision) at full cost, forcing it to enter READY, then lets each
// opponent may-ready a unit. ActivateCard owns the play's end-of-action (do not add SWU_AFTER_ACTION).
// "Each opponent may ready a unit" is an UNCONDITIONAL sentence (candidate #2 fix, 2026-08-14):
// it resolves whether the play half happened or not, so BOTH branches queue the offer.
$customDQHandlers["OZZEL_PLAY"] = function ($player, $parts, $lastDecision) {
  global $playerID, $gForceEnterReady;
  $playerID = intval($player);
  // Queue the ready-clause BUILDER first (ahead of any after-action turn swap). It computes its
  // pool at DRAIN time — after the played Imperial has seated and Ozzel's action-exhaust is
  // visible — never here (the pre-play board is stale).
  // "EACH opponent may ready a unit" — one builder per live opponent (was OtherPlayer()).
  foreach (OpponentsOf(intval($player)) as $ozOpp) {
    DecisionQueueController::AddDecision($ozOpp, "CUSTOM", "OZZEL_READY_OFFER", 1);
  }
  if (SWUDecisionDeclined($lastDecision)) {
    SWUAfterAction($player);
    return;
  }
  // Play the chosen Imperial — it enters READY (Ozzel overrides the default exhausted entry).
  $gForceEnterReady = true;
  SWUWithNestedActionFrame(fn() => ActivateCard(intval($player), $lastDecision, false, 0));
  $gForceEnterReady = false;
};

// SOR_129 "Each opponent may ready a unit" — drain-time offer builder, run on the OPPONENT's queue.
// The text is an unqualified "a unit": ANY unit in ANY arena, either side (the caster's units
// included). Pointless-prompt doctrine: only EXHAUSTED units are material picks (readying a ready
// unit is a no-op), and with none anywhere the prompt is skipped entirely.
$customDQHandlers["OZZEL_READY_OFFER"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $savedPID = $playerID;
  $playerID = intval($player);
  $units = array_values(array_filter(array_merge(
    ZoneSearch('myGroundArena',    AnyUnitFilter),
    ZoneSearch('mySpaceArena',     AnyUnitFilter),
    ZoneSearch('theirGroundArena', AnyUnitFilter),
    ZoneSearch('theirSpaceArena',  AnyUnitFilter)
  ), function($mz) {
    $o = GetZoneObject($mz);
    return $o !== null && empty($o->removed) && intval($o->Status ?? 1) === 0;
  }));
  if (!empty($units)) {
    SWUQueueMayChooseTarget(intval($player), $units, "You_may_ready_a_unit", "Ready_a_unit", "READY_UNIT");
  }
  $playerID = $savedPID;
};

// Cross-card: play the chosen hand card ($lastDecision) at $parts[0] discount. ActivateCard
// owns the end-of-action (unit branch → SWUAfterAction; event branch → FINISH_PLAY_CARD),
// so do NOT append SWU_AFTER_ACTION here — that would resolve the action twice.
$customDQHandlers["DISCOUNT_PLAY_FROM_HAND"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision)) {
    SWUAfterAction($player);
    return;
  }
  $discount = max(0, intval($parts[0] ?? 1));
  // ⚠ SWUBeginPlayCard, NOT ActivateCard. Playing a card from hand has to go through the FULL play
  // ceremony, and ActivateCard is only its second half — SWUBeginPlayCard is what owns the pre-payment
  // steps: ADDITIONAL COSTS (Exploit, HMW_048 Vernestra Rwoh's discard-bottoming, HMW_125 The
  // Marauder's damage picks) and TWI_116 Clone's copy choice.
  // $unitOnly = TRUE: every caller of this shared continuation grants a "play a UNIT" (or an EVENT),
  // never a bare "play a card" — and per CR 17.c / CR 525.a a Piloting card played through a "play a
  // unit" effect cannot be played as an upgrade. So the unit-vs-pilot fork stays suppressed here, as it
  // was under ActivateCard (ASH_108 Crix Madine + SOR_093 Alliance Dispatcher both assert this).
  // Routing here through ActivateCard silently SKIPPED all of them, so a card played by another card's
  // ability (LOF_094 Jedi Consular, and every other "play a unit from your hand" effect that lands on
  // this shared continuation) paid no additional cost and gained nothing from it. Per CR step 3.c an
  // additional cost is determined and paid on EVERY play, however the play was initiated.
  // Reported 2026-09-02 via Jedi Consular -> Vernestra Rwoh: her donors were never offered, so she
  // entered as a plain 5/5 having bottomed nothing.
  // SWUBeginPlayCard falls through to SWUContinuePlayAfterExploit synchronously when no picker applies,
  // so the no-additional-cost case is byte-identical to the old behaviour.
  SWUWithNestedActionFrame(fn() => SWUBeginPlayCard(intval($player), $lastDecision, $discount, unitOnly: true));
};
// Universal: draw $parts[0] cards for the acting player.
$customDQHandlers["DRAW_CARD"] = function ($player, $parts, $lastDecision) {
  DoDrawCard(intval($player), max(1, intval($parts[0] ?? 1)));
};

// SEC_147 Chopper — "When this unit deals combat damage to a base: each player discards a card from
// their hand." Combat-hit trigger (dealtToBase) collected in SWUCollectCombatHitTriggers.
function SEC147EachDiscardTrigger($player, $mzID)
{
  global $playerID;
  $savedPID = $playerID;
  foreach ([intval($player), OtherPlayer(intval($player))] as $p) {   // active player first, then opponent
    $playerID = $p;
    SWUOfferDiscard($p, ['from'=>'own']);
  }
  $playerID = $savedPID;
}

// SHD_145 Headhunting — a chosen unit attacks (noBases); a Bounty Hunter gets +2/+0 for that attack.
// Decrement the loop count + record the attacker so it isn't re-offered; a decline ends the loop.
$customDQHandlers["SHD145_ATTACK"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision)) {
    SetSWUVar('SWU_SHD145_LOOP', '');
    return;
  }
  $obj = GetZoneObject($lastDecision);
  if (SWUObjGone($obj)) {
    SetSWUVar('SWU_SHD145_LOOP', '');
    return;
  }
  $uid = intval($obj->UniqueID ?? 0);
  $lp = explode(',', GetSWUVar('SWU_SHD145_LOOP', ''));
  $remaining = max(0, intval($lp[0] ?? 0) - 1);
  $excl = array_values(array_filter(array_slice($lp, 1), fn($x) => $x !== ''));
  $excl[] = strval($uid);
  SetSWUVar('SWU_SHD145_LOOP', implode(',', array_merge([strval($remaining)], $excl)));   // comma-CSV, NOT pipe
  if (HasTrait($obj->CardID ?? '', 'Bounty Hunter'))
    SWUAddAttackPowerBonus($lastDecision, 2);  // +2/+0 for this attack
  BeginSWUAttack(intval($player), $lastDecision, true);   // noBases = true
};

// TS26_59 Brothers — a chosen unique unit attacks with all combat damage to it prevented (TS26_59
// attack-duration marker). Decrement the loop count + record the attacker so it isn't re-offered.
$customDQHandlers["TS26059_ATTACK"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision)) {
    SetSWUVar('SWU_TS26059_LOOP', '');
    return;
  }
  $obj = GetZoneObject($lastDecision);
  if (SWUObjGone($obj)) {
    SetSWUVar('SWU_TS26059_LOOP', '');
    return;
  }
  $uid = intval($obj->UniqueID ?? 0);
  $lp = explode(',', GetSWUVar('SWU_TS26059_LOOP', ''));
  $remaining = max(0, intval($lp[0] ?? 0) - 1);
  $excl = array_values(array_filter(array_slice($lp, 1), fn($x) => $x !== ''));
  $excl[] = strval($uid);
  SetSWUVar('SWU_TS26059_LOOP', implode(',', array_merge([strval($remaining)], $excl)));   // comma-CSV, NOT pipe
  AddTurnEffect($lastDecision, SWUMakeTurnEffect('TS26_59', [], SWU_DUR_ATTACK)); // prevent all combat damage to it
  BeginSWUAttack(intval($player), $lastDecision);   // bases allowed
};

$customDQHandlers["MONMOTHMA_ATTACK"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision)) {
    SetSWUVar('SWU_MONMOTHMA_LOOP', '');   // declined → end the loop (play flow finalizes)
    return;
  }
  $obj = GetZoneObject($lastDecision);
  if (SWUObjGone($obj)) {
    SetSWUVar('SWU_MONMOTHMA_LOOP', '');
    return;
  }
  $uid = intval($obj->UniqueID ?? 0);
  $loop = GetSWUVar('SWU_MONMOTHMA_LOOP', '');
  SetSWUVar('SWU_MONMOTHMA_LOOP', $loop === '' ? strval($uid) : $loop . ',' . $uid); // exclude it next round
  BeginSWUAttack(intval($player), $lastDecision, true);   // noBases = true; works even if exhausted
};
// SEC_088 First Light — "When this unit attacks and defeats a unit: you may draw a card." Trigger
// collected in SWUCollectCombatHitTriggers; dispatched here as a may-draw.
function SEC088MayDrawTrigger($player, $mzID)
{
  global $playerID;
  $playerID = intval($player);
  DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Draw_a_card?");
  DecisionQueueController::AddDecision($player, 'CUSTOM', 'SEC_088#0', 1);
}// Prevention — ABILITY/effect damage path (deferred from SWUDealDamageToUnit). Decline → apply the
// damage (skipPrevent); pick a trait-sharing friendly → defeat it and prevent the damage.
$customDQHandlers["AMIDALA_PREVENT_ABILITY"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $uid = intval($parts[0] ?? 0);
  $amount = intval($parts[1] ?? 0);
  $src = intval($parts[2] ?? 0);
  $amz = SWUFindMzByUID($uid);
  if (SWUDecisionDeclined($lastDecision)) {
    // Declined → apply the deferred damage in full. SWUDealDamageToUnit resolves its target mzID under
    // the SOURCE's frame ($src), but $amz was resolved under Amidala's controller ($player) — re-resolve
    // it under $src or the damage lands on the wrong (or an empty) slot and Amidala takes 0.
    $playerID = intval($src);
    $srcAmz = SWUFindMzByUID($uid);
    if ($srcAmz !== null)
      SWUDealDamageToUnit($srcAmz, $amount, $src, null, true);
    return;
  }
  SWUDefeatUnit(intval($player), $lastDecision);                 // defeat the chosen friendly → prevent
  if ($amz !== null)
    SWUQueuePreventedAnim($amz, intval($player));
};
// Prevention — SPLIT/divided damage path (SWUDealSplitDamage). One offer per SEC_101 target in the split;
// all carry-state rides the PARAM (DQ variables don't survive the request boundary). $parts = [casterPlayer,
// amidalaUID, amount, decider, applyCSV, offerCSV]. Accept (a trait-sharing friendly mzID) → defeat it and
// DROP this hit (prevented); decline ('-') → re-park the hit to apply with the rest. Either way, continue
// the offer loop, which applies all surviving damage simultaneously once the offers run out.
$customDQHandlers["SPLIT_PREVENT_RESOLVE"] = function ($player, $parts, $lastDecision) {
  $caster = intval($parts[0] ?? 0);
  $curUid = intval($parts[1] ?? 0);
  $curAmt = intval($parts[2] ?? 0);
  $decider = intval($parts[3] ?? 0);
  $apply = _SWUDecodeHits($parts[4] ?? '');
  $offer = _SWUDecodeHits($parts[5] ?? '');
  $srcTok = (string) ($parts[6] ?? '');   // the dealer, carried across this decision (CR 9.12)
  global $playerID;
  $playerID = $decider;
  if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
    SWUDefeatUnit($decider, $lastDecision);                       // pay the cost → prevent this hit
    $amz = SWUFindMzByUID($curUid);
    if ($amz !== null)
      SWUQueuePreventedAnim($amz, $decider);
  } else {
    $apply[] = ['uid' => $curUid, 'amount' => $curAmt];           // declined → apply it with the rest
  }
  _SWUSplitOfferStep($caster, $offer, $apply, $srcTok);
};
// Prevention — COMBAT damage path. Pick → defeat friendly + set the one-shot marker SWUCombatDamage
// consumes (skips Amidala's combat damage this attack). Decline → damage applies normally.
$customDQHandlers["AMIDALA_PREVENT_COMBAT"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision))
    return;
  SWUDefeatUnit(intval($player), $lastDecision);
  AddGlobalEffects(intval($player), 'SWU_AMIDALA_PREVENT_' . intval($parts[0] ?? 0));
};
// ASH_062 The Mandalorian — ABILITY/effect damage prevention (deferred from SWUDealDamageToUnit). Decline
// (or no Shield left to pay) → apply the damage (skipPrevent). Accept → defeat a Shield on a friendly
// ASH_062 and prevent the damage. $parts = [protectedUID, amount, sourcePlayer].
$customDQHandlers["ASH062_PREVENT_ABILITY"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $uid = intval($parts[0] ?? 0);
  $amount = intval($parts[1] ?? 0);
  $src = intval($parts[2] ?? 0);
  $pmz = SWUFindMzByUID($uid);
  $unit = $pmz !== null ? GetZoneObject($pmz) : null;
  // Declined / couldn't pay → apply the deferred damage. SWUDealDamageToUnit resolves its target mzID
  // under the SOURCE's frame ($src), but $pmz above was resolved under the protected unit's controller
  // ($player) — for a cross-player source the frames differ, so re-resolve under $src or the damage
  // lands on the wrong (or an empty) slot: the target's own Shield never popped and the ping silently
  // vanished (live-game Cad Bane report). Same fix as AMIDALA_PREVENT_ABILITY. skipPrevent=true skips
  // only the deferral blocks, NOT the target's own Shield — that still absorbs the instance.
  $applyDeferred = function () use ($uid, $amount, $src, $player) {
    global $playerID;
    $playerID = $src > 0 ? intval($src) : intval($player);
    $srcPmz = SWUFindMzByUID($uid);
    if ($srcPmz !== null)
      SWUDealDamageToUnit($srcPmz, $amount, $src, null, true);
  };
  if ($lastDecision !== 'YES') {
    $applyDeferred();                                         // declined → apply now
    return;
  }
  $provider = $unit !== null ? _SWUAsh062Provider($unit) : null;
  if ($provider === null || !SWUConsumeShieldToken($provider)) {
    $applyDeferred();                                         // couldn't pay → apply now
    return;
  }
  if ($pmz !== null)
    SWUQueuePreventedAnim($pmz, intval($player));             // paid → prevented
};
// ASH_062 — COMBAT damage prevention. Accept → defeat a Shield on a friendly ASH_062 + set the one-shot
// marker SWUCombatDamage consumes (keyed on the PROTECTED unit's UID). Decline / no Shield → damage normal.
$customDQHandlers["ASH062_PREVENT_COMBAT"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if ($lastDecision !== 'YES')
    return;
  $uid = intval($parts[0] ?? 0);
  $pmz = SWUFindMzByUID($uid);
  $unit = $pmz !== null ? GetZoneObject($pmz) : null;
  $provider = $unit !== null ? _SWUAsh062Provider($unit) : null;
  if ($provider === null || !SWUConsumeShieldToken($provider))
    return;        // couldn't pay → no prevent
  AddGlobalEffects(intval($player), 'SWU_ASH062_PREVENT_' . $uid);
};

// HMW_045 Logray — field observer for "another friendly unit that costs 3 or less is dealt damage".
// COST is the PRINTED cost (standing rule), read off the damaged unit's CardID. "Another" excludes the
// damaged unit itself, so a damaged Logray does not trigger his own ability — but a SECOND Logray does,
// which is why this counts copies rather than returning on the first.
// The offer is QUEUED, not built inline: this fires mid-combat, before CleanupRemovedCards compacts the
// arenas, so a pool built now would carry positional mzIDs that go stale before the player answers
// (the SEC_143 lesson).
function _SWUHmw045CheckObserve($obj, int $amount): void
{
  if ($obj === null || $amount <= 0)
    return;
  $ctrl = intval($obj->Controller ?? 0);
  if ($ctrl <= 0)
    return;
  if (intval(CardCost($obj->CardID ?? '')) > 3)
    return;
  $selfUid = intval($obj->UniqueID ?? 0);
  $lograys = 0;
  foreach (GetUnitsInPlay($ctrl) as $u) {
    if (SWUObjGone($u) || ($u->CardID ?? '') !== 'HMW_045')
      continue;
    if (intval($u->UniqueID ?? 0) === $selfUid)
      continue;                       // "another" — a damaged Logray is not his own trigger
    if (LostAbilities($u))
      continue;                       // the grant is HIS ability
    $lograys++;
  }
  for ($i = 0; $i < $lograys; $i++) {
    DecisionQueueController::AddDecision($ctrl, "CUSTOM", "HMW045_OFFER", 1);
  }
}

// ── HMW_013 Cham Syndulla, Hammer of Ryloth ──────────────────────────────────────────────────────
// ONE trigger, shared by both sides, differing only in what it costs:
//   FRONT : "When non-combat damage is dealt to a friendly unit or base: You may exhaust this leader.
//            If you do, deal 1 damage to an enemy unit or base."
//   DEPLOY: same trigger, no exhaust — "You may deal 1 damage to an enemy unit or base."
// So the OPTIONALITY sits in a different place on each side: the front's "may" is on paying the
// exhaust (and the damage that follows is mandatory, per "If you do"), while the deployed side's
// "may" is on the target itself. Do not collapse them into one shape.
//
// $damagedCtrl is the controller of the damaged unit, or the OWNER of the damaged base.
// Called from BOTH damage funnels — _SWUOnUnitDamaged for units and SWUDealDamageToBase for bases —
// because "a friendly unit or base" spans two entirely separate code paths.
function _SWUCham013DeployedActive(int $seat): bool
{
  if (!_SWULeaderDeployed($seat, 'HMW_013'))
    return false;
  foreach (GetUnitsInPlay($seat) as $u) {
    if (!SWUObjGone($u) && ($u->CardID ?? '') === 'HMW_013')
      return !LostAbilities($u);   // a blanked leader unit grants nothing
  }
  return false;
}

// The enemy pool, shared by both sides so the front and deployed offers cannot drift apart.
function _SWUCham013Targets(int $player): array
{
  return _SWUCollectUnitTargets($player, [
    'side' => 'their', 'includeBases' => true, 'baseSide' => 'their',
  ]);
}

function _SWUCham013CheckObserve(int $damagedCtrl, int $amount, bool $isCombat): void
{
  if ($amount <= 0 || $isCombat || $damagedCtrl <= 0)
    return;                          // ⚠ "NON-COMBAT damage" is the whole gate of the card
  foreach (GetLiveSeatsArray() as $seat) {
    // "friendly" is a TEAM relation (USER RULING 2026-08-26): in Team Suns a teammate's damaged unit
    // or base turns your Cham on. SWUTeamOf returns the seat itself outside a team game, so this
    // reduces to `$seat === $damagedCtrl` in Premier and Twin Suns — byte-identical there.
    if (SWUTeamOf($seat) !== SWUTeamOf($damagedCtrl))
      continue;
    if (!_SWULeaderReadyUndeployed($seat, 'HMW_013') && !_SWUCham013DeployedActive($seat))
      continue;
    // QUEUED rather than offered inline, for Logray's reason: this can fire mid-effect, before
    // CleanupRemovedCards compacts the arenas, so a pool built now would carry stale positional
    // mzIDs. The CUSTOM drains post-cleanup and builds the offer against the settled board.
    // One per damaged thing (USER RULING 2026-08-26) — an AoE hitting three friendly units queues
    // three, matching how HMW_045 Logray already behaves through this same seam.
    DecisionQueueController::AddDecision($seat, "CUSTOM", "HMW013_OFFER", 1);
  }
}

// Resolves the "ready N friendly resources" split (SEC_225 Synara San, SHD_221 Wanted).
// $parts[0] = the TOTAL to ready; $lastDecision = how many of it come from the player's OWN resources.
$customDQHandlers["READY_FRIENDLY_RES"] = function ($player, $parts, $lastDecision) {
  $total = intval($parts[0] ?? 0);
  if ($total <= 0) return;
  $mine = intval($lastDecision);
  if ($mine < 0) $mine = 0;
  if ($mine > $total) $mine = $total;   // never trust the client's number past the offered range
  _SWUApplyFriendlyResourceSplit(intval($player), $mine, $total - $mine, (string)($parts[1] ?? 'READY'));
};

$customDQHandlers["HMW013_OFFER"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  // Re-derive the SIDE at drain time — Cham may have deployed, exhausted or been blanked since.
  if (empty(_SWUCham013Targets(intval($player))))
    return;                          // no enemy unit or base left: never offer a choice that can only fizzle
  if (_SWUCham013DeployedActive(intval($player))) {
    // Deployed: no cost, so the "may" is on the TARGET.
    SWUOfferUnitTarget(intval($player), '', [
      'side' => 'their', 'includeBases' => true, 'baseSide' => 'their',
      'continuation' => 'DEAL_TARGET', 'amount' => 1, 'may' => true,
      'question' => 'Deal_1_damage_to_an_enemy_unit_or_base?',
      'prompt' => 'Choose_an_enemy_unit_or_base',
    ]);
    return;
  }
  if (!_SWULeaderReadyUndeployed(intval($player), 'HMW_013'))
    return;                          // front side needs a READY leader to pay the exhaust
  DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
      tooltip: "Exhaust_Cham_Syndulla_to_deal_1_damage_to_an_enemy_unit_or_base?");
  DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_013#0", 1);
};

// Front side, after the player accepts: pay the exhaust, then the damage is MANDATORY ("If you do").
$customDQHandlers["HMW_013#0"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision !== 'YES')
    return;
  global $playerID;
  $playerID = intval($player);
  if (!_SWULeaderReadyUndeployed(intval($player), 'HMW_013'))
    return;
  foreach (GetLeader(intval($player)) as $l) {
    if (($l->CardID ?? '') === 'HMW_013' && empty($l->Deployed)) { $l->Ready = false; break; }
  }
  SWUOfferUnitTarget(intval($player), '', [
    'side' => 'their', 'includeBases' => true, 'baseSide' => 'their',
    'continuation' => 'DEAL_TARGET', 'amount' => 1,   // mandatory: the "may" was the exhaust
    'prompt' => 'Choose_an_enemy_unit_or_base',
  ]);
};

// Builds Logray's offer post-cleanup, against the compacted board.
$customDQHandlers["HMW045_OFFER"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $targets = [];
  foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {   // "an enemy unit" — no arena word
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed))
        $targets[] = $mz;
    }
  }
  if (empty($targets))
    return;                           // no enemy unit → nothing to offer
  SWUQueueMayChooseTarget(intval($player), $targets,
      "Deal_1_damage_to_an_enemy_unit?", "Deal_1_damage_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|1");
};


// SEC_143 The Elite Squad — Grit (auto) + When Played / "When damage is dealt to this unit": you may
// deal 2 damage to another unique unit. The on-damaged reaction is POST-damage (no combat-pause): it is
// fired from _SWUCollectOnUnitDamagedReactions (combat) and SWUDealDamageToUnit (ability/effect damage).
function _SWUOnUnitDamaged($obj, int $amount = 0, bool $isCombat = false, bool $survived = true): void
{
  // ⚠ LOAD-BEARING, and it was missing until 2026-08-27. The SHD_084 case below pins the frame with
  // `$shd084Saved = $playerID; $playerID = $shd084Ctrl;` — without this declaration BOTH statements
  // touched a LOCAL, so the pin was a no-op, `GetMzID()` (which reads the GLOBAL) minted the unit's mzID
  // in whatever frame was ambient, and `DoGiveExperienceToken` re-resolved that relative string under
  // the unit's OWN frame. When the Dark Trooper DEFENDED, "their…-0" flipped sides and its Experience
  // token was handed to the unit that had just hit it. Guard:
  // shd/PhaseiiiDarkTrooper.md::DarkTrooper_DEFENDS_ExperienceGoesToTheTrooper_NotTheAttacker.
  global $playerID;
  if ($obj === null)
    return;
  // SEC_143 The Elite Squad — "When damage is dealt to this unit: you may deal 2 to another unique unit."
  // NO "and survives" clause, so it fires even when this damage DEFEATS Elite Squad (the target is ANOTHER
  // unit, so Elite Squad being gone is fine). Handled before the $survived gate below.
  if (($obj->CardID ?? '') === 'SEC_143' && $amount > 0) {
    // QUEUED, not built inline: this fires mid-combat, BEFORE CleanupRemovedCards compacts the arena
    // arrays — a pool built now carries positional mzIDs that go stale by the time the player answers
    // (a defeated earlier slot shifts every later index). The CUSTOM drains post-cleanup and builds
    // the offer against the compacted board. Same shape as the Traitorous queued revert.
    $sec143Ctrl = intval($obj->Controller ?? 0);
    if ($sec143Ctrl > 0) {
      DecisionQueueController::AddDecision($sec143Ctrl, "CUSTOM",
          "SEC143_OFFER|" . intval($obj->UniqueID ?? 0), 1);
    }
  }
  // HMW_045 Logray, Bright Tree Shaman — "When ANOTHER friendly unit that costs 3 or less is dealt
  // damage: You may deal 1 damage to an enemy unit."
  // ⚠ There is NO "and survives" clause, so this fires even when the damage DEFEATS the unit — which is
  // why it sits ABOVE the $survived gate, beside SEC_143 rather than with the observers below it.
  _SWUHmw045CheckObserve($obj, $amount);
  // HMW_013 Cham Syndulla — "When NON-COMBAT damage is dealt to a friendly unit or base". Above the
  // $survived gate for the same reason as Logray: there is no "and survives" clause, so a friendly
  // unit being killed by an event still turns him on.
  _SWUCham013CheckObserve(intval($obj->Controller ?? 0), $amount, $isCombat);
  // Every observer below has an explicit "and survives" / "isn't defeated" clause (or writes a marker on the
  // still-in-play unit), so they must NOT fire when the damage defeated the unit.
  if (!$survived)
    return;
  // ASH_188 Galvanized Leap — mark any unit that was damaged (and survived) this phase. (Cleared at
  // RegroupPhaseStart by the central phase-effect expiry.)
  if ($amount > 0 && is_array($obj->TurnEffects ?? null) && !in_array('SWU_DAMAGED_PHASE', $obj->TurnEffects, true)) {
    $obj->TurnEffects[] = 'SWU_DAMAGED_PHASE';
  }
  switch ($obj->CardID ?? '') {
    case 'SHD_084': // Phase-III Dark Trooper — "When COMBAT damage is dealt to this unit: give it an
      // Experience token (if it survives — guaranteed here, $obj is the surviving unit)."
      // ⚠ GetMzID() resolves against the AMBIENT $playerID ("my…" vs "their…"), so calling it on a unit
      // whose controller is not the ambient frame yields a two-seat answer: at 3+ seats a seat-3 unit
      // returns "theirGroundArena-N", which resolves to SEAT 2 — the token would land on the wrong unit.
      // Pin the frame to the unit's own controller first, the same way _SWUOnPlayerDrew does for LAW_052.
      if ($isCombat && $amount > 0) {
        $shd084Ctrl = intval($obj->Controller ?? 0);
        $shd084Saved = $playerID; $playerID = $shd084Ctrl;
        DoGiveExperienceToken($shd084Ctrl, $obj->GetMzID());
        $playerID = $shd084Saved;
      }
      break;
  }
  // SEC_002 Jabba (deployed) — field observer: "When ANOTHER friendly unit is dealt damage and
  // survives: you may have that unit deal that much damage to an enemy unit. Once each round."
  _SWUSec002CheckObserve($obj, $amount);
  // SHD_250 Tarfful — "When a friendly Wookiee unit is dealt COMBAT damage and isn't defeated: that
  // unit deals that much damage to an enemy ground unit."
  _SWUShd250CheckObserve($obj, $amount, $isCombat);
  // ASH_032 Rancor Keeper — "When a friendly unit is dealt damage and survives: deal 1 damage to any
  // number of bases. Use this ability only once each round."
  _SWUAsh032CheckObserve($obj);
  // HMW_211 Tech, I Thought It Was Obvious — "When THIS unit is dealt damage and survives: you may
  // exhaust a unit." A self observer, so it sits here under the $survived gate rather than above it with
  // Logray/Cham. ⚠ It has NO "once each round" clause (unlike ASH_032 and SEC_002 just above), so every
  // qualifying damage instance gets its own offer. See cards/hmw/Tech_IThoughtItWasObvious.php.
  _SWUHmw211CheckObserve($obj, $amount);
  // HMW_169 Crosshair, I've Changed — "When THIS unit is dealt damage and survives: Each player draws a
  // card." Same self-observer shape as HMW_211 above; its SECOND clause ("when an opponent draws…") is a
  // field observer on _SWUOnPlayerDrew, and the draws made here are what feed it.
  _SWUHmw169CheckObserve($obj, $amount);
  // TWI_016 Jango Fett — "When a FRIENDLY unit deals damage to an ENEMY unit: ..." $obj is the damaged
  // unit (the potential enemy). For combat the source is definitionally the opposing unit, so the source
  // controller is OtherPlayer($obj->Controller). For ability/effect damage read the recorded source-unit
  // context (SWU_DMG_SRC) — only UNIT dispatch sets it, so event/base damage correctly doesn't qualify.
  $srcController = 0;
  if ($isCombat) {
    $srcController = OtherPlayer(intval($obj->Controller ?? 0));
  } else {
    $src = GetSWUVar('SWU_DMG_SRC', '');
    if ($src !== '') {
      $pp = explode(',', $src);
      $srcController = intval($pp[1] ?? 0);
    }
  }
  _SWUTwi016CheckObserve($obj, $srcController);
}

// TWI_016 Jango Fett — the damaged unit is $damagedObj; $srcController is the controller of the friendly
// unit that dealt the damage. Fires only when the target is an ENEMY of that controller (source != target
// controller), the target survived (guaranteed by the callers) and is still ready (exhausting gains
// nothing otherwise). Offers the deployed side ("may exhaust that unit") or, while undeployed and ready,
// the front side ("may exhaust this leader; if you do, exhaust that enemy unit").
function _SWUTwi016CheckObserve($damagedObj, int $srcController): void
{
  if ($damagedObj === null || $srcController <= 0)
    return;
  $enemyCtrl = intval($damagedObj->Controller ?? 0);
  if ($enemyCtrl <= 0 || $enemyCtrl === $srcController)
    return; // must be a friendly source → enemy target
  if (intval($damagedObj->Status ?? 0) !== 1)
    return;          // already exhausted → auto-skip (SEC_069)
  $enemyUID = intval($damagedObj->UniqueID ?? 0);
  if ($enemyUID <= 0)
    return;
  $jango = $srcController; // the source's controller is Jango's controller
  // Tooltip names the enemy unit being exhausted; underscore-encode so the client's _→space render works.
  $enemyName = str_replace(' ', '_', SWUObjectTitle($damagedObj));
  global $playerID;
  // Deployed side — no leader-exhaust cost.
  if (_SWULeaderDeployed($jango, 'TWI_016')) {
    $playerID = $jango;
    DecisionQueueController::AddDecision($jango, "YESNO", "-", 1, tooltip: "Exhaust_enemy_{$enemyName}_using_Jango_Fett?");
    DecisionQueueController::AddDecision($jango, "CUSTOM", "TWI_016#0|{$enemyUID}|0", 1);
    return;
  }
  // Front (undeployed) side — cost is exhausting the leader, so it must be ready; else auto-skip.
  $ready = false;
  foreach (GetLeader($jango) as $l) {
    if (!empty($l->removed))
      continue;
    if (($l->CardID ?? '') === 'TWI_016') {
      $ready = (empty($l->Deployed) && !empty($l->Ready));
      break;
    }
  }
  if (!$ready)
    return; // not Jango's controller, or the leader can't pay the exhaust cost
  $playerID = $jango;
  DecisionQueueController::AddDecision($jango, "YESNO", "-", 1, tooltip: "Exhaust_enemy_{$enemyName}_using_Jango_Fett?");
  DecisionQueueController::AddDecision($jango, "CUSTOM", "TWI_016#0|{$enemyUID}|1", 1);
}// SHD_250 Tarfful observer — the damaged-and-surviving friendly Wookiee ($obj) deals $amount to an enemy
// ground unit, if its controller controls a Tarfful. Combat damage only.
function _SWUShd250CheckObserve($obj, int $amount, bool $isCombat): void
{
  if ($obj === null || $amount <= 0 || !$isCombat)
    return;
  if (!HasTrait($obj->CardID ?? '', 'Wookiee'))
    return;
  $ctrl = intval($obj->Controller ?? 0);
  if ($ctrl <= 0)
    return;
  $has = false;
  foreach (GetUnitsInPlay($ctrl) as $u) {
    if (empty($u->removed) && ($u->CardID ?? '') === 'SHD_250') {
      $has = true;
      break;
    }
  }
  if (!$has)
    return;
  SWUOfferUnitTarget($ctrl, '', ['continuation'=>'DEAL_UNIT_DAMAGE','amount'=>$amount,'side'=>'their','arena'=>'Ground',
      'prompt'=>"Deal_{$amount}_to_an_enemy_ground_unit"]);
}

// ASH_032 Rancor Keeper field observer. $obj = the damaged-and-surviving friendly unit (may be Rancor
// itself — "a friendly unit", not "another"). Once each round.
function _SWUAsh032CheckObserve($obj): void
{
  if ($obj === null)
    return;
  $ctrl = intval($obj->Controller ?? 0);
  if ($ctrl <= 0)
    return;
  if (GlobalEffectCount($ctrl, 'SWU_ASH032_USED') > 0)
    return;   // once each round
  $found = false;
  foreach (GetUnitsInPlay($ctrl) as $u) {
    if (empty($u->removed) && ($u->CardID ?? '') === 'ASH_032') {
      $found = true;
      break;
    }
  }
  if (!$found)
    return;
  AddGlobalEffects($ctrl, 'SWU_ASH032_USED');   // consumed when the ability triggers
  global $playerID;
  $playerID = $ctrl;
  DecisionQueueController::AddDecision(
    $ctrl,
    "MZMULTICHOOSE",
    "0|2|myBase-0&theirBase-0",
    1,
    tooltip: "Deal_1_damage_to_any_number_of_bases"
  );
  DecisionQueueController::AddDecision($ctrl, "CUSTOM", "ASH_032#0", 1, dontSkipOnPass: 1);
}// SEC_002 Jabba the Hutt (deployed) observer. $obj = the damaged-and-surviving unit; $amount = the
// damage just dealt. Fires for $obj's controller if they control a deployed SEC_002 (another unit),
// once each round; offers $obj dealing $amount to an enemy unit.
function _SWUSec002CheckObserve($obj, int $amount): void
{
  if ($obj === null || $amount <= 0)
    return;
  $ctrl = intval($obj->Controller ?? 0);
  if ($ctrl <= 0)
    return;
  if (GlobalEffectCount($ctrl, 'SWU_SEC002_USED') > 0)
    return;   // once each round
  $jabba = 0;
  foreach (GetUnitsInPlay($ctrl) as $u) {
    if (SWUObjGone($u))
      continue;
    if (($u->CardID ?? '') !== 'SEC_002')
      continue;
    if (intval($u->UniqueID ?? 0) === intval($obj->UniqueID ?? 0))
      continue; // "another" unit
    $jabba = intval($u->UniqueID ?? 0);
    break;
  }
  if ($jabba === 0)
    return;
  global $playerID;
  $playerID = $ctrl;   // resolve 'their' zones relative to the controller
  $targets = [];
  foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed))
        $targets[] = $mz;
    }
  }
  if (empty($targets))
    return;                  // no enemy unit → ability does nothing (use not spent)
  AddGlobalEffects($ctrl, 'SWU_SEC002_USED');   // once-per-round consumed when the ability triggers
  SWUQueueMayChooseTarget(
    $ctrl,
    $targets,
    "Deal_{$amount}_damage_to_an_enemy_unit?",
    "Deal_{$amount}_damage_to_an_enemy_unit",
    "DEAL_UNIT_DAMAGE|{$amount}"
  );
}
// Drained post-cleanup (see the queue site in _SWUOnUnitDamaged) so the pool's mzIDs are live.
$customDQHandlers["SEC143_OFFER"] = function($player, $parts, $lastDecision) {
  _SWUSec143Offer(intval($player), intval($parts[0] ?? 0));
};

function _SWUSec143Offer(int $player, int $selfUID): void
{
  if ($player <= 0)
    return;
  global $playerID;
  $playerID = $player;
  $targets = [];
  foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $zone) {
    foreach (ZoneSearch($zone, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if (SWUObjGone($o))
        continue;
      if (intval($o->UniqueID ?? 0) === $selfUID)
        continue;   // "another" unit
      if (!CardUnique($o->CardID ?? ''))
        continue;            // unique only
      $targets[] = $mz;
    }
  }
  if (empty($targets))
    return;   // no eligible unique unit → fizzle
  SWUOfferUnitTarget($player, '', ['continuation'=>'DEAL_UNIT_DAMAGE','amount'=>2,'may'=>true,'excludeUID'=>$selfUID,
      'extraFilter'=>fn($o)=>CardUnique($o->CardID ?? ''),
      'question'=>'Deal_2_damage_to_another_unique_unit?','prompt'=>"Deal_2_damage_to_a_unique_unit"]);
}
// All unit mzIDs (both players, all arenas) plus both bases — "a unit or base" target set.
function _SWUAllUnitsAndBases(int $player): array
{
  global $playerID;
  $playerID = $player;
  $out = [];
  foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed))
        $out[] = $mz;
    }
  }
  // Twin Suns: every live opponent's base, not just the in-view one (see SWUAllBaseMzIDs).
  foreach (SWUAllBaseMzIDs($player, 'any') as $bmz) $out[] = $bmz;
  return $out;
}

// SEC_013 Luthen Rael — "When a friendly unit is defeated while attacking" reaction (rides the
// after-attack flush). Front side (ready+undeployed): may exhaust the leader → deal 1 to a unit or base.
// Deployed: may deal 2 to a unit or base.
function SEC013AttackerDefeatedTrigger($player, string $mode = ''): void
{
  global $playerID;
  $playerID = intval($player);
  // DEPLOYED_SELF: Luthen himself was the defeated attacker — he has already returned to the leader zone
  // (undeployed/exhausted), so the live leader-state checks would fizzle. Per ruling the deployed reaction
  // ("may deal 2") still fires, so force that branch.
  if ($mode !== 'DEPLOYED_SELF' && _SWULeaderReadyUndeployed(intval($player), 'SEC_013')) {
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Exhaust_Luthen_Rael_to_deal_1_damage?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SEC_013#0', 1);
  } elseif ($mode === 'DEPLOYED_SELF' || _SWULeaderDeployed(intval($player), 'SEC_013')) {
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_TARGET', 'amount' => 2, 'includeBases' => true, 'may' => true,
        'question' => "Deal_2_damage_to_a_unit_or_base?", 'prompt' => "Choose_a_target",
    ]);
  }
}// All unit mzIDs (both players, all arenas) — "a unit" target set (no bases).
function _SWUAllUnitsOnly(int $player): array
{
  global $playerID;
  $playerID = $player;
  $out = [];
  foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if ($o !== null && empty($o->removed))
        $out[] = $mz;
    }
  }
  return $out;
}

// SEC_016 Padmé Amidala — "When you reveal or discard 1 or more cards from your hand" reaction (fired
// from DoDiscardCard and DISCLOSE_RESOLVE). Front (ready+undeployed): may exhaust leader → deal 1.
// Deployed: may deal 1 to a unit.
function _SWUSec016React(int $player): void
{
  global $playerID;
  $playerID = intval($player);
  $targets = _SWUAllUnitsOnly(intval($player));
  if (empty($targets))
    return;
  if (_SWULeaderReadyUndeployed(intval($player), 'SEC_016')) {
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Exhaust_Padmé_Amidala_to_deal_1_damage?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'SEC_016#0', 1);
  } elseif (_SWULeaderDeployed(intval($player), 'SEC_016')) {
    SWUOfferUnitTarget($player, '', ['continuation'=>'DEAL_UNIT_DAMAGE','amount'=>1,'may'=>true,
        'question'=>"Deal_1_damage_to_a_unit?",'prompt'=>"Deal_1_damage_to_a_unit"]);
  }
}// SEC_017 Sabé (deployed) — deals combat damage to a base: look at the defending player's hand, may
// discard a card; if you do, that player draws a card.
function SEC017DeployBaseHitTrigger($player): void
{
  global $playerID;
  $playerID = intval($player);
  // "look at THE DEFENDING PLAYER's hand" — determined by the board, so no picker. The old call passed
  // no seat, so the helper fell back to the single opponent: above two seats Sabé read the hand of a
  // player who was not in the combat. Passing the seat also makes the helper emit p{n}Hand-N, which is
  // what the transport's hidden-zone reveal needs in order to show the hand at all above two seats.
  $defSeat = SWUCurrentDefendingSeat(intval($player));
  $cards = SWULookAtOpponentHand(intval($player), null, $defSeat);
  if (empty($cards))
    return;
  SWUQueueMayChooseTarget($player, $cards, "Discard_a_card_from_the_opponent's_hand?", "Choose_a_card", "SEC_017#2");
}// SEC_017 Sabé (leader FRONT) — may exhaust → look at the top 2 of the defending player's deck, discard
// 1, put the other back on top. The defending player is the opponent of the attacking (active) player.
function SEC017LeaderBaseHitTrigger($player): void
{
  DecisionQueueController::AddDecision($player, 'YESNO', '-', 1, tooltip: "Exhaust_Sabé_to_look_at_the_top_2_cards?");
  DecisionQueueController::AddDecision($player, 'CUSTOM', 'SEC_017#3', 1);
}

// SEC_245 When Has Become Now — put the top card of your deck into play as a resource (the ramp half;
// mirror of SEC_107). Enters exhausted (no "ready" wording). Shared by the "no playable Plot" and
// post-Plot-play paths.
function _SWUSec245Ramp(int $player): void
{
  global $playerID;
  $playerID = intval($player);
  $deck = ZoneSearch("myDeck", null);
  if (!empty($deck))
    SWURampResourceExhausted(intval($player), $deck[0]);
}// End-of-action-phase retry. SWUPassAction queues this (high block, DontSkipOnPass) when the
// MAIN→RGS exit was blocked by a pending "when you take the initiative" trigger (e.g. ASH_155
// Grogu). It runs after the trigger drains the queue, so re-attempting the pass now succeeds.
$customDQHandlers["SWU_RETRY_ENDPHASE"] = function ($player, $parts, $lastDecision) {
  SWUPassAction(intval($player));
};

// IBH_009 / IBH_025 I've Found Them — top-deck-search finalize variant: draw chosen, DISCARD the rest
// (rather than bottoming them). Mirrors TOPDECKSEARCH_FINALIZE but the remaining go to discard (From:DECK).
$customDQHandlers["IBH_TOPDECK_DISCARD_FINALIZE"] = function ($player, $parts, $lastDecision) {
  $allIDs = array_values(array_filter(explode(',', $parts[0] ?? '')));
  $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
  foreach ($resolved['drawn'] as $cardID)
    AddHand(intval($player), CardID: $cardID);
  foreach ($resolved['remaining'] as $cardID)
    SWUAddToDiscard(intval($player), $cardID, 'DECK');
  DecisionQueueController::CleanupRemovedCards();
};
// LAW common bases (LAW_020/021/022/024/025/027/028/030) Epic Action continuation — play the chosen
// hand card, discounting one waivable battlefield-aspect penalty pip (capped at the real penalty).
// Mirrors LOF_018#0's "play ignoring aspect penalties" (turn/PASS save-restore around the nested play).
$customDQHandlers["LAW_COMMONBASE_PLAY"] = function ($player, $parts, $lastDecision) {
  global $playerID, $gTurnPlayer;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision)) {
    SWUAfterAction(intval($player));
    return;
  }
  $o = GetZoneObject($lastDecision);
  if (SWUObjGone($o)) {
    SWUAfterAction(intval($player));
    return;
  }
  $discount = min(_SWUCommonBaseWaivePenalty(intval($player), $o->CardID), SWUAspectPenalty(intval($player), $o->CardID));
  $savedTP = $gTurnPlayer;
  $savedPass = GetSWUVar('PASS', '0');
  SWUWithNestedActionFrame(fn() => ActivateCard(intval($player), $lastDecision, false, $discount));
  $gTurnPlayer = $savedTP;
  SetSWUVar('PASS', $savedPass);
  SWUAfterAction(intval($player));
};// ── Batch 4.4: exhaust / ready / bounce ─────────────────────────────────────

// Universal: tag the chosen unit ($lastDecision) with grant token $parts[0] — a source CardID
// (e.g. "SOR_086") that the registry resolves to its granted keyword + duration, so the Active
// Effects UI shows provenance. HasKeyword_* reads it via SWUHasTurnEffectKeyword; expiry is driven
// by the registry duration (SWUExpireTurnEffects). The token is added verbatim (no uppercasing —
// a CardID is already canonical).
$customDQHandlers["GRANT_PHASE_KEYWORD"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  AddTurnEffect($lastDecision, (string) ($parts[0] ?? ''));
};

// ── Batch 4.5: attack-with riders + OnAttack utility ────────────────────────

// Ready friendly units (Status=1) across both arenas — candidate attackers. Caller sets $playerID.
function _SWUReadyFriendlyUnits(int $player): array
{
  global $playerID;
  $playerID = intval($player);
  $out = [];
  foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
    $arr = GetZone($zone);
    for ($i = 0; $i < count($arr); $i++) {
      $u = $arr[$i];
      if (SWUObjGone($u))
        continue;
      if (intval($u->Status) === 1)
        $out[] = "{$zone}-{$i}";
    }
  }
  return $out;
}

// SOR_227 Snowtrooper Lieutenant (Imperial) / SOR_240 Fleet Lieutenant (Rebel) — When Played:
// You may attack with a unit; if it's a {trait} unit it gets +2/+0 for THIS attack.
// Single MZMAYCHOOSE over ready friendly units; ATTACK_WITH_TRAIT_BUFF resolves the pick
// (and already treats a '-' decline as a null attacker → CleanupRemovedCards, so declining
// ends the action cleanly via SWU_TRIGGER_RESUME).
$swuAttackWithTraitWhenPlayed = function ($trait) {
  return function ($player, $mzID) use ($trait) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueMayChooseTarget(
      intval($player),
      _SWUReadyFriendlyUnits(intval($player)),
      'Attack_with_a_unit?',
      'Choose_a_unit_to_attack_with',
      "ATTACK_WITH_TRAIT_BUFF|{$trait}"
    );
  };
};
// Buff the chosen attacker +2/+0 if it has $parts[0] trait, then attack. Attack-during-WhenPlayed:
// BeginSWUAttack's combat skips SWUAfterAction in trigger-resume mode; SWU_TRIGGER_RESUME ends it.
$customDQHandlers["ATTACK_WITH_TRAIT_BUFF"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $savedPID = $playerID;
  $playerID = intval($player);
  $trait = $parts[0] ?? '';
  $attackerMzID = $lastDecision ?? '';
  $attacker = (!empty($attackerMzID) && str_contains($attackerMzID, '-')) ? GetZoneObject($attackerMzID) : null;
  if (SWUObjGone($attacker) || intval($attacker->Status) !== 1) {
    DecisionQueueController::CleanupRemovedCards();
    $playerID = $savedPID;
    return;
  }
  if (HasTrait($attacker->CardID, $trait))
    SWUAddAttackPowerBonus($attackerMzID, 2);
  BeginSWUAttack($player, $attackerMzID);
  $playerID = $savedPID;
};

// ── Batch 4.6: draw / resource / discard / conditional-upgrade ───────────────

// Find ANY non-removed discard entry of $cardID (raw mzID "myDiscard-N"). Caller sets $playerID.
// "Any copy" suffices for ramp effects: simultaneous defeats each fire one handler, and MZMove
// removes the moved copy so the next handler finds another. Returns null if none present.
// Find the copy of a UNIT that JUST left play, in $player's discard — Clone-aware.
//
// ⚠ A TWI_116 CLONE COPY LEAVES PLAY AS THE REAL CARD. SWUAddToDiscard deliberately rewrites a Clone's
// CardID back to TWI_116 on the way out (a ruling: the physical card is a Clone, not the thing it
// copied). So a leave-play handler that re-finds ITSELF with a literal
// `_SWUFindDiscardMzID($player, 'SOR_083')` scans for a CardID that is no longer there and silently
// fizzles — the whole ability does nothing when the unit was a Clone copy.
// Measured on SOR_083 Superlaser Technician: its "put this unit into play as a resource" ramp no-opped
// for a Clone. The family is every clonable UNIT whose leave-play handler re-finds itself this way
// (SOR_083, SHD_085, LAW_159, LOF_097, SHD_107, SHD_161). Event and leader callers are unaffected —
// neither can be cloned — so they keep the plain literal lookup.
//
// Scans from the END: the body that just left play is the most recent entry, which is also what makes
// accepting a bare TWI_116 safe (an older, unrelated Clone in the pile is never the nearer match).
function _SWUFindSelfInDiscardMzID(int $player, string $cardID): ?string
{
  $discard = GetDiscard($player);
  for ($i = count($discard) - 1; $i >= 0; $i--) {
    if (!empty($discard[$i]->removed)) continue;
    $cid = $discard[$i]->CardID ?? '';
    if ($cid === $cardID || $cid === 'TWI_116') return "myDiscard-{$i}";
  }
  return null;
}

function _SWUFindDiscardMzID(int $player, string $cardID): ?string
{
  $discard = GetDiscard($player);
  for ($i = 0; $i < count($discard); $i++) {
    if (!empty($discard[$i]->removed))
      continue;
    if (($discard[$i]->CardID ?? '') === $cardID)
      return "myDiscard-{$i}";
  }
  return null;
}
// ── Batch 6.1: leaders ──────────────────────────────────────────────────────
// SOR_012 IG-88 — deployed passive ("each other friendly unit gains Raid 1") is already
// implemented in GetConditionalKeyword_Raid_Value (KeywordEffects.php). No handler needed.

// ── Batch 5.1: deck search ──────────────────────────────────────────────────
// Universal: give $parts[0] Experience tokens to the chosen unit ($lastDecision).
$customDQHandlers["GIVE_EXPERIENCE"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  $n = max(1, intval($parts[0] ?? 1));
  for ($i = 0; $i < $n; $i++)
    DoGiveExperienceToken(intval($player), $lastDecision);
};

// Universal "give N Advantage tokens (ASH_T02) to the chosen unit" handler (ASH). Param: GIVE_ADVANTAGE|N
// (default 1). No-op on a '-'/PASS decline so it composes with SWUQueueMayChooseTarget.
// Universal handler: apply a distribute-Advantage assignment ("mz:count,…") from MZSPLITASSIGN.
// ⚠ RE-VALIDATE THE TOTAL. The MZSPLITASSIGN client caps and gates the confirm button, but that is
// UX: the answer arrives as a plain "mz:n,mz:n" string and this handler used to apply it unchecked.
// $parts carries the offered total and mode (SPLIT_ADVANTAGE|total|mode); older queued decisions have
// neither, and fall through unvalidated exactly as before.
$customDQHandlers["SPLIT_ADVANTAGE"] = function ($player, $parts, $lastDecision) {
  $total = isset($parts[0]) && $parts[0] !== '' ? intval($parts[0]) : -1;
  $mode  = strtoupper(trim((string)($parts[1] ?? '')));
  if ($total >= 0 && $mode !== '') {
    $sum = 0;
    foreach (explode(',', (string)$lastDecision) as $pair) {
      $bits = explode(':', $pair);
      if (count($bits) >= 2) $sum += max(0, intval($bits[1]));
    }
    // ALL → exactly the total. ALLORNONE → 0 or exactly the total. UPTO → anything through the total.
    $ok = ($mode === 'UPTO')      ? ($sum <= $total)
        : (($mode === 'ALLORNONE') ? ($sum === 0 || $sum === $total)
                                   : ($sum === $total));
    if (!$ok) return;   // illegal split — drop it rather than half-apply an offer never made
  }
  SWUGiveSplitAdvantage(intval($player), (string) $lastDecision);
};

// Universal handler: apply a distribute-Weakness assignment ("mz:count,…") from MZSPLITASSIGN
// (HMW_071 Ravage). SWUGiveSplitWeakness applies every token before running a single shrink sweep.
$customDQHandlers["SPLIT_WEAKNESS"] = function ($player, $parts, $lastDecision) {
  SWUGiveSplitWeakness(intval($player), (string) $lastDecision);
};

$customDQHandlers["GIVE_ADVANTAGE"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  $n = max(1, intval($parts[0] ?? 1));
  for ($i = 0; $i < $n; $i++)
    DoGiveAdvantageToken(intval($player), $lastDecision);
};
// SOR_231 TIE Advanced / SOR_241 Wing Leader now live in cards/sor/TIEAdvanced.php and
// cards/sor/WingLeader.php (via the shared GiveTokenUpgrade helper).
// Universal: give a Shield token to the unit at $lastDecision (SOR_073 Moment of Peace).
$customDQHandlers["GIVE_SHIELD"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  GiveShieldToken(intval($player), $lastDecision);
};
// Universal: attach a Weakness token (HMW_T02, -1/-1) to the chosen unit (HMW_059 Clone X Assassin). No-op
// on a '-'/PASS decline (composes with SWUQueueMayChooseTarget). The -1 HP can drop the host's remaining HP
// to 0, which has no state-based defeat of its own — so run a shrink-defeat sweep after attaching.
$customDQHandlers["GIVE_WEAKNESS"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  DoGiveTokenUpgrade(intval($player), $lastDecision, 'HMW_T02');
  SWUCheckShrinkDefeats();
};// Universal: discard the chosen card ($lastDecision = "theirHand-N") from the opponent's hand to
// the opponent's discard (From=HAND). Used by the "look at an opponent's hand and discard a card
// from it" family (SOR_200, SOR_201).
$customDQHandlers["DISCARD_FROM_OPP_HAND"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  $obj = GetZoneObject($lastDecision);                    // theirHand-N / p{n}Hand-N → the hand card
  if (SWUObjGone($obj))
    return;
  // The discarded card's OWN mzID names the seat: "theirHand-N" at ≤2 seats, "p{n}Hand-N" above.
  // OtherPlayer() named one seat, so above two seats the card left seat 3's hand and landed in
  // seat 2's discard — and the log line credited the wrong player.
  $opp = SWUMzOwner((string)$lastDecision, intval($player));
  $cardID = $obj->CardID;
  $obj->Remove();
  SWUAddToDiscard($opp, $cardID, 'HAND');
  DecisionQueueController::CleanupRemovedCards();
  AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$opp}'s hand");
};

// Universal no-op acknowledge handler (the "OK" button on an information-only popup).
$customDQHandlers["ACK"] = function ($player, $parts, $lastDecision) { /* acknowledge — no effect */};// ── Generic defeat-upgrade resolution (shared by SOR_162/SHD_166/SOR_251/SHD_262/SOR_170)
// DEFEAT_UPGRADE receives the chosen HOST unit mzID (or '-' on a may-decline / fizzle).
// ── Generic "take control of an upgrade and attach it to a different eligible unit" (JTL_056 non-Pilot,
// JTL_242 token). Offers every matching upgrade as a SUBCARD mzID ("<hostMz>.u<subIdx>" — see
// MZParseSubcardID), so the player picks it ON THE BOARD, still attached to its host, then picks a
// destination unit, then it moves. ──
//
// ⚠ This used to stage the matching upgrades into TempZone as bare CardIDs and offer myTempZone-N,
// keeping the host association only in a side-channel `MoveUpgMap` variable. That rendered as a flat
// card-art popup with NO indication of which unit each upgrade was on — unusable for JTL_242, whose
// pool spans every unit on the board and whose legal targets are frequently several identical Shield
// tokens. The subcard mzID carries the host in the answer itself, so no side map is needed.
//
// $sourceHostMz: if non-empty, only scan that one host's upgrades ("an upgrade ON THIS unit" — JTL_070).
// $destScope:    '' = any unit OTHER than the source host (default); 'friendlyVehicle' = restrict the
//                destination to another friendly Vehicle unit (JTL_070); 'anyIncludingSource' = the
//                source host is ALSO a legal destination. Read back in MOVE_UPGRADE.
//                The default excludes the source because most consumers say "attach it to ANOTHER
//                eligible unit" (JTL_056/070/242). A card that says only "an eligible unit of your
//                choice" (SHD_077) must be able to leave the upgrade where it is - taking CONTROL of
//                it is itself the effect (USER RULING 2026-08-15).
function SWUQueueMoveUpgrade(int $player, string $filter, string $tooltip, string $sourceHostMz = '', string $destScope = '', bool $friendlyOnly = false): void
{
  global $playerID;
  $playerID = intval($player);
  $targets = []; // subcard mzIDs
  $scanZones = ($sourceHostMz !== '')
    ? [$sourceHostMz]
    : ($friendlyOnly ? ['myGroundArena', 'mySpaceArena']
      : ['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena']);
  foreach ($scanZones as $z) {
    // A single host mzID is fetched directly; a zone name is enumerated.
    $mzList = ($sourceHostMz !== '') ? [$z] : ZoneSearch($z, AnyUnitFilter);
    foreach ($mzList as $mz) {
      $o = GetZoneObject($mz);
      if (SWUObjGone($o) || !is_array($o->Subcards ?? null))
        continue;
      // The RAW Subcards key is the sub index — the same index space SWUMoveUpgradeCrossUnit expects,
      // and the only one the renderer can derive independently.
      foreach ($o->Subcards as $i => $sub) {
        if (_SWUUpgradeMatchesMoveFilter($sub, $filter))
          $targets[] = $mz . '.u' . $i;
      }
    }
  }
  if (empty($targets))
    return; // no movable upgrade → fizzle
  DecisionQueueController::StoreVariable("MoveUpgDestScope", $destScope);
  DecisionQueueController::AddDecision($player, "MZMAYCHOOSE", implode("&", $targets), 1, tooltip: $tooltip);
  DecisionQueueController::AddDecision($player, "CUSTOM", "MOVE_UPGRADE", 1);
  // Leave $playerID set: MZCountChoices validates the relative-frame specs next, under it.
}
// Upgrade chosen → pick the destination unit (any unit except the source host).
$customDQHandlers["MOVE_UPGRADE"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if (SWUDecisionDeclined($lastDecision))
    return;
  // The answer IS the address: "<zone>-<hostIdx>.u<subIdx>". No side map to fall out of sync with.
  $sub = MZParseSubcardID((string) $lastDecision);
  if ($sub === null)
    return;
  $hostMz = $sub['host'];
  $subIdx = $sub['subIndex'];
  DecisionQueueController::StoreVariable("MoveUpgSrc", $hostMz . '|' . $subIdx);
  $destScope = (string) DecisionQueueController::GetVariable("MoveUpgDestScope");
  // Resolve the moved upgrade's CardID so a destination must satisfy the upgrade's OWN attach restriction
  // (e.g. a "Force unit"-only upgrade can't be moved onto a non-Force unit). Token upgrades are unrestricted.
  $movedUpgCardID = ''; {
    $srcHost = GetZoneObject($hostMz);
    if ($srcHost !== null && is_array($srcHost->Subcards ?? null) && isset($srcHost->Subcards[intval($subIdx)])) {
      $sc = $srcHost->Subcards[intval($subIdx)];
      $movedUpgCardID = is_array($sc) ? ($sc['CardID'] ?? '') : ($sc->CardID ?? '');
    }
  }
  $dests = [];
  foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      if ($mz === $hostMz && $destScope !== 'anyIncludingSource')
        continue;
      $o = GetZoneObject($mz);
      if (SWUObjGone($o))
        continue;
      // Enforce the moved upgrade's printed attach restriction on the destination.
      if ($movedUpgCardID !== '' && !_SWUUpgradeCanAttachTo($o, $movedUpgCardID))
        continue;
      if ($destScope === 'friendlyVehicle') {
        if (intval($o->Controller ?? 0) !== intval($player))
          continue;
        if (!HasTrait($o->CardID ?? '', 'Vehicle'))
          continue;
      }
      // SHD_064 Survivors' Gauntlet — the destination must be controlled by the SAME player as the
      // source host ("to another eligible unit controlled by the same player").
      if ($destScope === 'sameController') {
        $srcObj = GetZoneObject($hostMz);
        if ($srcObj !== null && intval($o->Controller ?? 0) !== intval($srcObj->Controller ?? 0))
          continue;
      }
      $dests[] = $mz;
    }
  }
  if (empty($dests))
    return; // nowhere else to attach it → fizzle (upgrade stays put)
  SWUQueueChooseTarget(intval($player), $dests, "Attach_it_to_a_different_eligible_unit", "MOVE_UPGRADE#1");
};
// Destination chosen → move the upgrade there (taking control of it).
$customDQHandlers["MOVE_UPGRADE#1"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision))
    return;
  global $playerID;
  $playerID = intval($player);
  [$hostMz, $subIdx] = array_pad(explode('|', (string) DecisionQueueController::GetVariable("MoveUpgSrc"), 2), 2, '');
  if ($hostMz === '' || $subIdx === '')
    return;
  SWUMoveUpgradeCrossUnit($hostMz, intval($subIdx), $lastDecision, intval($player));
};
$customDQHandlers["DEFEAT_UPGRADE"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
    DecisionQueueController::CleanupRemovedCards();
    return;
  }
  global $playerID;
  $playerID = intval($player);
  // Two answer shapes reach here.
  //  • A SUBCARD mzID ("<hostMz>.u<subIdx>") — the single-pick flow (SWUQueueDefeatUpgradeDirect):
  //    the player picked the upgrade itself on the board, so defeat it and we're done. No host step,
  //    no TempZone.
  //  • A plain HOST mzID — the legacy two-step flow, still used by the multi-pick path and by the
  //    handful of cards that queue their own host choose and then chain "DEFEAT_UPGRADE" (Vane,
  //    Reforge, Finn, Exploit Advantage, Pegasus Tri-Wing). Those keep the host resolver.
  $sub = MZParseSubcardID((string) $lastDecision);
  if ($sub !== null) {
    $defeated = SWUDefeatUpgradeByMzID(intval($player), (string) $lastDecision);
    DecisionQueueController::CleanupRemovedCards();
    // Same continuation contract as the staged path: pass the HOST mzID plus whether a defeat actually
    // happened ('1'/'0'), so an "if you do" rider (JTL_175 System Shock) skips when the defeat was
    // prevented (Willrow SEC_061 / a pilot-immune upgrade) while an unconditional one still fires.
    $then = (string) DecisionQueueController::GetVariable("DefeatUpgThen");
    if ($then !== '') {
      DecisionQueueController::StoreVariable("DefeatUpgThen", "");
      global $customDQHandlers;
      if (isset($customDQHandlers[$then]))
        $customDQHandlers[$then]($player, [$sub['host'], $defeated ? '1' : '0'], '');
    }
    return;
  }
  _SWUResolveDefeatUpgradeHost(intval($player), $lastDecision);
  // leave $playerID set: _SWUResolveDefeatUpgradeHost may queue a relative-mzID pick,
  // and MZCountChoices runs immediately after and resolves myTempZone-* under $playerID.
};

// DEFEAT_UPGRADE#1 receives the staged-upgrade pick(s): a single mzID (MZCHOOSE/MZMAYCHOOSE)
// or an &-delimited list (MZMULTICHOOSE), or '-'/'' for "defeat none" (valid when $min==0).
// myTempZone-N maps positionally to the real GetUpgradesOnUnit index $matchIdx[N].
$customDQHandlers["DEFEAT_UPGRADE#1"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);

  $host = (string) DecisionQueueController::GetVariable("DefeatUpgHost");
  $idxRaw = (string) DecisionQueueController::GetVariable("DefeatUpgIdx");
  $matchIdx = ($idxRaw === '') ? [] : array_map('intval', explode(",", $idxRaw));

  $drain = function () use ($player) {
    $temp = &GetTempZone($player);
    while (count($temp) > 0)
      array_pop($temp);
  };

  if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
    $drain();
    DecisionQueueController::CleanupRemovedCards();
    return;
  }

  // map picked myTempZone-N → real subcard indices
  $realIdx = [];
  foreach (explode('&', $lastDecision) as $mz) {
    $mz = trim($mz);
    if ($mz === '' || $mz === '-')
      continue;
    if (preg_match('/-(\d+)$/', $mz, $m)) {
      $n = intval($m[1]);
      if (isset($matchIdx[$n]))
        $realIdx[] = $matchIdx[$n];
    }
  }
  // descending so defeating a higher index never renumbers the lower ones still to come
  $realIdx = array_unique($realIdx);
  rsort($realIdx);
  $anyDefeated = false;
  foreach ($realIdx as $idx) {
    if (SWUDefeatUpgrade($player, $host, $idx))
      $anyDefeated = true;
  }
  $drain();
  DecisionQueueController::CleanupRemovedCards();
  // Chain the next "may defeat 1" link if one was armed. Read-and-clear so the dispatched link
  // doesn't re-trigger; it re-reads the board (so picks span units).
  $then = (string) DecisionQueueController::GetVariable("DefeatUpgThen");
  if ($then !== '') {
    DecisionQueueController::StoreVariable("DefeatUpgThen", "");
    global $customDQHandlers;
    // Pass the host mzID + whether a defeat ACTUALLY happened ('1'/'0'). An "if you do" continuation
    // (JTL_175 System Shock "deal 1 to that unit") honors the flag and skips when '0' — a Willrow
    // SEC_061-protected (or JTL_012 pilot-immune) upgrade makes SWUDefeatUpgrade return false. An
    // UNCONDITIONAL modal continuation (SOR_155 "…and deal 4") ignores the flag and still fires.
    if (isset($customDQHandlers[$then]))
      $customDQHandlers[$then]($player, [$host, $anyDefeated ? '1' : '0'], '');
  }
};// Shared helper: given a known host unit mzID, compute its filter-matching upgrades
// (as real GetUpgradesOnUnit indices), then either auto-defeat the single mandatory
// match, or stage them into TempZone and queue the right pick decision by $min/$max.
// Reads $min|$max|$filter from the DefeatUpgParams DQ variable (set by SWUQueueDefeatUpgrade).
// Leaves $playerID set so MZCountChoices resolves the myTempZone-* specs.
function _SWUResolveDefeatUpgradeHost(int $player, string $hostMzID): void
{
  global $playerID;
  $playerID = intval($player);

  $host = GetZoneObject($hostMzID);
  if ($host === null || ($host->removed ?? false)) {
    DecisionQueueController::CleanupRemovedCards();
    return;
  }

  [$min, $max, $filter] = array_pad(
    explode('|', (string) DecisionQueueController::GetVariable("DefeatUpgParams"), 3),
    3,
    ''
  );
  $min = intval($min);
  $max = intval($max);

  // matching upgrades as real GetUpgradesOnUnit indices (the index space SWUDefeatUpgrade expects)
  $upgrades = GetUpgradesOnUnit($host);
  $matchIdx = [];
  foreach ($upgrades as $i => $up) {
    if (SWUUpgradeMatchesFilter($up->CardID ?? '', $filter))
      $matchIdx[] = $i;
  }
  $count = count($matchIdx);
  if ($count === 0) {
    DecisionQueueController::CleanupRemovedCards();
    return;
  }

  // mandatory single match → auto-defeat, no picker
  if ($min >= 1 && $count === 1) {
    $autoDefeated = SWUDefeatUpgrade($player, $hostMzID, $matchIdx[0]);
    DecisionQueueController::CleanupRemovedCards();
    // Honour the continuation on the auto-defeat path — passing whether a defeat actually happened so
    // an "if you do" continuation (JTL_175) can skip when the defeat was prevented (Willrow SEC_061).
    $then = (string) DecisionQueueController::GetVariable("DefeatUpgThen");
    if ($then !== '') {
      DecisionQueueController::StoreVariable("DefeatUpgThen", "");
      global $customDQHandlers;
      if (isset($customDQHandlers[$then]))
        $customDQHandlers[$then]($player, [$hostMzID, $autoDefeated ? '1' : '0'], '');
    }
    return;
  }

  // stage matching upgrades into TempZone IN $matchIdx ORDER (myTempZone-k ↔ $matchIdx[k])
  $temp = &GetTempZone($player);
  while (count($temp) > 0)
    array_pop($temp);
  foreach ($matchIdx as $i) {
    AddTempZone($player, $upgrades[$i]->CardID ?? '-');
  }
  $tempMZs = [];
  for ($k = 0; $k < $count; $k++)
    $tempMZs[] = "myTempZone-" . $k;

  DecisionQueueController::StoreVariable("DefeatUpgHost", $hostMzID);
  DecisionQueueController::StoreVariable("DefeatUpgIdx", implode(",", $matchIdx));

  if ($max <= 1) {
    // single pick → card-image popup (Mode=None routes myTempZone-N to ShowMZChoosePopup)
    $type = ($min === 0) ? "MZMAYCHOOSE" : "MZCHOOSE";
    DecisionQueueController::AddDecision(
      $player,
      $type,
      implode("&", $tempMZs),
      1,
      tooltip: "Choose_an_upgrade_to_defeat"
    );
  } else {
    // multi pick → MZMultiChooseUI modal (Select All / Clear). effectiveMax = count → Select All shows.
    $effectiveMax = min($max, $count);
    DecisionQueueController::AddDecision(
      $player,
      "MZMULTICHOOSE",
      $min . "|" . $effectiveMax . "|" . implode("&", $tempMZs),
      1,
      tooltip: "Choose_upgrades_to_defeat"
    );
  }
  DecisionQueueController::AddDecision($player, "CUSTOM", "DEFEAT_UPGRADE#1", 1);
}

// ── SOR_016 Grand Admiral Thrawn ─────────────────────────────────────────────
// Deployed OnAttack: "You may reveal the top card of any player's deck.
// Exhaust a unit that costs the same as or less than the revealed card."
// ── SOR_017 Han Solo "Audacious Smuggler" ───────────────────────────────────// Resolve the pending "defeat a resource you control" trigger (queued in
// ActionPhaseStart). The player picks one of their resources to defeat.
$customDQHandlers["HAN_DEFEAT_RESOURCE"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
    return; // controls no resources, or none chosen — fizzle
  }
  SWUDefeatResource(intval($player), $lastDecision);
};
// ── Reactive defeat / leaves-play triggers (SOR_036 Gideon, SOR_105 Krell, SOR_015 Boba) ──
// SOR_105 General Krell granted "When Defeated: you may draw a card" follow-up.
$customDQHandlers["KRELL_DRAW"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision === 'YES')
    DoDrawCard(intval($player), 1);
};
// ── Chained "attack with another unit" (SOR_009 Leia, SOR_103 Rebel Assault) ──
// Universal follow-up: apply a one-shot +{bonus}/+0 ("for this attack") to the chosen unit and
// begin its attack. No-op on a '-' decline (the optional "you may attack with another" case).
$customDQHandlers["CHAINED_ATTACK"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS')
    return;
  global $playerID;
  $playerID = intval($player);
  $bonus = intval($parts[0] ?? 0);
  $noBases = intval($parts[1] ?? 0) === 1;   // TS26_04 Padmé — "can't attack bases for this attack"
  if ($bonus > 0)
    SWUAddAttackPowerBonus($lastDecision, $bonus);
  BeginSWUAttack(intval($player), $lastDecision, $noBases);
};// ── SOR_193 Millennium Falcon "Piece of Junk" ───────────────────────────────

// Shared keep-vs-bounce logic for SOR_193 (both direct resource pay and the
// DROID_PAY FALCON_KEEP continuation path). $paidOk = true if payment succeeded.
function _SWUFalconKeepOrBounce(int $player, string $falconMz, bool $paidOk): void
{
  if ($paidOk) {
    AddGameLogEntry('ABILITY', 'P' . $player . ' paid 1 resource to keep Millennium Falcon');
  } else {
    SWUBounceUnit($player, $falconMz);
    AddGameLogEntry('ABILITY', 'P' . $player . ' returned Millennium Falcon to hand');
  }
}
// ── Task 1.3: Exploit pre-step resolver ─────────────────────────────────────
// Called after MZMULTICHOOSE "defeat up to X friendly units" for an Exploit card.
// $params: [ mzID-of-card-being-played, grantedExploit-count ].
// $lastDecision: '&'-joined mzIDs of chosen friendly units to defeat, or '-' / '' if none.
$customDQHandlers["EXPLOIT_RESOLVE"] = function ($player, $params, $lastDecision) {
  global $gPlayGrantedExploit, $playerID;
  $savedPID = $playerID;
  $playerID = intval($player);

  $mzID = $params[0] ?? '';
  $gPlayGrantedExploit = intval($params[1] ?? 0);   // restore across the request boundary
  $maxDefeats = intval($params[2] ?? 0);            // effective Exploit X (cap on units defeated)

  // Validation, the payability abort, the deferred-trigger defeat loop and the SEC_122 compensation all
  // live in _SWUResolveExploitPicks, shared with SMUGGLE_EXPLOIT so the two play paths cannot drift.
  // This path prices the card's PRINTED cost.
  $probeObj = ($mzID !== '') ? GetZoneObject($mzID) : null;
  $exploitDiscount = _SWUResolveExploitPicks(intval($player), $probeObj->CardID ?? '', $lastDecision,
      $maxDefeats,
      fn(int $optDisc, array $losing) => _SWUPlayIsPayableAtDiscount(intval($player), $mzID, $optDisc, $losing));

  if ($exploitDiscount === null) {
    SetFlashMessage("Not enough resources to play this even after Exploit — nothing was defeated.");
    $gPlayGrantedExploit = 0;
    $playerID = $savedPID;
    return;
  }

  // SWUContinuePlayAfterExploit → ActivateCard.
  // The event branch of ActivateCard does NOT restore $playerID, so we must not
  // restore it here either — SWUContinuePlayAfterExploit returns with $playerID
  // still set to $player (same as $savedPID), so the restore below is a safe no-op.
  SWUContinuePlayAfterExploit(intval($player), $mzID, $exploitDiscount);
  // CONSUME the restored grant now that the play has fully continued (the call above is
  // synchronous). Without this, the next play in the same request — a nested play, or the
  // next harness section — inherits a phantom Exploit X and raises a bogus defeat offer
  // (surfaced by the SOR_214 attach-pool guard running after a Dooku-grant section).
  $gPlayGrantedExploit = 0;
  $playerID = $savedPID;
};

// ── SMUGGLE_EXPLOIT — the Exploit picker answered on the SMUGGLE play path ──────────────────
// Reachable because SHD_248 Tech grants Smuggle to every friendly resource: no printed card carries
// both Smuggle and Exploit. Playing via Smuggle is a modified "Play a Card" action (CR 14.c) and the
// card's while-playing abilities stay active (CR 14.i), so Exploit applies and is used during Step 3,
// Determine cost(s) (CR 16.c) — reducing the SMUGGLE cost by 2 per unit defeated (CR 16.a).
// Params: {resourceIdx}|{discount}|{deferHandler}|{grant}|{exploitX}|{preExploitCost}. All scalars, so
// this survives the request boundary on the decision's own Param.
// $preExploitCost is the Smuggle cost after the aspect penalty, Lando -2 and the shared delta but
// BEFORE the JTL_105 halving — so the probe below must reduce first and halve second, matching
// SWUSmuggleResource exactly.
$customDQHandlers["SMUGGLE_EXPLOIT"] = function ($player, $params, $lastDecision) {
  global $gPlayGrantedExploit, $playerID;
  $savedPID = $playerID;
  $playerID = intval($player);

  $resourceIdx    = intval($params[0] ?? 0);
  $discount       = intval($params[1] ?? 0);
  $defer          = ($params[2] ?? '') === '' ? null : $params[2];
  $gPlayGrantedExploit = intval($params[3] ?? 0);   // restore across the request boundary
  $maxDefeats     = intval($params[4] ?? 0);
  $preExploitCost = intval($params[5] ?? 0);

  // Re-locate the smuggled resource by the SAME logical index the offer used (defeating friendly units
  // does not touch the resource zone, so the index is still valid).
  // Only the CardID is needed here (for the SEC_122 probe); the play itself re-locates the resource by
  // the same logical index. CR 14.e's self-payment needs no special handling in the probe below: the
  // smuggled card is still a ready resource, so SWUTotalPaymentCapacity already counts it.
  $cardID = '';
  $resources = &GetResources(intval($player));
  $seen = 0;
  for ($i = 0; $i < count($resources); $i++) {
    if (isset($resources[$i]->removed) && $resources[$i]->removed) continue;
    if (SWUIsCreditToken($resources[$i]->CardID ?? '')) continue;
    if ($seen === $resourceIdx) { $cardID = $resources[$i]->CardID ?? ''; break; }
    $seen++;
  }

  $exploitDiscount = _SWUResolveExploitPicks(intval($player), $cardID, $lastDecision, $maxDefeats,
      function (int $optDisc, array $losing) use ($player, $preExploitCost): bool {
        // Price the SMUGGLE cost, not the printed cost: reduce, then halve (JTL_105), exactly as
        // SWUSmuggleResource does. Capacity loses any Droid this Exploit is about to defeat, since it
        // can no longer be exhausted for payment.
        $cost = SWUApplyCostHalving(intval($player), max(0, $preExploitCost - $optDisc));
        $capacity = SWUTotalPaymentCapacity(intval($player));
        if (!empty($losing) && SWUPlayerControlsSEC122(intval($player))) {
          $lose = array_flip(array_map('intval', $losing));
          foreach (SWUReadyFriendlyDroids(intval($player)) as $dmz) {
            $d = GetZoneObject($dmz);
            if (!SWUObjGone($d) && isset($lose[intval($d->UniqueID ?? 0)])) $capacity--;
          }
        }
        return $capacity >= $cost;
      },
      compensateDroids: false);   // the cost is LOCKED, so it still carries the pre-defeat Droid reduction

  if ($exploitDiscount === null) {
    SetFlashMessage("Not enough resources to smuggle this even after Exploit — nothing was defeated.");
    $gPlayGrantedExploit = 0;
    $playerID = $savedPID;
    return;
  }

  // Re-enter the Smuggle play with the discount settled. Passing it (>= 0) is what stops the picker
  // being raised a second time.
  // $preExploitCost is handed straight back as the LOCKED determined cost — see the $determinedCost
  // note on SWUSmuggleResource for why it must not be recomputed after the defeats.
  SWUSmuggleResource(intval($player), $resourceIdx, $discount, $defer, -1, $exploitDiscount, $preExploitCost);
  $gPlayGrantedExploit = 0;
  // SWUSmuggleResource deliberately leaves $playerID = $player when it queued a decision, mirroring
  // EXPLOIT_RESOLVE's no-op restore.
  $playerID = $savedPID;
};


// ── DROID_PAY — central SEC_122 Droid alt-pay resolver ──────────────────────
// "Each friendly Droid unit may be exhausted to pay costs as if it were a resource."
// Queued by SWUOfferDroidPayment for every site (card plays, upgrades, Falcon regroup).
//
// Param encoding: $parts[0] = continuation name (PLAY_CARD | ATTACH_UPGRADE | FALCON_KEEP),
// implode("|", array_slice($parts, 1)) = args string passed to the continuation.
// $lastDecision = "&"-joined mzIDs of Droids the player chose to exhaust, or "-" for none.
//
// VALIDATION: re-derive the ready-Droid set (live, not snapshot) and accept only valid
// choices. Cap at min(|fodder|, MAX_DROIDS_OFFERED) — MAX_DROIDS_OFFERED is re-derived
// from the live ready set, which is conservative (may have shrunk since the offer).
// Exhausting a Droid flips Status to 0 but does not splice arena arrays, so no UID
// snapshot is needed.
//
// After exhausting, delegates to SWUDispatchDroidContinuation($player, $continuation, $args, $prepaid).
// $playerID handling: set to $player throughout. For PLAY_CARD the event branch of
// ActivateCard does NOT restore $playerID, so $savedPID restore is a safe no-op.
// For ATTACH_UPGRADE and FALCON_KEEP the downstream functions restore correctly.
$customDQHandlers["DROID_PAY"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $savedPID = $playerID;
  $playerID = intval($player);

  // $parts[0] = the cap computed by SWUOfferDroidPayment as min(ready Droids, cost). The queue
  // controller does NOT enforce the MZMULTICHOOSE bound on submission, so re-apply it here: CR 1.7.2
  // says a player exhausts resources EQUAL TO the cost, and CR 8.1.4 forbids exhausting one when not
  // paying a cost — so an over-long answer must not burn the extra Droids.
  $cap = intval($parts[0] ?? 0);
  $required = intval($parts[1] ?? 0);
  $continuation = $parts[2] ?? '';
  $args = implode('|', array_slice($parts, 3)); // rejoin remaining parts as args

  // Validate the player's Droid picks against the live ready set — WITHOUT spending anything yet.
  $chosenMZs = [];
  if ($lastDecision !== null && $lastDecision !== '-' && $lastDecision !== '') {
    $fodderSet = array_flip(SWUReadyFriendlyDroids(intval($player)));
    $maxExhaust = min(count($fodderSet), max(0, $cap)); // cannot exceed the ready set OR the cost
    foreach (explode('&', $lastDecision) as $chosen) {
      if ($chosen === '')
        continue;
      if (!isset($fodderSet[$chosen]))
        continue;      // not in offered set / no longer ready
      if (count($chosenMZs) >= $maxExhaust)
        break;             // cap reached
      $o = GetZoneObject($chosen);
      if (SWUObjGone($o))
        continue;
      if (intval($o->Status) !== 1)
        continue;         // redundant safety check
      $chosenMZs[] = $chosen;
    }
  }

  // ⚠ CR 4.a — "If any costs (including resource costs and additional costs) cannot be paid, cease this
  // process WITHOUT PAYING ANY COSTS. Return the game state to the way it was before the first step."
  // Spending happens BELOW this gate, never above it. The handler used to spend first and dispatch
  // second, so a player who picked FEWER tokens than the cost lost them and still did not play the card
  // (measured 2026-09-02: 2 Droids against a cost of 3 with no resources — card stranded in hand, both
  // Droids exhausted, repeatable). SWUOfferAltPayment's own gate only refuses a TOTALLY unaffordable
  // play; this is the partial-payment case one level down.
  // $required is the amount this offer was raised for (already halved under JTL_105 where applicable).
  // Guarded by core/AltPaymentUnderStarhawk.md::UnderpayingWithDroidsSpendsNothing.
  $shortfall = $required - count($chosenMZs);
  if ($shortfall > 0 && SWUResourceCount(intval($player), readyOnly: true) < $shortfall) {
    SetFlashMessage("Not enough to pay the cost — nothing was spent.");
    SWUDispatchDroidContinuation(intval($player), $continuation, $args, 0);
    $playerID = $savedPID;
    return;
  }

  $prepaid = 0;
  foreach ($chosenMZs as $chosen) { OnExhaustCard(intval($player), $chosen); $prepaid++; }

  // Dispatch to the named continuation with the exhaustion count.
  // PLAY_CARD: ActivateCard's event branch does not restore $playerID, so $savedPID
  // restore below is a safe no-op (mirrors EXPLOIT_RESOLVE's pattern).
  SWUDispatchDroidContinuation(intval($player), $continuation, $args, $prepaid);
  $playerID = $savedPID;
};

// ── CREDIT_PAY — Credit-token alt-pay resolver (CR 3.13) ─────────────────────
// Queued by SWUOfferAltPayment. "While paying resources, you may defeat this token. If you do, pay 1
// less." Each valid defeat = 1 prepaid (1 resource less). Then delegates to SWUDispatchDroidContinuation
// (the shared continuation registry — PLAY_CARD / ATTACH_UPGRADE / FALCON_KEEP) with prepaid = the
// number defeated.
//
// Param encoding: $parts[0] = the cap, $parts[1] = the TempZone->Resources index map, $parts[2] =
// continuation (a single pipe-free token), implode("|", array_slice($parts, 3)) = args (may contain "|").
//
// $lastDecision = "&"-joined "myTempZone-K" mzIDs (or "-"). The offer stages the Credits into TempZone
// so the picker shows the CREDITS ALONE rather than lighting them up inline across the whole resource
// row (see SWUOfferAltPayment), which means the answer is in TEMPZONE coordinates and has to be mapped
// back. Credit tokens are indistinguishable by CardID, so the positional map built at offer time is the
// only thing that can say WHICH resource slot a pick meant — never re-derive it by matching CardID.
// (Nothing reorders the resource zone between the offer and this handler — SWUKeepCreditTokensLast is
// only ever called from explicit effects, never a static recompute — and even if it did, every mapped
// slot is re-validated below against the LIVE usable-Credit set, so the worst case is defeating an
// identical token rather than the wrong KIND of card.)
//
// ⚠ Defeating a token splices the resource zone (CleanupRemovedCards reindexes), so mark ALL chosen
// tokens removed FIRST, then clean up once — otherwise later mzIDs in the same answer would shift.
$customDQHandlers["CREDIT_PAY"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $savedPID = $playerID;
  $playerID = intval($player);

  // Drain the staging zone on EVERY exit path (decline included) — a leftover TempZone entry renders
  // as a phantom card and poisons the next effect that stages there.
  $temp = &GetTempZone(intval($player));
  while (count($temp) > 0) array_pop($temp);

  // $parts[0] = the cap (min(usable Credits, cost)) — see the DROID_PAY note: the MZMULTICHOOSE bound
  // is client-side only, so the CR 1.7.2 "equal to the cost" limit is re-applied here.
  $cap = intval($parts[0] ?? 0);
  // ⚠ INDEX SHIFT (2026-09-02): the producer now threads the REQUIRED cost at index 1 so the CR 4.a
  // gate below can tell whether the player's picks can actually complete the payment. Every field
  // after it moved one to the right — the credit MAP is now [2], the continuation [3], args [4..].
  $required = intval($parts[1] ?? 0);
  $map = [];
  foreach (explode(',', (string)($parts[2] ?? '')) as $slot) {
    if ($slot !== '') $map[] = intval($slot);
  }
  $continuation = $parts[3] ?? '';
  $args = implode('|', array_slice($parts, 4));

  // Collect the valid picks WITHOUT defeating anything yet — see the CR 4.a note in DROID_PAY. A Credit
  // is defeated permanently, so a partial payment that cannot complete used to destroy the tokens and
  // leave the card in hand (the same shape as the live game-3608 report one level up).
  $chosenSlots = [];
  if ($lastDecision !== null && $lastDecision !== '-' && $lastDecision !== '') {
    $usable = array_flip(SWUUsableCreditTokenMzIDs(intval($player))); // live, validated set
    foreach (explode('&', $lastDecision) as $chosen) {
      if (count($chosenSlots) >= max(0, $cap))
        break;
      // Translate the staged pick back to the resource slot it stands for. Anything that is not a
      // recognised TempZone index is dropped rather than guessed at — a mismatched map must fail to
      // pay, never pay with the WRONG token.
      if (!preg_match('~^myTempZone-([0-9]+)$~', trim((string)$chosen), $m))
        continue;
      $k = intval($m[1]);
      if (!isset($map[$k]))
        continue;
      $mzID = 'myResources-' . $map[$k];
      if (!isset($usable[$mzID]))
        continue;
      $o = GetZoneObject($mzID);
      if (SWUObjGone($o))
        continue;
      if (!SWUIsCreditToken($o->CardID ?? ''))
        continue;
      $chosenSlots[] = $mzID;
    }
  }

  // CR 4.a gate — nothing is defeated unless the whole cost can be met.
  $shortfall = $required - count($chosenSlots);
  if ($shortfall > 0 && SWUResourceCount(intval($player), readyOnly: true) < $shortfall) {
    SetFlashMessage("Not enough to pay the cost — nothing was spent.");
    SWUDispatchDroidContinuation(intval($player), $continuation, $args, 0);
    $playerID = $savedPID;
    return;
  }

  $prepaid = 0;
  {
    {
      foreach ($chosenSlots as $mzID) {
        $o = GetZoneObject($mzID);
        if (SWUObjGone($o)) continue;
        $o->removed = true; // mark all first; cleanup once below to avoid mid-loop reindex
        $prepaid++;
      }
    }
    if ($prepaid > 0) {
      AddGameLogEntry('TOKEN', 'P' . intval($player) . ' defeated ' . $prepaid
        . ' Credit token' . ($prepaid === 1 ? '' : 's') . ' to pay ' . $prepaid . ' less');
      DecisionQueueController::CleanupRemovedCards();
      // LAW_015 Jabba (deployed): if this is a Jabba play (SWU_JABBA015_PENDING armed in LAW_015#0)
      // and a Credit was defeated paying its cost, arm the Ambush grant — consumed at the unit's
      // entry in ActivateCard (which runs synchronously below via the PLAY_CARD continuation).
      if (GlobalEffectCount(intval($player), 'SWU_JABBA015_PENDING') > 0) {
        AddGlobalEffects(intval($player), 'SWU_JABBA015_AMBUSH');
      }
    }
  }

  SWUDispatchDroidContinuation(intval($player), $continuation, $args, $prepaid);
  $playerID = $savedPID;
};
// GIVE_EXP_EACH — universal: give one Experience token to EACH mzID in an &-delimited
// MZMULTICHOOSE answer (no-op on decline). Multi-target sibling of GIVE_EXPERIENCE|N.
// Used by SHD_261 Rich Reward ("each of up to 2 units").
$customDQHandlers["GIVE_EXP_EACH"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision === null || $lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS")
    return;
  foreach (explode("&", $lastDecision) as $mz) {
    if ($mz === "" || $mz === "-" || $mz === "PASS")
      continue;
    DoGiveExperienceToken(intval($player), $mz);
  }
};

// ─── SMUGGLE_ATTACH — universal: attach a smuggled UPGRADE to the chosen host ──
// Queued by SWUSmuggleResource's upgrade branch (cost already paid). parts[0] = the upgrade
// CardID, parts[1] = its raw resource index. Attaches via _SWUFinalizeUpgradeAttach (ignoreCost,
// caller owns the After Action), replaces the spent resource slot from the deck top (CR 8.22.g),
// then fires the "When played using Smuggle" closure with the HOST mz. After-action ownership:
// the smuggle closure owns it when one exists (SHD_174 attacks or closes itself); else the
// attach's own triggers own it when any fired; else close here.
$customDQHandlers["SMUGGLE_ATTACH"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $cardID = $parts[0] ?? '';
  $resIdx = intval($parts[1] ?? -1);
  $host = ($lastDecision && str_contains($lastDecision, '-')) ? GetZoneObject($lastDecision) : null;
  if (SWUObjGone($host)) {
    SWUAfterAction(intval($player));
    return;
  }
  $hostUID = intval($host->UniqueID ?? 0);
  $triggered = _SWUFinalizeUpgradeAttach(
    intval($player),
    $cardID,
    "myResources-{$resIdx}",
    $lastDecision,
    0,
    true,
    false,
    true
  );
  // Phase 3: the smuggled-upgrade cost (paid up front in SWUSmuggleResource) already included the shared
  // modifier delta's upgrade discounts (SOR_061 Guardian best-case / SEC_064 / ASH_075). _SWUFinalizeUpgradeAttach
  // ran with ignoreCost=true (cost prepaid) so it skipped the used-flag consume — spend them here against
  // the REAL chosen host, so a best-case Guardian discount is only marked used when the upgrade actually
  // landed on the Guardian.
  _SWUConsumeUpgradeUsedFlags(intval($player), $host, $cardID);
  // CR 8.22.g: replace the spent slot with the top card of the deck (enters exhausted).
  $deck = &GetDeck(intval($player));
  for ($i = 0; $i < count($deck); $i++) {
    if (isset($deck[$i]->removed) && $deck[$i]->removed)
      continue;
    $topCardID = $deck[$i]->CardID;
    $deck[$i]->Remove();
    AddResources(intval($player), $topCardID, 0, intval($player), intval($player));
    break;
  }
  global $whenPlayedUsingSmuggleAbilities;
  if (isset($whenPlayedUsingSmuggleAbilities["{$cardID}:0"])) {
    $hostMz = SWUFindMzByUID($hostUID) ?? $lastDecision;
    $whenPlayedUsingSmuggleAbilities["{$cardID}:0"](intval($player), $hostMz);   // owns the close
    return;
  }
  if (intval($triggered) === 0)
    SWUAfterAction(intval($player));
};
// _SWUStageFriendlyCaptives — stage every captive guarded by a unit $player controls into the TempZone;
// returns [$tempMZs, $entries] where each entry is "captorUID:subIdx". Empties/refills the TempZone.
function _SWUStageFriendlyCaptives(int $player): array
{
  $entries = [];
  $cids = [];
  foreach (GetUnitsInPlay($player) as $u) {
    if (!empty($u->removed) || !is_array($u->Subcards ?? null))
      continue;
    foreach ($u->Subcards as $si => $sub) {
      $isCaptive = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
      $isRemoved = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
      if (!$isCaptive || $isRemoved)
        continue;
      $entries[] = intval($u->UniqueID ?? 0) . ':' . $si;
      $cids[] = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
    }
  }
  $temp = &GetTempZone($player);
  while (count($temp) > 0)
    array_pop($temp);
  foreach ($cids as $cid)
    AddTempZone($player, $cid);
  $tempMZs = [];
  for ($k = 0; $k < count($cids); $k++)
    $tempMZs[] = "myTempZone-" . $k;
  return [$tempMZs, $entries];
}
// _SWUPeekCaptiveByEntry — resolve "captorUID:subIdx" and read the captive WITHOUT detaching it, as
// [CardID, Owner] (['' ,0] when it is gone). Needed because a play that has to ask a question first
// (SHD_192's Unit-vs-Pilot fork) must not strand a detached card across a request boundary: the entry
// string is a scalar and re-resolves safely, a floating subcard object would not.
function _SWUPeekCaptiveByEntry(string $entry): array
{
  [$captorUID, $subIdx] = array_map('intval', explode(':', $entry));
  $captorMz = SWUFindMzByUID($captorUID);
  if ($captorMz === null) return ['', 0];
  $captor = GetZoneObject($captorMz);
  if ($captor === null || !is_array($captor->Subcards ?? null) || !isset($captor->Subcards[$subIdx]))
    return ['', 0];
  $sub = $captor->Subcards[$subIdx];
  return [
    is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? ''),
    is_array($sub) ? intval($sub['Owner'] ?? 0) : intval($sub->Owner ?? 0),
  ];
}

// _SWUDetachCaptiveByEntry — resolve "captorUID:subIdx", detach the captive, return the subcard (or null).
function _SWUDetachCaptiveByEntry(string $entry)
{
  [$captorUID, $subIdx] = array_map('intval', explode(':', $entry));
  $captorMz = SWUFindMzByUID($captorUID);
  if ($captorMz === null)
    return null;
  $captor = GetZoneObject($captorMz);
  if ($captor === null || !is_array($captor->Subcards ?? null) || !isset($captor->Subcards[$subIdx]))
    return null;
  $sub = $captor->Subcards[$subIdx];
  array_splice($captor->Subcards, $subIdx, 1);
  return $sub;
}

// ─── SHD_001 Gar Saxon (deployed grant): a friendly upgraded unit was defeated → return one upgrade that
// was attached to it (now in the owner's discard) to hand. Benefit-only auto-resolve run as a lazy sweep
// from SWUAfterAction (gShd001Pending), so it works for ENEMY defeats too (a When-Defeated trigger for the
// controller wouldn't drain on the enemy's turn — subsystem gap #1). ───
function _SWUShd001ProcessPending(): void
{
  global $playerID;
  if (empty($GLOBALS['gShd001Pending']))
    return;
  $pending = $GLOBALS['gShd001Pending'];
  $GLOBALS['gShd001Pending'] = [];
  foreach ($pending as $p) {
    $owner = intval($p['owner'] ?? 0);
    $need = $p['upgs'] ?? [];
    if ($owner <= 0 || empty($need))
      continue;
    $saved = $playerID;
    $playerID = $owner;
    $disc = GetDiscard($owner);
    for ($i = count($disc) - 1; $i >= 0; $i--) {      // most-recent matching upgrade = the one just defeated
      if (!empty($disc[$i]->removed))
        continue;
      if (in_array($disc[$i]->CardID ?? '', $need, true)) {
        SWUReturnFromDiscardToHand($owner, "myDiscard-{$i}");
        break;
      }
    }
    $playerID = $saved;
  }
}

function _SWUEnemyUnitsRemainingHPAtMost(int $player, int $maxHP): array
{
  global $playerID;
  $playerID = intval($player);
  $out = [];
  foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
    foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
      $o = GetZoneObject($mz);
      if (SWUObjGone($o))
        continue;
      if (intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0) <= $maxHP)
        $out[] = $mz;
    }
  }
  return $out;
}



// ─── SHD_223 Snapshot Reflexes ────────────────────────────────────────────────
// When Played: You may attack with attached unit. Reprint of SOR_215 — reuse its handler (SOR_215#0).
$whenPlayedAbilities["SHD_223:0"] = $whenPlayedAbilities["SOR_215:0"];// ─── SHD_109 Endless Legions ────────────────────────────────────────────────────
// Iterative reveal-one loop. Offer the player's UNIT resources (only units are playable this way, so
// non-unit resources are simply not offered — dimmed in the resource-pick panel). MZMAYCHOOSE: pick one
// to reveal+free-play, or pass to stop. Re-offer after each play. Loop ends on pass OR when no unit-
// resources remain. The nested free-play (ActivateCard ignoreCost from a CUSTOM continuation) drains to
// the arena and fires the played unit's own When Played exactly once — strictly one at a time.
function _SWUShd109OfferNext(int $player): void
{
  global $playerID;
  $playerID = intval($player);
  $units = [];
  foreach (ZoneSearch('myResources', ['Unit']) as $mz) {
    $o = GetZoneObject($mz);
    if ($o !== null && empty($o->removed))
      $units[] = $mz;
  }
  if (empty($units))
    return;                          // no unit-resources left → done
  SWUQueueMayChooseTarget(
    $player,
    $units,
    "Play_a_unit_from_your_resources_for_free",
    "Reveal_a_resource_to_play_for_free_(pass_to_stop)",
    "SHD_109#0"
  );
}
// _SWURevertShd213Steals — lazy leave-play sweep (run from SWUAfterAction): any SWU_SHD213 steal
// whose DJ is no longer in play returns the stolen resource (matched by CardID among the stealer's
// non-removed resources — copies are interchangeable) to its owner, preserving its status.
function _SWURevertShd213Steals(): void
{
  global $playerID;
  foreach (GetLiveSeatsArray() as $p) {
    $ge = &GetGlobalEffects($p);
    for ($i = count($ge) - 1; $i >= 0; $i--) {
      $flag = (string) ($ge[$i]->CardID ?? '');
      if (strpos($flag, 'SWU_SHD213|') !== 0)
        continue;
      $parts = explode('|', $flag);
      $djUID = intval($parts[1] ?? 0);
      $cardID = (string) ($parts[2] ?? '');
      $owner = intval($parts[3] ?? 0);
      if (_SWUUnitInPlayWithUID($p, $djUID))
        continue;   // DJ still in play → keep control
      $savedPID = $playerID;
      $playerID = $p;
      foreach (ZoneSearch('myResources') as $rmz) {
        $o = GetZoneObject($rmz);
        if (SWUObjGone($o) || ($o->CardID ?? '') !== $cardID)
          continue;
        $status = intval($o->Status ?? 0);
        $o->Remove();
        DecisionQueueController::CleanupRemovedCards();
        if ($owner > 0)
          AddResources($owner, $cardID, $status, $owner, $owner);
        break;
      }
      $playerID = $savedPID;
      array_splice($ge, $i, 1);
    }
  }
}
// ── Shared "defeat N resource(s)" pick — SWUQueueResourceDefeatPick's continuation ──────────────
// Used by TS26_12 Sundari Palace and TWI_177 Guerilla Insurgency. The pool is built by the caller; all
// this does is apply the answer in a reindex-safe order.
$customDQHandlers["RESOURCE_DEFEAT_PICK"] = function ($player, $parts, $lastDecision) {
  if (SWUDecisionDeclined($lastDecision) || $lastDecision === '') return;
  global $playerID; $playerID = intval($player);
  SWUDefeatResourcesByMzIDs(intval($player), explode('&', $lastDecision));
};

// ── Staged friendly-resource defeat — _SWUQueueFriendlyResourceDefeatStage's continuation ───────
// Applies this board's picks, then moves on to the NEXT seat with whatever debt is left. The pool for
// each stage is rebuilt when that stage is queued, never up front, so an earlier board's defeats cannot
// leave a stale index behind.
$customDQHandlers["RESOURCE_DEFEAT_STAGE"] = function ($player, $parts, $lastDecision) {
  global $playerID; $playerID = intval($player);
  $seats     = array_values(array_filter(array_map('intval', explode(',', (string)($parts[0] ?? '')))));
  $idx       = intval($parts[1] ?? 0);
  $remaining = intval($parts[2] ?? 0);
  $label     = (string)($parts[3] ?? '');
  // A TEAMMATE's board was offered as staged TempZone copies, so translate each pick back to the real
  // p{owner}Resources slot through the positional map that rode the param. Absent map = your own board,
  // which was offered inline and needs no translation.
  $owner  = intval($parts[4] ?? 0);
  $resMap = ($parts[5] ?? '') === '' ? [] : array_map('intval', explode(',', (string)$parts[5]));
  $picked = [];
  if (!SWUDecisionDeclined($lastDecision) && $lastDecision !== '') {
    foreach (explode('&', $lastDecision) as $mz) {
      if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
      if (!empty($resMap) && preg_match('/^myTempZone-(\d+)$/', $mz, $m)) {
        $k = intval($m[1]);
        if (!isset($resMap[$k])) continue;
        $picked[] = "p{$owner}Resources-{$resMap[$k]}";
        continue;
      }
      $picked[] = $mz;
    }
  }
  if (!empty($resMap)) {   // the staged copies are spent either way — never leave them for the next prompt
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    unset($temp);
  }
  if (!empty($picked)) SWUDefeatResourcesByMzIDs(intval($player), $picked);
  _SWUQueueFriendlyResourceDefeatStage(intval($player), $seats, $idx, $remaining - count($picked), $label);
};
