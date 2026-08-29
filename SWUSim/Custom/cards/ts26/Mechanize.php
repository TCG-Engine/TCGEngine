<?php
// TS26_57
// Cost 2 - Mechanize - [Command]
// Text: Play a non-Vehicle unit from your discard pile (paying its cost) and give an Experience token to it.

// TS26_57 Mechanize — play the chosen non-Vehicle unit from discard (paying its cost) and give it an
// Experience token.
$customDQHandlers["TS26_57#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || strpos($lastDecision, 'myDiscard-') !== 0) return;
    // Nested play: the outer EVENT owns this action's ending (see SWUNestedPlay for both after-action
    // legs — the immediate one and the deferred SWU_TRIGGER_RESUME).
    SWUNestedPlay(intval($player), strval($lastDecision), false, 0);   // full cost via canonical play
    $mz = $GLOBALS['gLastPlayedMzID'];
    if ($mz !== '') DoGiveExperienceToken(intval($player), $mz);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_57:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $ready = SWUTotalPaymentCapacity(intval($player));
    $discard = GetDiscard(intval($player));
    $tg = [];
    $liveIdx = 0;
    for ($i = 0; $i < count($discard); $i++) {
        $d = $discard[$i];
        if (SWUObjGone($d)) continue;
        $cid = $d->CardID ?? '';
        if (strpos(CardType($cid) ?? '', 'Unit') !== false && !HasTrait($cid, 'Vehicle')
            && max(0, intval(CardCost($cid)) + SWUAspectPenalty(intval($player), $cid)) <= $ready) {
            $tg[] = "myDiscard-{$liveIdx}";
        }
        $liveIdx++;
    }
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Play_a_non-Vehicle_unit_from_your_discard", "TS26_57#0");
};
