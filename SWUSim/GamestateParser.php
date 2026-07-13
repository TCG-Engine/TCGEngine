<?php
include __DIR__ . '/Custom/CustomInput.php';
include __DIR__ . '/Custom/GameLogic.php';
include __DIR__ . '/TurnController.php';
include __DIR__ . '/GeneratedCode/GeneratedMacroCode.php';
include __DIR__ . '/GeneratedCode/GeneratedKeywordCode.php';
include __DIR__ . '/GeneratedCode/GeneratedAbilityStubs.php';
include __DIR__ . '/Custom/KeywordEffects.php';
include_once __DIR__ . '/Telemetry.php';
function GetAssetReflectionPath() {
  return "SWUSim";
}

function SupportsRegressionRecording() {
  return false;
}

function GetEditAuth() {
  return "None";
}

function GamestateStorageMode() {
  return "apcu";
}

function GamestateUsesMemoryStorage() {
  return GamestateStorageMode() === "apcu";
}

function GetGamestateStorageKey($gameName) {
  return "tcgengine:gamestate:SWUSim:" . $gameName;
}

function InitializeGamestate() {
  global $p1Deck, $p2Deck, $p3Deck, $p4Deck;
  global $p1Hand, $p2Hand, $p3Hand, $p4Hand;
  global $p1Discard, $p2Discard, $p3Discard, $p4Discard;
  global $p1Resources, $p2Resources, $p3Resources, $p4Resources;
  global $p1Leader, $p2Leader, $p3Leader, $p4Leader;
  global $p1Base, $p2Base, $p3Base, $p4Base;
  global $p1GroundArena, $p2GroundArena, $p3GroundArena, $p4GroundArena;
  global $p1SpaceArena, $p2SpaceArena, $p3SpaceArena, $p4SpaceArena;
  global $p1GlobalEffects, $p2GlobalEffects, $p3GlobalEffects, $p4GlobalEffects;
  global $p1DecisionQueue, $p2DecisionQueue, $p3DecisionQueue, $p4DecisionQueue;
  global $p1TempZone, $p2TempZone, $p3TempZone, $p4TempZone;
  global $p1Versions, $p2Versions, $p3Versions, $p4Versions;
  global $gTurnNumber;
  global $gFirstPlayer;
  global $gTurnPlayer;
  global $gCurrentPhase;
  global $gPhaseParameters;
  global $gFlashMessage;
  global $gDecisionQueueVariables;
  global $gInitiativeCounter;
  global $gSeatOrder;
  global $gLiveSeats;
  global $gBlastCounter;
  global $gPlanCounter;
  global $gMacroTurnIndex;
  global $gMacroGameIndex;
  global $gUniqueIDCounter;
  global $gEffectStack;
  global $gGameLog;
  global $gMatchReplayInitialState;
  global $gMatchReplayCommands;

  global $currentPlayer, $updateNumber, $gRandomCounter;
  global $objectDataIndices;

  $objectDataIndices = [];
  $p1Deck = [];
  $p2Deck = [];
  $p3Deck = [];
  $p4Deck = [];
  $p1Hand = [];
  $p2Hand = [];
  $p3Hand = [];
  $p4Hand = [];
  $p1Discard = [];
  $p2Discard = [];
  $p3Discard = [];
  $p4Discard = [];
  $p1Resources = [];
  $p2Resources = [];
  $p3Resources = [];
  $p4Resources = [];
  $p1Leader = [];
  $p2Leader = [];
  $p3Leader = [];
  $p4Leader = [];
  $p1Base = [];
  $p2Base = [];
  $p3Base = [];
  $p4Base = [];
  $p1GroundArena = [];
  $p2GroundArena = [];
  $p3GroundArena = [];
  $p4GroundArena = [];
  $p1SpaceArena = [];
  $p2SpaceArena = [];
  $p3SpaceArena = [];
  $p4SpaceArena = [];
  $p1GlobalEffects = [];
  $p2GlobalEffects = [];
  $p3GlobalEffects = [];
  $p4GlobalEffects = [];
  $p1DecisionQueue = [];
  $p2DecisionQueue = [];
  $p3DecisionQueue = [];
  $p4DecisionQueue = [];
  $p1TempZone = [];
  $p2TempZone = [];
  $p3TempZone = [];
  $p4TempZone = [];
  $p1Versions = [];
  $p2Versions = [];
  $p3Versions = [];
  $p4Versions = [];
  $gTurnNumber = 0;
  $gFirstPlayer = 1;
  $gTurnPlayer = 1;
  $gCurrentPhase = "-";
  $gPhaseParameters = "-";
  $gFlashMessage = "-";
  $gDecisionQueueVariables = "-";
  $gInitiativeCounter = "P1_UNCLAIMED";
  $gSeatOrder = 12;
  $gLiveSeats = 12;
  $gBlastCounter = "AVAILABLE";
  $gPlanCounter = "AVAILABLE";
  $gMacroTurnIndex = "-";
  $gMacroGameIndex = "-";
  $gUniqueIDCounter = 0;
  $gEffectStack = [];
  $gGameLog = "-";
  $gMatchReplayInitialState = "";
  $gMatchReplayCommands = "";
  $currentPlayer = 1;
  $updateNumber = 1;
  $gRandomCounter = 0;
  global $gWinner, $gPendingTriggers, $gTriggerDepth;
  $gWinner = null;
  $gPendingTriggers = [];
  $gTriggerDepth = 1;
  global $gTelemetry; $gTelemetry = "-";
}

