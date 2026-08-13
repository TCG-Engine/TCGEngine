<?php
// HMW_070
// Cost 3 - Dark Sanctum - [Vigilance][Villainy] - Upgrade - Trait: Fortification - NON-unique
// Text: Fortify (Attach this to your base, not a unit.)
//       Attached base gains: "When the regroup phase starts: Draw a card and deal 2 damage to this base."
//
// The Fortify half needs no code: the keyword generator registers HMW_070 in $Fortify_Cards from the
// card text, and SWUGetUpgradeValidTargets' Fortify branch already returns ['myBase-0'].
//
// The granted ability is a BASE-hosted phase trigger, so it hangs off RegroupPhaseStart rather than any
// unit registry (a base has no ability registry of its own — this mirrors _SWUHmw004RegroupBaseDefeat).
// The call site is in RegroupPhaseStart; the body lives here with the rest of the card.
//
// "Draw a card AND deal 2" is joined by "and", so both halves are unconditional — neither is gated on
// the other. Dealing 2 to your OWN base can defeat it; SWUDealDamageToBase owns that loss check, so
// nothing extra is needed here.
//
// Fires once PER ATTACHED COPY: the card is non-unique, and each copy grants its own instance of the
// ability. Counting copies (rather than testing a boolean "is one attached?") is what makes two copies
// deal 4 instead of 2.
function _SWUHmw070RegroupBaseTriggers(): void {
    global $playerID;
    $saved = $playerID;
    for ($p = 1; $p <= SeatCountForGame(); $p++) {
        $zone = GetBase($p);
        if (empty($zone) || !isset($zone[0]) || !empty($zone[0]->removed)) continue;
        $copies = 0;
        foreach (GetUpgradesOnUnit($zone[0]) as $sub) {
            $cid = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
            if ($cid === 'HMW_070') $copies++;
        }
        if ($copies <= 0) continue;
        $playerID = $p;
        for ($i = 0; $i < $copies; $i++) {
            DoDrawCard($p, 1);
            SWUDealDamageToBase(2, $p);
        }
    }
    $playerID = $saved;
}
