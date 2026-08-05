<?php

include_once __DIR__ . '/../Fixtures/QuickStartFixtures.php';
include_once __DIR__ . '/CombatLogic.php';
include_once __DIR__ . '/../Tutorial/TutorialRuntime.php';

$debugMode = true;
$customDQHandlers = [];

function CardHasAbility($cardID, $from, $index = -1) {
    if(!function_exists('CardActivateAbilityCount')) return false;
    $count = intval(CardActivateAbilityCount(strval($cardID)));
    return intval($index) < 0 ? $count > 0 : intval($index) < $count;
}
function ActionMap($cardID, $action, $from = '') { return ''; }
function SelectionMetadata($cardID, $from = '') { return ''; }
function CardCurrentEffects($cardID, $from = '') { return []; }

function EngineTransitionOverride($currentPhase, $input) {
    if(function_exists('GetWinner') && intval(GetWinner()) > 0) return $currentPhase;
    return null;
}

function HellbreakLiveZoneObjects(array $zone): array {
    return array_values(array_filter($zone, function($obj) {
        return is_object($obj) && !(isset($obj->removed) && $obj->removed);
    }));
}

function HellbreakReindexZone(array &$zone): void {
    $zone = HellbreakLiveZoneObjects($zone);
    foreach($zone as $index => $obj) $obj->mzIndex = $index;
}

function HellbreakQueueHasHandler(int $player, string $handler): bool {
    $queue = &GetDecisionQueue($player);
    foreach($queue as $decision) {
        if(!is_object($decision) || (isset($decision->removed) && $decision->removed)) continue;
        if($decision->Type === 'CUSTOM' && strpos((string)$decision->Param, $handler . '|') === 0) return true;
        if($decision->Type === 'CUSTOM' && (string)$decision->Param === $handler) return true;
    }
    return false;
}

function HellbreakAddPublicLog(string $message, string $type = 'INFO'): void {
    $message = trim(preg_replace('/\s+/', ' ', $message));
    if($message === '') return;
    $entries = DecisionQueueController::GetVariable('HellbreakPublicLog');
    if(!is_array($entries)) $entries = [];
    $entries[] = [
        'round' => max(1, intval(GetTurnNumber())),
        'sequence' => intval(GetActionSequence()),
        'type' => strtoupper(trim($type)) ?: 'INFO',
        'message' => substr($message, 0, 240),
    ];
    if(count($entries) > 30) $entries = array_slice($entries, -30);
    DecisionQueueController::StoreVariable('HellbreakPublicLog', $entries);
}

function HellbreakIsAutoSetupPlayer(int $player): bool {
    $players = DecisionQueueController::GetVariable('HellbreakAutoSetupPlayers');
    if(!is_array($players)) return false;
    return in_array($player, array_map('intval', $players), true);
}

function HellbreakDrawCards(int $player, int $amount): int {
    $deck = &GetDeck($player);
    HellbreakReindexZone($deck);
    $drawn = 0;
    while($drawn < $amount && count($deck) > 0) {
        $card = array_shift($deck);
        AddHand($player, $card->CardID, $card);
        ++$drawn;
    }
    $failed = max(0, $amount - $drawn);
    HellbreakReindexZone($deck);
    $hand = &GetHand($player);
    HellbreakReindexZone($hand);
    if($failed > 0 && intval(GetWinner()) === 0) {
        HellbreakAddPublicLog('Player ' . $player . ' failed to draw ' . $failed . ' card' . ($failed === 1 ? '' : 's') . ' and takes ' . ($failed * 2) . ' damage.', 'DAMAGE');
        HellbreakStartMonsterDamage(
            $player,
            2,
            ['type' => 'EMPTY_DRAW', 'player' => $player, 'remaining' => $failed - 1],
            null,
            'EMPTY_DRAW'
        );
    }
    return $drawn;
}

function HellbreakEmptyResources(): array {
    return ['blood' => 0, 'malice' => 0, 'draw' => 0, 'aspects' => []];
}

function HellbreakNormalizeAspectCounts($value): array {
    if(is_string($value)) {
        $decoded = json_decode($value, true);
        if(is_array($decoded)) $value = $decoded;
        else {
            $value = trim($value);
            if($value === '' || $value === '-') return [];
            $parts = preg_split('/[,&|]+/', $value);
            $value = [];
            foreach($parts as $part) {
                $part = trim($part);
                if($part !== '') $value[$part] = ($value[$part] ?? 0) + 1;
            }
        }
    }
    if(!is_array($value)) return [];

    $counts = [];
    foreach($value as $aspect => $count) {
        if(is_int($aspect)) {
            $aspect = trim((string)$count);
            $count = 1;
        } else {
            $aspect = trim((string)$aspect);
            $count = intval($count);
        }
        if($aspect !== '' && $aspect !== '-' && $count > 0) $counts[$aspect] = ($counts[$aspect] ?? 0) + $count;
    }
    return $counts;
}

function HellbreakNormalizeResources($value): array {
    if(is_string($value)) {
        $decoded = json_decode($value, true);
        if(is_array($decoded)) $value = $decoded;
    }
    if(!is_array($value)) return HellbreakEmptyResources();

    $resources = HellbreakEmptyResources();
    foreach(['blood', 'malice', 'draw'] as $type) {
        $resources[$type] = max(0, intval($value[$type] ?? 0));
    }
    $resources['aspects'] = HellbreakNormalizeAspectCounts($value['aspects'] ?? []);
    return $resources;
}

function HellbreakCardResources(string $cardID): array {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && isset($fixture['resources'])) return HellbreakNormalizeResources($fixture['resources']);
    if(function_exists('CardResources')) return HellbreakNormalizeResources(CardResources($cardID));
    return HellbreakEmptyResources();
}

function HellbreakAddResourceSnapshot(array &$total, array $addition): void {
    foreach(['blood', 'malice', 'draw'] as $type) {
        $total[$type] = intval($total[$type] ?? 0) + intval($addition[$type] ?? 0);
    }
    foreach(HellbreakNormalizeAspectCounts($addition['aspects'] ?? []) as $aspect => $count) {
        $total['aspects'][$aspect] = intval($total['aspects'][$aspect] ?? 0) + $count;
    }
}

function HellbreakVaultResources(int $player): array {
    $total = HellbreakEmptyResources();
    foreach(HellbreakLiveZoneObjects(GetMonster($player)) as $monster) {
        HellbreakAddResourceSnapshot($total, HellbreakCardResources((string)$monster->CardID));
    }
    foreach(HellbreakLiveZoneObjects(GetVault($player)) as $card) {
        HellbreakAddResourceSnapshot($total, HellbreakCardResources((string)$card->CardID));
    }
    ksort($total['aspects']);
    return $total;
}

function HellbreakCollectResources(int $player): bool {
    if($player !== 1 && $player !== 2) return false;
    $round = max(1, intval(GetTurnNumber()));
    $key = 'HellbreakCollectedRoundP' . $player;
    if(intval(DecisionQueueController::GetVariable($key)) === $round) return false;

    $resources = HellbreakVaultResources($player);
    if(function_exists('HellbreakTutorialAdjustResources')) {
        $resources = HellbreakTutorialAdjustResources($player, $resources);
    }
    $blood = &BloodValue($player);
    $blood += $resources['blood'];
    $malice = &MaliceValue($player);
    $malice += $resources['malice'];
    $drawn = HellbreakDrawCards($player, $resources['draw']);
    DecisionQueueController::StoreVariable($key, $round);
    DecisionQueueController::StoreVariable('HellbreakLastResourcesP' . $player, [
        'round' => $round,
        'blood' => $resources['blood'],
        'malice' => $resources['malice'],
        'draw' => $resources['draw'],
        'drawn' => $drawn,
        'aspects' => $resources['aspects'],
    ]);
    if(function_exists('HellbreakResourcesCollectedHook')) HellbreakResourcesCollectedHook($player, $resources, $drawn);
    return true;
}

