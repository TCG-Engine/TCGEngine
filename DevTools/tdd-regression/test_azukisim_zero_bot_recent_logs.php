<?php

$garden = [];
$alley = [];
$decisionQueue = [];
$perspectiveObjects = [];
$canAttack = [];
$turnPlayer = 2;
$currentPhase = 'MAIN';
$pendingAttackResponse = false;

class DecisionQueueController {
    public static function GetVariable($name) { return ''; }
}

function GetGarden($player) { global $garden; return $garden; }
function GetAlley($player) { global $alley; return $alley; }
function GetHand($player) { return []; }
function GetDecisionQueue($player) { global $decisionQueue; return $decisionQueue; }
function GetZoneObject($mzID) {
    global $garden, $alley, $perspectiveObjects;
    $player = intval($GLOBALS['playerID'] ?? 0);
    if(isset($perspectiveObjects[$player][$mzID])) return $perspectiveObjects[$player][$mzID];
    if(preg_match('/^myGarden-(\d+)$/', $mzID, $m)) return $garden[intval($m[1])] ?? null;
    if(preg_match('/^myAlley-(\d+)$/', $mzID, $m)) return $alley[intval($m[1])] ?? null;
    return null;
}
function CardType($cardID) { return str_contains($cardID, '_L_') ? 'LEADER' : 'ENTITY'; }
function CardHealth($cardID) {
    if(str_contains($cardID, 'Glass-Blower')) return 1;
    if(str_contains($cardID, '_L_')) return 20;
    return 2;
}
function CardAttack($cardID) { return str_contains($cardID, '_L_') ? 0 : 1; }
function CardCost($cardID) { return 1; }
function ResolveEntityHealthValue($player, $obj) { return CardHealth($obj->CardID ?? ''); }
function ResolveEntityAttackValue($player, $obj) { return CardAttack($obj->CardID ?? ''); }
function CanAttackWith($player, $mzID) { global $canAttack; return !empty($canAttack[$mzID]); }
function GetTurnPlayer() { global $turnPlayer; return $turnPlayer; }
function GetCurrentPhase() { global $currentPhase; return $currentPhase; }
function HasPendingAttackResponse() { global $pendingAttackResponse; return $pendingAttackResponse; }

require_once __DIR__ . '/../../AzukiSim/Custom/RlBotHeuristics.php';

function entity($cardID, $index, $damage = 0) {
    return (object)[
        'CardID' => $cardID,
        'Status' => 2,
        'Damage' => $damage,
        'mzIndex' => $index,
        'removed' => false,
    ];
}

function expectValue($label, $actual, $expected) {
    if($actual === $expected) return;
    fwrite(STDERR, "[FAIL] $label: expected " . var_export($expected, true) . ', got ' . var_export($actual, true) . PHP_EOL);
    exit(1);
}

$zero = 'S1-STT04-001_Zero_L_L_die';
$kindler = 'S1-STT04-004_Fanatic-Kindler_E_C_die';
$glass = 'S1-AZK01-056_Glass-Blower-Hokuto_E_C_die';
$collateral = 'S1-STT04-016_Collateral-Burst_S_UC_die';
$snapshot = ['azukiCompactState' => [
    'p2' => ['remainingLife' => 10, 'readyAttack' => 1, 'gardenCount' => 2],
    'p1' => ['remainingLife' => 7, 'gardenCount' => 3],
]];
$activate = ['_semanticKey' => 'activate:zero', 'resolvedCardID' => $zero, 'cardID' => 'myGarden-0'];
$pass = ['_semanticKey' => 'pass:main', 'resolvedCardID' => '', 'cardID' => 'PASS'];

$garden = [entity($kindler, 0)];
$canAttack = ['myGarden-0' => true];
$turnPlayer = 1;
$choice = AzukiZeroHeuristicChooseAction(['activate:zero' => 999], [$activate, $pass], [], $snapshot, 2);
expectValue('zero-does-not-activate-on-opponent-turn', $choice['cardID'] ?? '', 'PASS');
$turnPlayer = 2;

$decisionQueue = [(object)['Type' => 'MZMAYCHOOSE'], (object)[
    'Type' => 'CUSTOM',
    'Param' => $collateral . ':0:OnPlay-1',
]];
$healthyTarget = ['cardID' => 'myGarden-0', 'resolvedCardID' => $kindler];
$optionalPass = ['cardID' => 'PASS'];
$legal = ['decisionType' => 'MZMAYCHOOSE'];
$choice = AzukiZeroHeuristicChooseAction([], [$healthyTarget, $optionalPass], $legal, $snapshot, 2);
expectValue('collateral-uses-survivable-enabler', $choice['cardID'] ?? '', 'myGarden-0');

$garden = [entity($glass, 0)];
$oneHpTarget = ['cardID' => 'myGarden-0', 'resolvedCardID' => $glass];
$choice = AzukiZeroHeuristicChooseAction([], [$oneHpTarget, $optionalPass], $legal, $snapshot, 2);
expectValue('collateral-preserves-one-hp-entity', $choice['cardID'] ?? '', 'PASS');

$garden = [entity($kindler, 0)];
$decisionQueue = [(object)['Type' => 'MZMAYCHOOSE'], (object)[
    'Type' => 'CUSTOM',
    'Param' => $collateral . ':0:OnPlay-2',
]];
$perspectiveObjects = [2 => ['theirGarden-0' => entity($zero, 0, 13)]];
$enemyLeader = ['cardID' => 'theirGarden-0', 'resolvedCardID' => $zero];
$choice = AzukiZeroHeuristicChooseAction([], [$enemyLeader, $optionalPass], $legal, $snapshot, 2);
expectValue('collateral-continues-to-enemy-target', $choice['cardID'] ?? '', 'theirGarden-0');

echo "[PASS] Azuki Zero heuristic recent-log regressions" . PHP_EOL;
