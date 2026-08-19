<?php
// ASH_230
// Cost 2 - Improvised Identity - [Cunning] - Upgrade Power 0 - Upgrade HP 3
// Text: Attach to a ground unit. / Attached unit gains: "Action: Search the top 3 cards of your deck for a ground unit and discard it. Then, you may attack with this unit. For this attack, this unit gains the discarded unit's abilities. Use this ability only once each round."

// ASH_230 Improvised Identity (upgrade-granted action, no exhaust, once each round) — "Search the top 3
// cards of your deck for a ground unit and discard it. Then, you may attack with this unit. For this
// attack, this unit gains the discarded unit's abilities." The abilities are transplanted via the Support
// SUPPORT_GRANT marker ([discarded CardID, uid 0]) — combat fires that CardID's On Attack / keywords / On
// Attack End at each combat site. Cost kind 'none' (the host must stay ready to attack); gated once/round.
$unitActionCostKind["ASH_230"] = 'none';

$unitAbilities["ASH_230"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $host    = GetZoneObject($mzID);
    $hostUID = SWUObjUID($host, 0);
    _topDeckSearchBegin(intval($player), 3,
        fn($cid) => strpos(CardType($cid) ?? '', 'Unit') !== false && CardArena($cid) === 'Ground',
        "count:1", "ASH_230#0|{$hostUID}");
};

$customDQHandlers["ASH_230#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $hostUID  = intval($parts[0] ?? 0);
    $allIDs   = array_values(array_filter(explode(',', $parts[1] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $chosen   = $resolved['drawn'];
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    AddGlobalEffects(intval($player), 'SWU_ASH230_USED');   // once each round (the ability was used)
    // "Then, you may attack with this unit" is UNCONDITIONAL (offered even when no ground unit was in the top 3
    // or the search was declined — just with no ability grant). Only the discard-and-grant is conditional.
    $discardedID = empty($chosen) ? '' : $chosen[0];
    if ($discardedID !== '') SWUAddToDiscard(intval($player), $discardedID, 'DECK');   // "and discard it"
    $hostMz = SWUFindMzByUID($hostUID);
    $host   = $hostMz !== null ? GetZoneObject($hostMz) : null;
    if (SWUObjGone($host) || intval($host->Status) !== 1) { SWUAfterAction(intval($player)); return; }
    // USER RULING (2026-08-18): if the discarded card carries a printed "This unit can't attack", the host
    // gains that too — and "cannot" beats "can", so the optional attack is NOT OFFERED AT ALL. The clause
    // is a "you may", so there is simply no resolution rather than an offer that later fizzles.
    // Checked on the DISCARDED CardID because the SUPPORT_GRANT marker is not applied until ASH_230#1,
    // after this prompt; _SWUCardIDCantAttack is the shared roster the in-play checks use.
    if ($discardedID !== '' && _SWUCardIDCantAttack($discardedID)) { SWUAfterAction(intval($player)); return; }
    $tip = $discardedID !== ''
        ? ("Attack_with_this_unit_(gaining_" . GameLogCardRef($discardedID) . "'s_abilities)?")
        : "Attack_with_this_unit?";
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: $tip);
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_230#1|{$hostUID}|{$discardedID}", 1);
};

$customDQHandlers["ASH_230#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') { SWUAfterAction(intval($player)); return; }
    $hostUID     = intval($parts[0] ?? 0);
    $discardedID = strval($parts[1] ?? '');
    $hostMz = SWUFindMzByUID($hostUID);
    if ($hostMz === null) { SWUAfterAction(intval($player)); return; }
    $host = GetZoneObject($hostMz);
    if (SWUObjGone($host)) { SWUAfterAction(intval($player)); return; }
    if ($discardedID !== '') {
        // Transplant the discarded unit's abilities for this attack. Its KEYWORDS come from the printed-keyword
        // lookup arrays (the card is in the discard pile, not a live object); its triggered/constant abilities
        // ride the SUPPORT_GRANT carrier (same mechanism the Support keyword uses).
        global $Grit_Cards, $Overwhelm_Cards, $Saboteur_Cards, $Sentinel_Cards, $Raid_Cards, $Restore_Cards;
        if (isset($Grit_Cards[$discardedID]))      AddTurnEffect($hostMz, SWUMakeTurnEffect('GRIT',      [], SWU_DUR_ATTACK, $discardedID));
        if (isset($Overwhelm_Cards[$discardedID])) AddTurnEffect($hostMz, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, $discardedID));
        if (isset($Saboteur_Cards[$discardedID]))  AddTurnEffect($hostMz, SWUMakeTurnEffect('SABOTEUR',  [], SWU_DUR_ATTACK, $discardedID));
        if (isset($Sentinel_Cards[$discardedID]))  AddTurnEffect($hostMz, SWUMakeTurnEffect('SENTINEL',  [], SWU_DUR_ATTACK, $discardedID));
        $raid = intval($Raid_Cards[$discardedID] ?? 0);
        if ($raid > 0)    AddTurnEffect($hostMz, SWUMakeTurnEffect('RAID',    [$raid],    SWU_DUR_ATTACK, $discardedID));
        $restore = intval($Restore_Cards[$discardedID] ?? 0);
        if ($restore > 0) AddTurnEffect($hostMz, SWUMakeTurnEffect('RESTORE', [$restore], SWU_DUR_ATTACK, $discardedID));
        AddTurnEffect($hostMz, SWUMakeTurnEffect('SUPPORT_GRANT', [$discardedID, 0], SWU_DUR_ATTACK));
    }
    BeginSWUAttack(intval($player), $hostMz);   // combat owns the after-action
};