function HellbreakCardLoyalty(string $cardID): array {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && isset($fixture['loyalty'])) return HellbreakNormalizeAspectCounts($fixture['loyalty']);
    if(!function_exists('CardLoyalty')) return [];

    $loyalty = CardLoyalty($cardID);
    if(is_array($loyalty) || (is_string($loyalty) && !is_numeric($loyalty))) {
        return HellbreakNormalizeAspectCounts($loyalty);
    }
    $count = max(0, intval($loyalty));
    $aspect = function_exists('CardAspect') ? trim((string)CardAspect($cardID)) : '';
    return $count > 0 && $aspect !== '' ? [$aspect => $count] : [];
}

function HellbreakMeetsLoyalty(array $available, array $required): bool {
    $available = HellbreakNormalizeAspectCounts($available);
    $required = HellbreakNormalizeAspectCounts($required);
    foreach($required as $aspect => $count) {
        if(intval($available[$aspect] ?? 0) < $count) return false;
    }
    return true;
}

function HellbreakCanPayLoyalty(int $player, string $cardID): bool {
    $resources = HellbreakVaultResources($player);
    return HellbreakMeetsLoyalty($resources['aspects'], HellbreakCardLoyalty($cardID));
}

function HellbreakPrintedBloodCost(string $cardID): int {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && isset($fixture['cost'])) return max(0, intval($fixture['cost']));
    if(function_exists('CardCost')) return max(0, intval(CardCost($cardID)));
    return 0;
}

function HellbreakCardPlayCost(int $player, string $cardID, $subjectObj = null): int {
    $cost = HellbreakPrintedBloodCost($cardID);
    if(is_object($subjectObj) && function_exists('HellbreakApplyValueModifiers')) {
        $cost = HellbreakApplyValueModifiers('PlayCostModifier', $player, $subjectObj, $cost);
    }
    return max(0, $cost);
}

function HellbreakLocationThreshold(string $cardID): int {
    if(function_exists('HellbreakFixtureCard')) {
        $fixture = HellbreakFixtureCard($cardID);
        if(is_array($fixture) && isset($fixture['threshold'])) return max(1, intval($fixture['threshold']));
    }
    return 3;
}

function HellbreakCommitLocation(int $player, string $selection): bool {
    if(GetCurrentPhase() !== 'SETUP_LOCATION') return false;
    if($player !== 1 && $player !== 2) return false;
    if(LocationCommitmentValue($player) !== '-') return false;

    $locations = &GetLocationDeck($player);
    HellbreakReindexZone($locations);
    $selection = trim($selection);
    if(preg_match('/^myLocationDeck-(\d+)$/', $selection, $matches)) $index = intval($matches[1]);
    else if(preg_match('/^\d+$/', $selection)) $index = intval($selection);
    else return false;
    if(!isset($locations[$index]) || !is_object($locations[$index])) return false;

    $commitment = &LocationCommitmentValue($player);
    $commitment = (string)$locations[$index]->CardID;
    return true;
}

function HellbreakResolveLocationCommitments(): bool {
    if(GetCurrentPhase() !== 'SETUP_LOCATION') return false;
    $choices = [1 => (string)LocationCommitmentValue(1), 2 => (string)LocationCommitmentValue(2)];
    if($choices[1] === '-' || $choices[2] === '-') return false;

    for($player = 1; $player <= 2; ++$player) {
        $locationDeck = &GetLocationDeck($player);
        $valid = false;
        foreach(HellbreakLiveZoneObjects($locationDeck) as $location) {
            if((string)$location->CardID === $choices[$player]) { $valid = true; break; }
        }
        if(!$valid) return false;
    }

    $shared = &GetLocations();
    $shared = [];
    for($player = 1; $player <= 2; ++$player) {
        AddLocations(
            $choices[$player],
            $player,
            $player,
            $player,
            0,
            0,
            HellbreakLocationThreshold($choices[$player]),
            [],
            []
        );
        $locationDeck = &GetLocationDeck($player);
        $locationDeck = [];
        $commitment = &LocationCommitmentValue($player);
        $commitment = '-';
    }

    for($player = 1; $player <= 2; ++$player) HellbreakDrawCards($player, 4);
    AdvanceAndExecute('READY');
    return true;
}

function HellbreakNormalizeMultiChoice(string $selection): array {
    $selection = trim($selection);
    if($selection === '' || $selection === '-' || strtoupper($selection) === 'PASS') return [];
    $out = [];
    foreach(explode('&', $selection) as $mzID) {
        $mzID = trim($mzID);
        if($mzID !== '' && !in_array($mzID, $out, true)) $out[] = $mzID;
    }
    return $out;
}

function HellbreakCommitMulligan(int $player, string $selection): bool {
    if(GetCurrentPhase() !== 'SETUP_MULLIGAN') return false;
    if($player !== 1 && $player !== 2) return false;
    if(MulliganCommittedValue($player)) return false;

    $hand = &GetHand($player);
    HellbreakReindexZone($hand);
    $choices = HellbreakNormalizeMultiChoice($selection);
    if(count($choices) > count($hand)) return false;

    $selectedIndices = [];
    foreach($choices as $mzID) {
        if(!preg_match('/^myHand-(\d+)$/', $mzID, $matches)) return false;
        $index = intval($matches[1]);
        if(!isset($hand[$index]) || isset($selectedIndices[$index])) return false;
        $selectedIndices[$index] = true;
    }

    $bottom = [];
    foreach($choices as $mzID) {
        preg_match('/^myHand-(\d+)$/', $mzID, $matches);
        $bottom[] = $hand[intval($matches[1])];
    }
    $kept = [];
    foreach($hand as $index => $card) {
        if(!isset($selectedIndices[$index])) $kept[] = $card;
    }
    $hand = $kept;
    HellbreakReindexZone($hand);

    $deck = &GetDeck($player);
    HellbreakReindexZone($deck);
    foreach($bottom as $card) {
        $card->Location = 'Deck';
        $card->PlayerID = $player;
        $card->removed = false;
        $deck[] = $card;
    }
    HellbreakReindexZone($deck);
    HellbreakDrawCards($player, count($bottom));

    $committed = &MulliganCommittedValue($player);
    $committed = true;
    return true;
}

function HellbreakBuildHealthStack(int $player, int $cards = 8): bool {
    $deck = &GetDeck($player);
    HellbreakReindexZone($deck);
    if(count($deck) < $cards) return false;

    $stack = &GetHealthStack($player);
    $stack = [];
    for($i = 0; $i < $cards; ++$i) {
        $card = array_shift($deck);
        AddHealthStack($player, $card->CardID, 2, $card);
    }
    HellbreakReindexZone($deck);
    HellbreakReindexZone($stack);
    $health = &HealthValue($player);
    $health = $cards * 2;
    $topHealth = &TopHealthRemainingValue($player);
    $topHealth = $cards > 0 ? 2 : 0;
    return true;
}

function HellbreakResolveMulligans(): bool {
    if(GetCurrentPhase() !== 'SETUP_MULLIGAN') return false;
    if(!MulliganCommittedValue(1) || !MulliganCommittedValue(2)) return false;
    if(count(HellbreakLiveZoneObjects(GetDeck(1))) < 8 || count(HellbreakLiveZoneObjects(GetDeck(2))) < 8) return false;

    if(!HellbreakBuildHealthStack(1) || !HellbreakBuildHealthStack(2)) return false;
    SetTurnNumber(1);
    SetTurnPlayer(GetInitiativePlayer());
    AdvanceAndExecute('READY');
    return true;
}

function HellbreakBeginSetup(): void {
    if(GetCurrentPhase() !== 'SETUP_LOCATION') SetCurrentPhase('SETUP_LOCATION');
    HellbreakSetupLocationPhase();
}

$customDQHandlers['HellbreakCommitLocation'] = function($player, $params, $lastDecision) {
    if(HellbreakCommitLocation(intval($player), (string)$lastDecision)) {
        HellbreakResolveLocationCommitments();
    } else {
        HellbreakSetupLocationPhase();
    }
};

$customDQHandlers['HellbreakCommitMulligan'] = function($player, $params, $lastDecision) {
    if(HellbreakCommitMulligan(intval($player), (string)$lastDecision)) {
        HellbreakResolveMulligans();
    } else {
        HellbreakSetupMulliganPhase();
    }
};

$customDQHandlers['HellbreakCommitBid'] = function($player, $params, $lastDecision) {
    if(HellbreakCommitBid(intval($player), (string)$lastDecision)) {
        HellbreakResolveBidCommitments();
    } else {
        HellbreakFeedingBidPhase();
    }
};

