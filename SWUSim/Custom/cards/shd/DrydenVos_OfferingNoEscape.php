<?php
// SHD_192
// Cost 7 - Dryden Vos - Offering No Escape - [Cunning,Villainy] - Power 5 - HP 7
// Text: Shielded / When Played: Choose a captured card guarded by a unit you control. You may play it for free under your control.

// ─── SHD_192 Dryden Vos ───────────────────────────────────────────────────────
// Shielded + When Played: Choose a captured card guarded by a unit you control. You may play it for free
// under your control (added to the arena under the caster's control, exhausted; owner unchanged).
$whenPlayedAbilities["SHD_192:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    [$tempMZs, $entries] = _SWUStageFriendlyCaptives(intval($player));
    if (empty($entries)) return;
    SWUQueueMayChooseTarget(intval($player), $tempMZs,
        "Play_a_captured_card_for_free_under_your_control?", "Choose_a_captured_card", "SHD_192#0|" . implode(",", $entries));
};

$customDQHandlers["SHD_192#0"] = function($player, $parts, $lastDecision) {
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
    // A captive's Owner should always be set; when it is not, the seat still must not default to 2.
    if ($owner === 0) $owner = SWUMzOwner((string)$lastDecision, intval($player));
    $uid   = NextUniqueID();
    $arena = CardTargetArena($cid);
    $newCard = ($arena === 'SpaceArena')
        ? AddSpaceArena(intval($player), CardID:$cid, Status:0, Owner:$owner, Damage:0, Controller:intval($player), UniqueID:$uid)
        : AddGroundArena(intval($player), CardID:$cid, Status:0, Owner:$owner, Damage:0, Controller:intval($player), UniqueID:$uid);
    // The card is PLAYED, not just placed — so its entry triggers fire, exactly as they do on every other
    // "play a card from a non-hand zone" path (_SWUSmuggleFireEntry, _SWUOwnDiscardPlayAsUnit). Without
    // this the freed unit's When Played was silently lost; CONSTANT abilities still worked, because those
    // are recomputed from board state, which is what made the gap look like it wasn't there.
    if ($newCard !== null) {
        CollectEntryTriggers(intval($player), $cid, $newCard->GetMzID(), $arena);
    }
};
