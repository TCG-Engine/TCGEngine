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
// (it enters EXHAUSTED — no "and ready it" rider — into the resource row of whoever controlled it when
// it was defeated).
// AUTO-RESOLVES rather than prompting, matching SOR_083/SHD_085 Superlaser Technician: free ramp off a
// unit that is already dead is taken every time in practice, so the offer was friction rather than a
// decision. This is a deliberate product call, not a rules reading — RAW the "may" is a real choice, and
// there are boards where declining is right (keeping the card in the discard for recursion or
// discard-counting, or staying BELOW an opponent's resource count for SEC_151 Kazuda's "+2/+0 while you
// control fewer resources"). Revisit if one of those ever matters in practice.
$whenDefeatedAbilities["LAW_159:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $dmz = _SWULaw159DiscardMz(intval($player));
    if ($dmz === null) return;   // already moved on (e.g. SHD_122 Arquitens got there first)
    $r = MZMove(intval($player), $dmz, "myResources");
    if ($r !== null) { $r->Status = 0; $r->Owner = intval($player); $r->Controller = intval($player); SWUKeepCreditTokensLast(intval($player)); }
};