$customDQHandlers['HellbreakAssignInitiative'] = function($player, $params, $lastDecision) {
    HellbreakAssignInitiative(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChooseHorrorAction'] = function($player, $params, $lastDecision) {
    HellbreakChooseHorrorAction(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChoosePlayCard'] = function($player, $params, $lastDecision) {
    HellbreakChoosePlayCard(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChooseMinionLocation'] = function($player, $params, $lastDecision) {
    HellbreakChooseMinionLocation(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakReadyPlayedMinion'] = function($player, $params, $lastDecision) {
    HellbreakReadyPlayedMinion(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakCheckUniqueAfterPlayed'] = function($player, $params, $lastDecision) {
    HellbreakQueueUniqueEnforcement(intval($player));
};

$customDQHandlers['HellbreakChooseUniqueToKill'] = function($player, $params, $lastDecision) {
    if(!HellbreakResolveUniqueChoice(intval($player), strval($lastDecision))) {
        HellbreakQueueUniqueEnforcement(intval($player));
    }
};

$customDQHandlers['HellbreakChooseAttacker'] = function($player, $params, $lastDecision) {
    HellbreakChooseAttacker(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChooseAttackLocation'] = function($player, $params, $lastDecision) {
    HellbreakChooseAttackLocation(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChooseAttackTarget'] = function($player, $params, $lastDecision) {
    HellbreakChooseAttackTarget(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChooseDefender'] = function($player, $params, $lastDecision) {
    HellbreakChooseDefender(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakAttackEventBarrier'] = function($player, $params, $lastDecision) {
    HellbreakReachAttackEventBarrier(
        intval($player),
        strval($params[0] ?? ''),
        intval($params[1] ?? 0)
    );
};

$customDQHandlers['HellbreakChooseSchemer'] = function($player, $params, $lastDecision) {
    HellbreakChooseSchemer(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChooseSchemeLocation'] = function($player, $params, $lastDecision) {
    HellbreakChooseSchemeLocation(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakSchemeEventBarrier'] = function($player, $params, $lastDecision) {
    HellbreakReachSchemeEventBarrier(
        intval($player),
        strval($params[0] ?? ''),
        intval($params[1] ?? 0)
    );
};
$customDQHandlers['HellbreakPhaseEventBarrier'] = function($player, $params, $lastDecision) {
    HellbreakReachPhaseEventBarrier(
        intval($player),
        strval($params[0] ?? ''),
        intval($params[1] ?? 0)
    );
};
$customDQHandlers['HellbreakContinuePhaseEvent'] = function($player, $params, $lastDecision) {
    HellbreakRunPhaseEventContinuation(
        intval($player),
        strval($params[0] ?? ''),
        intval($params[1] ?? 0)
    );
};

$customDQHandlers['HellbreakResolveIndirectAssignment'] = function($player, $params, $lastDecision) {
    HellbreakResolveIndirectAssignment(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakResolveForesee'] = function($player, $params, $lastDecision) {
    HellbreakResolveForesee(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakResolveHealthAbility'] = function($player, $params, $lastDecision) {
    HellbreakResolveHealthAbility(intval($player), (string)$lastDecision);
};
$customDQHandlers['HellbreakHealthEventBarrier'] = function($player, $params, $lastDecision) {
    HellbreakReachHealthEventBarrier(
        intval($player),
        strval($params[0] ?? ''),
        intval($params[1] ?? 0)
    );
};
$customDQHandlers['HellbreakMonsterFlipEventBarrier'] = function($player, $params, $lastDecision) {
    HellbreakReachMonsterFlipEventBarrier(
        intval($player),
        intval($params[0] ?? 0)
    );
};

$customDQHandlers['HellbreakChooseMonsterFlip'] = function($player, $params, $lastDecision) {
    HellbreakChooseMonsterFlip(intval($player), (string)$lastDecision);
};

$customDQHandlers['HellbreakChooseHandLimitDiscards'] = function($player, $params, $lastDecision) {
    HellbreakChooseHandLimitDiscards(intval($player), (string)$lastDecision);
};

function HellbreakSetupLocationPhase() {
    for($player = 1; $player <= 2; ++$player) {
        if(LocationCommitmentValue($player) !== '-' || HellbreakQueueHasHandler($player, 'HellbreakCommitLocation')) continue;
        if(HellbreakIsAutoSetupPlayer($player)) {
            HellbreakCommitLocation($player, '0');
            continue;
        }
        $locationDeck = &GetLocationDeck($player);
        HellbreakReindexZone($locationDeck);
        $labels = [];
        foreach($locationDeck as $location) {
            $name = function_exists('CardName') ? trim((string)CardName($location->CardID)) : '';
            if($name === '') $name = (string)$location->CardID;
            $labels[] = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_');
        }
        DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 0, 'Secretly_choose_your_location');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakCommitLocation', 1);
    }
    HellbreakResolveLocationCommitments();
    return true;
}

function HellbreakSetupMulliganPhase() {
    for($player = 1; $player <= 2; ++$player) {
        if(MulliganCommittedValue($player) || HellbreakQueueHasHandler($player, 'HellbreakCommitMulligan')) continue;
        if(HellbreakIsAutoSetupPlayer($player)) {
            HellbreakCommitMulligan($player, '-');
            continue;
        }
        $max = count(HellbreakLiveZoneObjects(GetHand($player)));
        DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE', '0|' . $max . '|myHand', 0, 'Choose_cards_to_put_on_the_bottom');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakCommitMulligan', 1);
    }
    HellbreakResolveMulligans();
    return true;
}

function HellbreakPumpPhaseEventQueues(): void {
    $controller = new DecisionQueueController();
    $controller->ExecuteStaticMethods(1, '-');
    $controller->ExecuteStaticMethods(2, '-');
}

function HellbreakQueuePhaseEventBarrier(array $context, string $stage, bool $pump = true): bool {
    $stage = strtoupper(trim($stage));
    if(!in_array($stage, ['RESOURCES_COLLECTED', 'BID_REVEALED', 'INITIATIVE_ASSIGNED', 'REFRESH_READY', 'ROUND_ENDED'], true)) return false;
    $token = intval(DecisionQueueController::GetVariable('HellbreakPhaseBarrierToken')) + 1;
    DecisionQueueController::StoreVariable('HellbreakPhaseBarrierToken', $token);
    DecisionQueueController::StoreVariable('HellbreakPendingPhaseBarrier', [
        'token' => $token,
        'stage' => $stage,
        'context' => $context,
        'reached' => ['1' => false, '2' => false],
    ]);
    foreach([1, 2] as $player) {
        DecisionQueueController::AddDecision(
            $player,
            'CUSTOM',
            'HellbreakPhaseEventBarrier|' . $stage . '|' . $token,
            100,
            '',
            1
        );
    }
    if($pump) HellbreakPumpPhaseEventQueues();
    return true;
}

function HellbreakReachPhaseEventBarrier(int $player, string $stage, int $token): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingPhaseBarrier');
    $stage = strtoupper(trim($stage));
    if(!is_array($pending) || intval($pending['token'] ?? 0) !== $token || strval($pending['stage'] ?? '') !== $stage) return false;
    $pending['reached'][strval($player)] = true;
    DecisionQueueController::StoreVariable('HellbreakPendingPhaseBarrier', $pending);
    if(empty($pending['reached']['1']) || empty($pending['reached']['2'])) return true;
    DecisionQueueController::StoreVariable('HellbreakPendingPhaseBarrier', []);
    $context = is_array($pending['context'] ?? null) ? $pending['context'] : [];
    DecisionQueueController::StoreVariable('HellbreakPendingPhaseContinuation', [
        'token' => $token,
        'stage' => $stage,
        'context' => $context,
    ]);
    DecisionQueueController::AddDecision(
        $player,
        'CUSTOM',
        'HellbreakContinuePhaseEvent|' . $stage . '|' . $token,
        0,
        '',
        1
    );
    return true;
}

function HellbreakRunPhaseEventContinuation(int $player, string $stage, int $token): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingPhaseContinuation');
    $stage = strtoupper(trim($stage));
    if(!is_array($pending) || intval($pending['token'] ?? 0) !== $token || strval($pending['stage'] ?? '') !== $stage) return false;
    DecisionQueueController::StoreVariable('HellbreakPendingPhaseContinuation', []);
    $context = is_array($pending['context'] ?? null) ? $pending['context'] : [];
    if($stage === 'BID_REVEALED') return HellbreakContinueAfterBidRevealed($context);
    if($stage === 'RESOURCES_COLLECTED' && GetCurrentPhase() === 'FEED_COLLECT') return AdvanceAndExecute('AUTO');
    if($stage === 'INITIATIVE_ASSIGNED' && GetCurrentPhase() === 'FEED_RESOLVE') return AdvanceAndExecute('AUTO');
    if($stage === 'REFRESH_READY' && GetCurrentPhase() === 'REFRESH_READY') return AdvanceAndExecute('AUTO');
    if($stage === 'ROUND_ENDED' && GetCurrentPhase() === 'REFRESH_HAND') return AdvanceAndExecute('READY');
    return true;
}

function HellbreakFeedingCollectPhase() {
    if(GetCurrentPhase() !== 'FEED_COLLECT') return false;
    $round = max(1, intval(GetTurnNumber()));
    if(intval(DecisionQueueController::GetVariable('HellbreakFeedingCollectStartedRound')) === $round) return true;
    DecisionQueueController::StoreVariable('HellbreakFeedingCollectStartedRound', $round);
    HellbreakQueuePhaseEventBarrier(['round' => $round], 'RESOURCES_COLLECTED', false);
    HellbreakCollectResources(1);
    if(intval(GetWinner()) === 0) HellbreakCollectResources(2);
    HellbreakPumpPhaseEventQueues();
    return true;
}

function HellbreakCommitBid(int $player, string $selection): bool {
    if(GetCurrentPhase() !== 'FEED_BID') return false;
    if($player !== 1 && $player !== 2) return false;
    if((string)BidCommitmentValue($player) !== '-') return false;

    $selection = trim($selection);
    if($selection === '' || $selection === '-' || strtoupper($selection) === 'PASS') {
        $commitment = &BidCommitmentValue($player);
        $commitment = 'PASS';
        return true;
    }
    if(!preg_match('/^myHand-(\d+)$/', $selection, $matches)) return false;

    $hand = &GetHand($player);
    HellbreakReindexZone($hand);
    $index = intval($matches[1]);
    if(!isset($hand[$index]) || !is_object($hand[$index])) return false;
    $commitment = &BidCommitmentValue($player);
    $commitment = $index . ':' . (string)$hand[$index]->CardID;
    return true;
}

function HellbreakResolveBidCommitments(): bool {
    if(GetCurrentPhase() !== 'FEED_BID') return false;
    if((string)BidCommitmentValue(1) === '-' || (string)BidCommitmentValue(2) === '-') return false;
    return AdvanceAndExecute('READY');
}

function HellbreakParseBidCommitment(int $player): array {
    $raw = (string)BidCommitmentValue($player);
    if($raw === 'PASS') return ['cardID' => null, 'cost' => 0, 'object' => null, 'index' => -1];
    if(!preg_match('/^(\d+):(.+)$/', $raw, $matches)) return ['cardID' => null, 'cost' => 0, 'object' => null, 'index' => -1];

    $hand = &GetHand($player);
    HellbreakReindexZone($hand);
    $index = intval($matches[1]);
    $cardID = (string)$matches[2];
    if(!isset($hand[$index]) || !is_object($hand[$index]) || (string)$hand[$index]->CardID !== $cardID) {
        return ['cardID' => null, 'cost' => 0, 'object' => null, 'index' => -1];
    }
    return ['cardID' => $cardID, 'cost' => HellbreakPrintedBloodCost($cardID), 'object' => $hand[$index], 'index' => $index];
}

function HellbreakBidWinner(int $p1Cost, int $p2Cost, int $previousInitiative): int {
    if($p1Cost > $p2Cost) return 1;
    if($p2Cost > $p1Cost) return 2;
    return $previousInitiative === 1 ? 2 : 1;
}

function HellbreakMoveResolvedBidsToVault(array $bids): void {
    for($player = 1; $player <= 2; ++$player) {
        $bid = $bids[$player];
        if($bid['cardID'] === null || $bid['object'] === null) continue;
        $hand = &GetHand($player);
        array_splice($hand, intval($bid['index']), 1);
        HellbreakReindexZone($hand);
        AddVault($player, $bid['cardID'], max(1, intval(GetTurnNumber())), $bid['object']);
        $vault = &GetVault($player);
        HellbreakReindexZone($vault);
    }
}

function HellbreakAssignInitiative(int $winner, string $selection): bool {
    if(GetCurrentPhase() !== 'FEED_RESOLVE') return false;
    if($winner !== intval(DecisionQueueController::GetVariable('HellbreakBidWinner'))) return false;

    $selection = trim($selection);
    if($selection === '0' || strtoupper($selection) === 'TAKE_INITIATIVE') $holder = $winner;
    else if($selection === '1' || strtoupper($selection) === 'GIVE_INITIATIVE_TO_OPPONENT') $holder = $winner === 1 ? 2 : 1;
    else return false;

    SetInitiativePlayer($holder);
    SetTurnPlayer($holder);
    DecisionQueueController::StoreVariable('HellbreakAssignedInitiative', $holder);
    HellbreakQueuePhaseEventBarrier(['winner' => $winner, 'holder' => $holder], 'INITIATIVE_ASSIGNED', false);
    if(function_exists('HellbreakInitiativeAssignedHook')) HellbreakInitiativeAssignedHook($winner, $holder);
    HellbreakPumpPhaseEventQueues();
    return true;
}

function HellbreakContinueAfterBidRevealed(array $context): bool {
    if(GetCurrentPhase() !== 'FEED_RESOLVE') return false;
    $winner = intval($context['winner'] ?? DecisionQueueController::GetVariable('HellbreakBidWinner'));
    if(!in_array($winner, [1, 2], true)) return false;
    if(HellbreakIsAutoSetupPlayer($winner)) {
        SetInitiativePlayer($winner);
        SetTurnPlayer($winner);
        DecisionQueueController::StoreVariable('HellbreakAssignedInitiative', $winner);
        HellbreakQueuePhaseEventBarrier(['winner' => $winner, 'holder' => $winner], 'INITIATIVE_ASSIGNED', false);
        if(function_exists('HellbreakInitiativeAssignedHook')) HellbreakInitiativeAssignedHook($winner, $winner);
        HellbreakPumpPhaseEventQueues();
        return true;
    }
    DecisionQueueController::AddDecision($winner, 'MZMODAL', '1|1|Take_Initiative&Give_Initiative_to_Opponent', 0, 'Choose_who_receives_initiative');
    DecisionQueueController::AddDecision($winner, 'CUSTOM', 'HellbreakAssignInitiative', 1);
    return true;
}

function HellbreakFeedingBidPhase() {
    if(GetCurrentPhase() !== 'FEED_BID') return false;
    for($player = 1; $player <= 2; ++$player) {
        if((string)BidCommitmentValue($player) !== '-' || HellbreakQueueHasHandler($player, 'HellbreakCommitBid')) continue;
        if(HellbreakIsAutoSetupPlayer($player)) {
            HellbreakCommitBid($player, 'PASS');
            continue;
        }
        DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE', '0|1|myHand', 0, 'Secretly_bid_one_card_or_confirm_none');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakCommitBid', 1, '', 1);
    }
    HellbreakResolveBidCommitments();
    return true;
}

function HellbreakFeedingResolvePhase() {
    if(GetCurrentPhase() !== 'FEED_RESOLVE') return false;
    if(DecisionQueueController::GetVariable('HellbreakBidResolutionRound') === intval(GetTurnNumber())) return true;

    $previousInitiative = intval(GetInitiativePlayer());
    $bids = [1 => HellbreakParseBidCommitment(1), 2 => HellbreakParseBidCommitment(2)];
    HellbreakMoveResolvedBidsToVault($bids);
    $winner = HellbreakBidWinner($bids[1]['cost'], $bids[2]['cost'], $previousInitiative);
    DecisionQueueController::StoreVariable('HellbreakBidResolutionRound', intval(GetTurnNumber()));
    DecisionQueueController::StoreVariable('HellbreakBidWinner', $winner);
    DecisionQueueController::StoreVariable('HellbreakRevealedBids', [
        1 => ['cardID' => $bids[1]['cardID'], 'cost' => $bids[1]['cost']],
        2 => ['cardID' => $bids[2]['cardID'], 'cost' => $bids[2]['cost']],
    ]);
    for($player = 1; $player <= 2; ++$player) {
        $commitment = &BidCommitmentValue($player);
        $commitment = '-';
    }
    $context = ['winner' => $winner, 'previousInitiative' => $previousInitiative];
    HellbreakQueuePhaseEventBarrier($context, 'BID_REVEALED', false);
    if(function_exists('HellbreakInitiativeBidRevealedHook')) HellbreakInitiativeBidRevealedHook($bids, $winner, $previousInitiative);
    HellbreakPumpPhaseEventQueues();
    return true;
}

function HellbreakCardType(string $cardID): string {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && isset($fixture['type'])) return strtoupper(trim((string)$fixture['type']));
    return function_exists('CardType') ? strtoupper(trim((string)CardType($cardID))) : '';
}

function HellbreakCardPlaySide(string $cardID): string {
    $fixture = function_exists('HellbreakFixtureCard') ? HellbreakFixtureCard($cardID) : null;
    if(is_array($fixture) && isset($fixture['playSide'])) return strtoupper(trim((string)$fixture['playSide']));
    if(function_exists('CardPlaySide')) return strtoupper(trim((string)CardPlaySide($cardID)));
    return '';
}

function HellbreakMonsterSide(int $player): string {
    $monsters = HellbreakLiveZoneObjects(GetMonster($player));
    return isset($monsters[0]) ? strtoupper(trim((string)$monsters[0]->Side)) : '';
}

function HellbreakParseHandMZ(int $player, string $mzID): ?array {
    if(!preg_match('/^myHand-(\d+)$/', trim($mzID), $matches)) return null;
    $hand = &GetHand($player);
    HellbreakReindexZone($hand);
    $index = intval($matches[1]);
    if(!isset($hand[$index]) || !is_object($hand[$index])) return null;
    return ['index' => $index, 'object' => $hand[$index], 'cardID' => (string)$hand[$index]->CardID];
}

function HellbreakCanPlayCard(int $player, string $mzID): bool {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player || intval(GetWinner()) > 0) return false;
    if(intval(GetSlumberPlayer()) === $player) return false;
    $handCard = HellbreakParseHandMZ($player, $mzID);
    if($handCard === null) return false;
    $cardID = $handCard['cardID'];
    if(!in_array(HellbreakCardType($cardID), ['MINION', 'ASSET', 'EVENT'], true)) return false;
    if(intval(BloodValue($player)) < HellbreakCardPlayCost($player, $cardID, $handCard['object'])) return false;
    if(!HellbreakCanPayLoyalty($player, $cardID)) return false;
    $requiredSide = HellbreakCardPlaySide($cardID);
    if($requiredSide !== '' && HellbreakMonsterSide($player) !== $requiredSide) return false;
    return true;
}

function HellbreakPlayableHandCards(int $player): array {
    $hand = &GetHand($player);
    HellbreakReindexZone($hand);
    $playable = [];
    foreach($hand as $index => $card) {
        $mzID = 'myHand-' . $index;
        if(HellbreakCanPlayCard($player, $mzID)) $playable[] = $mzID;
    }
    return $playable;
}

function HellbreakLegalActions(int $player): array {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player || intval(GetWinner()) > 0) return [];
    if(intval(GetSlumberPlayer()) === $player) return [];
    $actions = [];
    $playable = HellbreakPlayableHandCards($player);
    if(count($playable) > 0) $actions[] = ['id' => 'PLAY_CARD', 'label' => 'Play_Card', 'cards' => $playable];
    $attackers = HellbreakLegalAttackers($player);
    if(count($attackers) > 0) $actions[] = ['id' => 'ATTACK', 'label' => 'Attack', 'cards' => $attackers];
    $schemers = HellbreakLegalSchemers($player);
    if(count($schemers) > 0) $actions[] = ['id' => 'SCHEME', 'label' => 'Scheme', 'cards' => $schemers];
    $abilities = function_exists('HellbreakActivatableAbilities') ? HellbreakActivatableAbilities($player) : [];
    if(count($abilities) > 0) $actions[] = ['id' => 'ABILITY', 'label' => 'Use_Ability', 'abilities' => $abilities];
    if(!GetSlumberUsed()) $actions[] = ['id' => 'SLUMBER', 'label' => 'Slumber'];
    $actions[] = ['id' => 'PASS', 'label' => 'Pass'];
    return $actions;
}

function HellbreakOtherPlayer(int $player): int {
    return $player === 1 ? 2 : 1;
}

function HellbreakChangeBlood(int $player, int $amount): int {
    if($player !== 1 && $player !== 2) return 0;
    $blood = &BloodValue($player);
    $before = max(0, intval($blood));
    $blood = max(0, $before + $amount);
    return intval($blood) - $before;
}

function HellbreakGainBlood(int $player, int $amount): int {
    return HellbreakChangeBlood($player, max(0, $amount));
}

function HellbreakLoseBlood(int $player, int $amount): int {
    return -HellbreakChangeBlood($player, -max(0, $amount));
}

function HellbreakChangeMalice(int $player, int $amount): int {
    if($player !== 1 && $player !== 2) return 0;
    $malice = &MaliceValue($player);
    $before = max(0, intval($malice));
    $malice = max(0, $before + $amount);
    return intval($malice) - $before;
}

function HellbreakGainMalice(int $player, int $amount): int {
    return HellbreakChangeMalice($player, max(0, $amount));
}

function HellbreakLoseMalice(int $player, int $amount): int {
    return -HellbreakChangeMalice($player, -max(0, $amount));
}

function HellbreakCanonicalCardTitle(string $cardID): string {
    $name = function_exists('CardName') ? trim(strval(CardName($cardID))) : $cardID;
    $name = preg_replace('/\s*\((?:Borderless|Extended Art|Showcase)\)\s*$/i', '', $name);
    return strtoupper(trim(preg_replace('/\s+/', ' ', $name)));
}

function HellbreakUniqueIdentity(string $cardID): string {
    if(!function_exists('CardUnique')) return '';
    $identity = HellbreakCanonicalCardTitle($cardID);
    if(CardUnique($cardID)) return $identity;
    if(!function_exists('GetAllCardIds')) return '';
    foreach(GetAllCardIds() as $candidateID) {
        if(CardUnique($candidateID) && HellbreakCanonicalCardTitle(strval($candidateID)) === $identity) return $identity;
    }
    return '';
}

function HellbreakUniqueDuplicateGroups(int $player): array {
    if(!in_array($player, [1, 2], true)) return [];
    $groups = [];
    foreach(['myCharacters' => GetCharacters($player), 'myAssets' => GetAssets($player)] as $prefix => $zone) {
        foreach(HellbreakLiveZoneObjects($zone) as $index => $object) {
            if(intval($object->Controller ?? 0) !== $player) continue;
            $identity = HellbreakUniqueIdentity(strval($object->CardID ?? ''));
            if($identity === '') continue;
            $groups[$identity][] = $prefix . '-' . $index;
        }
    }
    return array_values(array_filter($groups, fn($cards) => count($cards) > 1));
}

function HellbreakQueueUniqueEnforcement(int $player): bool {
    $groups = HellbreakUniqueDuplicateGroups($player);
    if(count($groups) === 0) return false;
    $choices = array_values($groups[0]);
    DecisionQueueController::StoreVariable('HellbreakUniqueChoicesP' . $player, $choices);
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $choices), 0, 'Choose_one_duplicate_unique_card_to_kill');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseUniqueToKill', 1, '', 1);
    return true;
}

function HellbreakResolveUniqueChoice(int $player, string $selection): bool {
    $choices = DecisionQueueController::GetVariable('HellbreakUniqueChoicesP' . $player);
    if(preg_match('/^\d+$/', trim($selection)) && is_array($choices)) {
        $selection = strval($choices[intval($selection)] ?? '');
    }
    if(!is_array($choices) || !in_array($selection, $choices, true)) return false;
    if(!HellbreakKillControlledPermanentByMZ($player, $selection, 'UNIQUE_RULE')) return false;
    HellbreakQueueUniqueEnforcement($player);
    return true;
}

function HellbreakFinishNormalAction(int $player, string $actionType, array $details = []): bool {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player) return false;
    SetPreviousActionPassLike(false);
    SetActionSequence(intval(GetActionSequence()) + 1);
    SetTurnPlayer(HellbreakOtherPlayer($player));
    DecisionQueueController::StoreVariable('HellbreakLastHorrorAction', [
        'sequence' => intval(GetActionSequence()), 'player' => $player, 'type' => $actionType, 'details' => $details,
    ]);
    HellbreakAddPublicLog('Player ' . $player . ' completed ' . strtolower(str_replace('_', ' ', $actionType)) . '.', $actionType);
    HellbreakHorrorPhase();
    return true;
}

function HellbreakEndHorror(): bool {
    if(GetCurrentPhase() !== 'HORROR') return false;
    return AdvanceAndExecute('PASS');
}

function HellbreakTakePassLikeAction(int $player, string $action): bool {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player) return false;
    $action = strtoupper(trim($action));
    if($action !== 'PASS' && $action !== 'SLUMBER') return false;
    if($action === 'SLUMBER') {
        if(GetSlumberUsed() || intval(GetSlumberPlayer()) === $player) return false;
        SetSlumberUsed(true);
        SetSlumberPlayer($player);
        $malice = &MaliceValue($player);
        ++$malice;
    }

    $endsPhase = GetPreviousActionPassLike();
    SetPreviousActionPassLike(true);
    SetActionSequence(intval(GetActionSequence()) + 1);
    DecisionQueueController::StoreVariable('HellbreakLastHorrorAction', [
        'sequence' => intval(GetActionSequence()), 'player' => $player, 'type' => $action,
    ]);
    HellbreakAddPublicLog('Player ' . $player . ($action === 'SLUMBER' ? ' entered Slumber and gained 1 malice.' : ' passed.'), $action);
    if($endsPhase) return HellbreakEndHorror();
    SetTurnPlayer(HellbreakOtherPlayer($player));
    HellbreakHorrorPhase();
    return true;
}

function HellbreakChooseHorrorAction(int $player, string $selection): bool {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player) return false;
    $actions = DecisionQueueController::GetVariable('HellbreakLegalActionsP' . $player);
    if(!is_array($actions)) return false;
    if(!preg_match('/^\d+$/', trim($selection))) return false;
    $index = intval($selection);
    if(!isset($actions[$index]) || !is_array($actions[$index])) return false;
    $action = strtoupper((string)($actions[$index]['id'] ?? ''));
    if($action === 'PASS' || $action === 'SLUMBER') return HellbreakTakePassLikeAction($player, $action);
    if($action === 'ATTACK') {
        $attackers = HellbreakLegalAttackers($player);
        if(count($attackers) === 0) return false;
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $attackers), 0, 'Choose_a_character_to_attack');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseAttacker', 1);
        return true;
    }
    if($action === 'SCHEME') {
        $schemers = HellbreakLegalSchemers($player);
        if(count($schemers) === 0) return false;
        DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $schemers), 0, 'Choose_a_character_to_scheme');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseSchemer', 1);
        return true;
    }
    if($action === 'ABILITY') {
        $abilities = HellbreakActivatableAbilities($player);
        if(count($abilities) === 0) return false;
        DecisionQueueController::StoreVariable('HellbreakAbilityOptionsP' . $player, $abilities);
        $labels = array_map(function($ability) {
            return trim(preg_replace('/[^A-Za-z0-9]+/', '_', strval($ability['label'] ?? 'Ability')), '_');
        }, $abilities);
        DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 0, 'Choose_an_ability');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseAbilityAction', 1);
        return true;
    }
    if($action !== 'PLAY_CARD') return false;

    $playable = HellbreakPlayableHandCards($player);
    if(count($playable) === 0) return false;
    DecisionQueueController::AddDecision($player, 'MZCHOOSE', implode('&', $playable), 0, 'Choose_a_card_to_play');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChoosePlayCard', 1);
    return true;
}

