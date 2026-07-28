<?php
// SOR_035
// Cost 4 - Lieutenant Childsen - Death Star Prison Warden - [Vigilance,Villainy] - Power 2 - HP 2
// Text: Sentinel / When Played: Reveal up to 4 [Vigilance] cards from your hand. For each card revealed this way, give an Experience token to this unit.

// SOR_035 Lieutenant Childsen — When Played: reveal up to 4 [Vigilance] cards from hand; give an
// Experience token to THIS unit per card revealed. Revealed cards stay in hand (reveal, not discard).
// Sentinel is the auto-wired keyword (SOR_035 is in $Sentinel_Cards).
$whenPlayedAbilities["SOR_035:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    DecisionQueueController::CleanupRemovedCards();   // the just-played SOR_035 lingers in hand as removed
    // Collect [Vigilance] cards currently in hand.
    $vig = [];
    foreach (ZoneSearch("myHand") as $mz) {
        $o = GetZoneObject($mz);
        if ($o === null || !empty($o->removed)) continue;
        if (strpos((string)CardAspect($o->CardID), 'Vigilance') !== false) $vig[] = $mz;
    }
    if (empty($vig)) return;   // no Vigilance cards → 0 revealed → 0 Experience (clean fizzle)
    $self = GetZoneObject($mzID);
    $selfUID = $self !== null ? intval($self->UniqueID ?? 0) : 0;
    $max = min(4, count($vig));
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE",
        "0|" . $max . "|" . implode("&", $vig), 1, tooltip: "Reveal_up_to_4_Vigilance_cards");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_035#0|" . $selfUID, 1);
};

// Reveal the chosen cards (they stay in hand) and give one Experience token per card to SOR_035.
// Cap at 4 (the resolver validates the count — the harness doesn't enforce the MZMULTICHOOSE max).
$customDQHandlers["SOR_035#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    $picks = [];
    foreach (explode("&", (string)$lastDecision) as $mz) {
        $mz = trim($mz);
        if ($mz === '' || $mz === '-' || $mz === 'PASS') continue;
        $picks[] = $mz;
    }
    $picks = array_slice($picks, 0, 4);   // "up to 4" hard cap
    if (empty($picks)) return;
    foreach ($picks as $mz) DoRevealCard(intval($player), $mz);
    $selfMz = SWUFindMzByUID($selfUID);
    if ($selfMz === null) return;
    $count = count($picks);
    for ($i = 0; $i < $count; $i++) DoGiveExperienceToken(intval($player), $selfMz, false);
    // Fire the "1+ upgrades attached" observer (Sabine ASH_208) ONCE for the whole batch.
    $selfObj = GetZoneObject($selfMz);
    if ($selfObj !== null) _SWUAsh208OnUpgradeAttach(intval($player), $selfObj);
};
