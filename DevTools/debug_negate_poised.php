<?php

chdir(dirname(__DIR__));

include 'Core/EngineActionRunner.php';

EngineLoadRootRuntime('GrandArchiveSim');
$GLOBALS['gameName'] = 'debug_negate_poised';

copy(
  'Tests/Integration/GrandArchiveSim/negate-activation-poised-occlusion/initial_gamestate.txt',
  'GrandArchiveSim/Games/debug_negate_poised/Gamestate.txt'
);

ParseGamestate('./GrandArchiveSim/');

echo 'initial class bonus=' . (IsClassBonusActive(2, array('RANGER')) ? 'yes' : 'no') . PHP_EOL;
foreach (GetField(2) as $index => $obj) {
  if (strpos(EffectiveCardType($obj), 'CHAMPION') !== false) {
    echo 'champ ' . $index . ' ' . $obj->CardID . ' classes=' . EffectiveCardClasses($obj) . PHP_EOL;
  }
}

foreach (GetHand(2) as $index => $obj) {
  echo 'initial hand ' . $index . ' ' . $obj->CardID . PHP_EOL;
}

$actions = RegressionLoadActionsForFixture('Tests/Integration/GrandArchiveSim/negate-activation-poised-occlusion');
foreach ($actions as $index => $action) {
  if (in_array($index + 1, array(6, 7, 8, 9, 10, 11), true)) {
    echo 'before step ' . ($index + 1) . ' action=' . json_encode($action) . PHP_EOL;
    foreach (GetDecisionQueue(2) as $dqIndex => $dqObj) {
      echo ' dq ' . $dqIndex . ' ' . $dqObj->Type . ' ' . $dqObj->Param . ' mode=' . $dqObj->Mode . PHP_EOL;
    }
    foreach (GetHand(2) as $handIndex => $obj) {
      echo ' hand ' . $handIndex . ' ' . $obj->CardID . PHP_EOL;
    }
  }

  EngineExecuteLoadedAction($action, 'GrandArchiveSim', 'debug_negate_poised', array(
    'updateCache' => false,
    'disableRecording' => true,
  ));

  if (in_array($index + 1, array(4, 5, 6, 7, 8, 11), true)) {
    $fastCards = GetPlayableFastCards(2);
    echo 'step ' . ($index + 1)
      . ' class bonus=' . (IsClassBonusActive(2, array('RANGER')) ? 'yes' : 'no')
      . ' deck=' . count(GetDeck(2))
      . ' memory=' . count(GetMemory(2))
      . ' hand=' . count(GetHand(2))
      . ' effectstack=' . count(GetEffectStack())
      . ' fast=' . json_encode($fastCards)
      . ' dq1=' . count(GetDecisionQueue(1))
      . ' dq2=' . count(GetDecisionQueue(2))
      . PHP_EOL;

    foreach (GetDecisionQueue(1) as $dqIndex => $dqObj) {
      echo ' p1dq ' . $dqIndex . ' ' . $dqObj->Type . ' ' . $dqObj->Param . ' mode=' . $dqObj->Mode . PHP_EOL;
    }
    foreach (GetDecisionQueue(2) as $dqIndex => $dqObj) {
      echo ' p2dq ' . $dqIndex . ' ' . $dqObj->Type . ' ' . $dqObj->Param . ' mode=' . $dqObj->Mode . PHP_EOL;
    }

    foreach (GetField(2) as $fieldIndex => $obj) {
      if (strpos(EffectiveCardType($obj), 'CHAMPION') !== false) {
        echo ' champ ' . $fieldIndex
          . ' ' . $obj->CardID
          . ' status=' . $obj->Status
          . ' effects=' . json_encode($obj->TurnEffects)
          . PHP_EOL;
      }
    }
  }
}