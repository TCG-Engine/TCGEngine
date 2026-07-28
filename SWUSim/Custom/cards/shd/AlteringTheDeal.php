<?php
// SHD_243
// Cost 1 - Altering the Deal - [Villainy]
// Text: Discard a captured card guarded by a friendly unit.

// ─── SHD_243 Altering the Deal (Event) continuation ───────────────────────────
// Discard the chosen captured card (guarded by a friendly unit) to its owner's discard pile.
$customDQHandlers["SHD_243#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $entries = ($parts[0] ?? '') !== '' ? explode(",", $parts[0]) : [];
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    if (!$lastDecision || !preg_match('/myTempZone-(\d+)/', (string)$lastDecision, $m)) return;
    $pickedIdx = intval($m[1]);
    if (!isset($entries[$pickedIdx])) return;
    $sub = _SWUDetachCaptiveByEntry($entries[$pickedIdx]);
    if ($sub === null) return;
    $cid   = is_array($sub) ? ($sub['CardID'] ?? '') : ($sub->CardID ?? '');
    $owner = is_array($sub) ? intval($sub['Owner'] ?? 0) : intval($sub->Owner ?? 0);
    if ($cid === '') return;
    if ($owner === 0) $owner = OtherPlayer(intval($player));
    SWUAddToDiscard($owner, $cid, 'PLAY');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_243:0"] = function($player, $mzID = '') {
// Altering the Deal — "Discard a captured card guarded by a friendly unit."
            [$tempMZs, $entries] = _SWUStageFriendlyCaptives(intval($player));
            if (empty($entries)) return;
            SWUQueueChooseTarget(intval($player), $tempMZs, "Discard_a_captured_card", "SHD_243#0|" . implode(",", $entries));
            return;
};
