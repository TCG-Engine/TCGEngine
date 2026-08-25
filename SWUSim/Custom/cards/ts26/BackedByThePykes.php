<?php
// TS26_58
// Cost 3 - Backed by the Pykes - [Command]
// Text: Give an Experience token to a friendly unit. / You may deal damage to a unit equal to the number of Experience tokens on friendly units.

// TS26_58 Backed by the Pykes — give Exp to the chosen friendly, then MAY deal (# Exp on friendly
// units) to a unit.
$customDQHandlers["TS26_58#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && str_contains($lastDecision, '-')) DoGiveExperienceToken(intval($player), $lastDecision);
    $count = 0;
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        foreach (($o->Subcards ?? []) as $sc) {
            $scid = is_array($sc) ? ($sc['CardID'] ?? '') : ($sc->CardID ?? '');
            $srem = is_array($sc) ? !empty($sc['removed']) : !empty($sc->removed);
            if (!$srem && $scid === 'SOR_T01') $count++;
        }
    }
    if ($count <= 0) return;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $count, 'may' => true,
        'question' => "Deal_{$count}_damage_to_a_unit?", 'prompt' => "Deal_{$count}_damage_to_a_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_58:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $friendly = SWUFriendlyUnits();
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Give_an_Experience_to_a_friendly_unit", "TS26_58#0");
};