function WriteGamestate($filepath="./") {
  global $p1Deck, $p2Deck, $p3Deck, $p4Deck;
  global $p1Hand, $p2Hand, $p3Hand, $p4Hand;
  global $p1Discard, $p2Discard, $p3Discard, $p4Discard;
  global $p1Resources, $p2Resources, $p3Resources, $p4Resources;
  global $p1Leader, $p2Leader, $p3Leader, $p4Leader;
  global $p1Base, $p2Base, $p3Base, $p4Base;
  global $p1GroundArena, $p2GroundArena, $p3GroundArena, $p4GroundArena;
  global $p1SpaceArena, $p2SpaceArena, $p3SpaceArena, $p4SpaceArena;
  global $p1GlobalEffects, $p2GlobalEffects, $p3GlobalEffects, $p4GlobalEffects;
  global $p1DecisionQueue, $p2DecisionQueue, $p3DecisionQueue, $p4DecisionQueue;
  global $p1TempZone, $p2TempZone, $p3TempZone, $p4TempZone;
  global $p1Versions, $p2Versions, $p3Versions, $p4Versions;
  global $gTurnNumber;
  global $gFirstPlayer;
  global $gTurnPlayer;
  global $gCurrentPhase;
  global $gPhaseParameters;
  global $gFlashMessage;
  global $gDecisionQueueVariables;
  global $gInitiativeCounter;
  global $gSeatOrder;
  global $gLiveSeats;
  global $gBlastCounter;
  global $gPlanCounter;
  global $gMacroTurnIndex;
  global $gMacroGameIndex;
  global $gUniqueIDCounter;
  global $gEffectStack;
  global $gGameLog;
  global $gMatchReplayInitialState;
  global $gMatchReplayCommands;

  global $currentPlayer, $updateNumber, $gRandomCounter;
  global $objectDataIndices;

  global $gameName;
  $filename = $filepath . "Games/$gameName/Gamestate.txt";
  $gamestateText = "";
  $writeZone = function($zone) use (&$gamestateText) {
    $zoneText = "";
    $count = 0;
    foreach($zone as $obj) {
      if($obj == null || $obj->Removed()) continue;
      ++$count;
      $zoneText .= trim($obj->Serialize()) . "\r\n";
    }
    $gamestateText .= $count . "\r\n";
    $gamestateText .= $zoneText;
  };
  $gamestateText .= $currentPlayer . "\r\n";
  $gamestateText .= $updateNumber . "\r\n";
  $writeZone($p1Deck);
  $writeZone($p2Deck);
  $writeZone($p3Deck);
  $writeZone($p4Deck);
  $writeZone($p1Hand);
  $writeZone($p2Hand);
  $writeZone($p3Hand);
  $writeZone($p4Hand);
  $writeZone($p1Discard);
  $writeZone($p2Discard);
  $writeZone($p3Discard);
  $writeZone($p4Discard);
  $writeZone($p1Resources);
  $writeZone($p2Resources);
  $writeZone($p3Resources);
  $writeZone($p4Resources);
  $writeZone($p1Leader);
  $writeZone($p2Leader);
  $writeZone($p3Leader);
  $writeZone($p4Leader);
  $writeZone($p1Base);
  $writeZone($p2Base);
  $writeZone($p3Base);
  $writeZone($p4Base);
  $writeZone($p1GroundArena);
  $writeZone($p2GroundArena);
  $writeZone($p3GroundArena);
  $writeZone($p4GroundArena);
  $writeZone($p1SpaceArena);
  $writeZone($p2SpaceArena);
  $writeZone($p3SpaceArena);
  $writeZone($p4SpaceArena);
  $writeZone($p1GlobalEffects);
  $writeZone($p2GlobalEffects);
  $writeZone($p3GlobalEffects);
  $writeZone($p4GlobalEffects);
  $writeZone($p1DecisionQueue);
  $writeZone($p2DecisionQueue);
  $writeZone($p3DecisionQueue);
  $writeZone($p4DecisionQueue);
  $writeZone($p1TempZone);
  $writeZone($p2TempZone);
  $writeZone($p3TempZone);
  $writeZone($p4TempZone);
  $writeZone($p1Versions);
  $writeZone($p2Versions);
  $writeZone($p3Versions);
  $writeZone($p4Versions);
  $gamestateText .= $gTurnNumber . "\r\n";
  $gamestateText .= $gFirstPlayer . "\r\n";
  $gamestateText .= $gTurnPlayer . "\r\n";
  $gamestateText .= $gCurrentPhase . "\r\n";
  $gamestateText .= $gPhaseParameters . "\r\n";
  $gamestateText .= $gFlashMessage . "\r\n";
  $gamestateText .= $gDecisionQueueVariables . "\r\n";
  $gamestateText .= $gInitiativeCounter . "\r\n";
  $gamestateText .= $gSeatOrder . "\r\n";
  $gamestateText .= $gLiveSeats . "\r\n";
  $gamestateText .= $gBlastCounter . "\r\n";
  $gamestateText .= $gPlanCounter . "\r\n";
  $gamestateText .= $gMacroTurnIndex . "\r\n";
  $gamestateText .= $gMacroGameIndex . "\r\n";
  $gamestateText .= $gUniqueIDCounter . "\r\n";
  $writeZone($gEffectStack);
  $gamestateText .= $gGameLog . "\r\n";
  $gamestateText .= $gMatchReplayInitialState . "\r\n";
  $gamestateText .= $gMatchReplayCommands . "\r\n";
  $gamestateText .= $gRandomCounter . "\r\n";
  global $gTelemetry; $gamestateText .= (($gTelemetry === null || $gTelemetry === '') ? '-' : $gTelemetry) . "\r\n";
  if(GamestateUsesMemoryStorage() && function_exists("apcu_store")) {
    apcu_store(GetGamestateStorageKey($gameName), $gamestateText, 600);
  }
  file_put_contents($filename, $gamestateText);

}