function HellbreakChoosePlayCard(int $player, string $mzID): bool {
    if(!HellbreakCanPlayCard($player, $mzID)) return false;
    $handCard = HellbreakParseHandMZ($player, $mzID);
    if($handCard === null) return false;
    if(HellbreakCardType($handCard['cardID']) !== 'MINION') return HellbreakPlayCard($player, $mzID);

    $locations = HellbreakLiveZoneObjects(GetLocations());
    if(count($locations) === 0) return false;
    DecisionQueueController::StoreVariable('HellbreakPendingPlayP' . $player, [
        'index' => $handCard['index'], 'cardID' => $handCard['cardID'],
    ]);
    $labels = [];
    $slots = [];
    foreach($locations as $location) {
        $name = function_exists('CardName') ? trim((string)CardName($location->CardID)) : '';
        if($name === '') $name = (string)$location->CardID;
        $labels[] = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $name), '_');
        $slots[] = intval($location->Slot);
    }
    DecisionQueueController::StoreVariable('HellbreakPendingLocationSlotsP' . $player, $slots);
    DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 0, 'Choose_a_location_for_the_minion');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseMinionLocation', 1);
    return true;
}

function HellbreakChooseMinionLocation(int $player, string $selection): bool {
    if(!preg_match('/^\d+$/', trim($selection))) return false;
    $pending = DecisionQueueController::GetVariable('HellbreakPendingPlayP' . $player);
    $slots = DecisionQueueController::GetVariable('HellbreakPendingLocationSlotsP' . $player);
    $choice = intval($selection);
    if(!is_array($pending) || !is_array($slots) || !isset($slots[$choice])) return false;
    $mzID = 'myHand-' . intval($pending['index'] ?? -1);
    $handCard = HellbreakParseHandMZ($player, $mzID);
    if($handCard === null || $handCard['cardID'] !== (string)($pending['cardID'] ?? '')) return false;
    return HellbreakPlayCard($player, $mzID, intval($slots[$choice]));
}

