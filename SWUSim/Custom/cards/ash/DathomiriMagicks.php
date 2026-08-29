<?php
// ASH_104
// Cost 6 - Dathomiri Magicks - [Command,Villainy]
// Text: If you control a Force unit, this event costs 1 resource less to play. / Play up to 3 non-Vehicle units that each cost 2 or less from your discard pile for free.

// ASH_104 Dathomiri Magicks — play each chosen discard unit (non-Vehicle, ≤2) for free. Resolve the
// chosen myDiscard-N indices in DESCENDING order so each free play doesn't shift the remaining indices.
$customDQHandlers["ASH_104#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $idxs = [];
    foreach (explode('&', $lastDecision) as $mz) {
        if (strpos($mz, 'myDiscard-') === 0) $idxs[] = intval(substr($mz, strlen('myDiscard-')));
    }
    rsort($idxs);   // descending — highest discard index first
    foreach ($idxs as $idx) {   // descending, so removing higher indices leaves lower mzIDs valid
        // Nested play: the outer EVENT's FINISH_PLAY_CARD owns this action's ending, so ActivateCard
        // must not finalise it too (an extra action). SWUNestedPlay covers the deferred leg as well —
        // each of the up-to-3 plays can arm an entry trigger of its own.
        SWUNestedPlay(intval($player), "myDiscard-{$idx}", false, 99);   // free (via canonical play)
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_104:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = [];
    $discard = GetDiscard(intval($player));
    for ($i = 0; $i < count($discard); $i++) {
        $d = $discard[$i];
        if (SWUObjGone($d)) continue;
        $cid = $d->CardID ?? '';
        if (strpos(CardType($cid) ?? '', 'Unit') === false) continue;   // units only
        if (HasTrait($cid, 'Vehicle')) continue;                          // non-Vehicle
        if (intval(CardCost($cid)) > 2) continue;                         // cost 2 or less
        $tg[] = "myDiscard-{$i}";
    }
    if (empty($tg)) return;
    $max = min(3, count($tg));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode('&', $tg), 1,
        tooltip: "Play_up_to_3_non-Vehicle_units_(cost_2_or_less)_from_discard_for_free");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_104#0", 1, dontSkipOnPass: 1);
};
