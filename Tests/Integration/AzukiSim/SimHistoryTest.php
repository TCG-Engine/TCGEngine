<?php

require_once __DIR__ . '/../../../AzukiSim/GamestateParser.php';
require_once __DIR__ . '/../../../AzukiSim/ZoneAccessors.php';
require_once __DIR__ . '/../../../AzukiSim/ZoneClasses.php';

function SimHistoryTestAssert($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

global $playerID, $gRandomCounter;
InitializeGamestate();
$playerID = 1;
$p1Health = &GetLeaderHealth(1);
$p2Health = &GetLeaderHealth(2);
$p1Health = 20;
$p2Health = 20;
$gRandomCounter = 5;

SimHistoryTestAssert(SimHistoryInitialize('Game start'), 'History did not initialize.');
SimHistoryTestAssert(!SimHistoryCanUndo(), 'A new game should not be undoable.');
SimHistoryTestAssert(!SimHistoryCanRedo(), 'A new game should not be redoable.');

// Capture while the global perspective is P2. Restores must still map both seats correctly.
$playerID = 2;
SimHistoryTestAssert(SimHistoryBeginAction(2, 'Player 2 action'), 'P2 action did not begin.');
$p1Health = 19;
$p2Health = 17;
$gRandomCounter = 9;
SimHistoryTestAssert(SimHistoryCommitPending(true), 'P2 action did not commit.');

$playerID = 1;
SimHistoryTestAssert(SimHistoryBeginAction(1, 'Player 1 action'), 'P1 action did not begin.');
$p1Health = 15;
$gRandomCounter = 11;
SimHistoryTestAssert(SimHistoryCommitPending(true), 'P1 action did not commit.');

SimHistoryTestAssert(SimHistoryUndo(2), 'First undo failed.');
SimHistoryTestAssert(intval(GetLeaderHealth(1)) === 19, 'Undo restored the wrong P1 health.');
SimHistoryTestAssert(intval(GetLeaderHealth(2)) === 17, 'Undo restored the wrong P2 health.');
SimHistoryTestAssert(intval($gRandomCounter) === 9, 'Undo did not restore deterministic random state.');

SimHistoryTestAssert(SimHistoryUndo(1), 'Second undo failed.');
SimHistoryTestAssert(intval(GetLeaderHealth(1)) === 20, 'Second undo did not reach the initial state.');
SimHistoryTestAssert(intval(GetLeaderHealth(2)) === 20, 'Second undo swapped or corrupted P2 state.');
SimHistoryTestAssert(intval($gRandomCounter) === 5, 'Second undo did not restore the initial random state.');

SimHistoryTestAssert(SimHistoryRedo(1), 'First redo failed.');
SimHistoryTestAssert(SimHistoryRedo(2), 'Second redo failed.');
SimHistoryTestAssert(intval(GetLeaderHealth(1)) === 15, 'Redo did not restore the latest P1 state.');
SimHistoryTestAssert(intval(GetLeaderHealth(2)) === 17, 'Redo did not restore the latest P2 state.');
SimHistoryTestAssert(intval($gRandomCounter) === 11, 'Redo did not restore deterministic random state.');

// A new action after Undo creates a normal editor-style branch and invalidates Redo.
SimHistoryTestAssert(SimHistoryUndo(1), 'Branch setup undo failed.');
SimHistoryTestAssert(SimHistoryBeginAction(2, 'Alternate action'), 'Alternate action did not begin.');
$p2Health = 7;
SimHistoryTestAssert(SimHistoryCommitPending(true), 'Alternate action did not commit.');
SimHistoryTestAssert(!SimHistoryCanRedo(), 'A new action after Undo must clear Redo.');

// A pending multi-request action is cancellable but its partial state is not redoable.
SimHistoryTestAssert(SimHistoryBeginAction(1, 'Pending choice'), 'Pending action did not begin.');
$p1Health = 1;
SimHistoryTestAssert(SimHistoryUndo(2), 'Pending action cancellation failed.');
SimHistoryTestAssert(intval(GetLeaderHealth(1)) === 19, 'Cancelling a pending action did not restore its pre-state.');
SimHistoryTestAssert(!SimHistoryCanRedo(), 'A cancelled partial action must not be redoable.');

// History metadata is stored in a hidden object as one whitespace-safe base64 token.
$historyZone = &GetSimHistory();
SimHistoryTestAssert(count($historyZone) === 1, 'History storage should use one hidden global object.');
$roundTrip = new SimHistory($historyZone[0]->Serialize(), 'SimHistory', 0, 0);
SimHistoryTestAssert($roundTrip->Version === $historyZone[0]->Version, 'History storage did not round-trip safely.');

// Exercise the Phase 1 minimum depth in both directions and reject no-op checkpoints.
InitializeGamestate();
$playerID = 1;
$p1Health = &GetLeaderHealth(1);
$p1Health = 20;
$gRandomCounter = 100;
SimHistoryInitialize('Depth test');
SimHistoryBeginAction(1, 'No-op');
SimHistoryTestAssert(!SimHistoryCommitPending(true), 'A no-op action should not create a history entry.');
for ($i = 1; $i <= 12; ++$i) {
    SimHistoryTestAssert(SimHistoryBeginAction(($i % 2) + 1, 'Depth action ' . $i), 'Depth action did not begin.');
    $p1Health = 20 - $i;
    $gRandomCounter = 100 + $i;
    SimHistoryTestAssert(SimHistoryCommitPending(true), 'Depth action did not commit.');
}
for ($i = 0; $i < 12; ++$i) SimHistoryTestAssert(SimHistoryUndo(1), 'Depth undo failed.');
SimHistoryTestAssert(intval(GetLeaderHealth(1)) === 20, 'Twelve undos did not reach the initial state.');
for ($i = 0; $i < 12; ++$i) SimHistoryTestAssert(SimHistoryRedo(2), 'Depth redo failed.');
SimHistoryTestAssert(intval(GetLeaderHealth(1)) === 8, 'Twelve redos did not reach the latest state.');
SimHistoryTestAssert(intval($gRandomCounter) === 112, 'Depth redo lost random state.');

// Visibility=None history objects may contribute separators, but never their full-state payload.
$getNextTurnSource = file_get_contents(__DIR__ . '/../../../AzukiSim/GetNextTurn.php');
SimHistoryTestAssert(strpos($getNextTurnSource, 'echo($gSimHistory)') === false, 'History payload is exposed by GetNextTurn.');
if (preg_match('/\$arr = &GetSimHistory\(\);(.*?)echo\("<~>"\);/s', $getNextTurnSource, $historyRender)) {
    SimHistoryTestAssert(strpos($historyRender[1], '->Version') === false, 'History payload field is exposed by GetNextTurn.');
    SimHistoryTestAssert(strpos($historyRender[1], 'ClientRenderedCard') === false, 'History payload object is exposed by GetNextTurn.');
} else {
    throw new RuntimeException('Could not locate generated SimHistory response block.');
}

echo "SimHistoryTest passed\n";

?>
