<?php
// LAW_159
// Cost 4 - Expendable Mercenary - [Command] - Power 3 - HP 3
// Text: When Defeated: You may resource this unit from its owner's discard pile.

// Locate this card in the pile it actually landed in, as an mzID in $player's frame. Normally that is
// the controller's own discard, but when the unit is defeated while an OPPONENT controls it (JTL_043
// No Glory, Only Results takes control and then defeats) the card goes to its OWNER's discard while the
// When Defeated belongs to the controller at the time of defeat — so the other piles have to be checked
// too. Every other seat, not just OtherPlayer(): at four seats the owner can be any of them, and a
// far-seat owner's pile is named with its absolute `p<seat>Discard-N` mzID.
function _SWULaw159DiscardMz(int $player): ?string {
    global $playerID; $saved = $playerID;
    $playerID = $player;
    $mz = _SWUFindSelfInDiscardMzID($player, 'LAW_159');
    if ($mz === null) {
        foreach (GetSeatOrderArray() as $seat) {
            if (intval($seat) === $player) continue;
            $playerID = intval($seat);
            $seatMz = _SWUFindSelfInDiscardMzID(intval($seat), 'LAW_159');
            if ($seatMz !== null) { $mz = "p{$seat}Discard-" . substr($seatMz, strlen('myDiscard-')); break; }
        }
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
    if ($r !== null) {   // game log: the resource is face down, so "this unit", not its name
        $pile = preg_match('/^p(\d+)Discard-/', $dmz, $m) ? ("P{$m[1]}'s discard pile") : 'their discard pile';
        SWULogResourced(intval($player), "this unit from {$pile}");
    }
};
