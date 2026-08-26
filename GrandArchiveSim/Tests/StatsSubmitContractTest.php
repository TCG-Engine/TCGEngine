<?php
require_once __DIR__ . '/../StatsSubmit.php';

function GAStatsContractCheck($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: " . $message . PHP_EOL);
        exit(1);
    }
}

// Minimal match-store double for exercising the delivery gate without HTTP or filesystem writes.
$GLOBALS['gaStatsContractStoredMatch'] = null;
function MatchRead($rootName, $matchId) {
    return $GLOBALS['gaStatsContractStoredMatch'];
}
function MatchWithLock($rootName, $matchId, callable $fn) {
    $fn($GLOBALS['gaStatsContractStoredMatch']);
    return $GLOBALS['gaStatsContractStoredMatch'];
}

$GLOBALS['grandArchiveStatsSourceVersion'] = 'contract-test';
$match = [
    'matchId' => 'contract-match', 'format' => 'premier', 'bestOf' => 1,
    'winner' => 1, 'wins' => ['1' => 1, '2' => 0], 'createdAt' => 1724515200,
    'players' => ['1' => ['deckLink' => ''], '2' => ['deckLink' => '']],
];
$game = [
    'gameName' => 'contract-game', 'gameNumber' => 1, 'winner' => 1,
    'detail' => [
        'firstPlayer' => 2, 'turns' => 4,
        'champions' => [
            '1' => ['championId' => 'champion-one', 'championName' => 'Champion One', 'element' => 'FIRE', 'classes' => ['WARRIOR'], 'level' => 2, 'hp' => 10],
            '2' => ['championId' => 'champion-two', 'championName' => 'Champion Two', 'element' => 'WIND', 'classes' => ['RANGER'], 'level' => 2, 'hp' => 0],
        ],
        'telemetry' => ['cards' => [], 'turns' => [], 'combatEvents' => []],
    ],
];

$payload = GABuildGameResultPayload($match, $game);
$encoded = json_encode($payload);
$decoded = json_decode($encoded, true);

GAStatsContractCheck($payload['schemaVersion'] === 1, 'schemaVersion is 1');
GAStatsContractCheck($payload['submissionId'] === 'contract-match:1', 'submission identity is stable');
GAStatsContractCheck($payload['source']['version'] === 'contract-test', 'source version is emitted');
GAStatsContractCheck(!array_key_exists('apiKey', $payload), 'credential is not in the payload');
GAStatsContractCheck(str_starts_with($payload['submittedAt'], '2024-08-24T16:00:00'), 'submission timestamp is stable');
GAStatsContractCheck(str_contains($encoded, '"cardStats":{}'), 'empty cardStats serializes as an object');
GAStatsContractCheck($decoded['players']['1']['championId'] === 'champion-one', 'player champion is retained');
GAStatsContractCheck($decoded['players']['1']['championName'] === 'Champion One', 'player champion name is retained');
GAStatsContractCheck(GAShouldShareAnonymizedGameplayData([]), 'legacy matches default to sharing');
GAStatsContractCheck(GAShouldShareAnonymizedGameplayData(['shareAnonymizedGameplayData' => true]), 'enabled matches share');
GAStatsContractCheck(!GAShouldShareAnonymizedGameplayData(['shareAnonymizedGameplayData' => false]), 'opted-out matches do not share');

$GLOBALS['gaStatsContractStoredMatch'] = [
    'matchId' => 'opted-out-match',
    'state' => 'complete',
    'shareAnonymizedGameplayData' => false,
    'players' => ['1' => [], '2' => []],
    'games' => [],
];
GASubmitMatchResults('opted-out-match');
GAStatsContractCheck($GLOBALS['gaStatsContractStoredMatch']['statsSubmitted'] === true, 'opt-out is sealed without retrying');
GAStatsContractCheck($GLOBALS['gaStatsContractStoredMatch']['statsStatus'] === 'skipped_opt_out', 'opt-out records an explicit status');

echo "Grand Archive stats payload contract: PASS" . PHP_EOL;
