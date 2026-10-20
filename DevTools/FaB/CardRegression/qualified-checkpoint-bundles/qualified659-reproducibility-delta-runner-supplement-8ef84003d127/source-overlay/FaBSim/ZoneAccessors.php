<?php
$generatedModuleData = array (
  'Versions' => 'TurnPlayer,CurrentPhase,TurnNumber,PhaseParameters,SeatOrder,LiveSeats,p1Hero,p1Weapons,p1Equipment,p1Arena,p1CombatChain,p1Deck,p1Hand,p1Arsenal,p1Graveyard,p1Banish,p1Soul,p1Pitch,p1Inventory,p1Temp,p1DecisionQueue,p1Health,p1Resources,p1Chi,p1ActionPoints,p1ShortcutPreferences,p2Hero,p2Weapons,p2Equipment,p2Arena,p2CombatChain,p2Deck,p2Hand,p2Arsenal,p2Graveyard,p2Banish,p2Soul,p2Pitch,p2Inventory,p2Temp,p2DecisionQueue,p2Health,p2Resources,p2Chi,p2ActionPoints,p2ShortcutPreferences,p3Hero,p3Weapons,p3Equipment,p3Arena,p3CombatChain,p3Deck,p3Hand,p3Arsenal,p3Graveyard,p3Banish,p3Soul,p3Pitch,p3Inventory,p3Temp,p3DecisionQueue,p3Health,p3Resources,p3Chi,p3ActionPoints,p3ShortcutPreferences,p4Hero,p4Weapons,p4Equipment,p4Arena,p4CombatChain,p4Deck,p4Hand,p4Arsenal,p4Graveyard,p4Banish,p4Soul,p4Pitch,p4Inventory,p4Temp,p4DecisionQueue,p4Health,p4Resources,p4Chi,p4ActionPoints,p4ShortcutPreferences,DecisionQueueVariables,Stack,PriorityPlayer,ConsecutivePasses,GameState,Winner,MacroTurnIndex,MacroGameIndex,UniqueIDCounter,RandomCounter',
  'UniqueID' => 'Hero,UniqueID,UniqueIDCounter;Weapons,UniqueID,UniqueIDCounter;Equipment,UniqueID,UniqueIDCounter;Arena,UniqueID,UniqueIDCounter;CombatChain,UniqueID,UniqueIDCounter;Deck,UniqueID,UniqueIDCounter;Hand,UniqueID,UniqueIDCounter;Arsenal,UniqueID,UniqueIDCounter;Graveyard,UniqueID,UniqueIDCounter;Banish,UniqueID,UniqueIDCounter;Soul,UniqueID,UniqueIDCounter;Pitch,UniqueID,UniqueIDCounter;Inventory,UniqueID,UniqueIDCounter;Temp,UniqueID,UniqueIDCounter;Stack,UniqueID,UniqueIDCounter',
  'ShortcutWindows' => '{"BLOCK":{"label":"Declare Blocks","default":false,"group":"Combat","order":10},"ATTACK_REACTION":{"label":"Attack Reactions (Attacking)","default":false,"group":"Combat","order":20},"DEFENSE_REACTION":{"label":"Defense Reactions (Defending)","default":false,"group":"Combat","order":30},"ACTION_PRIORITY":{"label":"Action Phase Priority","default":true,"group":"Priority","order":40},"INSTANT_PRIORITY":{"label":"Stack Responses","default":true,"group":"Priority","order":50},"ATTACK_PRIORITY":{"label":"Before Blocks","default":true,"group":"Priority","order":60},"DEFEND_PRIORITY":{"label":"After Blocks","default":true,"group":"Priority","order":70},"OTHER_REACTION":{"label":"Reactions (Not in Combat)","default":true,"group":"Priority","order":80},"DAMAGE_PRIORITY":{"label":"After Damage","default":true,"group":"Priority","order":90},"RESOLUTION_PRIORITY":{"label":"Combat Resolution","default":true,"group":"Priority","order":100},"END_PHASE":{"label":"End of Turn Priority","default":true,"group":"Priority","order":110}}',
);
function GetModuleConfig($name) {
  global $generatedModuleData;
  return isset($generatedModuleData[$name]) ? $generatedModuleData[$name] : null;
}

function GetAllModuleConfigs() {
  global $generatedModuleData;
  return $generatedModuleData;
}

function &GetHero($player) {
  global $p1Hero, $p2Hero, $p3Hero, $p4Hero;
  switch ($player) {
    case 1: return $p1Hero;
    case 2: return $p2Hero;
    case 3: return $p3Hero;
    case 4: return $p4Hero;
    default: return $p1Hero;
  }
}