function HellbreakPlayCard(int $player, string $mzID, ?int $locationSlot = null): bool {
    if(!HellbreakCanPlayCard($player, $mzID)) return false;
    $handCard = HellbreakParseHandMZ($player, $mzID);
    if($handCard === null) return false;
    $cardID = $handCard['cardID'];
    $type = HellbreakCardType($cardID);
    if($type === 'MINION') {
        $validLocation = false;
        foreach(HellbreakLiveZoneObjects(GetLocations()) as $location) {
            if(intval($location->Slot) === intval($locationSlot)) { $validLocation = true; break; }
        }
        if(!$validLocation) return false;
    }

    $cost = HellbreakCardPlayCost($player, $cardID, $handCard['object']);
    $blood = &BloodValue($player);
    if($blood < $cost) return false;
    $blood -= $cost;
    $hand = &GetHand($player);
    $source = $handCard['object'];
    array_splice($hand, $handCard['index'], 1);
    HellbreakReindexZone($hand);

    $playedObject = null;
    if($type === 'MINION') {
        $playedObject = AddCharacters($player, $cardID, 1, 0, $player, $player, intval($locationSlot), [], [], $source);
    } else if($type === 'ASSET') {
        $playedObject = AddAssets($player, $cardID, 2, $player, $player, [], [], $source);
    } else {
        $playedObject = AddCrypt($player, $cardID, 'Hand', intval(GetTurnNumber()), true, $source);
    }
    DecisionQueueController::StoreVariable('HellbreakPendingPlayedCardP' . $player, [
        'cardID' => $cardID, 'type' => $type, 'locationSlot' => intval($locationSlot),
        'uniqueID' => intval($playedObject->UniqueID ?? 0),
    ]);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakAfterPlayedCard', 99, '', 1);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakCheckUniqueAfterPlayed', 90, '', 1);
    if(function_exists('HellbreakCardPlayedHook')) HellbreakCardPlayedHook($player, $cardID, $type, $playedObject, $locationSlot);
    if(HellbreakQueueHasHandler($player, 'HellbreakAfterPlayedCard')) {
        $dqController = new DecisionQueueController();
        $dqController->ExecuteStaticMethods($player, '-');
    }
    return true;
}

