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
    $entry = $entries[$pickedIdx];
    // PEEK, don't detach yet — the Unit-vs-Pilot fork below is a decision, and a detached subcard is a
    // floating object with no zone to re-resolve from across the request boundary. The entry string
    // ("captorUID:subIdx") is a scalar and rides the CUSTOM param safely.
    [$cid, $owner] = _SWUPeekCaptiveByEntry($entry);
    if ($cid === '') return;
    // A captive's Owner should always be set; when it is not, the seat still must not default to 2.
    if ($owner === 0) $owner = SWUMzOwner((string)$lastDecision, intval($player));

    // CR 17.c: "If a player is instructed to 'play a card,' they may play a unit with Piloting as a unit
    // or an upgrade." Dryden says "you may PLAY IT for free" — a bare card play, NOT the "play a unit"
    // wording that CR 17.c/525.a restrict — so a captured Piloting card must be offered the same
    // Unit-vs-Pilot choice every other play path offers. (Contrast the DISCOUNT_PLAY_FROM_HAND family,
    // whose grants all say "play a UNIT" and therefore suppress this fork.)
    // The play is FREE, so the Unit branch is always affordable and the hosts are gathered with
    // ignoreCost — a broke player must still be offered the pilot branch.
    if (HasKeyword_Piloting((object)['CardID' => $cid])
            && !_SWUGalenSuppressesCard(intval($player), $cid)) {
        $vehicles = SWUGetPilotValidTargets(intval($player), $cid, true);
        if (!empty($vehicles)) {
            DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Unit&Pilot", 1,
                tooltip: "Play_as_Unit_or_Pilot?");
            DecisionQueueController::AddDecision(intval($player), "CUSTOM",
                "SHD_192#1|{$entry}|{$cid}|" . intval($owner), 1);
            return;
        }
    }
    _SWUShd192PlayCaptiveAsUnit(intval($player), $entry, $cid, intval($owner));
};

// SHD_192#1 — the Unit-vs-Pilot answer for a captured Piloting card.
$customDQHandlers["SHD_192#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $entry = $parts[0] ?? '';
    $cid   = $parts[1] ?? '';
    $owner = intval($parts[2] ?? 0);
    if ($entry === '' || $cid === '') return;

    if ($lastDecision === 'Pilot') {
        // Re-derive the hosts (the board can move between the offer and the answer).
        $vehicles = SWUGetPilotValidTargets(intval($player), $cid, true);
        if (!empty($vehicles)) {
            // Detach NOW — from here on the play is committed and only scalars cross the boundary.
            // _SWUFinalizeUpgradeAttach tolerates an EMPTY source mzID (it only uses it to remove the
            // card from a zone, and a detached captive has no zone), and its $owner param keeps the
            // upgrade owned by its real owner so it returns to THEIR discard when defeated — the same
            // owner/controller split the unit branch below applies.
            if (_SWUDetachCaptiveByEntry($entry) === null) return;
            if (HasTrait($cid, 'Force')) AddGlobalEffects(intval($player), 'SWU_PLAYED_NONUNIT_FORCE');
            DecisionQueueController::AddDecision(intval($player), "MZCHOOSE", implode("&", $vehicles), 1,
                tooltip: "Choose_a_Vehicle_to_pilot");
            // ignoreCost=1 (Dryden plays it FOR FREE), isPilot=1, discount 0, then the owner.
            DecisionQueueController::AddDecision(intval($player), "CUSTOM",
                "ATTACH_UPGRADE|{$cid}||1|1|0|" . intval($owner), 1);
            return;
        }
    }
    _SWUShd192PlayCaptiveAsUnit(intval($player), $entry, $cid, $owner);
};

// Place a freed captive into its arena as a UNIT under the caster's control (owner unchanged), and fire
// its entry triggers. Extracted so the Unit-vs-Pilot fork can reach it from either answer.
function _SWUShd192PlayCaptiveAsUnit(int $player, string $entry, string $cid, int $owner): void {
    global $playerID; $playerID = intval($player);
    if (_SWUDetachCaptiveByEntry($entry) === null) return;
    $uid   = NextUniqueID();
    $arena = CardTargetArena($cid);
    $newCard = ($arena === 'SpaceArena')
        ? AddSpaceArena($player, CardID:$cid, Status:0, Owner:$owner, Damage:0, Controller:$player, UniqueID:$uid)
        : AddGroundArena($player, CardID:$cid, Status:0, Owner:$owner, Damage:0, Controller:$player, UniqueID:$uid);
    // The card is PLAYED, not just placed — so its entry triggers fire, exactly as they do on every other
    // "play a card from a non-hand zone" path (_SWUSmuggleFireEntry, _SWUOwnDiscardPlayAsUnit). Without
    // this the freed unit's When Played was silently lost; CONSTANT abilities still worked, because those
    // are recomputed from board state, which is what made the gap look like it wasn't there.
    if ($newCard !== null) {
        CollectEntryTriggers($player, $cid, $newCard->GetMzID(), $arena);
    }
}
