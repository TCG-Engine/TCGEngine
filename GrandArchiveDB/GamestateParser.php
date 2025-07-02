<?php
include __DIR__ . '/Custom/DeckValidation.php';
include __DIR__ . '/Custom/CustomInput.php';
function GetEditAuth() {
  return "AssetOwner";
}

function InitializeGamestate() {
  global $p1Commander, $p2Commander;
  global $p1ReserveDeck, $p2ReserveDeck;
  global $p1MainDeck, $p2MainDeck;
  global $p1CardPane, $p2CardPane;
  global $p1Commanders, $p2Commanders;
  global $p1Reserves, $p2Reserves;
  global $p1Cards, $p2Cards;
  global $p1NumReserve, $p2NumReserve;
  global $p1CountsDisplay, $p2CountsDisplay;
  global $p1Sort, $p2Sort;
  global $p1CardNotes, $p2CardNotes;
  global $p1Versions, $p2Versions;

  global $currentPlayer, $updateNumber;

  $p1Commander = [];
  $p2Commander = [];
  $p1ReserveDeck = [];
  $p2ReserveDeck = [];
  $p1MainDeck = [];
  $p2MainDeck = [];
  $p1CardPane = [];
  $p2CardPane = [];
  $p1Commanders = [];
  $p2Commanders = [];
  $p1Reserves = [];
  $p2Reserves = [];
  $p1Cards = [];
  $p2Cards = [];
  $p1NumReserve = [];
  $p2NumReserve = [];
  $p1CountsDisplay = [];
  $p2CountsDisplay = [];
  $p1Sort = [];
  $p2Sort = [];
  $p1CardNotes = [];
  $p2CardNotes = [];
  $p1Versions = [];
  $p2Versions = [];
  $currentPlayer = 1;
  $updateNumber = 1;
}

