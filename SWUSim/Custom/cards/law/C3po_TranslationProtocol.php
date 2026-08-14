<?php
// LAW_152
// Cost 2 - C-3PO - Translation Protocol - [Command] - Power 1 - HP 4
// Text: On Attack: You may give an Experience token to another non-leader unit that shares a Trait with a friendly leader.

// LAW_152 C-3PO — On Attack: you may give an Experience token to another non-leader unit that shares a
// Trait with a friendly leader. "A friendly leader" = the (undeployed) zone leader OR any friendly
// arena LEADER UNIT (deployed leader, leader-Pilot host, ASH_135 Darksaber host — IsLeaderUnit). The
// share reads LIVE traits on BOTH sides via TraitContains (upgrade grants like LAW_150 Fulcrum's
// Rebel, phase strips like LOF_033's Force loss), over the union of both sides' printed traits plus
// the grantable/strippable specials.
$onAttackAbilities["LAW_152:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $leaders = [];
    $zoneLeader = SWUGetLeader(intval($player));
    $zlDeployed = $zoneLeader !== null
        && (($zoneLeader->Deployed ?? false) === true || ($zoneLeader->Deployed ?? '') === 'true');
    if ($zoneLeader !== null && !$zlDeployed) $leaders[] = $zoneLeader; // deployed face is read from the ARENA object below
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $lmz) {
            $lo = GetZoneObject($lmz);
            if (!SWUObjGone($lo) && IsLeaderUnit($lo)) $leaders[] = $lo;
        }
    }
    if (empty($leaders)) return;
    $leaderPrinted = [];
    foreach ($leaders as $L) {
        foreach (array_filter(array_map('trim', explode(',', (string)(CardTrait($L->CardID ?? '') ?? '')))) as $t) $leaderPrinted[] = $t;
    }
    $grantables = ['Rebel', 'Underworld', 'Mandalorian', 'Jedi', 'Force', 'Clone'];
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'GIVE_EXPERIENCE', 'side' => 'any', 'nonLeader' => true, 'excludeSelf' => true, 'may' => true,
        'extraFilter' => function($o) use ($leaders, $leaderPrinted, $grantables) {
            $cand = array_filter(array_map('trim', explode(',', (string)(CardTrait($o->CardID ?? '') ?? ''))));
            foreach (array_unique(array_merge($leaderPrinted, $cand, $grantables)) as $t) {
                if (!TraitContains($o, $t)) continue;
                foreach ($leaders as $L) {
                    if (TraitContains($L, $t)) return true;
                }
            }
            return false;
        },
        'question' => "Give_an_Experience_token_to_a_unit_sharing_a_Trait_with_your_leader?",
        'prompt'   => "Choose_a_unit",
    ]);
};