function HellbreakAfterPlayedCard(int $player): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingPlayedCardP' . $player);
    if(!is_array($pending)) return false;
    $type = strtoupper(strval($pending['type'] ?? ''));
    $cardID = strval($pending['cardID'] ?? '');
    $uniqueID = intval($pending['uniqueID'] ?? 0);
    $playedMinionIsExhausted = false;
    if($type === 'MINION') {
        foreach(HellbreakLiveZoneObjects(GetCharacters($player)) as $character) {
            if(intval($character->UniqueID ?? 0) !== $uniqueID) continue;
            $playedMinionIsExhausted = intval($character->Status ?? 0) !== 2;
            break;
        }
    }
    if($type === 'MINION' && $playedMinionIsExhausted && intval(MaliceValue($player)) > 0) {
        DecisionQueueController::StoreVariable('HellbreakPendingReadyMinionP' . $player, $uniqueID);
        DecisionQueueController::AddDecision($player, 'YESNO', '', 0, 'Pay_1_malice_to_ready_this_minion');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakReadyPlayedMinion', 1, '', 1);
        return true;
    }
    return HellbreakFinishNormalAction($player, 'PLAY_CARD', ['cardID' => $cardID, 'type' => $type]);
}

function HellbreakChooseAbilityAction(int $player, string $selection): bool {
    if(!preg_match('/^\d+$/', trim($selection))) return false;
    $abilities = DecisionQueueController::GetVariable('HellbreakAbilityOptionsP' . $player);
    $index = intval($selection);
    if(!is_array($abilities) || !isset($abilities[$index]) || !is_array($abilities[$index])) return false;
    $ability = $abilities[$index];
    return HellbreakBeginActivatedAbility($player, strval($ability['mzID'] ?? ''), intval($ability['abilityIndex'] ?? -1));
}

