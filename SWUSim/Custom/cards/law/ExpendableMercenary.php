<?php
// LAW_159
// Cost 4 - Expendable Mercenary - [Command] - Power 3 - HP 3
// Text: When Defeated: You may resource this unit from its owner's discard pile.

// Locate this card in the pile it actually landed in, as an mzID in $player's frame. Normally that is
// the controller's own discard, but when the unit is defeated while an OPPONENT controls it (JTL_043
// No Glory, Only Results takes control and then defeats) the card goes to its OWNER's discard while the
// When Defeated belongs to the controller at the time of defeat — so both piles have to be checked.
function _SWULaw159DiscardMz(int $player): ?string {
    global $playerID; $saved = $playerID;
    $playerID = $player;
    $mz = _SWUFindDiscardMzID($player, 'LAW_159');
    if ($mz === null) {
        $opp = OtherPlayer($player);
        $playerID = $opp;
        $oppMz = _SWUFindDiscardMzID($opp, 'LAW_159');
        if ($oppMz !== null) $mz = 'theirDiscard-' . substr($oppMz, strlen('myDiscard-'));
    }
    $playerID = $saved;
    return $mz;
}

// LAW_159 Expendable Mercenary — When Defeated: you MAY resource this unit from its owner's discard pile
// (it enters exhausted, into the resource row of whoever controlled it when it was defeated).
// The choice is real: declining keeps the card in the discard, which matters for anything that counts
// discarded cards or recurs from the discard, and for effects that compare resource counts
// (SEC_151 Kazuda gets +2/+0 "while you control FEWER resources than an opponent").
$whenDefeatedAbilities["LAW_159:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (_SWULaw159DiscardMz(intval($player)) === null) return;   // already moved on (e.g. SHD_122 Arquitens)
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Resource_this_unit_from_its_owner's_discard_pile?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_159#0", 1);
};

$customDQHandlers["LAW_159#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $dmz = _SWULaw159DiscardMz(intval($player));
    if ($dmz === null) return;
    $r = MZMove(intval($player), $dmz, "myResources");
    if ($r !== null) { $r->Status = 0; $r->Owner = intval($player); $r->Controller = intval($player); SWUKeepCreditTokensLast(intval($player)); }
};
