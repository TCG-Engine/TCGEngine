<?php
// ASH_224
// Cost 6 - Elzar Mann - Haunted by a Vision - [Cunning] - Power 3 - HP 7
// Text: While you control a Force leader, this unit enters play ready. / When Played: Distribute up to 5 Advantage tokens among other friendly units. Then, an opponent searches twice that many cards from the top of their deck for an event, reveals it, and draws it.

// ASH_224 Elzar Mann — When Played: distribute up to 5 Advantage tokens among OTHER friendly units. Then,
// an opponent searches twice that many cards from the top of THEIR deck for an event, reveals it, draws it.
// (Enters-ready-while-Force-leader is gated in the entry path.) ASH_224#0 applies + counts, then searches.
$whenPlayedAbilities["ASH_224:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? -1) !== $selfUID) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;   // no other friendly unit → distribute 0 → opponent searches 0
    DecisionQueueController::AddDecision(intval($player), "MZSPLITASSIGN", "5|" . implode("&", $targets) . "|UPTO", 1,
        tooltip: "Distribute_up_to_5_Advantage_among_other_friendly_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_224#0", 1);
};

$customDQHandlers["ASH_224#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $total = 0;
    foreach (explode(',', (string)$lastDecision) as $pair) {
        $pp = explode(':', $pair);
        if (count($pp) < 2) continue;
        $cnt = intval($pp[1]);
        if ($cnt <= 0) continue;
        $mz = trim($pp[0]);
        $obj = GetZoneObject($mz);
        if (SWUObjGone($obj)) continue;
        for ($k = 0; $k < $cnt; $k++) DoGiveAdvantageToken(intval($player), $mz);
        $total += $cnt;
    }
    if ($total <= 0) return;   // "twice that many" = 0 → opponent searches nothing
    $opp = OtherPlayer(intval($player));
    DoTopDeckSearch($opp, 2 * $total, fn($cid) => strpos(CardType($cid) ?? '', 'Event') !== false, 1);
};
