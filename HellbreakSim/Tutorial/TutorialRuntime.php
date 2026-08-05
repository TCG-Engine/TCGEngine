<?php

function HellbreakTutorialIsActive(): bool {
    return strval(DecisionQueueController::GetVariable('GameMode') ?? '') === 'tutorial';
}

function HellbreakTutorialInitialize(): void {
    DecisionQueueController::StoreVariable('GameMode', 'tutorial');
    DecisionQueueController::StoreVariable('TutorialLesson', 'quick-start');
    DecisionQueueController::StoreVariable('TutorialIntroSeen', '0');
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
    // resource, so the player can ready and attack with their first minion.
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
