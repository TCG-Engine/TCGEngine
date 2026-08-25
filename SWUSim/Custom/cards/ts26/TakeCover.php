<?php
// TS26_47
// Cost 3 - Take Cover - [Vigilance]
// Text: This event costs 1 resource less to play for each friendly leader unit. / Heal up to 3 damage from a unit and give a Shield token to it.

// TS26_47 Take Cover — heal up to 3 from the chosen unit (OnHealUnit clamps at its damage) and shield it.
$customDQHandlers["TS26_47#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    // USER RULING (2026-08-14): for an "up to N" effect the TARGET is mandatory and the AMOUNT is the
    // player's, zero included. The Shield is unconditional ("and give a Shield token to it"), so it is
    // given here regardless of how much is healed — only the heal is "up to 3". With nothing healable
    // there is no amount to pick and the card is just a Shield.
    $uid     = intval($o->UniqueID ?? 0);
    $maxHeal = min(3, intval($o->Damage ?? 0));
    DoGiveShieldToken(intval($player), $lastDecision);
    if ($maxHeal <= 0) return;
    $opts = [];
    for ($i = 0; $i <= $maxHeal; $i++) $opts[] = "Heal{$i}";
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", implode('&', $opts), 1,
        tooltip: "Heal_up_to_{$maxHeal}_damage");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_47#1|{$uid}", 1);
};

$customDQHandlers["TS26_47#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    $amt = intval(str_replace('Heal', '', (string)$lastDecision));
    if ($amt <= 0) return;                                  // the soft pass
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) OnHealUnit(intval($player), $mz, $amt);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_47:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $tg = SWUAllUnits();
    if (empty($tg)) return;
    SWUQueueChooseTarget(intval($player), $tg, "Heal_up_to_3_and_Shield_a_unit", "TS26_47#0");
};
