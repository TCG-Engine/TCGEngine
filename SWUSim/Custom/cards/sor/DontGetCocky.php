<?php
// SOR_223
// Cost 4 - Don't Get Cocky - [Cunning]
// Text: Choose a unit. One at a time, reveal cards from your deck until you choose to stop or have revealed 7 cards. If the combined cost of the revealed cards is 7 or less, deal that much damage to the chosen unit. Put the revealed cards on the bottom of your deck in a random order.

$customDQHandlers["SOR_223#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DontGetCockyStep(intval($player), intval($o->UniqueID), []);
};

$customDQHandlers["SOR_223#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $revealed = (($parts[1] ?? '') !== '') ? explode(",", $parts[1]) : [];
    if ($lastDecision === "YES") DontGetCockyStep(intval($player), $uid, $revealed);  // reveal another
    else                        _SOR223Resolve(intval($player), $uid, $revealed); // stop
};

// Reveal one more card, then either continue (queue the YESNO) or resolve (stopped / 7 revealed / deck
// empty). $revealed = the CardIDs revealed so far (this call appends one).
function DontGetCockyStep(int $player, int $targetUID, array $revealed): void
{
  $cid = _SOR223RevealTop($player);
  if ($cid === null) {
    _SOR223Resolve($player, $targetUID, $revealed);
    return;
  }  // deck already empty
  $revealed[] = $cid;
  $deckEmpty = (count(GetDeck($player)) === 0);
  if (count($revealed) >= 7 || $deckEmpty) {            // hard cap / out of cards → resolve now
    _SOR223Resolve($player, $targetUID, $revealed);
    return;
  }
  $revealedStr = implode(",", $revealed);
  DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Reveal_another_card?");
  DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_223#1|{$targetUID}|{$revealedStr}", 1);
}

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_223:0"] = function($player, $mzID = '') {
// Don't Get Cocky — "Choose a unit. One at a time, reveal cards from your deck
                          // until you choose to stop or have revealed 7 cards. If the combined cost of the
                          // revealed cards is 7 or less, deal that much to the chosen unit. Put the
                          // revealed cards on the bottom of your deck in a random order." The reveal loop
                          // + running cost is carried across requests in the SOR_223#1 handler param.
            global $playerID;
            $playerID = intval($player);
            $units = SWUAllUnits();
            if (empty($units)) return;   // no unit to choose → fizzle
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit", "SOR_223#0");
            return;
};