function HellbreakBeginActivatedAbility(int $player, string $mzID, int $abilityIndex): bool {
    if(!function_exists('ActivateAbility') || !HellbreakCanUseActivatedAbility($player, $mzID, $abilityIndex)) return false;
    $source = GetZoneObject($mzID);
    DecisionQueueController::StoreVariable('HellbreakPendingAbilityP' . $player, [
        'mzID' => $mzID,
        'cardID' => strval($source->CardID ?? ''),
        'abilityIndex' => $abilityIndex,
    ]);
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakAfterActivatedAbility', 99, '', 1);
    ActivateAbility($player, $mzID, $abilityIndex);
    if(HellbreakQueueHasHandler($player, 'HellbreakAfterActivatedAbility')) {
        $dqController = new DecisionQueueController();
        $dqController->ExecuteStaticMethods($player, '-');
    }
    return true;
}

function HellbreakAfterActivatedAbility(int $player): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingAbilityP' . $player);
    if(!is_array($pending)) return false;
    return HellbreakFinishNormalAction($player, 'ABILITY', [
        'cardID' => strval($pending['cardID'] ?? ''),
        'abilityIndex' => intval($pending['abilityIndex'] ?? -1),
    ]);
}

function HellbreakReadyPlayedMinion(int $player, string $selection): bool {
    if(GetCurrentPhase() !== 'HORROR' || intval(GetTurnPlayer()) !== $player) return false;
    $uniqueID = intval(DecisionQueueController::GetVariable('HellbreakPendingReadyMinionP' . $player));
    $ready = strtoupper(trim($selection));
    if(in_array($ready, ['YES', '1', 'TRUE'], true) && intval(MaliceValue($player)) > 0) {
        $characters = &GetCharacters($player);
        foreach($characters as $character) {
            if(is_object($character) && intval($character->UniqueID) === $uniqueID && intval($character->Controller) === $player) {
                $malice = &MaliceValue($player);
                --$malice;
                $character->Status = 2;
                break;
            }
        }
    }
    return HellbreakFinishNormalAction($player, 'PLAY_CARD', ['minionUniqueID' => $uniqueID]);
}

function HellbreakHorrorPhase() {
    if(GetCurrentPhase() !== 'HORROR') return false;
    $round = max(1, intval(GetTurnNumber()));
    if(intval(DecisionQueueController::GetVariable('HellbreakHorrorInitializedRound')) !== $round) {
        DecisionQueueController::StoreVariable('HellbreakHorrorInitializedRound', $round);
        SetTurnPlayer(intval(GetInitiativePlayer()));
        SetPreviousActionPassLike(false);
        SetSlumberPlayer(0);
        SetSlumberUsed(false);
        SetActionSequence(0);
    }

    $player = intval(GetTurnPlayer());
    if($player !== 1 && $player !== 2) return false;
    if(intval(GetSlumberPlayer()) === $player || HellbreakIsAutoSetupPlayer($player)) {
        return HellbreakTakePassLikeAction($player, 'PASS');
    }
    if(HellbreakQueueHasHandler($player, 'HellbreakChooseHorrorAction')) return true;
    $actions = HellbreakLegalActions($player);
    if(count($actions) === 0) return false;
    DecisionQueueController::StoreVariable('HellbreakLegalActionsP' . $player, $actions);
    $labels = array_map(fn($action) => (string)$action['label'], $actions);
    DecisionQueueController::AddDecision($player, 'MZMODAL', '1|1|' . implode('&', $labels), 0, 'Choose_your_Horror_action');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseHorrorAction', 1);
    return true;
}

function HellbreakReadyAllControlled(int $player): void {
    $monster = &GetMonster($player);
    $characters = &GetCharacters($player);
    $assets = &GetAssets($player);
    foreach([$monster, $characters, $assets] as $zone) {
        foreach($zone as $object) if(is_object($object) && intval($object->Controller ?? 0) === $player) $object->Status = 2;
    }
}

function HellbreakRefreshReadyPhase() {
    if(GetCurrentPhase() !== 'REFRESH_READY') return false;
    $round = max(1, intval(GetTurnNumber()));
    if(intval(DecisionQueueController::GetVariable('HellbreakRefreshReadyStartedRound')) === $round) return true;
    DecisionQueueController::StoreVariable('HellbreakRefreshReadyStartedRound', $round);
    HellbreakReadyAllControlled(1);
    HellbreakReadyAllControlled(2);
    HellbreakQueuePhaseEventBarrier(['round' => $round], 'REFRESH_READY', false);
    if(function_exists('HellbreakRefreshReadyHook')) HellbreakRefreshReadyHook();
    HellbreakPumpPhaseEventQueues();
    return true;
}

function HellbreakPumpMonsterFlipEventQueues(): void {
    $controller = new DecisionQueueController();
    $controller->ExecuteStaticMethods(1, '-');
    $controller->ExecuteStaticMethods(2, '-');
}

function HellbreakQueueMonsterFlipEventBarrier(array $context, bool $pump = true): bool {
    $token = intval(DecisionQueueController::GetVariable('HellbreakMonsterFlipBarrierToken')) + 1;
    DecisionQueueController::StoreVariable('HellbreakMonsterFlipBarrierToken', $token);
    DecisionQueueController::StoreVariable('HellbreakPendingMonsterFlipBarrier', [
        'token' => $token,
        'context' => $context,
        'reached' => ['1' => false, '2' => false],
    ]);
    foreach([1, 2] as $player) {
        DecisionQueueController::AddDecision(
            $player,
            'CUSTOM',
            'HellbreakMonsterFlipEventBarrier|' . $token,
            100,
            '',
            1
        );
    }
    if($pump) HellbreakPumpMonsterFlipEventQueues();
    return true;
}

function HellbreakReachMonsterFlipEventBarrier(int $player, int $token): bool {
    $pending = DecisionQueueController::GetVariable('HellbreakPendingMonsterFlipBarrier');
    if(!is_array($pending) || intval($pending['token'] ?? 0) !== $token) return false;
    $pending['reached'][strval($player)] = true;
    DecisionQueueController::StoreVariable('HellbreakPendingMonsterFlipBarrier', $pending);
    if(empty($pending['reached']['1']) || empty($pending['reached']['2'])) return true;
    DecisionQueueController::StoreVariable('HellbreakPendingMonsterFlipBarrier', []);
    return HellbreakContinueAfterMonsterFlipped(is_array($pending['context'] ?? null) ? $pending['context'] : []);
}

