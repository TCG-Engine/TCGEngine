<?php
// TS26_49
// Cost 4 - Separatist Council - Quarrelsome Holdouts - [Command,Villainy] - Power 2 - HP 6
// Text: When Played/On Attack: Choose one: / Create a Battle Droid token. / Give 2 Experience tokens to a Battle Droid token. /

// TS26_49 Separatist Council — When Played / On Attack: choose one — create a Battle Droid token, OR
// give 2 Experience tokens to a Battle Droid token.
$whenPlayedAbilities["TS26_49:0"] = $onAttackAbilities["TS26_49:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "CreateDroid&GiveExp", 1,
        tooltip: "Create_a_Battle_Droid_OR_give_2_Experience_to_a_Battle_Droid");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_49#0", 1);
};

$customDQHandlers["TS26_49#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'GiveExp') {
        $tg = [];
        foreach (['myGroundArena', 'mySpaceArena'] as $z) {
            foreach (ZoneSearch($z, ['Token Unit']) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && CardTitle($o->CardID ?? '') === 'Battle Droid') $tg[] = $mz;
            }
        }
        if (empty($tg)) return;   // no Battle Droid token → fizzle
        SWUQueueChooseTarget(intval($player), $tg, "Give_2_Experience_to_a_Battle_Droid", "GIVE_EXPERIENCE|2");
    } else {
        SWUCreateUnitToken(intval($player), 'TS26_T01');   // default: create a Battle Droid token
    }
};
