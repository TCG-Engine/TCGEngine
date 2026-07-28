<?php
// JTL_156
// Cost 1 - Trench Run - [Aggression,Heroism]
// Text: Attack with a Fighter unit. For this attack, it gets +4/+0 and gains: "On Attack: Discard 2 cards from the defending player's deck. Deal unpreventable damage equal to the difference in the discarded cards' costs to this unit."

// ── JTL_156 Trench Run — chosen Fighter gets +4/+0 + a granted On-Attack (JTL_156 marker), then attacks.
$customDQHandlers["JTL_156#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SWUAddAttackPowerBonus($lastDecision, 4);
    AddTurnEffect($lastDecision, 'JTL_156');   // granted On-Attack this attack (registry duration = attack)
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent. Shared by JTL_156 Trench Run / JTL_177 Stay on
// Target / JTL_193 I Have You Now — the play-effect branches on the played CardID (trait to attack
// with, and the per-card #0 continuation), so each printing passes its own CardID.
function TrenchRunAttackPlay(int $player, string $cardID): void {
    global $playerID;
    $playerID = intval($player);
    $trait = ($cardID === 'JTL_156') ? 'Fighter' : 'Vehicle';
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $zone) {
        $arr = GetZone($zone);
        for ($i = 0; $i < count($arr); $i++) {
            $u = $arr[$i];
            if (SWUObjGone($u) || intval($u->Status) !== 1) continue;
            if (HasTrait($u->CardID, $trait)) $units[] = "{$zone}-{$i}";
        }
    }
    if (empty($units)) return;
    SWUQueueChooseTarget(intval($player), $units, "Choose_a_unit_to_attack_with", $cardID . '#0');
}
$whenPlayedAbilities["JTL_156:0"] = function($player, $mzID = '') { TrenchRunAttackPlay(intval($player), 'JTL_156'); };
