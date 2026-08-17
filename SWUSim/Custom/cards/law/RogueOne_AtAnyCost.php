<?php
// LAW_119
// Cost 3 - Rogue One - At Any Cost - [Vigilance] - Power 3 - HP 3
// Text: When a friendly unit is defeated: Look at the top 2 cards of your deck. Put any number of them on the bottom of your deck and the rest on top in any order.

// LAW_119 Rogue One — put the chosen top cards on the bottom of the deck (the rest stay on top).
// $lastDecision is an &-delimited list of myTempZone-K specs (Law119Trigger stages the peeked cards
// there so the prompt renders the CARDS — see the note on that function). K is the index into the
// top-of-deck slice, so each pick maps to the K-th LIVE deck entry, resolved here rather than from the
// staged copy — that is what keeps a second scry correct after the first one moved a card to the bottom.
// ⚠ This handler must NOT drain TempZone. A mass wipe (SOR_043) defeats several friendly units at once
// and queues one scry PER defeat, all before any of them is answered — draining here emptied the pool the
// still-pending scry was pointing at, so it auto-skipped and the player silently lost it
// (MassWipe_FiresForEACHFriendlyDefeated). The staging is cleared at the START of each trigger instead,
// which is the convention every other TempZone stager already follows.
$customDQHandlers["LAW_119#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $idxs = [];
    if (!SWUDecisionDeclined($lastDecision)) {
        foreach (explode("&", (string)$lastDecision) as $mz) {
            $mz = trim($mz);
            if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
            if (preg_match('/-(\d+)$/', $mz, $m)) $idxs[] = intval($m[1]);
        }
    }
    if (empty($idxs)) return;                          // none → all stay on top
    $deck = ZoneSearch("myDeck", null);
    $cardIDs = [];
    foreach ($idxs as $i) {
        if (!isset($deck[$i])) continue;
        $o = GetZoneObject($deck[$i]);
        if ($o === null || !empty($o->removed)) continue;
        $cardIDs[] = $o->CardID;
        $o->removed = true;
    }
    if (empty($cardIDs)) return;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom(intval($player), $cardIDs);
};