function HellbreakContinueAfterMonsterFlipped(array $context): bool {
    if(strtoupper(strval($context['type'] ?? '')) !== 'REFRESH_FLIP') return true;
    $index = intval($context['refreshIndex'] ?? DecisionQueueController::GetVariable('HellbreakRefreshFlipIndex'));
    DecisionQueueController::StoreVariable('HellbreakRefreshFlipIndex', $index + 1);
    return HellbreakContinueMonsterFlips();
}

function HellbreakFlipMonster(int $player, array $continuation = []): bool {
    $monsters = &GetMonster($player);
    foreach($monsters as $monster) {
        if(!is_object($monster) || intval($monster->Controller ?? 0) !== $player) continue;
        $fromSide = strtoupper(strval($monster->Side ?? 'LURKING'));
        $toSide = $fromSide === 'UNLEASHED' ? 'LURKING' : 'UNLEASHED';
        $monster->Side = $toSide;
        HellbreakAddPublicLog('Player ' . $player . ' flipped their monster to ' . strtolower($toSide) . '.', 'FLIP');
        $context = array_merge($continuation, [
            'player' => $player,
            'fromSide' => $fromSide,
            'toSide' => $toSide,
        ]);
        HellbreakQueueMonsterFlipEventBarrier($context, false);
        if(function_exists('HellbreakMonsterFlippedHook')) HellbreakMonsterFlippedHook($player, $monster, $fromSide, $toSide);
        HellbreakPumpMonsterFlipEventQueues();
        return true;
    }
    return false;
}

function HellbreakContinueMonsterFlips(): bool {
    if(GetCurrentPhase() !== 'REFRESH_FLIP') return false;
    $order = DecisionQueueController::GetVariable('HellbreakRefreshFlipOrder');
    $index = intval(DecisionQueueController::GetVariable('HellbreakRefreshFlipIndex'));
    if(!is_array($order) || count($order) !== 2) return false;
    if($index >= 2) return AdvanceAndExecute('READY');
    $player = intval($order[$index]);
    if(HellbreakIsAutoSetupPlayer($player)) {
        DecisionQueueController::StoreVariable('HellbreakRefreshFlipIndex', $index + 1);
        return HellbreakContinueMonsterFlips();
    }
    if(HellbreakQueueHasHandler($player, 'HellbreakChooseMonsterFlip')) return true;
    DecisionQueueController::AddDecision($player, 'YESNO', '', 0, 'Flip_your_monster_to_its_other_side');
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseMonsterFlip', 1, '', 1);
    return true;
}

function HellbreakChooseMonsterFlip(int $player, string $selection): bool {
    if(GetCurrentPhase() !== 'REFRESH_FLIP') return false;
    $order = DecisionQueueController::GetVariable('HellbreakRefreshFlipOrder');
    $index = intval(DecisionQueueController::GetVariable('HellbreakRefreshFlipIndex'));
    if(!is_array($order) || intval($order[$index] ?? 0) !== $player) return false;
    if(in_array(strtoupper(trim($selection)), ['YES', '1', 'TRUE'], true)) {
        return HellbreakFlipMonster($player, ['type' => 'REFRESH_FLIP', 'refreshIndex' => $index]);
    }
    DecisionQueueController::StoreVariable('HellbreakRefreshFlipIndex', $index + 1);
    return HellbreakContinueMonsterFlips();
}

function HellbreakRefreshFlipPhase() {
    if(GetCurrentPhase() !== 'REFRESH_FLIP') return false;
    $round = max(1, intval(GetTurnNumber()));
    if(intval(DecisionQueueController::GetVariable('HellbreakRefreshFlipRound')) !== $round) {
        DecisionQueueController::StoreVariable('HellbreakRefreshFlipRound', $round);
        $initiative = intval(GetInitiativePlayer());
        DecisionQueueController::StoreVariable('HellbreakRefreshFlipOrder', [$initiative, HellbreakOtherPlayer($initiative)]);
        DecisionQueueController::StoreVariable('HellbreakRefreshFlipIndex', 0);
    }
    return HellbreakContinueMonsterFlips();
}

function HellbreakCommitHandLimit(int $player, string $selection): bool {
    if(GetCurrentPhase() !== 'REFRESH_HAND') return false;
    $round = max(1, intval(GetTurnNumber()));
    if(intval(DecisionQueueController::GetVariable('HellbreakRefreshHandDoneP' . $player)) === $round) return false;
    $hand = &GetHand($player);
    HellbreakReindexZone($hand);
    $excess = max(0, count($hand) - 6);
    $choices = HellbreakNormalizeMultiChoice($selection);
    if(count($choices) !== $excess) return false;
    $indices = [];
    foreach($choices as $mzID) {
        if(!preg_match('/^myHand-(\d+)$/', $mzID, $matches)) return false;
        $index = intval($matches[1]);
        if(!isset($hand[$index]) || isset($indices[$index])) return false;
        $indices[$index] = true;
    }
    krsort($indices);
    foreach(array_keys($indices) as $index) {
        $card = $hand[$index];
        array_splice($hand, $index, 1);
        AddCrypt($player, $card->CardID, 'HandLimit', intval(GetTurnNumber()), true, $card);
    }
    HellbreakReindexZone($hand);
    DecisionQueueController::StoreVariable('HellbreakRefreshHandDoneP' . $player, $round);
    return true;
}

function HellbreakResolveHandLimits(): bool {
    if(GetCurrentPhase() !== 'REFRESH_HAND') return false;
    $round = max(1, intval(GetTurnNumber()));
    if(intval(DecisionQueueController::GetVariable('HellbreakRefreshHandDoneP1')) !== $round) return false;
    if(intval(DecisionQueueController::GetVariable('HellbreakRefreshHandDoneP2')) !== $round) return false;
    if(intval(DecisionQueueController::GetVariable('HellbreakRoundEndStartedRound')) === $round) return true;
    DecisionQueueController::StoreVariable('HellbreakRoundEndStartedRound', $round);
    SetTurnNumber($round + 1);
    SetPreviousActionPassLike(false);
    SetSlumberPlayer(0);
    SetSlumberUsed(false);
    HellbreakAddPublicLog('Round ' . $round . ' ended. Round ' . ($round + 1) . ' Feeding begins.', 'ROUND');
    HellbreakQueuePhaseEventBarrier(['round' => $round], 'ROUND_ENDED', false);
    if(function_exists('HellbreakRoundEndedHook')) HellbreakRoundEndedHook($round);
    HellbreakPumpPhaseEventQueues();
    return true;
}

function HellbreakChooseHandLimitDiscards(int $player, string $selection): bool {
    if(!HellbreakCommitHandLimit($player, $selection)) return false;
    return HellbreakResolveHandLimits() || true;
}

function HellbreakRefreshHandPhase() {
    if(GetCurrentPhase() !== 'REFRESH_HAND') return false;
    $round = max(1, intval(GetTurnNumber()));
    for($player = 1; $player <= 2; ++$player) {
        if(intval(DecisionQueueController::GetVariable('HellbreakRefreshHandDoneP' . $player)) === $round) continue;
        $hand = &GetHand($player);
        HellbreakReindexZone($hand);
        $excess = max(0, count($hand) - 6);
        if($excess === 0) {
            DecisionQueueController::StoreVariable('HellbreakRefreshHandDoneP' . $player, $round);
            continue;
        }
        if(HellbreakIsAutoSetupPlayer($player)) {
            $choices = [];
            for($i = 0; $i < $excess; ++$i) $choices[] = 'myHand-' . (count($hand) - 1 - $i);
            HellbreakCommitHandLimit($player, implode('&', $choices));
            continue;
        }
        if(HellbreakQueueHasHandler($player, 'HellbreakChooseHandLimitDiscards')) continue;
        DecisionQueueController::AddDecision($player, 'MZMULTICHOOSE', $excess . '|' . $excess . '|myHand', 0, 'Discard_exactly_' . $excess . '_cards_to_six');
        DecisionQueueController::AddDecision($player, 'CUSTOM', 'HellbreakChooseHandLimitDiscards', 1);
    }
    HellbreakResolveHandLimits();
    return true;
}

?>