function AddHero($player, $CardID="-", $Status=2, $Owner=0, $Controller=0, $Damage=0, $TurnEffects="-", $Counters="-", $sourceObject=null) {
  $zoneObj = new Hero($CardID . ' ' . $Status . ' ' . $Owner . ' ' . $Controller . ' ' . $Damage . ' ' . 0 . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters), 'Hero', $player);
  $zone = &GetHero($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetWeapons($player) {
  global $p1Weapons, $p2Weapons, $p3Weapons, $p4Weapons;
  switch ($player) {
    case 1: return $p1Weapons;
    case 2: return $p2Weapons;
    case 3: return $p3Weapons;
    case 4: return $p4Weapons;
    default: return $p1Weapons;
  }
}

function AddWeapons($player, $CardID="-", $Status=2, $Owner=0, $Controller=0, $Damage=0, $TurnEffects="-", $Counters="-", $sourceObject=null) {
  $zoneObj = new Weapons($CardID . ' ' . $Status . ' ' . $Owner . ' ' . $Controller . ' ' . $Damage . ' ' . 0 . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters), 'Weapons', $player);
  $zone = &GetWeapons($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetEquipment($player) {
  global $p1Equipment, $p2Equipment, $p3Equipment, $p4Equipment;
  switch ($player) {
    case 1: return $p1Equipment;
    case 2: return $p2Equipment;
    case 3: return $p3Equipment;
    case 4: return $p4Equipment;
    default: return $p1Equipment;
  }
}

function AddEquipment($player, $CardID="-", $Status=2, $FaceDown=0, $Owner=0, $Controller=0, $Damage=0, $TurnEffects="-", $Counters="-", $sourceObject=null) {
  $zoneObj = new Equipment($CardID . ' ' . $Status . ' ' . $FaceDown . ' ' . $Owner . ' ' . $Controller . ' ' . $Damage . ' ' . 0 . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters), 'Equipment', $player);
  $zone = &GetEquipment($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetArena($player) {
  global $p1Arena, $p2Arena, $p3Arena, $p4Arena;
  switch ($player) {
    case 1: return $p1Arena;
    case 2: return $p2Arena;
    case 3: return $p3Arena;
    case 4: return $p4Arena;
    default: return $p1Arena;
  }
}

function AddArena($player, $CardID="-", $Status=2, $Owner=0, $Controller=0, $Damage=0, $TurnEffects="-", $Counters="-", $sourceObject=null) {
  $zoneObj = new Arena($CardID . ' ' . $Status . ' ' . $Owner . ' ' . $Controller . ' ' . $Damage . ' ' . 0 . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters), 'Arena', $player);
  $zone = &GetArena($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetCombatChain($player) {
  global $p1CombatChain, $p2CombatChain, $p3CombatChain, $p4CombatChain;
  switch ($player) {
    case 1: return $p1CombatChain;
    case 2: return $p2CombatChain;
    case 3: return $p3CombatChain;
    case 4: return $p4CombatChain;
    default: return $p1CombatChain;
  }
}

function AddCombatChain($player, $CardID="-", $Status=2, $Owner=0, $Controller=0, $Damage=0, $TurnEffects="-", $Counters="-", $Role="", $ChainLink=0, $FromZone="", $sourceObject=null) {
  $zoneObj = new CombatChain($CardID . ' ' . $Status . ' ' . $Owner . ' ' . $Controller . ' ' . $Damage . ' ' . 0 . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters) . ' ' . $Role . ' ' . $ChainLink . ' ' . $FromZone, 'CombatChain', $player);
  $zone = &GetCombatChain($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetDeck($player) {
  global $p1Deck, $p2Deck, $p3Deck, $p4Deck;
  switch ($player) {
    case 1: return $p1Deck;
    case 2: return $p2Deck;
    case 3: return $p3Deck;
    case 4: return $p4Deck;
    default: return $p1Deck;
  }
}

function AddDeck($player, $CardID="-", $Owner=0, $Controller=0, $sourceObject=null) {
  $zoneObj = new Deck($CardID . ' ' . 0 . ' ' . $Owner . ' ' . $Controller, 'Deck', $player);
  $zone = &GetDeck($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetHand($player) {
  global $p1Hand, $p2Hand, $p3Hand, $p4Hand;
  switch ($player) {
    case 1: return $p1Hand;
    case 2: return $p2Hand;
    case 3: return $p3Hand;
    case 4: return $p4Hand;
    default: return $p1Hand;
  }
}

function AddHand($player, $CardID="-", $Owner=0, $Controller=0, $sourceObject=null) {
  $zoneObj = new Hand($CardID . ' ' . 0 . ' ' . $Owner . ' ' . $Controller, 'Hand', $player);
  $zone = &GetHand($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetArsenal($player) {
  global $p1Arsenal, $p2Arsenal, $p3Arsenal, $p4Arsenal;
  switch ($player) {
    case 1: return $p1Arsenal;
    case 2: return $p2Arsenal;
    case 3: return $p3Arsenal;
    case 4: return $p4Arsenal;
    default: return $p1Arsenal;
  }
}

function AddArsenal($player, $CardID="-", $FaceDown=1, $TurnEffects="-", $Counters="-", $Owner=0, $Controller=0, $sourceObject=null) {
  $zoneObj = new Arsenal($CardID . ' ' . 0 . ' ' . $FaceDown . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters) . ' ' . $Owner . ' ' . $Controller, 'Arsenal', $player);
  $zone = &GetArsenal($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetGraveyard($player) {
  global $p1Graveyard, $p2Graveyard, $p3Graveyard, $p4Graveyard;
  switch ($player) {
    case 1: return $p1Graveyard;
    case 2: return $p2Graveyard;
    case 3: return $p3Graveyard;
    case 4: return $p4Graveyard;
    default: return $p1Graveyard;
  }
}

function AddGraveyard($player, $CardID="-", $Owner=0, $Controller=0, $FaceDown=0, $sourceObject=null) {
  $zoneObj = new Graveyard($CardID . ' ' . 0 . ' ' . $Owner . ' ' . $Controller . ' ' . $FaceDown, 'Graveyard', $player);
  $zone = &GetGraveyard($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetBanish($player) {
  global $p1Banish, $p2Banish, $p3Banish, $p4Banish;
  switch ($player) {
    case 1: return $p1Banish;
    case 2: return $p2Banish;
    case 3: return $p3Banish;
    case 4: return $p4Banish;
    default: return $p1Banish;
  }
}

function AddBanish($player, $CardID="-", $FaceDown=0, $ReturnAtEndTurn=0, $PlayableFromBanish=0, $PlayableChainLink=0, $Counters="-", $TurnEffects="-", $Owner=0, $Controller=0, $sourceObject=null) {
  $zoneObj = new Banish($CardID . ' ' . 0 . ' ' . $FaceDown . ' ' . $ReturnAtEndTurn . ' ' . $PlayableFromBanish . ' ' . $PlayableChainLink . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters) . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . $Owner . ' ' . $Controller, 'Banish', $player);
  $zone = &GetBanish($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetSoul($player) {
  global $p1Soul, $p2Soul, $p3Soul, $p4Soul;
  switch ($player) {
    case 1: return $p1Soul;
    case 2: return $p2Soul;
    case 3: return $p3Soul;
    case 4: return $p4Soul;
    default: return $p1Soul;
  }
}

function AddSoul($player, $CardID="-", $sourceObject=null) {
  $zoneObj = new Soul($CardID . ' ' . 0, 'Soul', $player);
  $zone = &GetSoul($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetPitch($player) {
  global $p1Pitch, $p2Pitch, $p3Pitch, $p4Pitch;
  switch ($player) {
    case 1: return $p1Pitch;
    case 2: return $p2Pitch;
    case 3: return $p3Pitch;
    case 4: return $p4Pitch;
    default: return $p1Pitch;
  }
}

function AddPitch($player, $CardID="-", $Owner=0, $Controller=0, $sourceObject=null) {
  $zoneObj = new Pitch($CardID . ' ' . 0 . ' ' . $Owner . ' ' . $Controller, 'Pitch', $player);
  $zone = &GetPitch($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetInventory($player) {
  global $p1Inventory, $p2Inventory, $p3Inventory, $p4Inventory;
  switch ($player) {
    case 1: return $p1Inventory;
    case 2: return $p2Inventory;
    case 3: return $p3Inventory;
    case 4: return $p4Inventory;
    default: return $p1Inventory;
  }
}

function AddInventory($player, $CardID="-", $Owner=0, $Controller=0, $sourceObject=null) {
  $zoneObj = new Inventory($CardID . ' ' . 0 . ' ' . $Owner . ' ' . $Controller, 'Inventory', $player);
  $zone = &GetInventory($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetTemp($player) {
  global $p1Temp, $p2Temp, $p3Temp, $p4Temp;
  switch ($player) {
    case 1: return $p1Temp;
    case 2: return $p2Temp;
    case 3: return $p3Temp;
    case 4: return $p4Temp;
    default: return $p1Temp;
  }
}

function AddTemp($player, $CardID="-", $Owner=0, $Controller=0, $sourceObject=null) {
  $zoneObj = new Temp($CardID . ' ' . 0 . ' ' . $Owner . ' ' . $Controller, 'Temp', $player);
  $zone = &GetTemp($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetHealth($player) {
  global $p1Health, $p2Health, $p3Health, $p4Health;
  switch ($player) {
    case 1: return $p1Health;
    case 2: return $p2Health;
    case 3: return $p3Health;
    case 4: return $p4Health;
    default: return $p1Health;
  }
}

function &HealthValue($player) {
  global $p1Health, $p2Health, $p3Health, $p4Health;
  switch ($player) {
    case 1: return $p1Health;
    case 2: return $p2Health;
    case 3: return $p3Health;
    case 4: return $p4Health;
    default: return $p1Health;
  }
}

function AddHealth($player, $Value=20, $sourceObject=null) {
  if(!FaBELEBeforeHealth($player, $Value, $sourceObject)) return null;
  $zoneValue = &GetHealth($player);
  $zoneValue = $Value;
  return null;
}

function &GetResources($player) {
  global $p1Resources, $p2Resources, $p3Resources, $p4Resources;
  switch ($player) {
    case 1: return $p1Resources;
    case 2: return $p2Resources;
    case 3: return $p3Resources;
    case 4: return $p4Resources;
    default: return $p1Resources;
  }
}

function &ResourcesValue($player) {
  global $p1Resources, $p2Resources, $p3Resources, $p4Resources;
  switch ($player) {
    case 1: return $p1Resources;
    case 2: return $p2Resources;
    case 3: return $p3Resources;
    case 4: return $p4Resources;
    default: return $p1Resources;
  }
}

function AddResources($player, $Value=0, $sourceObject=null) {
  if(!FaBMSTBeforeResources($player, $Value, $sourceObject)) return null;
  $zoneValue = &GetResources($player);
  $zoneValue = $Value;
  return null;
}

function &GetChi($player) {
  global $p1Chi, $p2Chi, $p3Chi, $p4Chi;
  switch ($player) {
    case 1: return $p1Chi;
    case 2: return $p2Chi;
    case 3: return $p3Chi;
    case 4: return $p4Chi;
    default: return $p1Chi;
  }
}

function &ChiValue($player) {
  global $p1Chi, $p2Chi, $p3Chi, $p4Chi;
  switch ($player) {
    case 1: return $p1Chi;
    case 2: return $p2Chi;
    case 3: return $p3Chi;
    case 4: return $p4Chi;
    default: return $p1Chi;
  }
}

function AddChi($player, $Value=0, $sourceObject=null) {
  $zoneValue = &GetChi($player);
  $zoneValue = $Value;
  return null;
}

function &GetActionPoints($player) {
  global $p1ActionPoints, $p2ActionPoints, $p3ActionPoints, $p4ActionPoints;
  switch ($player) {
    case 1: return $p1ActionPoints;
    case 2: return $p2ActionPoints;
    case 3: return $p3ActionPoints;
    case 4: return $p4ActionPoints;
    default: return $p1ActionPoints;
  }
}

function &ActionPointsValue($player) {
  global $p1ActionPoints, $p2ActionPoints, $p3ActionPoints, $p4ActionPoints;
  switch ($player) {
    case 1: return $p1ActionPoints;
    case 2: return $p2ActionPoints;
    case 3: return $p3ActionPoints;
    case 4: return $p4ActionPoints;
    default: return $p1ActionPoints;
  }
}

function AddActionPoints($player, $Value=1, $sourceObject=null) {
  $zoneValue = &GetActionPoints($player);
  $zoneValue = $Value;
  return null;
}

function &GetShortcutPreferences($player) {
  global $p1ShortcutPreferences, $p2ShortcutPreferences, $p3ShortcutPreferences, $p4ShortcutPreferences;
  switch ($player) {
    case 1: return $p1ShortcutPreferences;
    case 2: return $p2ShortcutPreferences;
    case 3: return $p3ShortcutPreferences;
    case 4: return $p4ShortcutPreferences;
    default: return $p1ShortcutPreferences;
  }
}

function &ShortcutPreferencesValue($player) {
  global $p1ShortcutPreferences, $p2ShortcutPreferences, $p3ShortcutPreferences, $p4ShortcutPreferences;
  switch ($player) {
    case 1: return $p1ShortcutPreferences;
    case 2: return $p2ShortcutPreferences;
    case 3: return $p3ShortcutPreferences;
    case 4: return $p4ShortcutPreferences;
    default: return $p1ShortcutPreferences;
  }
}

function AddShortcutPreferences($player, $Value="", $sourceObject=null) {
  $zoneValue = &GetShortcutPreferences($player);
  $zoneValue = $Value;
  return null;
}

function &GetStack() {
  global $gStack;
  return $gStack;
}

function AddStack( $CardID="-", $Owner=0, $Controller=0, $Kind="", $SourceZone="", $SourceUniqueID=0, $Params="-", $TurnEffects="-", $Counters="-", $sourceObject=null) {
  $zoneObj = new Stack($CardID . ' ' . $Owner . ' ' . $Controller . ' ' . $Kind . ' ' . $SourceZone . ' ' . $SourceUniqueID . ' ' . (is_array($Params) ? base64_encode(json_encode($Params)) : $Params) . ' ' . 0 . ' ' . (is_array($TurnEffects) ? (count($TurnEffects) > 0 ? implode('~', $TurnEffects) : '-') : $TurnEffects) . ' ' . (is_array($Counters) ? base64_encode(json_encode($Counters)) : $Counters), 'Stack', 0);
  $zone = &GetStack();
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  global $gUniqueIDCounter;
  // A zone move keeps the physical card's identity; only newly created objects get a new ID.
  if($sourceObject === null || intval($zoneObj->UniqueID ?? 0) <= 0) {
    $gUniqueIDCounter++;
    $zoneObj->UniqueID = $gUniqueIDCounter;
  }
  return $zoneObj;
}

function &GetPriorityPlayer() {
  global $gPriorityPlayer;
  return $gPriorityPlayer;
}

function SetPriorityPlayer($value) {
  global $gPriorityPlayer;
  $gPriorityPlayer = $value;
}

function AddPriorityPlayer( $Value=1, $sourceObject=null) {
  global $gPriorityPlayer;
  $gPriorityPlayer = $Value;
  return null;
}

function &GetConsecutivePasses() {
  global $gConsecutivePasses;
  return $gConsecutivePasses;
}

function SetConsecutivePasses($value) {
  global $gConsecutivePasses;
  $gConsecutivePasses = $value;
}

function AddConsecutivePasses( $Value=0, $sourceObject=null) {
  global $gConsecutivePasses;
  $gConsecutivePasses = $Value;
  return null;
}

function &GetGameState() {
  global $gGameState;
  return $gGameState;
}

function SetGameState($value) {
  global $gGameState;
  $gGameState = $value;
}

function AddGameState( $Value="{}", $sourceObject=null) {
  global $gGameState;
  $gGameState = $Value;
  return null;
}

function &GetWinner() {
  global $gWinner;
  return $gWinner;
}

function SetWinner($value) {
  global $gWinner;
  $gWinner = $value;
}

function AddWinner( $Value=0, $sourceObject=null) {
  global $gWinner;
  $gWinner = $Value;
  return null;
}

function &GetSeatOrder() {
  global $gSeatOrder;
  return $gSeatOrder;
}

function SetSeatOrder($value) {
  global $gSeatOrder;
  $gSeatOrder = $value;
}

function AddSeatOrder( $Value=12, $sourceObject=null) {
  global $gSeatOrder;
  $gSeatOrder = $Value;
  return null;
}

function &GetLiveSeats() {
  global $gLiveSeats;
  return $gLiveSeats;
}

function SetLiveSeats($value) {
  global $gLiveSeats;
  $gLiveSeats = $value;
}

function AddLiveSeats( $Value=12, $sourceObject=null) {
  global $gLiveSeats;
  $gLiveSeats = $Value;
  return null;
}

function &GetDecisionQueue($player) {
  global $p1DecisionQueue, $p2DecisionQueue, $p3DecisionQueue, $p4DecisionQueue;
  switch ($player) {
    case 1: return $p1DecisionQueue;
    case 2: return $p2DecisionQueue;
    case 3: return $p3DecisionQueue;
    case 4: return $p4DecisionQueue;
    default: return $p1DecisionQueue;
  }
}

function AddDecisionQueue($player, $Type="-", $Param="-", $Block=1, $Tooltip="", $DontSkipOnPass=0, $sourceObject=null) {
  $zoneObj = new DecisionQueue($Type . ' ' . $Param . ' ' . $Block . ' ' . $Tooltip . ' ' . $DontSkipOnPass, 'DecisionQueue', $player);
  $zone = &GetDecisionQueue($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  return $zoneObj;
}

function &GetVersions($player) {
  global $p1Versions, $p2Versions, $p3Versions, $p4Versions;
  switch ($player) {
    case 1: return $p1Versions;
    case 2: return $p2Versions;
    case 3: return $p3Versions;
    case 4: return $p4Versions;
    default: return $p1Versions;
  }
}

function AddVersions($player, $Version="-", $sourceObject=null) {
  $zoneObj = new Versions($Version, 'Versions', $player);
  $zone = &GetVersions($player);
  $zoneObj->mzIndex = count($zone);
  array_push($zone, $zoneObj);
  // Deep copy properties from sourceObject if provided
  if($sourceObject !== null) {
    $properties = get_object_vars($sourceObject);
    foreach($properties as $prop => $value) {
      if($prop !== 'removed' && $prop !== 'Location' && $prop !== 'mzIndex') {
        $zoneObj->$prop = $value;
      }
    }
  }
  return $zoneObj;
}

function &GetTurnNumber() {
  global $gTurnNumber;
  return $gTurnNumber;
}

function SetTurnNumber($value) {
  global $gTurnNumber;
  $gTurnNumber = $value;
}

function AddTurnNumber( $Value=0, $sourceObject=null) {
  global $gTurnNumber;
  $gTurnNumber = $Value;
  return null;
}

function &GetTurnPlayer() {
  global $gTurnPlayer;
  return $gTurnPlayer;
}

function SetTurnPlayer($value) {
  global $gTurnPlayer;
  $gTurnPlayer = $value;
}

function AddTurnPlayer( $Value=1, $sourceObject=null) {
  global $gTurnPlayer;
  $gTurnPlayer = $Value;
  return null;
}

function &GetCurrentPhase() {
  global $gCurrentPhase;
  return $gCurrentPhase;
}

function SetCurrentPhase($value) {
  global $gCurrentPhase;
  $gCurrentPhase = $value;
}

function AddCurrentPhase( $Value="SOT", $sourceObject=null) {
  global $gCurrentPhase;
  $gCurrentPhase = $Value;
  return null;
}

function &GetPhaseParameters() {
  global $gPhaseParameters;
  return $gPhaseParameters;
}

function SetPhaseParameters($value) {
  global $gPhaseParameters;
  $gPhaseParameters = $value;
}

function AddPhaseParameters( $Value="", $sourceObject=null) {
  global $gPhaseParameters;
  $gPhaseParameters = $Value;
  return null;
}

function &GetDecisionQueueVariables() {
  global $gDecisionQueueVariables;
  return $gDecisionQueueVariables;
}

function SetDecisionQueueVariables($value) {
  global $gDecisionQueueVariables;
  $gDecisionQueueVariables = $value;
}

function AddDecisionQueueVariables( $Value="", $sourceObject=null) {
  global $gDecisionQueueVariables;
  $gDecisionQueueVariables = $Value;
  return null;
}

function &GetMacroTurnIndex() {
  global $gMacroTurnIndex;
  return $gMacroTurnIndex;
}

function SetMacroTurnIndex($value) {
  global $gMacroTurnIndex;
  $gMacroTurnIndex = $value;
}

function AddMacroTurnIndex( $Value="", $sourceObject=null) {
  global $gMacroTurnIndex;
  $gMacroTurnIndex = $Value;
  return null;
}

function &GetMacroGameIndex() {
  global $gMacroGameIndex;
  return $gMacroGameIndex;
}

function SetMacroGameIndex($value) {
  global $gMacroGameIndex;
  $gMacroGameIndex = $value;
}

function AddMacroGameIndex( $Value="", $sourceObject=null) {
  global $gMacroGameIndex;
  $gMacroGameIndex = $Value;
  return null;
}

function &GetUniqueIDCounter() {
  global $gUniqueIDCounter;
  return $gUniqueIDCounter;
}

function SetUniqueIDCounter($value) {
  global $gUniqueIDCounter;
  $gUniqueIDCounter = $value;
}

function AddUniqueIDCounter( $Value=0, $sourceObject=null) {
  global $gUniqueIDCounter;
  $gUniqueIDCounter = $Value;
  return null;
}

function &GetRandomCounter() {
  global $gRandomCounter;
  return $gRandomCounter;
}

function SetRandomCounter($value) {
  global $gRandomCounter;
  $gRandomCounter = $value;
}

function AddRandomCounter( $Value=0, $sourceObject=null) {
  global $gRandomCounter;
  $gRandomCounter = $Value;
  return null;
}

function &GetZoneObject($mzID) {
  global $playerID;
  $mzArr = explode("-",$mzID);
  switch($mzArr[0]) {
    case "myHero": $zoneArr = &GetHero($playerID); break;
    case "theirHero": $zoneArr = &GetHero($playerID == 1 ? 2 : 1); break;
    case "p1Hero": $zoneArr = &GetHero(1); break;
    case "p2Hero": $zoneArr = &GetHero(2); break;
    case "p3Hero": $zoneArr = &GetHero(3); break;
    case "p4Hero": $zoneArr = &GetHero(4); break;
    case "myWeapons": $zoneArr = &GetWeapons($playerID); break;
    case "theirWeapons": $zoneArr = &GetWeapons($playerID == 1 ? 2 : 1); break;
    case "p1Weapons": $zoneArr = &GetWeapons(1); break;
    case "p2Weapons": $zoneArr = &GetWeapons(2); break;
    case "p3Weapons": $zoneArr = &GetWeapons(3); break;
    case "p4Weapons": $zoneArr = &GetWeapons(4); break;
    case "myEquipment": $zoneArr = &GetEquipment($playerID); break;
    case "theirEquipment": $zoneArr = &GetEquipment($playerID == 1 ? 2 : 1); break;
    case "p1Equipment": $zoneArr = &GetEquipment(1); break;
    case "p2Equipment": $zoneArr = &GetEquipment(2); break;
    case "p3Equipment": $zoneArr = &GetEquipment(3); break;
    case "p4Equipment": $zoneArr = &GetEquipment(4); break;
    case "myArena": $zoneArr = &GetArena($playerID); break;
    case "theirArena": $zoneArr = &GetArena($playerID == 1 ? 2 : 1); break;
    case "p1Arena": $zoneArr = &GetArena(1); break;
    case "p2Arena": $zoneArr = &GetArena(2); break;
    case "p3Arena": $zoneArr = &GetArena(3); break;
    case "p4Arena": $zoneArr = &GetArena(4); break;
    case "myCombatChain": $zoneArr = &GetCombatChain($playerID); break;
    case "theirCombatChain": $zoneArr = &GetCombatChain($playerID == 1 ? 2 : 1); break;
    case "p1CombatChain": $zoneArr = &GetCombatChain(1); break;
    case "p2CombatChain": $zoneArr = &GetCombatChain(2); break;
    case "p3CombatChain": $zoneArr = &GetCombatChain(3); break;
    case "p4CombatChain": $zoneArr = &GetCombatChain(4); break;
    case "myDeck": $zoneArr = &GetDeck($playerID); break;
    case "theirDeck": $zoneArr = &GetDeck($playerID == 1 ? 2 : 1); break;
    case "p1Deck": $zoneArr = &GetDeck(1); break;
    case "p2Deck": $zoneArr = &GetDeck(2); break;
    case "p3Deck": $zoneArr = &GetDeck(3); break;
    case "p4Deck": $zoneArr = &GetDeck(4); break;
    case "myHand": $zoneArr = &GetHand($playerID); break;
    case "theirHand": $zoneArr = &GetHand($playerID == 1 ? 2 : 1); break;
    case "p1Hand": $zoneArr = &GetHand(1); break;
    case "p2Hand": $zoneArr = &GetHand(2); break;
    case "p3Hand": $zoneArr = &GetHand(3); break;
    case "p4Hand": $zoneArr = &GetHand(4); break;
    case "myArsenal": $zoneArr = &GetArsenal($playerID); break;
    case "theirArsenal": $zoneArr = &GetArsenal($playerID == 1 ? 2 : 1); break;
    case "p1Arsenal": $zoneArr = &GetArsenal(1); break;
    case "p2Arsenal": $zoneArr = &GetArsenal(2); break;
    case "p3Arsenal": $zoneArr = &GetArsenal(3); break;
    case "p4Arsenal": $zoneArr = &GetArsenal(4); break;
    case "myGraveyard": $zoneArr = &GetGraveyard($playerID); break;
    case "theirGraveyard": $zoneArr = &GetGraveyard($playerID == 1 ? 2 : 1); break;
    case "p1Graveyard": $zoneArr = &GetGraveyard(1); break;
    case "p2Graveyard": $zoneArr = &GetGraveyard(2); break;
    case "p3Graveyard": $zoneArr = &GetGraveyard(3); break;
    case "p4Graveyard": $zoneArr = &GetGraveyard(4); break;
    case "myBanish": $zoneArr = &GetBanish($playerID); break;
    case "theirBanish": $zoneArr = &GetBanish($playerID == 1 ? 2 : 1); break;
    case "p1Banish": $zoneArr = &GetBanish(1); break;
    case "p2Banish": $zoneArr = &GetBanish(2); break;
    case "p3Banish": $zoneArr = &GetBanish(3); break;
    case "p4Banish": $zoneArr = &GetBanish(4); break;
    case "mySoul": $zoneArr = &GetSoul($playerID); break;
    case "theirSoul": $zoneArr = &GetSoul($playerID == 1 ? 2 : 1); break;
    case "p1Soul": $zoneArr = &GetSoul(1); break;
    case "p2Soul": $zoneArr = &GetSoul(2); break;
    case "p3Soul": $zoneArr = &GetSoul(3); break;
    case "p4Soul": $zoneArr = &GetSoul(4); break;
    case "myPitch": $zoneArr = &GetPitch($playerID); break;
    case "theirPitch": $zoneArr = &GetPitch($playerID == 1 ? 2 : 1); break;
    case "p1Pitch": $zoneArr = &GetPitch(1); break;
    case "p2Pitch": $zoneArr = &GetPitch(2); break;
    case "p3Pitch": $zoneArr = &GetPitch(3); break;
    case "p4Pitch": $zoneArr = &GetPitch(4); break;
    case "myInventory": $zoneArr = &GetInventory($playerID); break;
    case "theirInventory": $zoneArr = &GetInventory($playerID == 1 ? 2 : 1); break;
    case "p1Inventory": $zoneArr = &GetInventory(1); break;
    case "p2Inventory": $zoneArr = &GetInventory(2); break;
    case "p3Inventory": $zoneArr = &GetInventory(3); break;
    case "p4Inventory": $zoneArr = &GetInventory(4); break;
    case "myTemp": $zoneArr = &GetTemp($playerID); break;
    case "theirTemp": $zoneArr = &GetTemp($playerID == 1 ? 2 : 1); break;
    case "p1Temp": $zoneArr = &GetTemp(1); break;
    case "p2Temp": $zoneArr = &GetTemp(2); break;
    case "p3Temp": $zoneArr = &GetTemp(3); break;
    case "p4Temp": $zoneArr = &GetTemp(4); break;
    case "myHealth": return GetHealth($playerID);
    case "theirHealth": return GetHealth($playerID == 1 ? 2 : 1);
    case "p1Health": return GetHealth(1);
    case "p2Health": return GetHealth(2);
    case "p3Health": return GetHealth(3);
    case "p4Health": return GetHealth(4);
    case "myResources": return GetResources($playerID);
    case "theirResources": return GetResources($playerID == 1 ? 2 : 1);
    case "p1Resources": return GetResources(1);
    case "p2Resources": return GetResources(2);
    case "p3Resources": return GetResources(3);
    case "p4Resources": return GetResources(4);
    case "myChi": return GetChi($playerID);
    case "theirChi": return GetChi($playerID == 1 ? 2 : 1);
    case "p1Chi": return GetChi(1);
    case "p2Chi": return GetChi(2);
    case "p3Chi": return GetChi(3);
    case "p4Chi": return GetChi(4);
    case "myActionPoints": return GetActionPoints($playerID);
    case "theirActionPoints": return GetActionPoints($playerID == 1 ? 2 : 1);
    case "p1ActionPoints": return GetActionPoints(1);
    case "p2ActionPoints": return GetActionPoints(2);
    case "p3ActionPoints": return GetActionPoints(3);
    case "p4ActionPoints": return GetActionPoints(4);
    case "myShortcutPreferences": return GetShortcutPreferences($playerID);
    case "theirShortcutPreferences": return GetShortcutPreferences($playerID == 1 ? 2 : 1);
    case "p1ShortcutPreferences": return GetShortcutPreferences(1);
    case "p2ShortcutPreferences": return GetShortcutPreferences(2);
    case "p3ShortcutPreferences": return GetShortcutPreferences(3);
    case "p4ShortcutPreferences": return GetShortcutPreferences(4);
    case "Stack": $zoneArr = &GetStack(); break;
    case "PriorityPlayer": return GetPriorityPlayer();
    case "ConsecutivePasses": return GetConsecutivePasses();
    case "GameState": return GetGameState();
    case "Winner": return GetWinner();
    case "SeatOrder": return GetSeatOrder();
    case "LiveSeats": return GetLiveSeats();
    case "myDecisionQueue": $zoneArr = &GetDecisionQueue($playerID); break;
    case "theirDecisionQueue": $zoneArr = &GetDecisionQueue($playerID == 1 ? 2 : 1); break;
    case "p1DecisionQueue": $zoneArr = &GetDecisionQueue(1); break;
    case "p2DecisionQueue": $zoneArr = &GetDecisionQueue(2); break;
    case "p3DecisionQueue": $zoneArr = &GetDecisionQueue(3); break;
    case "p4DecisionQueue": $zoneArr = &GetDecisionQueue(4); break;
    case "myVersions": $zoneArr = &GetVersions($playerID); break;
    case "theirVersions": $zoneArr = &GetVersions($playerID == 1 ? 2 : 1); break;
    case "p1Versions": $zoneArr = &GetVersions(1); break;
    case "p2Versions": $zoneArr = &GetVersions(2); break;
    case "p3Versions": $zoneArr = &GetVersions(3); break;
    case "p4Versions": $zoneArr = &GetVersions(4); break;
    case "TurnNumber": return GetTurnNumber();
    case "TurnPlayer": return GetTurnPlayer();
    case "CurrentPhase": return GetCurrentPhase();
    case "PhaseParameters": return GetPhaseParameters();
    case "DecisionQueueVariables": return GetDecisionQueueVariables();
    case "MacroTurnIndex": return GetMacroTurnIndex();
    case "MacroGameIndex": return GetMacroGameIndex();
    case "UniqueIDCounter": return GetUniqueIDCounter();
    case "RandomCounter": return GetRandomCounter();
    default: $_null = null; return $_null;
  }
  // Guard: returning a missing index from a by-ref function would auto-vivify a null entry in the zone
  if(!isset($mzArr[1]) || !is_numeric($mzArr[1]) || !isset($zoneArr[intval($mzArr[1])])) { $_null = null; return $_null; }
  return $zoneArr[$mzArr[1]];
}

function &GetZone($mzID) {
  global $playerID;
  $mzArr = explode("-",$mzID);
  switch($mzArr[0]) {
    case "myHero": $zoneArr = &GetHero($playerID); return $zoneArr;
    case "theirHero": $zoneArr = &GetHero($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Hero": $zoneArr = &GetHero(1); return $zoneArr;
    case "p2Hero": $zoneArr = &GetHero(2); return $zoneArr;
    case "p3Hero": $zoneArr = &GetHero(3); return $zoneArr;
    case "p4Hero": $zoneArr = &GetHero(4); return $zoneArr;
    case "myWeapons": $zoneArr = &GetWeapons($playerID); return $zoneArr;
    case "theirWeapons": $zoneArr = &GetWeapons($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Weapons": $zoneArr = &GetWeapons(1); return $zoneArr;
    case "p2Weapons": $zoneArr = &GetWeapons(2); return $zoneArr;
    case "p3Weapons": $zoneArr = &GetWeapons(3); return $zoneArr;
    case "p4Weapons": $zoneArr = &GetWeapons(4); return $zoneArr;
    case "myEquipment": $zoneArr = &GetEquipment($playerID); return $zoneArr;
    case "theirEquipment": $zoneArr = &GetEquipment($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Equipment": $zoneArr = &GetEquipment(1); return $zoneArr;
    case "p2Equipment": $zoneArr = &GetEquipment(2); return $zoneArr;
    case "p3Equipment": $zoneArr = &GetEquipment(3); return $zoneArr;
    case "p4Equipment": $zoneArr = &GetEquipment(4); return $zoneArr;
    case "myArena": $zoneArr = &GetArena($playerID); return $zoneArr;
    case "theirArena": $zoneArr = &GetArena($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Arena": $zoneArr = &GetArena(1); return $zoneArr;
    case "p2Arena": $zoneArr = &GetArena(2); return $zoneArr;
    case "p3Arena": $zoneArr = &GetArena(3); return $zoneArr;
    case "p4Arena": $zoneArr = &GetArena(4); return $zoneArr;
    case "myCombatChain": $zoneArr = &GetCombatChain($playerID); return $zoneArr;
    case "theirCombatChain": $zoneArr = &GetCombatChain($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1CombatChain": $zoneArr = &GetCombatChain(1); return $zoneArr;
    case "p2CombatChain": $zoneArr = &GetCombatChain(2); return $zoneArr;
    case "p3CombatChain": $zoneArr = &GetCombatChain(3); return $zoneArr;
    case "p4CombatChain": $zoneArr = &GetCombatChain(4); return $zoneArr;
    case "myDeck": $zoneArr = &GetDeck($playerID); return $zoneArr;
    case "theirDeck": $zoneArr = &GetDeck($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Deck": $zoneArr = &GetDeck(1); return $zoneArr;
    case "p2Deck": $zoneArr = &GetDeck(2); return $zoneArr;
    case "p3Deck": $zoneArr = &GetDeck(3); return $zoneArr;
    case "p4Deck": $zoneArr = &GetDeck(4); return $zoneArr;
    case "myHand": $zoneArr = &GetHand($playerID); return $zoneArr;
    case "theirHand": $zoneArr = &GetHand($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Hand": $zoneArr = &GetHand(1); return $zoneArr;
    case "p2Hand": $zoneArr = &GetHand(2); return $zoneArr;
    case "p3Hand": $zoneArr = &GetHand(3); return $zoneArr;
    case "p4Hand": $zoneArr = &GetHand(4); return $zoneArr;
    case "myArsenal": $zoneArr = &GetArsenal($playerID); return $zoneArr;
    case "theirArsenal": $zoneArr = &GetArsenal($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Arsenal": $zoneArr = &GetArsenal(1); return $zoneArr;
    case "p2Arsenal": $zoneArr = &GetArsenal(2); return $zoneArr;
    case "p3Arsenal": $zoneArr = &GetArsenal(3); return $zoneArr;
    case "p4Arsenal": $zoneArr = &GetArsenal(4); return $zoneArr;
    case "myGraveyard": $zoneArr = &GetGraveyard($playerID); return $zoneArr;
    case "theirGraveyard": $zoneArr = &GetGraveyard($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Graveyard": $zoneArr = &GetGraveyard(1); return $zoneArr;
    case "p2Graveyard": $zoneArr = &GetGraveyard(2); return $zoneArr;
    case "p3Graveyard": $zoneArr = &GetGraveyard(3); return $zoneArr;
    case "p4Graveyard": $zoneArr = &GetGraveyard(4); return $zoneArr;
    case "myBanish": $zoneArr = &GetBanish($playerID); return $zoneArr;
    case "theirBanish": $zoneArr = &GetBanish($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Banish": $zoneArr = &GetBanish(1); return $zoneArr;
    case "p2Banish": $zoneArr = &GetBanish(2); return $zoneArr;
    case "p3Banish": $zoneArr = &GetBanish(3); return $zoneArr;
    case "p4Banish": $zoneArr = &GetBanish(4); return $zoneArr;
    case "mySoul": $zoneArr = &GetSoul($playerID); return $zoneArr;
    case "theirSoul": $zoneArr = &GetSoul($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Soul": $zoneArr = &GetSoul(1); return $zoneArr;
    case "p2Soul": $zoneArr = &GetSoul(2); return $zoneArr;
    case "p3Soul": $zoneArr = &GetSoul(3); return $zoneArr;
    case "p4Soul": $zoneArr = &GetSoul(4); return $zoneArr;
    case "myPitch": $zoneArr = &GetPitch($playerID); return $zoneArr;
    case "theirPitch": $zoneArr = &GetPitch($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Pitch": $zoneArr = &GetPitch(1); return $zoneArr;
    case "p2Pitch": $zoneArr = &GetPitch(2); return $zoneArr;
    case "p3Pitch": $zoneArr = &GetPitch(3); return $zoneArr;
    case "p4Pitch": $zoneArr = &GetPitch(4); return $zoneArr;
    case "myInventory": $zoneArr = &GetInventory($playerID); return $zoneArr;
    case "theirInventory": $zoneArr = &GetInventory($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Inventory": $zoneArr = &GetInventory(1); return $zoneArr;
    case "p2Inventory": $zoneArr = &GetInventory(2); return $zoneArr;
    case "p3Inventory": $zoneArr = &GetInventory(3); return $zoneArr;
    case "p4Inventory": $zoneArr = &GetInventory(4); return $zoneArr;
    case "myTemp": $zoneArr = &GetTemp($playerID); return $zoneArr;
    case "theirTemp": $zoneArr = &GetTemp($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Temp": $zoneArr = &GetTemp(1); return $zoneArr;
    case "p2Temp": $zoneArr = &GetTemp(2); return $zoneArr;
    case "p3Temp": $zoneArr = &GetTemp(3); return $zoneArr;
    case "p4Temp": $zoneArr = &GetTemp(4); return $zoneArr;
    case "myHealth": return GetHealth($playerID);
    case "theirHealth": return GetHealth($playerID == 1 ? 2 : 1);
    case "p1Health": return GetHealth(1);
    case "p2Health": return GetHealth(2);
    case "p3Health": return GetHealth(3);
    case "p4Health": return GetHealth(4);
    case "myResources": return GetResources($playerID);
    case "theirResources": return GetResources($playerID == 1 ? 2 : 1);
    case "p1Resources": return GetResources(1);
    case "p2Resources": return GetResources(2);
    case "p3Resources": return GetResources(3);
    case "p4Resources": return GetResources(4);
    case "myChi": return GetChi($playerID);
    case "theirChi": return GetChi($playerID == 1 ? 2 : 1);
    case "p1Chi": return GetChi(1);
    case "p2Chi": return GetChi(2);
    case "p3Chi": return GetChi(3);
    case "p4Chi": return GetChi(4);
    case "myActionPoints": return GetActionPoints($playerID);
    case "theirActionPoints": return GetActionPoints($playerID == 1 ? 2 : 1);
    case "p1ActionPoints": return GetActionPoints(1);
    case "p2ActionPoints": return GetActionPoints(2);
    case "p3ActionPoints": return GetActionPoints(3);
    case "p4ActionPoints": return GetActionPoints(4);
    case "myShortcutPreferences": return GetShortcutPreferences($playerID);
    case "theirShortcutPreferences": return GetShortcutPreferences($playerID == 1 ? 2 : 1);
    case "p1ShortcutPreferences": return GetShortcutPreferences(1);
    case "p2ShortcutPreferences": return GetShortcutPreferences(2);
    case "p3ShortcutPreferences": return GetShortcutPreferences(3);
    case "p4ShortcutPreferences": return GetShortcutPreferences(4);
    case "Stack": $zoneArr = &GetStack(); return $zoneArr;
    case "PriorityPlayer": return GetPriorityPlayer();
    case "ConsecutivePasses": return GetConsecutivePasses();
    case "GameState": return GetGameState();
    case "Winner": return GetWinner();
    case "SeatOrder": return GetSeatOrder();
    case "LiveSeats": return GetLiveSeats();
    case "myDecisionQueue": $zoneArr = &GetDecisionQueue($playerID); return $zoneArr;
    case "theirDecisionQueue": $zoneArr = &GetDecisionQueue($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1DecisionQueue": $zoneArr = &GetDecisionQueue(1); return $zoneArr;
    case "p2DecisionQueue": $zoneArr = &GetDecisionQueue(2); return $zoneArr;
    case "p3DecisionQueue": $zoneArr = &GetDecisionQueue(3); return $zoneArr;
    case "p4DecisionQueue": $zoneArr = &GetDecisionQueue(4); return $zoneArr;
    case "myVersions": $zoneArr = &GetVersions($playerID); return $zoneArr;
    case "theirVersions": $zoneArr = &GetVersions($playerID == 1 ? 2 : 1); return $zoneArr;
    case "p1Versions": $zoneArr = &GetVersions(1); return $zoneArr;
    case "p2Versions": $zoneArr = &GetVersions(2); return $zoneArr;
    case "p3Versions": $zoneArr = &GetVersions(3); return $zoneArr;
    case "p4Versions": $zoneArr = &GetVersions(4); return $zoneArr;
    case "TurnNumber": return GetTurnNumber();
    case "TurnPlayer": return GetTurnPlayer();
    case "CurrentPhase": return GetCurrentPhase();
    case "PhaseParameters": return GetPhaseParameters();
    case "DecisionQueueVariables": return GetDecisionQueueVariables();
    case "MacroTurnIndex": return GetMacroTurnIndex();
    case "MacroGameIndex": return GetMacroGameIndex();
    case "UniqueIDCounter": return GetUniqueIDCounter();
    case "RandomCounter": return GetRandomCounter();
    default: $_null = null; return $_null;
  }
}

// Get all zone names for iteration (used by CleanupRemovedCards)
function GetAllZones() {
  return [
    "myHero", "theirHero",
    "myWeapons", "theirWeapons",
    "myEquipment", "theirEquipment",
    "myArena", "theirArena",
    "myCombatChain", "theirCombatChain",
    "myDeck", "theirDeck",
    "myHand", "theirHand",
    "myArsenal", "theirArsenal",
    "myGraveyard", "theirGraveyard",
    "myBanish", "theirBanish",
    "mySoul", "theirSoul",
    "myPitch", "theirPitch",
    "myInventory", "theirInventory",
    "myTemp", "theirTemp",
    "Stack",
    "myDecisionQueue", "theirDecisionQueue",
    "myVersions", "theirVersions",
  ];
}

function MZMove($player, $mzIndex, $toZone) {
  global $playerID;
  if($player != $playerID) {
    $mzIndex = FlipZonePerspective($mzIndex);
    $toZone = FlipZonePerspective($toZone);
  }
  $removed = GetZoneObject($mzIndex);
  if($removed === null) return null;
  if(property_exists($removed, 'removed') && $removed->removed === true) return null;
  $removed->_sourceZone = strtok($mzIndex, '-');
  $removed->Remove();
  // The zone names above are normalized to the request viewer's perspective.
  // Use that same player for the destination so cross-player moves keep their owner.
  $destinationPlayer = intval($playerID) > 0 ? intval($playerID) : intval($player);
  // Pass the removed object to MZAddZone so properties are copied before AfterAdd hooks fire
  $newObj = MZAddZone($destinationPlayer, $toZone, $removed->CardID, $removed);
  return $newObj;
}

function MZZoneCount($zoneName) {
  $zone = GetZone($zoneName);
  for($i=0, $count=0; $i<count($zone); ++$i) {
    if($zone[$i] === null || (isset($zone[$i]->removed) && $zone[$i]->removed)) continue;
    ++$count;
  }
  return $count;
}

function MZAddZone($player, $zoneName, $cardID, $sourceObject=null) {
  switch($zoneName) {
    case "myHero": return AddHero($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirHero": return AddHero($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myWeapons": return AddWeapons($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirWeapons": return AddWeapons($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myEquipment": return AddEquipment($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirEquipment": return AddEquipment($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myArena": return AddArena($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirArena": return AddArena($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myCombatChain": return AddCombatChain($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirCombatChain": return AddCombatChain($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myDeck": return AddDeck($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirDeck": return AddDeck($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myHand": return AddHand($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirHand": return AddHand($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myArsenal": return AddArsenal($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirArsenal": return AddArsenal($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myGraveyard": return AddGraveyard($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirGraveyard": return AddGraveyard($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myBanish": return AddBanish($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirBanish": return AddBanish($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "mySoul": return AddSoul($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirSoul": return AddSoul($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myPitch": return AddPitch($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirPitch": return AddPitch($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myInventory": return AddInventory($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirInventory": return AddInventory($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myTemp": return AddTemp($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirTemp": return AddTemp($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myHealth": return AddHealth($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirHealth": return AddHealth($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myResources": return AddResources($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirResources": return AddResources($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myChi": return AddChi($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirChi": return AddChi($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myActionPoints": return AddActionPoints($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirActionPoints": return AddActionPoints($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myShortcutPreferences": return AddShortcutPreferences($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirShortcutPreferences": return AddShortcutPreferences($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "Stack": return AddStack(CardID:$cardID, sourceObject:$sourceObject);
    case "PriorityPlayer": return AddPriorityPlayer(CardID:$cardID, sourceObject:$sourceObject);
    case "ConsecutivePasses": return AddConsecutivePasses(CardID:$cardID, sourceObject:$sourceObject);
    case "GameState": return AddGameState(CardID:$cardID, sourceObject:$sourceObject);
    case "Winner": return AddWinner(CardID:$cardID, sourceObject:$sourceObject);
    case "SeatOrder": return AddSeatOrder(CardID:$cardID, sourceObject:$sourceObject);
    case "LiveSeats": return AddLiveSeats(CardID:$cardID, sourceObject:$sourceObject);
    case "myDecisionQueue": return AddDecisionQueue($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirDecisionQueue": return AddDecisionQueue($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "myVersions": return AddVersions($player, CardID:$cardID, sourceObject:$sourceObject);
    case "theirVersions": return AddVersions($player == 1 ? 2 : 1, CardID:$cardID, sourceObject:$sourceObject);
    case "TurnNumber": return AddTurnNumber(CardID:$cardID, sourceObject:$sourceObject);
    case "TurnPlayer": return AddTurnPlayer(CardID:$cardID, sourceObject:$sourceObject);
    case "CurrentPhase": return AddCurrentPhase(CardID:$cardID, sourceObject:$sourceObject);
    case "PhaseParameters": return AddPhaseParameters(CardID:$cardID, sourceObject:$sourceObject);
    case "DecisionQueueVariables": return AddDecisionQueueVariables(CardID:$cardID, sourceObject:$sourceObject);
    case "MacroTurnIndex": return AddMacroTurnIndex(CardID:$cardID, sourceObject:$sourceObject);
    case "MacroGameIndex": return AddMacroGameIndex(CardID:$cardID, sourceObject:$sourceObject);
    case "UniqueIDCounter": return AddUniqueIDCounter(CardID:$cardID, sourceObject:$sourceObject);
    case "RandomCounter": return AddRandomCounter(CardID:$cardID, sourceObject:$sourceObject);
    default: return null;
  }
}

function MZClearZone($player, $zoneName) {
  switch($zoneName) {
    case "myHero": $zone = &GetHero($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirHero": $zone = &GetHero($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myWeapons": $zone = &GetWeapons($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirWeapons": $zone = &GetWeapons($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myEquipment": $zone = &GetEquipment($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirEquipment": $zone = &GetEquipment($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myArena": $zone = &GetArena($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirArena": $zone = &GetArena($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myCombatChain": $zone = &GetCombatChain($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirCombatChain": $zone = &GetCombatChain($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myDeck": $zone = &GetDeck($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirDeck": $zone = &GetDeck($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myHand": $zone = &GetHand($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirHand": $zone = &GetHand($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myArsenal": $zone = &GetArsenal($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirArsenal": $zone = &GetArsenal($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myGraveyard": $zone = &GetGraveyard($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirGraveyard": $zone = &GetGraveyard($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myBanish": $zone = &GetBanish($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirBanish": $zone = &GetBanish($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "mySoul": $zone = &GetSoul($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirSoul": $zone = &GetSoul($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myPitch": $zone = &GetPitch($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirPitch": $zone = &GetPitch($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myInventory": $zone = &GetInventory($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirInventory": $zone = &GetInventory($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myTemp": $zone = &GetTemp($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirTemp": $zone = &GetTemp($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myHealth": ${'p' . $player . 'Health'} = 20; break;
    case "theirHealth": ${'p' . ($player == 1 ? 2 : 1) . 'Health'} = 20; break;
    case "myResources": ${'p' . $player . 'Resources'} = 0; break;
    case "theirResources": ${'p' . ($player == 1 ? 2 : 1) . 'Resources'} = 0; break;
    case "myChi": ${'p' . $player . 'Chi'} = 0; break;
    case "theirChi": ${'p' . ($player == 1 ? 2 : 1) . 'Chi'} = 0; break;
    case "myActionPoints": ${'p' . $player . 'ActionPoints'} = 1; break;
    case "theirActionPoints": ${'p' . ($player == 1 ? 2 : 1) . 'ActionPoints'} = 1; break;
    case "myShortcutPreferences": ${'p' . $player . 'ShortcutPreferences'} = ""; break;
    case "theirShortcutPreferences": ${'p' . ($player == 1 ? 2 : 1) . 'ShortcutPreferences'} = ""; break;
    case "Stack": $zone = &GetStack(); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "PriorityPlayer": $gPriorityPlayer = 1; break;
    case "ConsecutivePasses": $gConsecutivePasses = 0; break;
    case "GameState": $gGameState = "{}"; break;
    case "Winner": $gWinner = 0; break;
    case "SeatOrder": $gSeatOrder = 12; break;
    case "LiveSeats": $gLiveSeats = 12; break;
    case "myDecisionQueue": $zone = &GetDecisionQueue($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirDecisionQueue": $zone = &GetDecisionQueue($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "myVersions": $zone = &GetVersions($player); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "theirVersions": $zone = &GetVersions($player == 1 ? 2 : 1); for($i=0; $i<count($zone); ++$i) $zone[$i]->Remove(); break;
    case "TurnNumber": $gTurnNumber = 0; break;
    case "TurnPlayer": $gTurnPlayer = 1; break;
    case "CurrentPhase": $gCurrentPhase = "SOT"; break;
    case "PhaseParameters": $gPhaseParameters = ""; break;
    case "DecisionQueueVariables": $gDecisionQueueVariables = ""; break;
    case "MacroTurnIndex": $gMacroTurnIndex = ""; break;
    case "MacroGameIndex": $gMacroGameIndex = ""; break;
    case "UniqueIDCounter": $gUniqueIDCounter = 0; break;
    case "RandomCounter": $gRandomCounter = 0; break;
    default: break;
  }
}

$systemDQHandlers = [];
$systemDQHandlers["PlayCard_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["PlayCard_AfterAction"] = function($player, $param, $lastResult) {
};

function PlayCard($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["PlayCardCalls"]) || !is_array($_ti["PlayCardCalls"])) $_ti["PlayCardCalls"] = [];
    $_ti["PlayCardCalls"][$player] = ($_ti["PlayCardCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["PlayCardCalls"]) || !is_array($_gi["PlayCardCalls"])) $_gi["PlayCardCalls"] = [];
    $_gi["PlayCardCalls"][$player] = ($_gi["PlayCardCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["PlayCard"]) || !is_array($_ti["PlayCard"])) $_ti["PlayCard"] = [];
    if (!isset($_ti["PlayCard"][$player]) || !is_array($_ti["PlayCard"][$player])) $_ti["PlayCard"][$player] = [];
    $_ti["PlayCard"][$player][$_mzCardID] = ($_ti["PlayCard"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["PlayCard"]) || !is_array($_gi["PlayCard"])) $_gi["PlayCard"] = [];
    if (!isset($_gi["PlayCard"][$player]) || !is_array($_gi["PlayCard"][$player])) $_gi["PlayCard"][$player] = [];
    $_gi["PlayCard"][$player][$_mzCardID] = ($_gi["PlayCard"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  if(function_exists("CanPlayCard") && !CanPlayCard($player, $mzID)) return null;
  $result = DoPlayCard($player, $mzID);
  global $systemDQHandlers;
  $systemDQHandlers["PlayCard_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "PlayCard_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["Draw_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["Draw_AfterAction"] = function($player, $param, $lastResult) {
};

function Draw($player, $amount) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["DrawCalls"]) || !is_array($_ti["DrawCalls"])) $_ti["DrawCalls"] = [];
    $_ti["DrawCalls"][$player] = ($_ti["DrawCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["DrawCalls"]) || !is_array($_gi["DrawCalls"])) $_gi["DrawCalls"] = [];
    $_gi["DrawCalls"][$player] = ($_gi["DrawCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("amount", $amount);
  $result = DoDrawCard($player, $amount);
  global $systemDQHandlers;
  $systemDQHandlers["Draw_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "Draw_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["ActivateAbility_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["ActivateAbility_AfterAction"] = function($player, $param, $lastResult) {
};

function ActivateAbility($player, $mzID, $abilityIndex) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["ActivateAbilityCalls"]) || !is_array($_ti["ActivateAbilityCalls"])) $_ti["ActivateAbilityCalls"] = [];
    $_ti["ActivateAbilityCalls"][$player] = ($_ti["ActivateAbilityCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["ActivateAbilityCalls"]) || !is_array($_gi["ActivateAbilityCalls"])) $_gi["ActivateAbilityCalls"] = [];
    $_gi["ActivateAbilityCalls"][$player] = ($_gi["ActivateAbilityCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  DecisionQueueController::StoreVariable("abilityIndex", $abilityIndex);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["ActivateAbility"]) || !is_array($_ti["ActivateAbility"])) $_ti["ActivateAbility"] = [];
    if (!isset($_ti["ActivateAbility"][$player]) || !is_array($_ti["ActivateAbility"][$player])) $_ti["ActivateAbility"][$player] = [];
    $_ti["ActivateAbility"][$player][$_mzCardID] = ($_ti["ActivateAbility"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["ActivateAbility"]) || !is_array($_gi["ActivateAbility"])) $_gi["ActivateAbility"] = [];
    if (!isset($_gi["ActivateAbility"][$player]) || !is_array($_gi["ActivateAbility"][$player])) $_gi["ActivateAbility"][$player] = [];
    $_gi["ActivateAbility"][$player][$_mzCardID] = ($_gi["ActivateAbility"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  $result = DoActivatedAbility($player, $mzID, $abilityIndex);
  global $systemDQHandlers;
  $systemDQHandlers["ActivateAbility_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "ActivateAbility_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["PitchCard_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["PitchCard_AfterAction"] = function($player, $param, $lastResult) {
};

function PitchCard($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["PitchCardCalls"]) || !is_array($_ti["PitchCardCalls"])) $_ti["PitchCardCalls"] = [];
    $_ti["PitchCardCalls"][$player] = ($_ti["PitchCardCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["PitchCardCalls"]) || !is_array($_gi["PitchCardCalls"])) $_gi["PitchCardCalls"] = [];
    $_gi["PitchCardCalls"][$player] = ($_gi["PitchCardCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["PitchCard"]) || !is_array($_ti["PitchCard"])) $_ti["PitchCard"] = [];
    if (!isset($_ti["PitchCard"][$player]) || !is_array($_ti["PitchCard"][$player])) $_ti["PitchCard"][$player] = [];
    $_ti["PitchCard"][$player][$_mzCardID] = ($_ti["PitchCard"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["PitchCard"]) || !is_array($_gi["PitchCard"])) $_gi["PitchCard"] = [];
    if (!isset($_gi["PitchCard"][$player]) || !is_array($_gi["PitchCard"][$player])) $_gi["PitchCard"][$player] = [];
    $_gi["PitchCard"][$player][$_mzCardID] = ($_gi["PitchCard"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  if(function_exists("CanPitchCard") && !CanPitchCard($player, $mzID)) return null;
  $result = DoPitchCard($player, $mzID);
  global $systemDQHandlers;
  $systemDQHandlers["PitchCard_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "PitchCard_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["CardPitched_AfterChoice"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "CardPitched_Action", 99);
};

$systemDQHandlers["CardPitched_Action"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "CardPitched_AfterAction", 99);
};

$systemDQHandlers["CardPitched_AfterAction"] = function($player, $param, $lastResult) {
};

function CardPitched($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["CardPitchedCalls"]) || !is_array($_ti["CardPitchedCalls"])) $_ti["CardPitchedCalls"] = [];
    $_ti["CardPitchedCalls"][$player] = ($_ti["CardPitchedCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["CardPitchedCalls"]) || !is_array($_gi["CardPitchedCalls"])) $_gi["CardPitchedCalls"] = [];
    $_gi["CardPitchedCalls"][$player] = ($_gi["CardPitchedCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  $paramString = str_replace(' ', '_', $mzID);
  DecisionQueueController::AddDecision($player, "SYSTEM", "CardPitched_Choice|$paramString", 100);
}

$systemDQHandlers["RevealCard_AfterChoice"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "RevealCard_Action", 99);
};

$systemDQHandlers["RevealCard_Action"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "RevealCard_AfterAction", 99);
};

$systemDQHandlers["RevealCard_AfterAction"] = function($player, $param, $lastResult) {
};

function RevealCard($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["RevealCardCalls"]) || !is_array($_ti["RevealCardCalls"])) $_ti["RevealCardCalls"] = [];
    $_ti["RevealCardCalls"][$player] = ($_ti["RevealCardCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["RevealCardCalls"]) || !is_array($_gi["RevealCardCalls"])) $_gi["RevealCardCalls"] = [];
    $_gi["RevealCardCalls"][$player] = ($_gi["RevealCardCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  $paramString = str_replace(' ', '_', $mzID);
  DecisionQueueController::AddDecision($player, "SYSTEM", "RevealCard_Choice|$paramString", 100);
}

$systemDQHandlers["ResolveCard_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["ResolveCard_AfterAction"] = function($player, $param, $lastResult) {
};

function ResolveCard($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["ResolveCardCalls"]) || !is_array($_ti["ResolveCardCalls"])) $_ti["ResolveCardCalls"] = [];
    $_ti["ResolveCardCalls"][$player] = ($_ti["ResolveCardCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["ResolveCardCalls"]) || !is_array($_gi["ResolveCardCalls"])) $_gi["ResolveCardCalls"] = [];
    $_gi["ResolveCardCalls"][$player] = ($_gi["ResolveCardCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["ResolveCard"]) || !is_array($_ti["ResolveCard"])) $_ti["ResolveCard"] = [];
    if (!isset($_ti["ResolveCard"][$player]) || !is_array($_ti["ResolveCard"][$player])) $_ti["ResolveCard"][$player] = [];
    $_ti["ResolveCard"][$player][$_mzCardID] = ($_ti["ResolveCard"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["ResolveCard"]) || !is_array($_gi["ResolveCard"])) $_gi["ResolveCard"] = [];
    if (!isset($_gi["ResolveCard"][$player]) || !is_array($_gi["ResolveCard"][$player])) $_gi["ResolveCard"][$player] = [];
    $_gi["ResolveCard"][$player][$_mzCardID] = ($_gi["ResolveCard"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  $result = DoResolveCard($player, $mzID);
  global $systemDQHandlers;
  $systemDQHandlers["ResolveCard_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "ResolveCard_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["PrepareCard_AfterChoice"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "PrepareCard_Action", 99);
};

$systemDQHandlers["PrepareCard_Action"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "PrepareCard_AfterAction", 99);
};

$systemDQHandlers["PrepareCard_AfterAction"] = function($player, $param, $lastResult) {
};

function PrepareCard($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["PrepareCardCalls"]) || !is_array($_ti["PrepareCardCalls"])) $_ti["PrepareCardCalls"] = [];
    $_ti["PrepareCardCalls"][$player] = ($_ti["PrepareCardCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["PrepareCardCalls"]) || !is_array($_gi["PrepareCardCalls"])) $_gi["PrepareCardCalls"] = [];
    $_gi["PrepareCardCalls"][$player] = ($_gi["PrepareCardCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  $paramString = str_replace(' ', '_', $mzID);
  DecisionQueueController::AddDecision($player, "SYSTEM", "PrepareCard_Choice|$paramString", 100);
}

$systemDQHandlers["ResolveAbility_AfterChoice"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "ResolveAbility_Action", 99);
};

$systemDQHandlers["ResolveAbility_Action"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "ResolveAbility_AfterAction", 99);
};

$systemDQHandlers["ResolveAbility_AfterAction"] = function($player, $param, $lastResult) {
};

function ResolveAbility($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["ResolveAbilityCalls"]) || !is_array($_ti["ResolveAbilityCalls"])) $_ti["ResolveAbilityCalls"] = [];
    $_ti["ResolveAbilityCalls"][$player] = ($_ti["ResolveAbilityCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["ResolveAbilityCalls"]) || !is_array($_gi["ResolveAbilityCalls"])) $_gi["ResolveAbilityCalls"] = [];
    $_gi["ResolveAbilityCalls"][$player] = ($_gi["ResolveAbilityCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  $paramString = str_replace(' ', '_', $mzID);
  DecisionQueueController::AddDecision($player, "SYSTEM", "ResolveAbility_Choice|$paramString", 100);
}

$systemDQHandlers["StartTurn_AfterChoice"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "StartTurn_Action", 99);
};

$systemDQHandlers["StartTurn_Action"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "StartTurn_AfterAction", 99);
};

$systemDQHandlers["StartTurn_AfterAction"] = function($player, $param, $lastResult) {
};

function StartTurn($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["StartTurnCalls"]) || !is_array($_ti["StartTurnCalls"])) $_ti["StartTurnCalls"] = [];
    $_ti["StartTurnCalls"][$player] = ($_ti["StartTurnCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["StartTurnCalls"]) || !is_array($_gi["StartTurnCalls"])) $_gi["StartTurnCalls"] = [];
    $_gi["StartTurnCalls"][$player] = ($_gi["StartTurnCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  $paramString = str_replace(' ', '_', $mzID);
  DecisionQueueController::AddDecision($player, "SYSTEM", "StartTurn_Choice|$paramString", 100);
}

$systemDQHandlers["CardPlayed_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["CardPlayed_AfterAction"] = function($player, $param, $lastResult) {
};

function CardPlayed($player, $mzID, $cardID, $fromZone) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["CardPlayedCalls"]) || !is_array($_ti["CardPlayedCalls"])) $_ti["CardPlayedCalls"] = [];
    $_ti["CardPlayedCalls"][$player] = ($_ti["CardPlayedCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["CardPlayedCalls"]) || !is_array($_gi["CardPlayedCalls"])) $_gi["CardPlayedCalls"] = [];
    $_gi["CardPlayedCalls"][$player] = ($_gi["CardPlayedCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  DecisionQueueController::StoreVariable("cardID", $cardID);
  DecisionQueueController::StoreVariable("fromZone", $fromZone);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["CardPlayed"]) || !is_array($_ti["CardPlayed"])) $_ti["CardPlayed"] = [];
    if (!isset($_ti["CardPlayed"][$player]) || !is_array($_ti["CardPlayed"][$player])) $_ti["CardPlayed"][$player] = [];
    $_ti["CardPlayed"][$player][$_mzCardID] = ($_ti["CardPlayed"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["CardPlayed"]) || !is_array($_gi["CardPlayed"])) $_gi["CardPlayed"] = [];
    if (!isset($_gi["CardPlayed"][$player]) || !is_array($_gi["CardPlayed"][$player])) $_gi["CardPlayed"][$player] = [];
    $_gi["CardPlayed"][$player][$_mzCardID] = ($_gi["CardPlayed"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  $result = OnCardPlayed($player, $mzID, $cardID, $fromZone);
  global $systemDQHandlers;
  $systemDQHandlers["CardPlayed_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "CardPlayed_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["AttackDeclared_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["AttackDeclared_AfterAction"] = function($player, $param, $lastResult) {
};

function AttackDeclared($player, $mzID, $attacker, $defender) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["AttackDeclaredCalls"]) || !is_array($_ti["AttackDeclaredCalls"])) $_ti["AttackDeclaredCalls"] = [];
    $_ti["AttackDeclaredCalls"][$player] = ($_ti["AttackDeclaredCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["AttackDeclaredCalls"]) || !is_array($_gi["AttackDeclaredCalls"])) $_gi["AttackDeclaredCalls"] = [];
    $_gi["AttackDeclaredCalls"][$player] = ($_gi["AttackDeclaredCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  DecisionQueueController::StoreVariable("attacker", $attacker);
  DecisionQueueController::StoreVariable("defender", $defender);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["AttackDeclared"]) || !is_array($_ti["AttackDeclared"])) $_ti["AttackDeclared"] = [];
    if (!isset($_ti["AttackDeclared"][$player]) || !is_array($_ti["AttackDeclared"][$player])) $_ti["AttackDeclared"][$player] = [];
    $_ti["AttackDeclared"][$player][$_mzCardID] = ($_ti["AttackDeclared"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["AttackDeclared"]) || !is_array($_gi["AttackDeclared"])) $_gi["AttackDeclared"] = [];
    if (!isset($_gi["AttackDeclared"][$player]) || !is_array($_gi["AttackDeclared"][$player])) $_gi["AttackDeclared"][$player] = [];
    $_gi["AttackDeclared"][$player][$_mzCardID] = ($_gi["AttackDeclared"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  $result = OnAttackDeclared($player, $mzID, $attacker, $defender);
  global $systemDQHandlers;
  $systemDQHandlers["AttackDeclared_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "AttackDeclared_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["Defended_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["Defended_AfterAction"] = function($player, $param, $lastResult) {
};

function Defended($player, $mzID, $defender) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["DefendedCalls"]) || !is_array($_ti["DefendedCalls"])) $_ti["DefendedCalls"] = [];
    $_ti["DefendedCalls"][$player] = ($_ti["DefendedCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["DefendedCalls"]) || !is_array($_gi["DefendedCalls"])) $_gi["DefendedCalls"] = [];
    $_gi["DefendedCalls"][$player] = ($_gi["DefendedCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  DecisionQueueController::StoreVariable("defender", $defender);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["Defended"]) || !is_array($_ti["Defended"])) $_ti["Defended"] = [];
    if (!isset($_ti["Defended"][$player]) || !is_array($_ti["Defended"][$player])) $_ti["Defended"][$player] = [];
    $_ti["Defended"][$player][$_mzCardID] = ($_ti["Defended"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["Defended"]) || !is_array($_gi["Defended"])) $_gi["Defended"] = [];
    if (!isset($_gi["Defended"][$player]) || !is_array($_gi["Defended"][$player])) $_gi["Defended"][$player] = [];
    $_gi["Defended"][$player][$_mzCardID] = ($_gi["Defended"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  $result = OnDefended($player, $mzID, $defender);
  global $systemDQHandlers;
  $systemDQHandlers["Defended_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "Defended_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["Damage_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["Damage_AfterAction"] = function($player, $param, $lastResult) {
};

function Damage($player, $sourceMZ, $targetPlayer, $amount, $damageType) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["DamageCalls"]) || !is_array($_ti["DamageCalls"])) $_ti["DamageCalls"] = [];
    $_ti["DamageCalls"][$player] = ($_ti["DamageCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["DamageCalls"]) || !is_array($_gi["DamageCalls"])) $_gi["DamageCalls"] = [];
    $_gi["DamageCalls"][$player] = ($_gi["DamageCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("sourceMZ", $sourceMZ);
  DecisionQueueController::StoreVariable("targetPlayer", $targetPlayer);
  DecisionQueueController::StoreVariable("amount", $amount);
  DecisionQueueController::StoreVariable("damageType", $damageType);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($sourceMZ);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("sourceMZCardID", $_sourceObj->CardID);
  }
  $result = DoDamage($player, $sourceMZ, $targetPlayer, $amount, $damageType);
  global $systemDQHandlers;
  $systemDQHandlers["Damage_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "Damage_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["Hit_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["Hit_AfterAction"] = function($player, $param, $lastResult) {
};

function Hit($player, $mzID, $amount) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["HitCalls"]) || !is_array($_ti["HitCalls"])) $_ti["HitCalls"] = [];
    $_ti["HitCalls"][$player] = ($_ti["HitCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["HitCalls"]) || !is_array($_gi["HitCalls"])) $_gi["HitCalls"] = [];
    $_gi["HitCalls"][$player] = ($_gi["HitCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  DecisionQueueController::StoreVariable("amount", $amount);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["Hit"]) || !is_array($_ti["Hit"])) $_ti["Hit"] = [];
    if (!isset($_ti["Hit"][$player]) || !is_array($_ti["Hit"][$player])) $_ti["Hit"][$player] = [];
    $_ti["Hit"][$player][$_mzCardID] = ($_ti["Hit"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["Hit"]) || !is_array($_gi["Hit"])) $_gi["Hit"] = [];
    if (!isset($_gi["Hit"][$player]) || !is_array($_gi["Hit"][$player])) $_gi["Hit"][$player] = [];
    $_gi["Hit"][$player][$_mzCardID] = ($_gi["Hit"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  $result = OnHit($player, $mzID, $amount);
  global $systemDQHandlers;
  $systemDQHandlers["Hit_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "Hit_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["ChainLinkResolved_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["ChainLinkResolved_AfterAction"] = function($player, $param, $lastResult) {
};

function ChainLinkResolved($player, $mzID) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["ChainLinkResolvedCalls"]) || !is_array($_ti["ChainLinkResolvedCalls"])) $_ti["ChainLinkResolvedCalls"] = [];
    $_ti["ChainLinkResolvedCalls"][$player] = ($_ti["ChainLinkResolvedCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["ChainLinkResolvedCalls"]) || !is_array($_gi["ChainLinkResolvedCalls"])) $_gi["ChainLinkResolvedCalls"] = [];
    $_gi["ChainLinkResolvedCalls"][$player] = ($_gi["ChainLinkResolvedCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  // Store macro parameters for access in generated ability code
  DecisionQueueController::StoreVariable("mzID", $mzID);
  // Store the source card ID when a prerequisite helper needs card-scoped lookup
  $_sourceObj = GetZoneObject($mzID);
  if ($_sourceObj !== null && isset($_sourceObj->CardID)) {
    DecisionQueueController::StoreVariable("mzIDCardID", $_sourceObj->CardID);
  }
  // Track this macro invocation in the persistent turn index (keyed by card ID)
  $_mzObj = GetZoneObject($mzID);
  $_mzCardID = $_mzObj !== null ? ($_mzObj->CardID ?? null) : null;
  if ($_mzCardID !== null) {
    $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["ChainLinkResolved"]) || !is_array($_ti["ChainLinkResolved"])) $_ti["ChainLinkResolved"] = [];
    if (!isset($_ti["ChainLinkResolved"][$player]) || !is_array($_ti["ChainLinkResolved"][$player])) $_ti["ChainLinkResolved"][$player] = [];
    $_ti["ChainLinkResolved"][$player][$_mzCardID] = ($_ti["ChainLinkResolved"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti));
    $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["ChainLinkResolved"]) || !is_array($_gi["ChainLinkResolved"])) $_gi["ChainLinkResolved"] = [];
    if (!isset($_gi["ChainLinkResolved"][$player]) || !is_array($_gi["ChainLinkResolved"][$player])) $_gi["ChainLinkResolved"][$player] = [];
    $_gi["ChainLinkResolved"][$player][$_mzCardID] = ($_gi["ChainLinkResolved"][$player][$_mzCardID] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi));
  }
  $result = OnChainLinkResolved($player, $mzID);
  global $systemDQHandlers;
  $systemDQHandlers["ChainLinkResolved_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "ChainLinkResolved_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

$systemDQHandlers["EndTurn_AfterChoice"] = function($player, $param, $lastResult) {
  DecisionQueueController::AddDecision($player, "SYSTEM", "EndTurn_Action", 99);
};

$systemDQHandlers["EndTurn_AfterAction"] = function($player, $param, $lastResult) {
};

function EndTurn($player) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["EndTurnCalls"]) || !is_array($_ti["EndTurnCalls"])) $_ti["EndTurnCalls"] = [];
    $_ti["EndTurnCalls"][$player] = ($_ti["EndTurnCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["EndTurnCalls"]) || !is_array($_gi["EndTurnCalls"])) $_gi["EndTurnCalls"] = [];
    $_gi["EndTurnCalls"][$player] = ($_gi["EndTurnCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  DecisionQueueController::AddDecision($player, "SYSTEM", "EndTurn_Choice", 100);
}

$systemDQHandlers["CombatChainClosed_Action"] = function($player, $result, $lastDecision) {
};

$systemDQHandlers["CombatChainClosed_AfterAction"] = function($player, $param, $lastResult) {
};

function CombatChainClosed($player) {
  // Track total macro call count per player
  { $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
    if (!isset($_ti["CombatChainClosedCalls"]) || !is_array($_ti["CombatChainClosedCalls"])) $_ti["CombatChainClosedCalls"] = [];
    $_ti["CombatChainClosedCalls"][$player] = ($_ti["CombatChainClosedCalls"][$player] ?? 0) + 1;
    SetMacroTurnIndex(json_encode($_ti)); }
  { $_gi = GetMacroGameIndexArray();
    if (!isset($_gi["CombatChainClosedCalls"]) || !is_array($_gi["CombatChainClosedCalls"])) $_gi["CombatChainClosedCalls"] = [];
    $_gi["CombatChainClosedCalls"][$player] = ($_gi["CombatChainClosedCalls"][$player] ?? 0) + 1;
    SetMacroGameIndex(json_encode($_gi)); }
  $result = OnCombatChainClosed($player);
  global $systemDQHandlers;
  $systemDQHandlers["CombatChainClosed_Action"]($player, $result, null);
  DecisionQueueController::AddDecision($player, "PASSPARAMETER", "$result", 99);
  DecisionQueueController::AddDecision($player, "SYSTEM", "CombatChainClosed_AfterAction", 99);
  $dqController = new DecisionQueueController();
  $dqController->ExecuteStaticMethods($player, "-");
}

// Shared MacroGameIndex helpers for hooks that learn the specific card later.
function GetMacroGameIndexArray() {
  $raw = GetMacroGameIndex();
  if (!is_string($raw) || $raw === '' || $raw === '-') return [];
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

function IncrementMacroGameIndexCount($bucketName, $player, $amount = 1) {
  if($bucketName === '') return;
  $_gi = GetMacroGameIndexArray();
  if (!isset($_gi[$bucketName]) || !is_array($_gi[$bucketName])) $_gi[$bucketName] = [];
  $_gi[$bucketName][$player] = ($_gi[$bucketName][$player] ?? 0) + max(1, intval($amount));
  SetMacroGameIndex(json_encode($_gi));
}

function IncrementMacroGameIndexBucket($bucketName, $player, $entryKey, $amount = 1) {
  if($bucketName === '' || $entryKey === '') return;
  $_gi = GetMacroGameIndexArray();
  if (!isset($_gi[$bucketName]) || !is_array($_gi[$bucketName])) $_gi[$bucketName] = [];
  if (!isset($_gi[$bucketName][$player]) || !is_array($_gi[$bucketName][$player])) $_gi[$bucketName][$player] = [];
  $_gi[$bucketName][$player][$entryKey] = ($_gi[$bucketName][$player][$entryKey] ?? 0) + max(1, intval($amount));
  SetMacroGameIndex(json_encode($_gi));
}

function IncrementMacroGameIndexTimeline($bucketName, $player, $turnNumber, $amount = 1) {
  $turnKey = strval(max(1, intval($turnNumber)));
  IncrementMacroGameIndexBucket($bucketName, $player, $turnKey, $amount);
}

function IncrementMacroGameIndexCard($macroName, $player, $cardID, $amount = 1) {
  IncrementMacroGameIndexBucket($macroName, $player, $cardID, $amount);
}

// Macro turn-index helpers
// For each macro that has an mzID parameter, three functions are generated:
//   MacroNameTurnCount($player, $cardID)  - how many times the macro fired for that card this turn
//   MacroNameTurnCards($player)           - array of [cardID => count] for all cards this turn
//   ClearMacroNameTurnIndex()             - reset the index (call at end-of-turn)
//   MacroNameCallCount($player)           - total times the macro was called for that player this turn
//   ClearMacroNameCallIndex()             - reset the total call counter

function PlayCardTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PlayCard"][$player][$cardID] ?? 0;
}

function PlayCardTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PlayCard"][$player] ?? [];
}

function ClearPlayCardTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["PlayCard"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ActivateAbilityTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ActivateAbility"][$player][$cardID] ?? 0;
}

function ActivateAbilityTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ActivateAbility"][$player] ?? [];
}

function ClearActivateAbilityTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ActivateAbility"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function PitchCardTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PitchCard"][$player][$cardID] ?? 0;
}

function PitchCardTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PitchCard"][$player] ?? [];
}

function ClearPitchCardTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["PitchCard"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function CardPitchedTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CardPitched"][$player][$cardID] ?? 0;
}

function CardPitchedTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CardPitched"][$player] ?? [];
}

function ClearCardPitchedTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["CardPitched"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function RevealCardTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["RevealCard"][$player][$cardID] ?? 0;
}

function RevealCardTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["RevealCard"][$player] ?? [];
}

function ClearRevealCardTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["RevealCard"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ResolveCardTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ResolveCard"][$player][$cardID] ?? 0;
}

function ResolveCardTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ResolveCard"][$player] ?? [];
}

function ClearResolveCardTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ResolveCard"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function PrepareCardTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PrepareCard"][$player][$cardID] ?? 0;
}

function PrepareCardTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PrepareCard"][$player] ?? [];
}

function ClearPrepareCardTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["PrepareCard"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ResolveAbilityTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ResolveAbility"][$player][$cardID] ?? 0;
}

function ResolveAbilityTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ResolveAbility"][$player] ?? [];
}

function ClearResolveAbilityTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ResolveAbility"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function StartTurnTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["StartTurn"][$player][$cardID] ?? 0;
}

function StartTurnTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["StartTurn"][$player] ?? [];
}

function ClearStartTurnTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["StartTurn"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function CardPlayedTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CardPlayed"][$player][$cardID] ?? 0;
}

function CardPlayedTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CardPlayed"][$player] ?? [];
}

function ClearCardPlayedTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["CardPlayed"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function AttackDeclaredTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["AttackDeclared"][$player][$cardID] ?? 0;
}

function AttackDeclaredTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["AttackDeclared"][$player] ?? [];
}

function ClearAttackDeclaredTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["AttackDeclared"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function DefendedTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["Defended"][$player][$cardID] ?? 0;
}

function DefendedTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["Defended"][$player] ?? [];
}

function ClearDefendedTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["Defended"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function HitTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["Hit"][$player][$cardID] ?? 0;
}

function HitTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["Hit"][$player] ?? [];
}

function ClearHitTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["Hit"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ChainLinkResolvedTurnCount($player, $cardID) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ChainLinkResolved"][$player][$cardID] ?? 0;
}

function ChainLinkResolvedTurnCards($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ChainLinkResolved"][$player] ?? [];
}

function ClearChainLinkResolvedTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ChainLinkResolved"]);
  SetMacroTurnIndex(json_encode($_ti));
}

// Macro game-index helpers
// For each macro that has an mzID parameter, three functions are generated:
//   MacroNameGameCount($player, $cardID)  - how many times the macro fired for that card this game
//   MacroNameGameCards($player)           - array of [cardID => count] for all cards this game
//   MacroNameGameCallCount($player)       - total times the macro was called for that player this game

function PlayCardGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PlayCard"][$player][$cardID] ?? 0;
}

function PlayCardGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PlayCard"][$player] ?? [];
}

function ActivateAbilityGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ActivateAbility"][$player][$cardID] ?? 0;
}

function ActivateAbilityGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ActivateAbility"][$player] ?? [];
}

function PitchCardGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PitchCard"][$player][$cardID] ?? 0;
}

function PitchCardGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PitchCard"][$player] ?? [];
}

function CardPitchedGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CardPitched"][$player][$cardID] ?? 0;
}

function CardPitchedGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CardPitched"][$player] ?? [];
}

function RevealCardGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["RevealCard"][$player][$cardID] ?? 0;
}

function RevealCardGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["RevealCard"][$player] ?? [];
}

function ResolveCardGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ResolveCard"][$player][$cardID] ?? 0;
}

function ResolveCardGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ResolveCard"][$player] ?? [];
}

function PrepareCardGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PrepareCard"][$player][$cardID] ?? 0;
}

function PrepareCardGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PrepareCard"][$player] ?? [];
}

function ResolveAbilityGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ResolveAbility"][$player][$cardID] ?? 0;
}

function ResolveAbilityGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ResolveAbility"][$player] ?? [];
}

function StartTurnGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["StartTurn"][$player][$cardID] ?? 0;
}

function StartTurnGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["StartTurn"][$player] ?? [];
}

function CardPlayedGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CardPlayed"][$player][$cardID] ?? 0;
}

function CardPlayedGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CardPlayed"][$player] ?? [];
}

function AttackDeclaredGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["AttackDeclared"][$player][$cardID] ?? 0;
}

function AttackDeclaredGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["AttackDeclared"][$player] ?? [];
}

function DefendedGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["Defended"][$player][$cardID] ?? 0;
}

function DefendedGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["Defended"][$player] ?? [];
}

function HitGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["Hit"][$player][$cardID] ?? 0;
}

function HitGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["Hit"][$player] ?? [];
}

function ChainLinkResolvedGameCount($player, $cardID) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ChainLinkResolved"][$player][$cardID] ?? 0;
}

function ChainLinkResolvedGameCards($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ChainLinkResolved"][$player] ?? [];
}

// Per-player total call-count helpers for all macros.
// CallCount increments every time the macro function is entered, regardless of card.

function PlayCardCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PlayCardCalls"][$player] ?? 0;
}

function ClearPlayCardCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["PlayCardCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function PlayCardGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PlayCardCalls"][$player] ?? 0;
}

function DrawCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["DrawCalls"][$player] ?? 0;
}

function ClearDrawCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["DrawCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function DrawGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["DrawCalls"][$player] ?? 0;
}

function ActivateAbilityCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ActivateAbilityCalls"][$player] ?? 0;
}

function ClearActivateAbilityCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ActivateAbilityCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ActivateAbilityGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ActivateAbilityCalls"][$player] ?? 0;
}

function PitchCardCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PitchCardCalls"][$player] ?? 0;
}

function ClearPitchCardCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["PitchCardCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function PitchCardGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PitchCardCalls"][$player] ?? 0;
}

function CardPitchedCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CardPitchedCalls"][$player] ?? 0;
}

function ClearCardPitchedCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["CardPitchedCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function CardPitchedGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CardPitchedCalls"][$player] ?? 0;
}

function RevealCardCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["RevealCardCalls"][$player] ?? 0;
}

function ClearRevealCardCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["RevealCardCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function RevealCardGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["RevealCardCalls"][$player] ?? 0;
}

function ResolveCardCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ResolveCardCalls"][$player] ?? 0;
}

function ClearResolveCardCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ResolveCardCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ResolveCardGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ResolveCardCalls"][$player] ?? 0;
}

function PrepareCardCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["PrepareCardCalls"][$player] ?? 0;
}

function ClearPrepareCardCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["PrepareCardCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function PrepareCardGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["PrepareCardCalls"][$player] ?? 0;
}

function ResolveAbilityCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ResolveAbilityCalls"][$player] ?? 0;
}

function ClearResolveAbilityCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ResolveAbilityCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ResolveAbilityGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ResolveAbilityCalls"][$player] ?? 0;
}

function StartTurnCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["StartTurnCalls"][$player] ?? 0;
}

function ClearStartTurnCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["StartTurnCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function StartTurnGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["StartTurnCalls"][$player] ?? 0;
}

function CardPlayedCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CardPlayedCalls"][$player] ?? 0;
}

function ClearCardPlayedCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["CardPlayedCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function CardPlayedGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CardPlayedCalls"][$player] ?? 0;
}

function AttackDeclaredCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["AttackDeclaredCalls"][$player] ?? 0;
}

function ClearAttackDeclaredCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["AttackDeclaredCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function AttackDeclaredGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["AttackDeclaredCalls"][$player] ?? 0;
}

function DefendedCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["DefendedCalls"][$player] ?? 0;
}

function ClearDefendedCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["DefendedCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function DefendedGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["DefendedCalls"][$player] ?? 0;
}

function DamageCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["DamageCalls"][$player] ?? 0;
}

function ClearDamageCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["DamageCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function DamageGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["DamageCalls"][$player] ?? 0;
}

function HitCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["HitCalls"][$player] ?? 0;
}

function ClearHitCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["HitCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function HitGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["HitCalls"][$player] ?? 0;
}

function ChainLinkResolvedCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["ChainLinkResolvedCalls"][$player] ?? 0;
}

function ClearChainLinkResolvedCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["ChainLinkResolvedCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function ChainLinkResolvedGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["ChainLinkResolvedCalls"][$player] ?? 0;
}

function EndTurnCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["EndTurnCalls"][$player] ?? 0;
}

function ClearEndTurnCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["EndTurnCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function EndTurnGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["EndTurnCalls"][$player] ?? 0;
}

function CombatChainClosedCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CombatChainClosedCalls"][$player] ?? 0;
}

function ClearCombatChainClosedCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["CombatChainClosedCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function CombatChainClosedGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CombatChainClosedCalls"][$player] ?? 0;
}

function AttackPowerModifierCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["AttackPowerModifierCalls"][$player] ?? 0;
}

function ClearAttackPowerModifierCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["AttackPowerModifierCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function AttackPowerModifierGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["AttackPowerModifierCalls"][$player] ?? 0;
}

function DefenseModifierCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["DefenseModifierCalls"][$player] ?? 0;
}

function ClearDefenseModifierCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["DefenseModifierCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function DefenseModifierGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["DefenseModifierCalls"][$player] ?? 0;
}

function CostModifierCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["CostModifierCalls"][$player] ?? 0;
}

function ClearCostModifierCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["CostModifierCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function CostModifierGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["CostModifierCalls"][$player] ?? 0;
}

function GoAgainModifierCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["GoAgainModifierCalls"][$player] ?? 0;
}

function ClearGoAgainModifierCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["GoAgainModifierCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function GoAgainModifierGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["GoAgainModifierCalls"][$player] ?? 0;
}

function DominateModifierCallCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["DominateModifierCalls"][$player] ?? 0;
}

function ClearDominateModifierCallIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["DominateModifierCalls"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function DominateModifierGameCallCount($player) {
  $_gi = GetMacroGameIndexArray();
  return $_gi["DominateModifierCalls"][$player] ?? 0;
}

// Per-player turn-count helpers for amount-based macros (no mzID).
// The counter is incremented inside the ChoiceFunction (e.g. DoDrawCard) per
// actual unit processed, not per macro call.

function DrawTurnCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["Draw"][$player] ?? 0;
}

function ClearDrawTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["Draw"]);
  SetMacroTurnIndex(json_encode($_ti));
}

function DamageTurnCount($player) {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  return $_ti["Damage"][$player] ?? 0;
}

function ClearDamageTurnIndex() {
  $_ti = json_decode(GetMacroTurnIndex() ?: '{}', true) ?: [];
  unset($_ti["Damage"]);
  SetMacroTurnIndex(json_encode($_ti));
}

// Compute virtual properties for objects before sending to client
function ComputeVirtualProperties($obj) {
  if(!isset($obj->Location)) return;
  switch($obj->Location) {
    case "Hero":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      $obj->Marked = FaBHNTMarkedBadge($obj);
      break;
    case "Weapons":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      $obj->PowerCounters = FaBPowerCounters($obj);
      $obj->SteamCounters = FaBSteamCounters($obj);
      break;
    case "Equipment":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      $obj->EnergyCounters = FaBEnergyCounters($obj);
      $obj->DefenseCounters = FaBDefenseCounters($obj);
      $obj->HauntCounters = FaBUPRHauntCounters($obj);
      $obj->BindCounters = FaBELEBindCounters($obj);
      break;
    case "Arena":
      $obj->HoloCounters = FaBOMNHolo($obj);
      $obj->EnergyCounters = FaBEnergyCounters($obj);
      $obj->FrostZone = FaBMPGFrostZone($obj);
      $obj->SuspenseCounters = FaBSUPDisplaySuspense($obj);
      $obj->GoldCounters = FaBSEAGoldCounters($obj);
      $obj->BalanceCounters = FaBSEABalanceCounters($obj);
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      $obj->SteamCounters = FaBSteamCounters($obj);
      $obj->PowerCounters = FaBPowerCounters($obj);
      $obj->AllyHealth = FaBUPRDisplayHealth($obj);
      $obj->FrostCounters = FaBUPRFrostCounters($obj);
      $obj->RazeCounters = FaBUPRRazeCounters($obj);
      $obj->EnduranceCounters = FaBUPREnduranceCounters($obj);
      $obj->FlowCounters = FaBELEFlowCounters($obj);
      $obj->VerseCounters = FaBEVRVerseCounters($obj);
      $obj->DoomCounters = FaBDYNDoomCounter($obj);
      break;
    case "CombatChain":
      $obj->CombatPower = FaBDisplayCombatPower($obj);
      $obj->CombatDefense = FaBDisplayCombatDefense($obj);
      $obj->CombatGoAgain = FaBDisplayGoAgain($obj);
      $obj->CombatTotals = FaBDisplayCombatTotals($obj);
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      break;
    case "Deck":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      break;
    case "Hand":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      break;
    case "Arsenal":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      $obj->LessonCounters = FaBLessonCounters($obj);
      $obj->AimCounters = FaBDYNAimCounter($obj);
      break;
    case "Graveyard":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      break;
    case "Banish":
      $obj->SelectionMetadata = FaBSelectionMetadata($obj);
      break;
    default: break;
  }
}

?>