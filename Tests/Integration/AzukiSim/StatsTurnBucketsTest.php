<?php

$azukiStatsTestIndex = [];
$azukiStatsTestTurn = 1;

function GetMacroGameIndexArray() {
    global $azukiStatsTestIndex;
    return $azukiStatsTestIndex;
}

function SetMacroGameIndex($value) {
    global $azukiStatsTestIndex;
    $decoded = json_decode($value, true);
    $azukiStatsTestIndex = is_array($decoded) ? $decoded : [];
}

function GetTurnNumber() {
    global $azukiStatsTestTurn;
    return $azukiStatsTestTurn;
}

class AzukiStatsFakeStatement {
    public $boundValues = 0;

    public function bind_param($types, &...$values) {
        if(strlen($types) !== count($values)) {
            throw new RuntimeException('Bind type/value count mismatch.');
        }
        $this->boundValues = count($values);
    }

    public function execute() {
        return true;
    }

    public function close() {
    }
}

class AzukiStatsFakeConnection {
    public $statement;

    public function prepare($sql) {
        if(substr_count($sql, '?') !== 33) {
            throw new RuntimeException('Unexpected stats upsert placeholder count.');
        }
        $this->statement = new AzukiStatsFakeStatement();
        return $this->statement;
    }
}

require_once __DIR__ . '/../../../AzukiSim/Custom/Stats.php';

$expectedBuckets = [
    1 => 1,
    2 => 1,
    3 => 2,
    4 => 2,
    17 => 9,
    18 => 9,
    19 => 10,
    20 => 10,
    99 => 10,
];
foreach($expectedBuckets as $rawTurn => $expectedBucket) {
    if(AzukiStatsTurnCycleBucket($rawTurn) !== $expectedBucket) {
        throw new RuntimeException("Raw turn $rawTurn mapped to the wrong cycle.");
    }
}

$azukiStatsTestTurn = 4;
AzukiStatsTrackPlay(1, 'card', 2);
if(
    intval($azukiStatsTestIndex['AzukiPlays'][1]['card'] ?? 0) !== 2
    || intval($azukiStatsTestIndex['AzukiPlaysByTurn'][1][2]['card'] ?? 0) !== 2
) {
    throw new RuntimeException('Successful play was not tracked in full-turn cycle 2.');
}

$turnPlays = array_fill(1, 10, []);
$turnPlays[1]['card'] = 2;
$turnPlays[10]['card'] = 1;
$connection = new AzukiStatsFakeConnection();
$recorded = AzukiStatsRecordDeck(
    $connection,
    7,
    ['card' => 4],
    ['card' => 3],
    $turnPlays,
    ['card' => 3],
    ['card' => 1],
    ['card' => 2],
    true
);
if(!$recorded || $connection->statement->boundValues !== 33) {
    throw new RuntimeException('Stats upsert binding failed.');
}

echo "Azuki turn-cycle stats tests passed.\n";
