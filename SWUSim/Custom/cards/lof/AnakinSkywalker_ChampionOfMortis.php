<?php
// LOF_070
// Cost 6 - Anakin Skywalker - Champion of Mortis - [Vigilance] - Power 5 - HP 7
// Text: When Played: If there is a Heroism card in your discard pile, you may give a unit -3/-3 for this phase. / When Played: If there is a Villainy card in your discard pile, you may give a unit -3/-3 for this phase.

// ── LOF When-Played units (Phase 7) ─────────────────────────────────────────────────────────────────
// LOF_070 Anakin Skywalker — two separate When-Played abilities (the first card to use ":0"/":1"
// trigger tokens, dispatched in order by OnWhenPlayed):
//   :0 — if a Heroism card is in your discard, you may give a unit -3/-3 for this phase.
//   :1 — if a Villainy card is in your discard, you may give a unit -3/-3 for this phase.
$lof070HasAspectInDiscard = function ($player, $aspect) {
  foreach (GetDiscard(intval($player)) as $d) {
    if (!empty($d->removed))
      continue;
    if (strpos(CardAspect($d->CardID ?? '') ?? '', $aspect) !== false)
      return true;
  }
  return false;
};

$whenPlayedAbilities["LOF_070:0"] = function ($player, $mzID) use ($lof070HasAspectInDiscard) {
  if (!$lof070HasAspectInDiscard($player, 'Heroism'))
    return;
  SWUOfferUnitTarget($player, $mzID, [
    'continuation' => 'APPLY_PHASE_DEBUFF|3|3|LOF_070',
    'side' => 'any', 'may' => true,
    'question' => "Heroism_in_discard:_give_a_unit_-3/-3?", 'prompt' => "Choose_a_unit",
  ]);
};

$whenPlayedAbilities["LOF_070:1"] = function ($player, $mzID) use ($lof070HasAspectInDiscard) {
  if (!$lof070HasAspectInDiscard($player, 'Villainy'))
    return;
  SWUOfferUnitTarget($player, $mzID, [
    'continuation' => 'APPLY_PHASE_DEBUFF|3|3|LOF_070',
    'side' => 'any', 'may' => true,
    'question' => "Villainy_in_discard:_give_a_unit_-3/-3?", 'prompt' => "Choose_a_unit",
  ]);
};
