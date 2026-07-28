<?php
// JTL_175
// Cost 1 - System Shock - [Aggression]
// Text: Defeat a non-leader upgrade attached to a unit. If you do, deal 1 damage to that unit.

// ── JTL_175 System Shock (thenHandler) — after defeating the upgrade, deal 1 to its host unit. ────────
$customDQHandlers["JTL_175#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $host = (string)($parts[0] ?? ''); // host mzID passed by DEFEAT_UPGRADE#1
    if ($host === '') return; // no upgrade defeated (fizzle) → no damage
    // "If you do": DEFEAT_UPGRADE passes '0' in $parts[1] when the defeat was PREVENTED (Willrow SEC_061 /
    // JTL_012 pilot immunity), so the 1 damage must not fire. (Absent flag → '1' for backward compatibility.)
    if ((string)($parts[1] ?? '1') === '0') return;
    $o = GetZoneObject($host);
    if (SWUObjGone($o)) return;
    SWUDealDamageToUnit($host, 1, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_175:0"] = function($player, $mzID = '') {
// System Shock — defeat a non-leader upgrade attached to a unit; if you do,
                          // deal 1 to that unit (thenHandler JTL_175 reads DefeatUpgHost).
            SWUQueueDefeatUpgrade(intval($player), "Defeat_a_non-leader_upgrade",
                may: false, max: 1, filter: "leader=0", min: 1, thenHandler: "JTL_175#0");
            return;
};