function ParseGamestate($filepath="./") {
  global $p1Deck, $p2Deck, $p3Deck, $p4Deck;
  global $p1Hand, $p2Hand, $p3Hand, $p4Hand;
  global $p1Discard, $p2Discard, $p3Discard, $p4Discard;
  global $p1Resources, $p2Resources, $p3Resources, $p4Resources;
  global $p1Leader, $p2Leader, $p3Leader, $p4Leader;
  global $p1Base, $p2Base, $p3Base, $p4Base;
  global $p1GroundArena, $p2GroundArena, $p3GroundArena, $p4GroundArena;
  global $p1SpaceArena, $p2SpaceArena, $p3SpaceArena, $p4SpaceArena;
  global $p1GlobalEffects, $p2GlobalEffects, $p3GlobalEffects, $p4GlobalEffects;
  global $p1DecisionQueue, $p2DecisionQueue, $p3DecisionQueue, $p4DecisionQueue;
  global $p1TempZone, $p2TempZone, $p3TempZone, $p4TempZone;
  global $p1Versions, $p2Versions, $p3Versions, $p4Versions;
  global $gTurnNumber;
  global $gFirstPlayer;
  global $gTurnPlayer;
  global $gCurrentPhase;
  global $gPhaseParameters;
  global $gFlashMessage;
  global $gDecisionQueueVariables;
  global $gInitiativeCounter;
  global $gSeatOrder;
  global $gLiveSeats;
  global $gBlastCounter;
  global $gPlanCounter;
  global $gMacroTurnIndex;
  global $gMacroGameIndex;
  global $gUniqueIDCounter;
  global $gEffectStack;
  global $gGameLog;
  global $gMatchReplayInitialState;
  global $gMatchReplayCommands;

  global $currentPlayer, $updateNumber, $gRandomCounter;
  global $objectDataIndices;

  InitializeGamestate();
  global $gameName;
  $filename = $filepath . "Games/$gameName/Gamestate.txt";
  $gamestateText = "";
  if(GamestateUsesMemoryStorage() && function_exists("apcu_fetch")) {
    $cachedGamestate = apcu_fetch(GetGamestateStorageKey($gameName));
    if($cachedGamestate !== false) $gamestateText = $cachedGamestate;
  }
  if($gamestateText === "" && is_file($filename)) {
    $gamestateText = file_get_contents($filename);
  }
  if($gamestateText === false || $gamestateText === "") return;
  $handler = fopen("php://temp", "r+");
  fwrite($handler, $gamestateText);
  rewind($handler);
  $currentPlayer = intval(fgets($handler));
  $updateNumber = intval(fgets($handler));
  while (!feof($handler)) {
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Deck(trim($line), 'Deck', 1, $i);
          array_push($p1Deck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Deck(trim($line), 'Deck', 2, $i);
          array_push($p2Deck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Deck(trim($line), 'Deck', 3, $i);
          array_push($p3Deck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Deck(trim($line), 'Deck', 4, $i);
          array_push($p4Deck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Hand(trim($line), 'Hand', 1, $i);
          array_push($p1Hand, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Hand(trim($line), 'Hand', 2, $i);
          array_push($p2Hand, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Hand(trim($line), 'Hand', 3, $i);
          array_push($p3Hand, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Hand(trim($line), 'Hand', 4, $i);
          array_push($p4Hand, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Discard(trim($line), 'Discard', 1, $i);
          array_push($p1Discard, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Discard(trim($line), 'Discard', 2, $i);
          array_push($p2Discard, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Discard(trim($line), 'Discard', 3, $i);
          array_push($p3Discard, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Discard(trim($line), 'Discard', 4, $i);
          array_push($p4Discard, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Resources(trim($line), 'Resources', 1, $i);
          array_push($p1Resources, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Resources(trim($line), 'Resources', 2, $i);
          array_push($p2Resources, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Resources(trim($line), 'Resources', 3, $i);
          array_push($p3Resources, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Resources(trim($line), 'Resources', 4, $i);
          array_push($p4Resources, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Leader(trim($line), 'Leader', 1, $i);
          array_push($p1Leader, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Leader(trim($line), 'Leader', 2, $i);
          array_push($p2Leader, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Leader(trim($line), 'Leader', 3, $i);
          array_push($p3Leader, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Leader(trim($line), 'Leader', 4, $i);
          array_push($p4Leader, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Base(trim($line), 'Base', 1, $i);
          array_push($p1Base, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Base(trim($line), 'Base', 2, $i);
          array_push($p2Base, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Base(trim($line), 'Base', 3, $i);
          array_push($p3Base, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Base(trim($line), 'Base', 4, $i);
          array_push($p4Base, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GroundArena(trim($line), 'GroundArena', 1, $i);
          array_push($p1GroundArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GroundArena(trim($line), 'GroundArena', 2, $i);
          array_push($p2GroundArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GroundArena(trim($line), 'GroundArena', 3, $i);
          array_push($p3GroundArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GroundArena(trim($line), 'GroundArena', 4, $i);
          array_push($p4GroundArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new SpaceArena(trim($line), 'SpaceArena', 1, $i);
          array_push($p1SpaceArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new SpaceArena(trim($line), 'SpaceArena', 2, $i);
          array_push($p2SpaceArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new SpaceArena(trim($line), 'SpaceArena', 3, $i);
          array_push($p3SpaceArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new SpaceArena(trim($line), 'SpaceArena', 4, $i);
          array_push($p4SpaceArena, $obj);
          $obj->BuildIndex();
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GlobalEffects(trim($line), 'GlobalEffects', 1, $i);
          array_push($p1GlobalEffects, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GlobalEffects(trim($line), 'GlobalEffects', 2, $i);
          array_push($p2GlobalEffects, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GlobalEffects(trim($line), 'GlobalEffects', 3, $i);
          array_push($p3GlobalEffects, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new GlobalEffects(trim($line), 'GlobalEffects', 4, $i);
          array_push($p4GlobalEffects, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new DecisionQueue(trim($line), 'DecisionQueue', 1, $i);
          array_push($p1DecisionQueue, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new DecisionQueue(trim($line), 'DecisionQueue', 2, $i);
          array_push($p2DecisionQueue, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new DecisionQueue(trim($line), 'DecisionQueue', 3, $i);
          array_push($p3DecisionQueue, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new DecisionQueue(trim($line), 'DecisionQueue', 4, $i);
          array_push($p4DecisionQueue, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new TempZone(trim($line), 'TempZone', 1, $i);
          array_push($p1TempZone, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new TempZone(trim($line), 'TempZone', 2, $i);
          array_push($p2TempZone, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new TempZone(trim($line), 'TempZone', 3, $i);
          array_push($p3TempZone, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new TempZone(trim($line), 'TempZone', 4, $i);
          array_push($p4TempZone, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Versions(trim($line), 'Versions', 1, $i);
          array_push($p1Versions, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Versions(trim($line), 'Versions', 2, $i);
          array_push($p2Versions, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Versions(trim($line), 'Versions', 3, $i);
          array_push($p3Versions, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Versions(trim($line), 'Versions', 4, $i);
          array_push($p4Versions, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gTurnNumber = intval(trim($line));
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gFirstPlayer = intval(trim($line));
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gTurnPlayer = intval(trim($line));
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gCurrentPhase = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gPhaseParameters = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gFlashMessage = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gDecisionQueueVariables = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gInitiativeCounter = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gSeatOrder = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gLiveSeats = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gBlastCounter = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gPlanCounter = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gMacroTurnIndex = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gMacroGameIndex = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gUniqueIDCounter = intval(trim($line));
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new EffectStack(trim($line), 'EffectStack', 0, $i);
          array_push($gEffectStack, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gGameLog = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gMatchReplayInitialState = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gMatchReplayCommands = trim($line);
    }
    $line = fgets($handler);
    if ($line !== false) {
      $gRandomCounter = intval(trim($line));
    }
    $line = fgets($handler);
    if ($line !== false) { global $gTelemetry; $gTelemetry = trim($line); }
  }
  fclose($handler);

}

function LoadVersion($playerID, $versionNum = -1) {
  $versions = &GetVersions($playerID);
  if($versionNum == -1) $versionNum = count($versions) - 1;
  if($versionNum == -1) return;
//No versions to load
  $versionNum = intval($versionNum);
  $copyFrom = $versions[$versionNum];
  $zones = explode("<v0>", $copyFrom->Version);
  if(count($zones) > 0) {
    $data = str_replace("<v2>", " ", $zones[0]);
  global $gTurnPlayer;
  $gTurnPlayer = $data;
  }
  if(count($zones) > 1) {
    $data = str_replace("<v2>", " ", $zones[1]);
  global $gFirstPlayer;
  $gFirstPlayer = $data;
  }
  if(count($zones) > 2) {
    $data = str_replace("<v2>", " ", $zones[2]);
  global $gCurrentPhase;
  $gCurrentPhase = $data;
  }
  if(count($zones) > 3) {
    $data = str_replace("<v2>", " ", $zones[3]);
  global $gTurnNumber;
  $gTurnNumber = $data;
  }
  if(count($zones) > 4) {
    $data = str_replace("<v2>", " ", $zones[4]);
  global $gPhaseParameters;
  $gPhaseParameters = $data;
  }
  if(count($zones) > 5) {
    $data = str_replace("<v2>", " ", $zones[5]);
  global $gDecisionQueueVariables;
  $gDecisionQueueVariables = $data;
  }
  if(count($zones) > 6) {
    $data = explode("<v1>", $zones[6]);
    if(count($data) > 0) {
      $zone = &GetZone("EffectStack");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'EffectStack';
        $controller = 0;
        array_push($zone, new EffectStack($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 7) {
    $data = str_replace("<v2>", " ", $zones[7]);
  global $gMacroTurnIndex;
  $gMacroTurnIndex = $data;
  }
  if(count($zones) > 8) {
    $data = str_replace("<v2>", " ", $zones[8]);
  global $gMacroGameIndex;
  $gMacroGameIndex = $data;
  }
  if(count($zones) > 9) {
    $data = str_replace("<v2>", " ", $zones[9]);
  global $gInitiativeCounter;
  $gInitiativeCounter = $data;
  }
  if(count($zones) > 10) {
    $data = str_replace("<v2>", " ", $zones[10]);
  global $gSeatOrder;
  $gSeatOrder = $data;
  }
  if(count($zones) > 11) {
    $data = str_replace("<v2>", " ", $zones[11]);
  global $gLiveSeats;
  $gLiveSeats = $data;
  }
  if(count($zones) > 12) {
    $data = str_replace("<v2>", " ", $zones[12]);
  global $gBlastCounter;
  $gBlastCounter = $data;
  }
  if(count($zones) > 13) {
    $data = str_replace("<v2>", " ", $zones[13]);
  global $gPlanCounter;
  $gPlanCounter = $data;
  }
  if(count($zones) > 14) {
    $data = str_replace("<v2>", " ", $zones[14]);
  global $gGameLog;
  $gGameLog = $data;
  }
  if(count($zones) > 15) {
    $data = explode("<v1>", $zones[15]);
    if(count($data) > 0) {
      $zone = &GetZone("p1DecisionQueue");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'DecisionQueue';
        $controller = 1;
        array_push($zone, new DecisionQueue($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 16) {
    $data = explode("<v1>", $zones[16]);
    if(count($data) > 0) {
      $zone = &GetZone("p1GlobalEffects");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GlobalEffects';
        $controller = 1;
        array_push($zone, new GlobalEffects($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 17) {
    $data = explode("<v1>", $zones[17]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Hand");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Hand';
        $controller = 1;
        array_push($zone, new Hand($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 18) {
    $data = explode("<v1>", $zones[18]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Discard");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Discard';
        $controller = 1;
        array_push($zone, new Discard($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 19) {
    $data = explode("<v1>", $zones[19]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Deck");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Deck';
        $controller = 1;
        array_push($zone, new Deck($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 20) {
    $data = explode("<v1>", $zones[20]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Leader");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Leader';
        $controller = 1;
        array_push($zone, new Leader($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 21) {
    $data = explode("<v1>", $zones[21]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Base");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Base';
        $controller = 1;
        array_push($zone, new Base($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 22) {
    $data = explode("<v1>", $zones[22]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Resources");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Resources';
        $controller = 1;
        array_push($zone, new Resources($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 23) {
    $data = explode("<v1>", $zones[23]);
    if(count($data) > 0) {
      $zone = &GetZone("p1GroundArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GroundArena';
        $controller = 1;
        array_push($zone, new GroundArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 24) {
    $data = explode("<v1>", $zones[24]);
    if(count($data) > 0) {
      $zone = &GetZone("p1SpaceArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'SpaceArena';
        $controller = 1;
        array_push($zone, new SpaceArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 25) {
    $data = explode("<v1>", $zones[25]);
    if(count($data) > 0) {
      $zone = &GetZone("p1TempZone");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'TempZone';
        $controller = 1;
        array_push($zone, new TempZone($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 26) {
    $data = explode("<v1>", $zones[26]);
    if(count($data) > 0) {
      $zone = &GetZone("p2DecisionQueue");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'DecisionQueue';
        $controller = 2;
        array_push($zone, new DecisionQueue($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 27) {
    $data = explode("<v1>", $zones[27]);
    if(count($data) > 0) {
      $zone = &GetZone("p2GlobalEffects");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GlobalEffects';
        $controller = 2;
        array_push($zone, new GlobalEffects($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 28) {
    $data = explode("<v1>", $zones[28]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Hand");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Hand';
        $controller = 2;
        array_push($zone, new Hand($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 29) {
    $data = explode("<v1>", $zones[29]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Discard");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Discard';
        $controller = 2;
        array_push($zone, new Discard($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 30) {
    $data = explode("<v1>", $zones[30]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Deck");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Deck';
        $controller = 2;
        array_push($zone, new Deck($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 31) {
    $data = explode("<v1>", $zones[31]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Leader");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Leader';
        $controller = 2;
        array_push($zone, new Leader($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 32) {
    $data = explode("<v1>", $zones[32]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Base");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Base';
        $controller = 2;
        array_push($zone, new Base($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 33) {
    $data = explode("<v1>", $zones[33]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Resources");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Resources';
        $controller = 2;
        array_push($zone, new Resources($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 34) {
    $data = explode("<v1>", $zones[34]);
    if(count($data) > 0) {
      $zone = &GetZone("p2GroundArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GroundArena';
        $controller = 2;
        array_push($zone, new GroundArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 35) {
    $data = explode("<v1>", $zones[35]);
    if(count($data) > 0) {
      $zone = &GetZone("p2SpaceArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'SpaceArena';
        $controller = 2;
        array_push($zone, new SpaceArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 36) {
    $data = explode("<v1>", $zones[36]);
    if(count($data) > 0) {
      $zone = &GetZone("p2TempZone");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'TempZone';
        $controller = 2;
        array_push($zone, new TempZone($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 37) {
    $data = explode("<v1>", $zones[37]);
    if(count($data) > 0) {
      $zone = &GetZone("p3DecisionQueue");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'DecisionQueue';
        $controller = 0;
        array_push($zone, new DecisionQueue($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 38) {
    $data = explode("<v1>", $zones[38]);
    if(count($data) > 0) {
      $zone = &GetZone("p3GlobalEffects");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GlobalEffects';
        $controller = 0;
        array_push($zone, new GlobalEffects($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 39) {
    $data = explode("<v1>", $zones[39]);
    if(count($data) > 0) {
      $zone = &GetZone("p3Hand");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Hand';
        $controller = 0;
        array_push($zone, new Hand($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 40) {
    $data = explode("<v1>", $zones[40]);
    if(count($data) > 0) {
      $zone = &GetZone("p3Discard");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Discard';
        $controller = 0;
        array_push($zone, new Discard($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 41) {
    $data = explode("<v1>", $zones[41]);
    if(count($data) > 0) {
      $zone = &GetZone("p3Deck");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Deck';
        $controller = 0;
        array_push($zone, new Deck($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 42) {
    $data = explode("<v1>", $zones[42]);
    if(count($data) > 0) {
      $zone = &GetZone("p3Leader");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Leader';
        $controller = 0;
        array_push($zone, new Leader($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 43) {
    $data = explode("<v1>", $zones[43]);
    if(count($data) > 0) {
      $zone = &GetZone("p3Base");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Base';
        $controller = 0;
        array_push($zone, new Base($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 44) {
    $data = explode("<v1>", $zones[44]);
    if(count($data) > 0) {
      $zone = &GetZone("p3Resources");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Resources';
        $controller = 0;
        array_push($zone, new Resources($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 45) {
    $data = explode("<v1>", $zones[45]);
    if(count($data) > 0) {
      $zone = &GetZone("p3GroundArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GroundArena';
        $controller = 0;
        array_push($zone, new GroundArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 46) {
    $data = explode("<v1>", $zones[46]);
    if(count($data) > 0) {
      $zone = &GetZone("p3SpaceArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'SpaceArena';
        $controller = 0;
        array_push($zone, new SpaceArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 47) {
    $data = explode("<v1>", $zones[47]);
    if(count($data) > 0) {
      $zone = &GetZone("p3TempZone");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'TempZone';
        $controller = 0;
        array_push($zone, new TempZone($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 48) {
    $data = explode("<v1>", $zones[48]);
    if(count($data) > 0) {
      $zone = &GetZone("p4DecisionQueue");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'DecisionQueue';
        $controller = 0;
        array_push($zone, new DecisionQueue($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 49) {
    $data = explode("<v1>", $zones[49]);
    if(count($data) > 0) {
      $zone = &GetZone("p4GlobalEffects");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GlobalEffects';
        $controller = 0;
        array_push($zone, new GlobalEffects($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 50) {
    $data = explode("<v1>", $zones[50]);
    if(count($data) > 0) {
      $zone = &GetZone("p4Hand");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Hand';
        $controller = 0;
        array_push($zone, new Hand($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 51) {
    $data = explode("<v1>", $zones[51]);
    if(count($data) > 0) {
      $zone = &GetZone("p4Discard");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Discard';
        $controller = 0;
        array_push($zone, new Discard($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 52) {
    $data = explode("<v1>", $zones[52]);
    if(count($data) > 0) {
      $zone = &GetZone("p4Deck");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Deck';
        $controller = 0;
        array_push($zone, new Deck($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 53) {
    $data = explode("<v1>", $zones[53]);
    if(count($data) > 0) {
      $zone = &GetZone("p4Leader");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Leader';
        $controller = 0;
        array_push($zone, new Leader($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 54) {
    $data = explode("<v1>", $zones[54]);
    if(count($data) > 0) {
      $zone = &GetZone("p4Base");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Base';
        $controller = 0;
        array_push($zone, new Base($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 55) {
    $data = explode("<v1>", $zones[55]);
    if(count($data) > 0) {
      $zone = &GetZone("p4Resources");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'Resources';
        $controller = 0;
        array_push($zone, new Resources($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 56) {
    $data = explode("<v1>", $zones[56]);
    if(count($data) > 0) {
      $zone = &GetZone("p4GroundArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'GroundArena';
        $controller = 0;
        array_push($zone, new GroundArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 57) {
    $data = explode("<v1>", $zones[57]);
    if(count($data) > 0) {
      $zone = &GetZone("p4SpaceArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'SpaceArena';
        $controller = 0;
        array_push($zone, new SpaceArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 58) {
    $data = explode("<v1>", $zones[58]);
    if(count($data) > 0) {
      $zone = &GetZone("p4TempZone");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'TempZone';
        $controller = 0;
        array_push($zone, new TempZone($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 59) {
    $data = str_replace("<v2>", " ", $zones[59]);
  global $gRandomCounter;
  $gRandomCounter = intval($data);
  }
}

function SaveVersion($playerID, $name = "") {
  $zones = Versions::GetSerializedZones();
  global $gRandomCounter;
  $zones .= "<v0>" . $gRandomCounter;
  $existingVersions = &GetVersions($playerID);
  $nextNum = 0;
  foreach($existingVersions as $v) {
    if(isset($v->DisplayNumber) && $v->DisplayNumber >= $nextNum) $nextNum = $v->DisplayNumber + 1;
  }
  $namePrefix = (strlen($name) > 0 ? $name . '<vname>' : '');
  AddVersions($playerID, $nextNum . ':' . $namePrefix . $zones);
}

?>