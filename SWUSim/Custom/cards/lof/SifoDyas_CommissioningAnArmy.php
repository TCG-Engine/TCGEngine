<?php
// LOF_117
// Cost 5 - Sifo-Dyas - Commissioning An Army - [Command] - Power 4 - HP 4
// Text: When Defeated: Search the top 8 cards of your deck for any number of Clone units with combined cost 4 or less and discard them. (Put the other cards on the bottom of your deck in a random order.) For this phase, you may play those cards from your discard pile for free.

// LOF_117 Sifo-Dyas — When Defeated: Search the top 8 for any number of Clone units with combined cost 4
// or less and discard them (rest to the bottom, random); this phase you may play those from your discard
// for free. The discard entries are marked TPF (free-playable-from-discard this phase, expires at the phase
// turn via SWUClearDiscardModifiers); the player plays them later via the normal PlayFromDiscard action.
$whenDefeatedAbilities["LOF_117:0"] = function($player, $mzID) {
    _topDeckSearchBegin(intval($player), 8,
        fn($cid) => CardType($cid) === 'Unit' && HasTrait($cid, 'Clone'),
        "cost:4", "LOF_117#0");
};

// ── LOF When-Defeated units (Phase 9) ───────────────────────────────────────────────────────────────
$customDQHandlers["LOF_117#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $allIDs = array_values(array_filter(explode(',', $parts[0] ?? '')));
  $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
  // Server-side budget guard: keep only chosen Clone units whose RUNNING combined printed cost stays ≤4
  // (greedy in pick order); anything that would exceed it (or isn't a Clone unit) is dropped to the bottom.
  $budget = 4;
  $kept = [];
  $rejected = [];
  foreach ($resolved['drawn'] as $cid) {
    $cost = intval(CardCost($cid));
    if (CardType($cid) === 'Unit' && HasTrait($cid, 'Clone') && $cost <= $budget) {
      $kept[] = $cid;
      $budget -= $cost;
    } else {
      $rejected[] = $cid;
    }
  }
  foreach ($kept as $cid)
    SWUAddToDiscard(intval($player), $cid, 'DECK', 'TPF'); // free-playable this phase
  _topDeckPutRemainingToBottom(intval($player), array_merge($resolved['remaining'], $rejected));
};
