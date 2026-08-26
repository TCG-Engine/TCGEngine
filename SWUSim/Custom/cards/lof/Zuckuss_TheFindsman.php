<?php
// LOF_204
// Cost 5 - Zuckuss - The Findsman - [Cunning,Cunning] - Power 4 - HP 7
// Text: On Attack: Name a card, then discard the top card of the defending player's deck. If a card with that name is discarded, this unit gets +4/+0 for this attack.

// ── Phase 20 — name-a-card / look-at-hand input UIs ───────────────────────────────────────────────────
// LOF_204 Zuckuss — On Attack: Name a card, then discard the top card of the defending player's deck. If a
// card with that name is discarded, this unit gets +4/+0 for this attack. (NAMECARD input, then a CUSTOM
// continuation — safe vs the OnAttack $playerID-restore gotcha.)
$onAttackAbilities["LOF_204:0"] = function($player, $mzID) {
    $o = GetZoneObject($mzID);
    $uid = ($o !== null) ? intval($o->UniqueID ?? -1) : -1;
    DecisionQueueController::AddDecision($player, "NAMECARD", "", 1, "Name_a_card");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LOF_204#0|{$uid}", 1);
};

$customDQHandlers["LOF_204#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $uid   = intval($parts[0] ?? -1);
    $named = trim($lastDecision);
    $opp   = SWUCurrentDefendingSeat(intval($player));  // the defending player, at any seat count
    $deck  = &GetDeck($opp);
    if (empty($deck)) return;
    $topCid = $deck[0]->CardID;
    array_shift($deck);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    SWUAddToDiscard($opp, $topCid, 'DECK');
    AddGameLogEntry('DISCARD', "P{$opp} discarded the top of their deck: " . GameLogCardRef($topCid), 'ALL');
    if (CardTitle($topCid) === $named) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null && $mz !== '') SWUAddAttackPowerBonus($mz, 4); // +4/+0 for this attack
    }
};