function WriteGamestate($filepath="./") {
  global $p1Commander, $p2Commander;
  global $p1ReserveDeck, $p2ReserveDeck;
  global $p1MainDeck, $p2MainDeck;
  global $p1CardPane, $p2CardPane;
  global $p1Commanders, $p2Commanders;
  global $p1Reserves, $p2Reserves;
  global $p1Cards, $p2Cards;
  global $p1NumReserve, $p2NumReserve;
  global $p1CountsDisplay, $p2CountsDisplay;
  global $p1Sort, $p2Sort;
  global $p1CardNotes, $p2CardNotes;
  global $p1Versions, $p2Versions;

  global $currentPlayer, $updateNumber;

  global $gameName;
  $filename = $filepath . "Games/$gameName/Gamestate.txt";
  $handler = fopen($filename, "w");
  fwrite($handler, $currentPlayer . "\r\n");
  fwrite($handler, $updateNumber . "\r\n");
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1Commander); ++$i) {
    if($p1Commander[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1Commander[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2Commander); ++$i) {
    if($p2Commander[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2Commander[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1ReserveDeck); ++$i) {
    if($p1ReserveDeck[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1ReserveDeck[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2ReserveDeck); ++$i) {
    if($p2ReserveDeck[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2ReserveDeck[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1MainDeck); ++$i) {
    if($p1MainDeck[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1MainDeck[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2MainDeck); ++$i) {
    if($p2MainDeck[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2MainDeck[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1CardPane); ++$i) {
    if($p1CardPane[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1CardPane[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2CardPane); ++$i) {
    if($p2CardPane[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2CardPane[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1Commanders); ++$i) {
    if($p1Commanders[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1Commanders[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2Commanders); ++$i) {
    if($p2Commanders[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2Commanders[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1Reserves); ++$i) {
    if($p1Reserves[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1Reserves[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2Reserves); ++$i) {
    if($p2Reserves[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2Reserves[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1Cards); ++$i) {
    if($p1Cards[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1Cards[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2Cards); ++$i) {
    if($p2Cards[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2Cards[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1NumReserve); ++$i) {
    if($p1NumReserve[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1NumReserve[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2NumReserve); ++$i) {
    if($p2NumReserve[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2NumReserve[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1CountsDisplay); ++$i) {
    if($p1CountsDisplay[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1CountsDisplay[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2CountsDisplay); ++$i) {
    if($p2CountsDisplay[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2CountsDisplay[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1Sort); ++$i) {
    if($p1Sort[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1Sort[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2Sort); ++$i) {
    if($p2Sort[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2Sort[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1CardNotes); ++$i) {
    if($p1CardNotes[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1CardNotes[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2CardNotes); ++$i) {
    if($p2CardNotes[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2CardNotes[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p1Versions); ++$i) {
    if($p1Versions[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p1Versions[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);
  $zoneText = "";
  $count = 0;
  for($i=0; $i<count($p2Versions); ++$i) {
    if($p2Versions[$i]->Removed()) continue;
    ++$count;
    $zoneText .= trim($p2Versions[$i]->Serialize()) . "\r\n";
  }
  fwrite($handler, $count . "\r\n");
  fwrite($handler, $zoneText);

}

function ParseGamestate($filepath="./") {
  global $p1Commander, $p2Commander;
  global $p1ReserveDeck, $p2ReserveDeck;
  global $p1MainDeck, $p2MainDeck;
  global $p1CardPane, $p2CardPane;
  global $p1Commanders, $p2Commanders;
  global $p1Reserves, $p2Reserves;
  global $p1Cards, $p2Cards;
  global $p1NumReserve, $p2NumReserve;
  global $p1CountsDisplay, $p2CountsDisplay;
  global $p1Sort, $p2Sort;
  global $p1CardNotes, $p2CardNotes;
  global $p1Versions, $p2Versions;

  global $currentPlayer, $updateNumber;

  InitializeGamestate();
  global $gameName;
  $filename = $filepath . "Games/$gameName/Gamestate.txt";
  $handler = fopen($filename, "r");
  $currentPlayer = intval(fgets($handler));
  $updateNumber = intval(fgets($handler));
  while (!feof($handler)) {
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Commander(trim($line));
          array_push($p1Commander, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Commander(trim($line));
          array_push($p2Commander, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new ReserveDeck(trim($line));
          array_push($p1ReserveDeck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new ReserveDeck(trim($line));
          array_push($p2ReserveDeck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new MainDeck(trim($line));
          array_push($p1MainDeck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new MainDeck(trim($line));
          array_push($p2MainDeck, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new CardPane(trim($line));
          array_push($p1CardPane, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new CardPane(trim($line));
          array_push($p2CardPane, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Commanders(trim($line));
          array_push($p1Commanders, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Commanders(trim($line));
          array_push($p2Commanders, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Reserves(trim($line));
          array_push($p1Reserves, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Reserves(trim($line));
          array_push($p2Reserves, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Cards(trim($line));
          array_push($p1Cards, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Cards(trim($line));
          array_push($p2Cards, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new NumReserve(trim($line));
          array_push($p1NumReserve, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new NumReserve(trim($line));
          array_push($p2NumReserve, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new CountsDisplay(trim($line));
          array_push($p1CountsDisplay, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new CountsDisplay(trim($line));
          array_push($p2CountsDisplay, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Sort(trim($line));
          array_push($p1Sort, $obj);
        }
      }
    }
    if(count($p1Sort) == 0) array_push($p1Sort, new Sort(0));
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Sort(trim($line));
          array_push($p2Sort, $obj);
        }
      }
    }
    if(count($p2Sort) == 0) array_push($p2Sort, new Sort(0));
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new CardNotes(trim($line));
          array_push($p1CardNotes, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new CardNotes(trim($line));
          array_push($p2CardNotes, $obj);
        }
      }
    }
    $line = fgets($handler);
    if ($line !== false) {
      $num = intval($line);
      for($i=0; $i<$num; ++$i) {
        $line = fgets($handler);
        if ($line !== false) {
          $obj = new Versions(trim($line));
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
          $obj = new Versions(trim($line));
          array_push($p2Versions, $obj);
        }
      }
    }
  }
  fclose($handler);

}

?>