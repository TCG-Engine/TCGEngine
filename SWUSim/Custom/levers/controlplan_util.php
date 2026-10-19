<?php
// Shared helpers for the 'controlplan' levers (Hemlock DV report, Plan 2). No lever registers here.

// Would the bot TAKE $action right now — does it score above PASS (0.0)? A hold that waits for another action ("play the removal first",
// "deploy first", "play the curve card first") must only wait for something that will actually happen; otherwise the held action and the
// awaited one both sit below pass and the round stalls (final review 2026-10-19, Important 1-3). Nested calls (a hold asked while another
// hold is already scoring) answer false — never hold on a guess.
function _SWUBotLvScoresAbovePass(array $ctx, array $action): bool {
    if (!empty($GLOBALS['SWUBotLvHoldGuard'])) return false;
    $GLOBALS['SWUBotLvHoldGuard'] = 1;
    try {
        $i = array_search($action, (array)($ctx['actions'] ?? []), true);
        return SWUBotScoreAction($ctx, $action, $i === false ? 0 : intval($i)) > 0.0;
    } finally {
        $GLOBALS['SWUBotLvHoldGuard'] = 0;
    }
}

// The deciding seat — the only seat whose HAND a lever may read (opponents' hands are hidden; SWUBotOpponentCardModel models them).
function _SWUBotLvIsViewer(int $seat): bool {
    return !isset($GLOBALS['SWUBotViewerSeat']) || intval($GLOBALS['SWUBotViewerSeat']) === $seat;
}
