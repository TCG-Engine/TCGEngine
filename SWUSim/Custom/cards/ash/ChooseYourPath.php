<?php
// ASH_257
// Cost 2 - Choose Your Path - [Heroism]
// Text: Choose one: / If you control a Force unit, heal 5 damage from your base. / If you control a Mandalorian unit, create a Mandalorian token and give an Advantage token to it. /

// ASH_257 Choose Your Path — modal: Heal (if you control a Force unit, heal 5 from your base) OR
// Mandalorian (if you control a Mandalorian unit, create a Mandalorian token + give it an Advantage).
$customDQHandlers["ASH_257#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision === 'Heal') {
        foreach (GetUnitsInPlay(intval($player)) as $u) {
            if (empty($u->removed) && TraitContains($u, 'Force')) { OnHealBase(intval($player), intval($player), 5); break; }
        }
    } elseif ($lastDecision === 'Mandalorian') {
        foreach (GetUnitsInPlay(intval($player)) as $u) {
            if (empty($u->removed) && TraitContains($u, 'Mandalorian')) {
                $uid = SWUCreateUnitToken(intval($player), 'ASH_T01');
                $mz  = SWUFindMzByUID($uid);
                if ($mz !== null) DoGiveAdvantageToken(intval($player), $mz);
                break;
            }
        }
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["ASH_257:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Heal&Mandalorian", 1,
        "Choose_one:_heal_5_(Force)_or_create_a_Mandalorian_(Mandalorian)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "ASH_257#0", 1);
};
