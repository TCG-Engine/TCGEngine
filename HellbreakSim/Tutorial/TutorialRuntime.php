<?php

function HellbreakTutorialIsActive(): bool {
    // Card-data callers (HellbreakFixtureCard) reach this from contexts that load the card layer
    // without a gamestate — tests and tools. No gamestate means no game, so it is not the tutorial.
    if(!class_exists('DecisionQueueController') || !function_exists('GetDecisionQueueVariables')) return false;
    return strval(DecisionQueueController::GetVariable('GameMode') ?? '') === 'tutorial';
}

function HellbreakTutorialInitialize(): void {
    DecisionQueueController::StoreVariable('GameMode', 'tutorial');
    DecisionQueueController::StoreVariable('TutorialLesson', 'quick-start');
    DecisionQueueController::StoreVariable('TutorialIntroSeen', '0');
    DecisionQueueController::StoreVariable('TutorialLocationControlExplained', '0');
    DecisionQueueController::StoreVariable('TutorialRetakeExplained', '0');
}

function HellbreakTutorialAcknowledge(int $player, string $stage): void {
    if(!HellbreakTutorialIsActive() || $player !== 1) return;
    switch(strtoupper(trim($stage))) {
        case 'LOCATION_CONTROL':
            DecisionQueueController::StoreVariable('TutorialLocationControlExplained', '1');
            break;
        case 'RETAKE_CONTROL':
            if(strval(DecisionQueueController::GetVariable('TutorialLocationControlExplained') ?? '0') !== '1') return;
            DecisionQueueController::StoreVariable('TutorialRetakeExplained', '1');
            break;
    }
}

function HellbreakTutorialContinue(int $player): void {
    if(!HellbreakTutorialIsActive() || $player !== 1) return;
    DecisionQueueController::StoreVariable('TutorialIntroSeen', '1');
}

function HellbreakTutorialAdjustResources(int $player, array $resources): array {
    if(!HellbreakTutorialIsActive()) return $resources;
    // The engine fixtures intentionally use minimal synthetic resource bars for
    // universal-rule tests. The authored lesson adds Dracula's reviewed blood
    // and draw income while retaining the fixture's 1 malice as a teaching
    // resource. ⚠ That malice is NOT what lets the first minion attack: the
    // lesson's Transylvanian Wolf has Fearsome and enters ready on its own, so
    // no ready prompt appears. (This comment and the lesson text both used to
    // say the player pays it to ready the Wolf.) Printed Dracula has 0 malice.
    if($player === 1) {
        $resources['blood'] = intval($resources['blood'] ?? 0) + 2;
        $resources['draw'] = intval($resources['draw'] ?? 0) + 2;
    } else if($player === 2) {
        $resources['blood'] = intval($resources['blood'] ?? 0) + 2;
        $resources['draw'] = intval($resources['draw'] ?? 0) + 2;
    }
    return $resources;
}

function HellbreakTutorialSchemeIcons(string $cardID): ?array {
    if(!HellbreakTutorialIsActive() || $cardID !== 'DOT_001') return null;
    $reviewed = function_exists('HellbreakReviewedCard') ? HellbreakReviewedCard($cardID) : null;
    return is_array($reviewed) && is_array($reviewed['scheme'] ?? null)
        ? $reviewed['scheme']
        : [['type' => 'FORESEE', 'value' => 1], ['type' => 'HAUNT', 'value' => 1]];
}

?>
