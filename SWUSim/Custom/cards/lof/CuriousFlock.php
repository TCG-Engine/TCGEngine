<?php
// LOF_255
// Cost 1 - Curious Flock - Power 1 - HP 1
// Text: When Played: Pay up to 6 resources. For each resource paid this way, give an Experience token to this unit.

// ── Phase 18 — "pay up to N resources; per resource, do X" iterative pay-X seam ───────────────────────
// LOF_255 Curious Flock — When Played: Pay up to 6 resources. For each resource paid, give an Experience
// token to this unit. Iterative YESNO (pay 1 → 1 Experience) up to 6 or until you decline / run dry.
$whenPlayedAbilities["LOF_255:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if (SWUObjGone($o)) return;
    CuriousFlockOffer(intval($player), intval($o->UniqueID ?? -1), 6);
};

$customDQHandlers["LOF_255#0"] = function ($player, $parts, $lastDecision) {
  if ($lastDecision !== 'YES')
    return; // declined → stop paying
  global $playerID;
  $playerID = intval($player);
  $uid = intval($parts[0] ?? -1);
  $remaining = intval($parts[1] ?? 0);
  // ⚠ SCALED-EFFECT COST — resources ONLY, never Credit tokens / SEC_122 Droids.
  // The magnitude keys off "resources paid this way", and a Credit is NOT a resource (CR 3.13):
  // defeating one pays 1 less, it does not become a resource paid. So a Credit can pay this CARD's
  // own play cost (the normal play path), but must never scale this effect. Deliberate exception to
  // the engine-wide SWUPayInlineAbilityCost conversion — do not "fix" it back.
  if (!SWUExhaustResources(intval($player), 1))
    return; // pay 1 resource
  $mz = SWUFindMzByUID($uid);
  if ($mz !== null && $mz !== '')
    DoGiveExperienceToken(intval($player), $mz);
  CuriousFlockOffer(intval($player), $uid, $remaining - 1);
};

function CuriousFlockOffer(int $player, int $uid, int $remaining): void
{
  global $playerID;
  $playerID = $player;
  if ($remaining <= 0)
    return;
  if (SWUResourceCount($player, readyOnly: true) < 1) // resources only — see the note on LOF_255#0
    return; // nothing left to pay
  $mz = SWUFindMzByUID($uid);
  if ($mz === null || $mz === '')
    return;
  DecisionQueueController::AddDecision(
    $player,
    "YESNO",
    "-",
    1,
    tooltip: "Pay_1_resource_for_an_Experience_token_on_this_unit?_({$remaining}_remaining)"
  );
  DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_255#0|{$uid}|{$remaining}", 1);
}
