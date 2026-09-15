<?php
// HMW_079
// Cost 6 - Radiant VII - Negotiating For Naboo - [Vigilance,Heroism] - Unit (Space) 5/7
// Traits: Republic, Vehicle, Transport - Unique
// Text: When Played: You may deal 3 damage to this unit. If you do, give a Shield token to it.
//
// JTL_051 Red Squadron X-Wing is the shape ("You may deal 2 damage to this unit. If you do, draw a card"):
// a target-less "you may", so a YESNO, not a MZMAYCHOOSE — "this unit" is the only object involved.
//
// ★ "IF YOU DO" IS SATISFIED BY THE CHOICE, NOT BY THE DAMAGE LANDING. CR 9.2: "If a replacement effect
// replaces the resolution of the text before 'If you do' with another effect, the controlling player is
// still considered to have resolved that text." A Shield's prevention is a replacement effect (CR 843),
// and the official Malakili ruling (07/14/2025) says it outright for prevented self-damage. So an already
// shielded Radiant VII (LOF_225 Three Lessons plays it with one) spends that Shield on the 3 and still
// gets a new one. HMW is a preview set with no ruling of its own — this is the CR reading.
// (Contrast "for each damage dealt this way", which DOES measure — TS26_19 Coleman Trebor.)
//
// The unit is carried by UniqueID, not by its positional mzID: the YESNO ends the request, and the space
// arena can reindex before the answer comes back.

$whenPlayedAbilities["HMW_079:0"] = function ($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject((string)$mzID);
    if (SWUObjGone($o)) return;
    $uid = intval($o->UniqueID ?? 0);
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        tooltip: "Deal_3_damage_to_Radiant_VII_to_give_it_a_Shield?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "HMW_079#0|{$uid}", 1);
};

// YES → deal the 3, then give the Shield in a SEPARATE step queued behind it. The damage can be DEFERRED
// behind a prevention prompt (SEC_101 Queen Amidala / ASH_062 The Mandalorian offer to prevent ability
// damage to a friendly unit); a Shield given inline would be sitting there when that damage finally
// resolved, and would eat it — the new token must arrive after the 3 has been dealt or prevented.
$customDQHandlers["HMW_079#0"] = function ($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $mz  = SWUFindMzByUID($uid);
    if ($mz === null) return;                     // left play before the answer — nothing to damage or shield
    SWUDealDamageToUnit($mz, 3, intval($player));
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "HMW_079#1|{$uid}", 1, dontSkipOnPass: 1);
};

// "If you do, give a Shield token to it" — unconditional on whether the 3 landed (CR 9.2, see the header),
// but "it" has to still be in play: 3 damage can be lethal on a unit whose HP an aura has cut.
$customDQHandlers["HMW_079#1"] = function ($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    DoGiveShieldToken(intval($player), $mz);
};
