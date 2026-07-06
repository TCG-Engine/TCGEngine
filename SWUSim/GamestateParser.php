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
  global $p1Deck, $p2Deck;
  global $p1Hand, $p2Hand;
  global $p1Discard, $p2Discard;
  global $p1Resources, $p2Resources;
  global $p1Leader, $p2Leader;
  global $p1Base, $p2Base;
  global $p1GroundArena, $p2GroundArena;
  global $p1SpaceArena, $p2SpaceArena;
  global $p1GlobalEffects, $p2GlobalEffects;
  global $p1DecisionQueue, $p2DecisionQueue;
  global $p1TempZone, $p2TempZone;
  global $p1Versions, $p2Versions;
  global $gTurnNumber;
  global $gFirstPlayer;
  global $gTurnPlayer;
  global $gCurrentPhase;
  global $gPhaseParameters;
  global $gFlashMessage;
  global $gDecisionQueueVariables;
  global $gInitiativeCounter;
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
  $p1Hand = [];
  $p2Hand = [];
  $p1Discard = [];
  $p2Discard = [];
  $p1Resources = [];
  $p2Resources = [];
  $p1Leader = [];
  $p2Leader = [];
  $p1Base = [];
  $p2Base = [];
  $p1GroundArena = [];
  $p2GroundArena = [];
  $p1SpaceArena = [];
  $p2SpaceArena = [];
  $p1GlobalEffects = [];
  $p2GlobalEffects = [];
  $p1DecisionQueue = [];
  $p2DecisionQueue = [];
  $p1TempZone = [];
  $p2TempZone = [];
  $p1Versions = [];
  $p2Versions = [];
  $gTurnNumber = 0;
  $gFirstPlayer = 1;
  $gTurnPlayer = 1;
  $gCurrentPhase = "-";
  $gPhaseParameters = "-";
  $gFlashMessage = "-";
  $gDecisionQueueVariables = "-";
  $gInitiativeCounter = "P1_UNCLAIMED";
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
  global $p1Deck, $p2Deck;
  global $p1Hand, $p2Hand;
  global $p1Discard, $p2Discard;
  global $p1Resources, $p2Resources;
  global $p1Leader, $p2Leader;
  global $p1Base, $p2Base;
  global $p1GroundArena, $p2GroundArena;
  global $p1SpaceArena, $p2SpaceArena;
  global $p1GlobalEffects, $p2GlobalEffects;
  global $p1DecisionQueue, $p2DecisionQueue;
  global $p1TempZone, $p2TempZone;
  global $p1Versions, $p2Versions;
  global $gTurnNumber;
  global $gFirstPlayer;
  global $gTurnPlayer;
  global $gCurrentPhase;
  global $gPhaseParameters;
  global $gFlashMessage;
  global $gDecisionQueueVariables;
  global $gInitiativeCounter;
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
  $writeZone($p1Hand);
  $writeZone($p2Hand);
  $writeZone($p1Discard);
  $writeZone($p2Discard);
  $writeZone($p1Resources);
  $writeZone($p2Resources);
  $writeZone($p1Leader);
  $writeZone($p2Leader);
  $writeZone($p1Base);
  $writeZone($p2Base);
  $writeZone($p1GroundArena);
  $writeZone($p2GroundArena);
  $writeZone($p1SpaceArena);
  $writeZone($p2SpaceArena);
  $writeZone($p1GlobalEffects);
  $writeZone($p2GlobalEffects);
  $writeZone($p1DecisionQueue);
  $writeZone($p2DecisionQueue);
  $writeZone($p1TempZone);
  $writeZone($p2TempZone);
  $writeZone($p1Versions);
  $writeZone($p2Versions);
  $gamestateText .= $gTurnNumber . "\r\n";
  $gamestateText .= $gFirstPlayer . "\r\n";
  $gamestateText .= $gTurnPlayer . "\r\n";
  $gamestateText .= $gCurrentPhase . "\r\n";
  $gamestateText .= $gPhaseParameters . "\r\n";
  $gamestateText .= $gFlashMessage . "\r\n";
  $gamestateText .= $gDecisionQueueVariables . "\r\n";
  $gamestateText .= $gInitiativeCounter . "\r\n";
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
  global $p1Deck, $p2Deck;
  global $p1Hand, $p2Hand;
  global $p1Discard, $p2Discard;
  global $p1Resources, $p2Resources;
  global $p1Leader, $p2Leader;
  global $p1Base, $p2Base;
  global $p1GroundArena, $p2GroundArena;
  global $p1SpaceArena, $p2SpaceArena;
  global $p1GlobalEffects, $p2GlobalEffects;
  global $p1DecisionQueue, $p2DecisionQueue;
  global $p1TempZone, $p2TempZone;
  global $p1Versions, $p2Versions;
  global $gTurnNumber;
  global $gFirstPlayer;
  global $gTurnPlayer;
  global $gCurrentPhase;
  global $gPhaseParameters;
  global $gFlashMessage;
  global $gDecisionQueueVariables;
  global $gInitiativeCounter;
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
  global $gGameLog;
  $gGameLog = $data;
  }
  if(count($zones) > 11) {
    $data = explode("<v1>", $zones[11]);
    if(count($data) > 0) {
      $zone = &GetZone("p1DecisionQueue");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1DecisionQueue';
        $controller = 0;
        array_push($zone, new DecisionQueue($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 12) {
    $data = explode("<v1>", $zones[12]);
    if(count($data) > 0) {
      $zone = &GetZone("p1GlobalEffects");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1GlobalEffects';
        $controller = 0;
        array_push($zone, new GlobalEffects($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 13) {
    $data = explode("<v1>", $zones[13]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Hand");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1Hand';
        $controller = 0;
        array_push($zone, new Hand($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 14) {
    $data = explode("<v1>", $zones[14]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Discard");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1Discard';
        $controller = 0;
        array_push($zone, new Discard($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 15) {
    $data = explode("<v1>", $zones[15]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Deck");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1Deck';
        $controller = 0;
        array_push($zone, new Deck($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 16) {
    $data = explode("<v1>", $zones[16]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Leader");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1Leader';
        $controller = 0;
        array_push($zone, new Leader($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 17) {
    $data = explode("<v1>", $zones[17]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Base");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1Base';
        $controller = 0;
        array_push($zone, new Base($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 18) {
    $data = explode("<v1>", $zones[18]);
    if(count($data) > 0) {
      $zone = &GetZone("p1Resources");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1Resources';
        $controller = 0;
        array_push($zone, new Resources($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 19) {
    $data = explode("<v1>", $zones[19]);
    if(count($data) > 0) {
      $zone = &GetZone("p1GroundArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1GroundArena';
        $controller = 0;
        array_push($zone, new GroundArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 20) {
    $data = explode("<v1>", $zones[20]);
    if(count($data) > 0) {
      $zone = &GetZone("p1SpaceArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1SpaceArena';
        $controller = 0;
        array_push($zone, new SpaceArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 21) {
    $data = explode("<v1>", $zones[21]);
    if(count($data) > 0) {
      $zone = &GetZone("p1TempZone");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p1TempZone';
        $controller = 0;
        array_push($zone, new TempZone($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 22) {
    $data = explode("<v1>", $zones[22]);
    if(count($data) > 0) {
      $zone = &GetZone("p2DecisionQueue");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2DecisionQueue';
        $controller = 0;
        array_push($zone, new DecisionQueue($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 23) {
    $data = explode("<v1>", $zones[23]);
    if(count($data) > 0) {
      $zone = &GetZone("p2GlobalEffects");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2GlobalEffects';
        $controller = 0;
        array_push($zone, new GlobalEffects($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 24) {
    $data = explode("<v1>", $zones[24]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Hand");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2Hand';
        $controller = 0;
        array_push($zone, new Hand($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 25) {
    $data = explode("<v1>", $zones[25]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Discard");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2Discard';
        $controller = 0;
        array_push($zone, new Discard($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 26) {
    $data = explode("<v1>", $zones[26]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Deck");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2Deck';
        $controller = 0;
        array_push($zone, new Deck($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 27) {
    $data = explode("<v1>", $zones[27]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Leader");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2Leader';
        $controller = 0;
        array_push($zone, new Leader($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 28) {
    $data = explode("<v1>", $zones[28]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Base");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2Base';
        $controller = 0;
        array_push($zone, new Base($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 29) {
    $data = explode("<v1>", $zones[29]);
    if(count($data) > 0) {
      $zone = &GetZone("p2Resources");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2Resources';
        $controller = 0;
        array_push($zone, new Resources($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 30) {
    $data = explode("<v1>", $zones[30]);
    if(count($data) > 0) {
      $zone = &GetZone("p2GroundArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2GroundArena';
        $controller = 0;
        array_push($zone, new GroundArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 31) {
    $data = explode("<v1>", $zones[31]);
    if(count($data) > 0) {
      $zone = &GetZone("p2SpaceArena");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2SpaceArena';
        $controller = 0;
        array_push($zone, new SpaceArena($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 32) {
    $data = explode("<v1>", $zones[32]);
    if(count($data) > 0) {
      $zone = &GetZone("p2TempZone");
      $zone = [];
      for($j=0; $j<count($data); ++$j) {
        if(trim($data[$j]) == "") continue;
        $data[$j] = str_replace("<v2>", " ", $data[$j]);
        $location = 'p2TempZone';
        $controller = 0;
        array_push($zone, new TempZone($data[$j], $location, $controller));
      }
    }
  }
  if(count($zones) > 33) {
    $data = str_replace("<v2>", " ", $zones[33]);
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