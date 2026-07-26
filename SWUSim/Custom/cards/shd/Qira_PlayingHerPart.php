<?php
// SHD_202
// Cost 4 - Qi'ra - Playing Her Part - [Cunning,Heroism] - Power 3 - HP 5
// Text: When Played: Look at an opponent's hand, then name a card. While this unit is in play, each card with that name costs 3 resources more for your opponents to play.

// ─── SHD_202 Qi'ra (When Played) — "Look at an opponent's hand, then name a card. While this unit is in
// play, each card with that name costs 3 resources more for your opponents to play." The reveal is
// implicit (the MZCHOOSE shows the opponent's hand); the named card's TITLE is stored under the PLAYER
// who played Qi'ra, keyed by Qi'ra's UID, as SWU_SHD202_NAMED|{uid}|{title}, read by SWUComputePlayCost.
// Storing by TITLE (not CardID) implements the ruling that "name" excludes the subtitle — the surcharge
// hits every printing sharing that name. Keying by UID (not controller) ties the surcharge to THIS
// instance and the player who played her, so per the rulings it survives an opponent taking control and
// does NOT resume if she is captured then rescued (a rescued unit gets a fresh UID → the stale flag no
// longer matches an in-play Qi'ra). HARNESS NOTE: naming allows any card via a dropdown of all
// card titles; we approximate by naming a card SELECTED from the opponent's hand, so an empty hand can't
// name (naming a card with an empty hand is otherwise allowed). ───
$whenPlayedAbilities["SHD_202:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    $oppHand = array_values(array_filter(ZoneSearch('theirHand'),
        fn($m) => ($o = GetZoneObject($m)) !== null && empty($o->removed)));
    if (empty($oppHand) || $uid === 0) return;   // empty hand → nothing to see/name
    SWUQueueChooseTarget(intval($player), $oppHand, "Look_at_the_opponent's_hand_and_name_a_card", "SHD_202#0|{$uid}");
};

$customDQHandlers["SHD_202#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $uid = intval($parts[0] ?? 0);
    $o = GetZoneObject($lastDecision);
    $named = $o !== null ? ($o->CardID ?? '') : '';
    if ($named === '' || $uid === 0) return;
    // Store the named card's CardID (a token with no spaces — the GlobalEffects key splits on the first
    // space). SWUComputePlayCost matches by TITLE, so every printing sharing the name is surcharged.
    AddGlobalEffects(intval($player), 'SWU_SHD202_NAMED|' . $uid . '|' . $named);
};
