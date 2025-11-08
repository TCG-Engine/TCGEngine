<?php

include './zzImageConverter.php';
include './Core/Trie.php';
include "./Core/HTTPLibraries.php";
include_once "./AccountFiles/AccountSessionAPI.php";

$response = new stdClass();
$error = CheckLoggedInUserMod();
if($error !== "") {
  $response->error = $error;
  echo json_encode($response);
  exit();
}

$rootName = TryGET("rootName", "");
$schemaFile = "./Schemas/" . $rootName . "/GameSchema.txt";

$handler = fopen($schemaFile, "r");
$rootName = trim(fgets($handler));
$reloadStyle = trim(fgets($handler));
$readAuth = trim(fgets($handler));
$editAuth = trim(fgets($handler));
$zones = [];
$headerElements = [];
$initializeScript = "";
$clientIncludes = [];
$serverIncludes = [];
$assetReflection = null;
$pageBackground = "";
$numRows = 0;

$zoneObj = null;
while(!feof($handler)) {
  $line = fgets($handler);
  if($line !== false) {
    $line = trim($line);
    if($line == "") continue;
    $lineArr = explode(":", $line);
    $lineType = $lineArr[0];
    $lineValue = count($lineArr) > 1 ? implode(":", array_slice($lineArr, 1)) : "";
    switch($lineType) {
      case "Initialization":
        $initializeScript = trim($lineValue);
        break;
      case "ClientInclude":
        array_push($clientIncludes, trim($lineValue));
        break;
      case "ServerInclude":
        array_push($serverIncludes, trim($lineValue));
        break;
      case "AssetReflection":
        $assetReflection = trim($lineValue);
        break;
      case "Header":
        $headerElement = new StdClass();
        $headerElement->Title = "";
        $headerElement->Icon = "";
        $headerElement->Link = "";
        $headerElement->Module = "";
        $headerElement->Target = "";
        $elementArr = explode(",", $lineValue);
        for($i=0; $i<count($elementArr); ++$i) {
          $elementArr[$i] = trim($elementArr[$i]);
          $parameterArr = explode("=", $elementArr[$i]);
          $varName = ucwords($parameterArr[0]);
          $headerElement->$varName = $parameterArr[1];
          if($varName == "Module" && $parameterArr[1] == "AssetVisibility")
          {
            $headerElement->AssetType = 1;
          }
          if($varName == "Link") $headerElement->Link = str_replace(">", "=", $headerElement->Link);
        }
        array_push($headerElements, $headerElement);
        break;
      case "PageBackground":
        $pageBackground = trim($lineValue);
        break;
      case "Display":
        $displayArr = explode(",", $lineValue);
        for($i=0; $i<count($displayArr); ++$i) {
          $displayArr[$i] = trim($displayArr[$i]);
          $propertyArr = explode("=", $displayArr[$i]);
          switch($propertyArr[0]) {
            case "Visibility":
              $zoneObj->Visibility = $propertyArr[1];
              break;
            case "Mode":
              $thisPropArr = explode("(",$propertyArr[1]);
              $zoneObj->DisplayMode = $thisPropArr[0];
              $params = count($thisPropArr) < 2 || $thisPropArr[1] == "" ? "" : substr($thisPropArr[1], 0, strlen($thisPropArr[1])-1);//Remove ending parenthesis
              $zoneObj->DisplayParameters = $params == "" ? [] : explode(",", $params);
              break;
            case "Scope":
              // Scope can be Global or Player (default)
              $zoneObj->Scope = $propertyArr[1];
              break;
            case "Split":
              $zoneObj->Split = $propertyArr[1];
              break;
            case "Row":
              $zoneObj->Row = $propertyArr[1];
              if($zoneObj->Row > $numRows) $numRows = $zoneObj->Row;
              break;
            case "Left":
              $zoneObj->Left = $propertyArr[1];
              break;
            case "Right":
              $zoneObj->Right = $propertyArr[1];
              break;
            case "Top":
              $zoneObj->Top = $propertyArr[1];
              break;
            case "Bottom":
              $zoneObj->Bottom = $propertyArr[1];
              break;
            case "Width":
              $zoneObj->Width = $propertyArr[1];
              break;
          }
        }
        break;
      case "DragMode":
        $zoneObj->DragMode = trim($lineValue);
        break;
      case "Filter":
        array_push($zoneObj->Filters, trim($lineValue));
        break;
      case "Heatmap":
        $heatmap = new StdClass();
        $heatmapArr = explode(", ", $lineValue);
        for($i=0; $i<count($heatmapArr); ++$i) {
          $heatmapArr[$i] = trim($heatmapArr[$i]);
          $propertyArr = explode("=", $heatmapArr[$i]);
          switch($propertyArr[0]) {
            case "Property":
              $heatmap->Property = $propertyArr[1];
              break;
            case "FunctionMap":
              $heatmap->FunctionMap = $propertyArr[1];
              break;
            default: break;
          }
        }
        array_push($zoneObj->Heatmaps, $heatmap);
        break;
      case "Sort":
        $zoneObj->Sort = new StdClass();
        $sortArr = explode(", ", $lineValue);
        for($i=0; $i<count($sortArr); ++$i) {
          $sortArr[$i] = trim($sortArr[$i]);
          $propertyArr = explode("=", $sortArr[$i]);
          switch($propertyArr[0]) {
            case "Property":
              $zoneObj->Sort->Property = $propertyArr[1];
              break;
            default: break;
          }
        }
        break;
      case "AddValidation":
        $zoneObj->AddValidation = trim($lineValue);
        break;
      case "Click":
        $clickArr = explode(",", $lineValue);
        for($i=0; $i<count($clickArr); ++$i) {
          $clickArr[$i] = trim($clickArr[$i]);
          if($clickArr[$i] == "None") continue;
          $clickObj = new StdClass();
          list($functionName, $paramString) = explode('(', $clickArr[$i], 2);
          $clickObj->Action = $functionName;
          $params = rtrim($paramString, ')');
          $clickObj->Parameters = $params ? explode(',', $params) : [];
          echo $clickObj->Action . "<BR>";
          echo implode(", ", $clickObj->Parameters) . "<BR>";
          array_push($zoneObj->ClickActions, $clickObj);
        }
        break;
      case "Macros":
        $macrosArr = explode(",", $lineValue);
        for($i=0; $i<count($macrosArr); ++$i) {
          $macrosArr[$i] = trim($macrosArr[$i]);
          if($macrosArr[$i] == "None") continue;
          $macroObj = new StdClass();
          $macroObj->Name = $macrosArr[$i];
          array_push($zoneObj->Macros, $macroObj);
        }
        break;
      case "Widgets":
        $widgetsArr = explode(",", $lineValue);
        for($i=0; $i<count($widgetsArr); ++$i) {
          $widgetsArr[$i] = trim($widgetsArr[$i]);
          if($widgetsArr[$i] == "None") continue;
          $widgetArr = explode("=", $widgetsArr[$i]);
          $widgetObj = new StdClass();
          $widgetObj->LinkedProperty = $widgetArr[0];
          $actionArr = explode("&", $widgetArr[1]);
          $widgetObj->Actions = [];
          for($j=0; $j<count($actionArr); ++$j) {
            $actionArr[$j] = trim($actionArr[$j]);
            if($actionArr[$j] == "None") continue;
            $actionObj = new StdClass();
            $actionObj->Action = $actionArr[$j];
            array_push($widgetObj->Actions, $actionObj);
          }
          array_push($zoneObj->Widgets, $widgetObj);
        }
        break;
      default://This is a new zone
        if($zoneObj != null) array_push($zones, $zoneObj);
        $zone = str_replace(' ', '', $line);
        $zoneArr = explode("-", $zone);
        $zoneName = $zoneArr[0];
        $zoneObj = new StdClass();
        $zoneObj->Name = $zoneName;
        $zoneObj->Properties = [];
        $propertyArr = explode(",", $zoneArr[1]);
        for($i=0; $i<count($propertyArr); ++$i) {
          $thisProperty = explode(":", $propertyArr[$i]);
          $propertyObj = new StdClass();
          $propertyObj->Name = trim($thisProperty[0]);
          $thisProperty = explode("=", $thisProperty[1]);
          $propertyObj->Type = trim($thisProperty[0]);
          $propertyObj->DefaultValue = count($thisProperty)>1 ? trim($thisProperty[1]) : "\"-\"";
          array_push($zoneObj->Properties, $propertyObj);
        }
        //Assign default value for all zoneObj display properties
        $zoneObj->Visibility = "Public";
        $zoneObj->DisplayMode = "Single";
        $zoneObj->Split = "Auto";
        $zoneObj->Row = -1;
        $zoneObj->Left = -1;
        $zoneObj->Right = -1;
        $zoneObj->Top = -1;
        $zoneObj->Bottom = -1;
        $zoneObj->Width = -1;
        $zoneObj->Macros = [];
        $zoneObj->Widgets = [];
        $zoneObj->ClickActions = [];
        $zoneObj->DragMode = "Normal";
        $zoneObj->Filters = [];
        $zoneObj->Heatmaps = [];
        $zoneObj->Sort = null;
        $zoneObj->AddValidation = "";
        $zoneObj->Scope = "Player";
        break;
    }
  }
}

if($zoneObj != null) array_push($zones, $zoneObj);//The previous ones are added when a new one is found, need to add the last one

fclose($handler);

$rootPath = "./" . $rootName;
if(!is_dir($rootPath)) mkdir($rootPath, 0755, true);

//Write the zone accessors file
$filename = $rootPath . "/ZoneAccessors.php";
$handler = fopen($filename, "w");
$mzGetObject = "function &GetZoneObject(\$mzID) {\r\n";
$mzGetObject .= "  global \$playerID;\r\n";
$mzGetObject .= "  \$mzArr = explode(\"-\",\$mzID);\r\n";
$mzGetObject .= "  switch(\$mzArr[0]) {\r\n";
$mzGetZone = "function &GetZone(\$mzID) {\r\n";
$mzGetZone .= "  global \$playerID;\r\n";
$mzGetZone .= "  \$mzArr = explode(\"-\",\$mzID);\r\n";
$mzGetZone .= "  switch(\$mzArr[0]) {\r\n";
fwrite($handler, "<?php\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  //Getter
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    // Global-scoped zones don't take a player parameter
    fwrite($handler, "function &Get" . $zoneName . "() {\r\n");
    fwrite($handler, "  global \$g" . $zoneName . ";\r\n");
    fwrite($handler, "  return \$g" . $zoneName . ";\r\n");
    fwrite($handler, "}\r\n\r\n");
  } else {
    fwrite($handler, "function &Get" . $zoneName . "(\$player) {\r\n");
    fwrite($handler, "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n");
    fwrite($handler, "  if (\$player == 1) return \$p1" . $zoneName . ";\r\n");
    fwrite($handler, "  else return \$p2" . $zoneName . ";\r\n");
    fwrite($handler, "}\r\n\r\n");
  }
  //Setter
  fwrite($handler, "function Add" . $zoneName . "(\$player");
    for($j=0; $j<count($zone->Properties); ++$j) {
      $property = $zone->Properties[$j];
      fwrite($handler, ", \$" . $property->Name . "=" . $property->DefaultValue);
    }
  fwrite($handler, ") {\r\n");
  if($zone->AddValidation != "") {
    fwrite($handler, "  if(!" . $zone->AddValidation . "(\$CardID)) return;\r\n");
  }
  fwrite($handler, "  \$zoneObj = new " . $zoneName . "(");
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    fwrite($handler, "\$" . $property->Name);
    if($j < count($zone->Properties) - 1) fwrite($handler, " . ' ' . ");
  }
  fwrite($handler, ");\r\n");
  if (strtolower($scope) == 'global') {
    fwrite($handler, "  \$zone = &Get" . $zoneName . "();\r\n");
    fwrite($handler, "  array_push(\$zone, \$zoneObj);\r\n");
  } else {
    fwrite($handler, "  \$zone = &Get" . $zoneName . "(\$player);\r\n");
    fwrite($handler, "  array_push(\$zone, \$zoneObj);\r\n");
  }
  fwrite($handler, "}\r\n\r\n");

  //Add to the master zone object getter
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    $mzGetObject .= "    case \"" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(); return \$zoneArr[\$mzArr[1]];\r\n";
    $mzGetZone .= "    case \"" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(); return \$zoneArr;\r\n";
  } else {
    $mzGetObject .= "    case \"my" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID); return \$zoneArr[\$mzArr[1]];\r\n";
    $mzGetObject .= "    case \"their" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID == 1 ? 2 : 1); return \$zoneArr[\$mzArr[1]];\r\n";
    $mzGetZone .= "    case \"my" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID); return \$zoneArr;\r\n";
    $mzGetZone .= "    case \"their" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID == 1 ? 2 : 1); return \$zoneArr;\r\n";
  }
}
$mzGetObject .= "    default: return null;\r\n";
$mzGetObject .= "  }\r\n";
$mzGetObject .= "}\r\n\r\n";
$mzGetZone .= "    default: return null;\r\n";
$mzGetZone .= "  }\r\n";
$mzGetZone .= "}\r\n\r\n";
fwrite($handler, $mzGetObject);
fwrite($handler, $mzGetZone);

//MZAddZone
fwrite($handler, "function MZAddZone(\$player, \$zoneName, \$cardID) {\r\n");
fwrite($handler, "  switch(\$zoneName) {\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    fwrite($handler, "    case \"" . $zoneName . "\": Add" . $zoneName . "(CardID:\$cardID); break;\r\n");
  } else {
    fwrite($handler, "    case \"my" . $zoneName . "\": Add" . $zoneName . "(\$player, CardID:\$cardID); break;\r\n");
    fwrite($handler, "    case \"their" . $zoneName . "\": Add" . $zoneName . "(\$player == 1 ? 2 : 1, CardID:\$cardID); break;\r\n");
  }
}
fwrite($handler, "    default: break;\r\n");
fwrite($handler, "  }\r\n");
fwrite($handler, "}\r\n\r\n");

//MZClearZone
fwrite($handler, "function MZClearZone(\$player, \$zoneName) {\r\n");
fwrite($handler, "  switch(\$zoneName) {\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    fwrite($handler, "    case \"" . $zoneName . "\": \$zone = &Get" . $zoneName . "(); for(\$i=0; \$i<count(\$zone); ++\$i) \$zone[\$i]->Remove(); break;\r\n");
  } else {
    fwrite($handler, "    case \"my" . $zoneName . "\": \$zone = &Get" . $zoneName . "(\$player); for(\$i=0; \$i<count(\$zone); ++\$i) \$zone[\$i]->Remove(); break;\r\n");
    fwrite($handler, "    case \"their" . $zoneName . "\": \$zone = &Get" . $zoneName . "(\$player == 1 ? 2 : 1); for(\$i=0; \$i<count(\$zone); ++\$i) \$zone[\$i]->Remove(); break;\r\n");
  }
}
fwrite($handler, "    default: break;\r\n");
fwrite($handler, "  }\r\n");
fwrite($handler, "}\r\n\r\n");

fwrite($handler, "?>");
fclose($handler);
//Write the class file
$filename = $rootPath . "/ZoneClasses.php";
$handler = fopen($filename, "w");
fwrite($handler, "<?php\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  fwrite($handler, "class " . $zoneName . " {\r\n");
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    fwrite($handler, "  public \$" . $property->Name . ";\r\n");
  }
  fwrite($handler, "  public \$removed;\r\n");
  fwrite($handler, "  function __construct(\$line) {\r\n");
  fwrite($handler, "    \$arr = explode(\" \", \$line);\r\n");
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    $propertyName = $property->Name;
    $propertyType = $property->Type;
    fwrite($handler, "    \$this->" . $propertyName . " = ");
    if($propertyType == "int" || $propertyType == "number") fwrite($handler, "(count(\$arr) > " . $j . " ? intval(\$arr[" . $j . "]) : -1);\r\n");
    else if($propertyType == "float") fwrite($handler, "(count(\$arr) > " . $j . " ? floatval(\$arr[" . $j . "]) : -1);\r\n");
    else fwrite($handler, "(count(\$arr) > " . $j . " ? \$arr[" . $j . "] : \"\");\r\n");
  }
  fwrite($handler, "  }\r\n");
  //Serialize function
  fwrite($handler, "  function Serialize() {\r\n");
  fwrite($handler, "    \$rv = \"\";\r\n");
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    $propertyName = $property->Name;
    if($j > 0) fwrite($handler, "    \$rv .= \" \";\r\n");
    fwrite($handler, "    \$rv .= \$this->" . $propertyName . ";\r\n");
  }
  fwrite($handler, "    return \$rv;\r\n");
  fwrite($handler, "  }\r\n");
  //Remove function
  fwrite($handler, "  function Remove(\$trigger=\"\") {\r\n");
  fwrite($handler, "    \$this->removed = true;\r\n");
  fwrite($handler, "  }\r\n");
  //Removed function
  fwrite($handler, "  function Removed() {\r\n");
  fwrite($handler, "    return \$this->removed;\r\n");
  fwrite($handler, "  }\r\n");
  //DragMode function
  fwrite($handler, "  function DragMode() {\r\n");
  fwrite($handler, "    return \"" . $zone->DragMode . "\";\r\n");
  fwrite($handler, "  }\r\n");

  fwrite($handler, "  static function GetMacros() {\r\n");
  fwrite($handler, "    return [");
  for($j=0; $j<count($zone->Macros); ++$j) {
    $macro = $zone->Macros[$j];
    fwrite($handler, "\"" . $macro->Name . "\"");
    if($j < count($zone->Macros) - 1) fwrite($handler, ", ");
  }
  fwrite($handler, "];\r\n");
  fwrite($handler, "  }\r\n");
  if($zoneName == "Versions") {
    $versionsModule = GetModuleOfType("Versions");
    if($versionsModule != NULL) {
      $versionZones = explode(";", $versionsModule->Zones);
      fwrite($handler, "  static function GetSerializedZones() {\r\n");
      fwrite($handler, "    \$rv = \"\";\r\n");
      for($j=0; $j<count($versionZones); ++$j) {
        fwrite($handler, "    \$zone = &GetZone(\"my" . $versionZones[$j] . "\");\r\n");
        fwrite($handler, "    for(\$i=0; \$i<count(\$zone); ++\$i) {\r\n");
        fwrite($handler, "      if(\$i > 0) \$rv .= \"<v1>\";\r\n");
        fwrite($handler, "      \$rv .= \$zone[\$i]->Serialize();\r\n");
        fwrite($handler, "    }\r\n");
        if($j < count($versionZones) - 1) fwrite($handler, "    \$rv .= \"<v0>\";\r\n");
      }
      fwrite($handler, "    return \$rv;\r\n");
      fwrite($handler, "  }\r\n");
    }
  }

  fwrite($handler, "}\r\n\r\n");//End of this class
}
fwrite($handler, "?>");
fclose($handler);
//Write the Gamestate parsing file
$filename = $rootPath . "/GamestateParser.php";
$handler = fopen($filename, "w");
fwrite($handler, "<?php\r\n");
for($i=0; $i<count($serverIncludes); ++$i) {
  fwrite($handler, "include __DIR__ . '" . $serverIncludes[$i] . "';\r\n");
}
//Function to get asset reflection path
fwrite($handler, "function GetAssetReflectionPath() {\r\n");
fwrite($handler, "  return \"" . ($assetReflection === null ? "" : $assetReflection) . "\";\r\n");
fwrite($handler, "}\r\n\r\n");
//Function to get edit authorization type
fwrite($handler, "function GetEditAuth() {\r\n");
fwrite($handler, "  return \"" . $editAuth . "\";\r\n");
fwrite($handler, "}\r\n\r\n");
//Initialize gamestate function
fwrite($handler, "function InitializeGamestate() {\r\n");
fwrite($handler, GetZoneGlobals($zones) . "\r\n");
fwrite($handler, GetCoreGlobals() . "\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    fwrite($handler, "  \$g" . $zoneName . " = [];\r\n");
  } else {
    fwrite($handler, "  \$p1" . $zoneName . " = [];\r\n");
    fwrite($handler, "  \$p2" . $zoneName . " = [];\r\n");
  }
}
fwrite($handler, "  \$currentPlayer = 1;\r\n");//TODO: Change this to startPlayer (needs to be linked up w/ lobby code)
fwrite($handler, "  \$updateNumber = 1;\r\n");//TODO: Change this to startPlayer (needs to be linked up w/ lobby code)
fwrite($handler, "}\r\n\r\n");
//Write gamestate function
fwrite($handler, "function WriteGamestate(\$filepath=\"./\") {\r\n");
fwrite($handler, GetZoneGlobals($zones) . "\r\n");
fwrite($handler, GetCoreGlobals() . "\r\n");
fwrite($handler, AddWriteGamestate() . "\r\n");

fwrite($handler, "}\r\n\r\n");
//Parse gamestate function
fwrite($handler, "function ParseGamestate(\$filepath=\"./\") {\r\n");
fwrite($handler, GetZoneGlobals($zones) . "\r\n");
fwrite($handler, GetCoreGlobals() . "\r\n");
fwrite($handler, AddReadGamestate() . "\r\n");

fwrite($handler, "}\r\n\r\n");
fwrite($handler, "?>");
fclose($handler);

//Write the Gamestate network file
$filename = $rootPath . "/GetNextTurn.php";
$handler = fopen($filename, "w");
fwrite($handler, "<?php\r\n");
fwrite($handler, "include '../Core/UILibraries.php';\r\n");
fwrite($handler, "include '../Core/NetworkingLibraries.php';\r\n");
fwrite($handler, "include '../Core/HTTPLibraries.php';\r\n");
fwrite($handler, "include '../Assets/patreon-php-master/src/PatreonLibraries.php';\r\n");
fwrite($handler, "include './GamestateParser.php';\r\n");
fwrite($handler, "include './ZoneAccessors.php';\r\n");
fwrite($handler, "include './ZoneClasses.php';\r\n");
//TODO: Validate these inputs
fwrite($handler, "\$gameName = TryGet(\"gameName\");\r\n");
fwrite($handler, "\$playerID = TryGet(\"playerID\");\r\n");
fwrite($handler, "\$lastUpdate = TryGet(\"lastUpdate\", 0);\r\n");
fwrite($handler, "\$count = 0;\r\n");
fwrite($handler, "while(!CheckUpdate(\$gameName, \$lastUpdate) && \$count < 100) {\r\n");
fwrite($handler, "  usleep(100000); //100 milliseconds\r\n");
fwrite($handler, "  ++\$count;\r\n");
fwrite($handler, "}\r\n");
fwrite($handler, "if(\$count == 100) {\r\n");
fwrite($handler, "  echo(\"KEEPALIVE\");\r\n");
fwrite($handler, "  exit;\r\n");
fwrite($handler, "}\r\n");
fwrite($handler, "ParseGamestate();\r\n");
fwrite($handler, "SetCachePiece(\$gameName, 1, \$updateNumber);\r\n");
fwrite($handler, "echo(\$updateNumber . \"<~>\");\r\n");
$assetVisibilityModule = GetModuleOfType("AssetVisibility");
if($assetVisibilityModule != NULL) {
  fwrite($handler, "include_once '../AccountFiles/AccountSessionAPI.php';\r\n");
  fwrite($handler, "include_once '../AccountFiles/AccountDatabaseAPI.php';\r\n");
  fwrite($handler, "include_once '../Database/ConnectionManager.php';\r\n");
  fwrite($handler, "include_once '../Database/functions.inc.php';\r\n");
  fwrite($handler, "include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';\r\n");
  fwrite($handler, "include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';\r\n");
  fwrite($handler, "\$assetData = LoadAssetData(" . $assetVisibilityModule->AssetType . ", \$gameName);\r\n");
  fwrite($handler, "if(\$assetData[\"assetVisibility\"] == 0 || \$assetData[\"assetVisibility\"] > 1000) {\r\n");
  fwrite($handler, "  if (!IsUserLoggedIn()) {\r\n");
  fwrite($handler, "    if (isset(\$_COOKIE[\"rememberMeToken\"])) {\r\n");
  fwrite($handler, "      loginFromCookie();\r\n");
  fwrite($handler, "    }\r\n");
  fwrite($handler, "  }\r\n");
  fwrite($handler, "  if(!IsUserLoggedIn()) {\r\n");
  fwrite($handler, "    echo(\"You must be logged in to view this asset.\");\r\n");
  fwrite($handler, "    exit;\r\n");
  fwrite($handler, "  }\r\n");
  fwrite($handler, "  \$loggedInUser = LoggedInUser();\r\n");
  fwrite($handler, "  \$assetOwner = \$assetData[\"assetOwner\"];\r\n");
  fwrite($handler, "  if(\$loggedInUser != \$assetOwner) {\r\n");
  fwrite($handler, "    if(\$assetData[\"assetVisibility\"] > 1000000) {\r\n");
  fwrite($handler, "      if(\$assetData[\"assetVisibility\"] != 99999999 && !IsPatron(\$assetData[\"assetVisibility\"])){\r\n");//Check if they're a patron
  fwrite($handler, "        echo(\"You must be a patron to view this.\");\r\n");
  fwrite($handler, "        exit;\r\n");
  fwrite($handler, "      }\r\n");
  fwrite($handler, "    } else if(\$assetData[\"assetVisibility\"] > 1000) {\r\n");
  fwrite($handler, "      \$userData = LoadUserDataFromId(\$loggedInUser);\r\n");
  fwrite($handler, "      if(\$userData[\"teamID\"] == null || \$assetData[\"assetVisibility\"] != \$userData[\"teamID\"]+1000) {\r\n");
  fwrite($handler, "        echo(\"You must be on this team to view this.\");\r\n");
  fwrite($handler, "        exit;\r\n");
  fwrite($handler, "      }\r\n");
  fwrite($handler, "    } else {\r\n");
  fwrite($handler, "      echo(\"You must own this asset view it.\");\r\n");
  fwrite($handler, "      exit;\r\n");
  fwrite($handler, "    }\r\n");
  fwrite($handler, "  }\r\n");
  fwrite($handler, "}\r\n");
}
fwrite($handler, AddGetNextTurnForPlayer(1) . "\r\n");
fwrite($handler, AddGetNextTurnForPlayer(2) . "\r\n");

fwrite($handler, "?>");
fclose($handler);

//Write the game render file
$filename = $rootPath . "/NextTurnRender.php";
$handler = fopen($filename, "w");
fwrite($handler, "<?php\r\n");
fwrite($handler, AddNextTurn() . "\r\n");
fwrite($handler, "?>");
fclose($handler);

WriteInitialLayout();

//Write JavaScript helper file
$fileSuffix = date("YmdHis");
$filename = "$rootPath/GeneratedUI_$fileSuffix.js";
//delete old files if they exist
$oldFiles = glob("$rootPath/GeneratedUI*.js");
foreach ($oldFiles as $oldFile) {
    unlink($oldFile);
}
$handler = fopen($filename, "w");
fwrite($handler, AddGeneratedUI() . "\r\n");
fclose($handler);


echo("Game code generator completed successfully!");

function GetZoneGlobals($zones) {
  $zoneGlobals = "";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $zoneName = $zone->Name;
    $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
    if (strtolower($scope) == 'global') {
      $zoneGlobals .= "  global \$g" . $zoneName . ";\r\n";
    } else {
      $zoneGlobals .= "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n";
    }
  }
  return $zoneGlobals;
}

function GetCoreGlobals() {
  $coreGlobals = "";
  $coreGlobals .= "  global \$currentPlayer, \$updateNumber;\r\n";
  return $coreGlobals;
}

function AddReadGamestate() {
  $readGamestate = "";
  global $zones;
  $readGamestate .= "  InitializeGamestate();\r\n";
  $readGamestate .= "  global \$gameName;\r\n";
  $readGamestate .= "  \$filename = \$filepath . \"Games/\$gameName/Gamestate.txt\";\r\n";
  $readGamestate .= "  \$handler = fopen(\$filename, \"r\");\r\n";
  $readGamestate .= "  \$currentPlayer = intval(fgets(\$handler));\r\n";
  $readGamestate .= "  \$updateNumber = intval(fgets(\$handler));\r\n";
  $readGamestate .= "  while (!feof(\$handler)) {\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
    if (strtolower($scope) == 'global') {
      $readGamestate .= "    \$line = fgets(\$handler);\r\n";
      $readGamestate .= "    if (\$line !== false) {\r\n";
      $readGamestate .= "      \$num = intval(\$line);\r\n";
      $readGamestate .= "      for(\$i=0; \$i<\$num; ++\$i) {\r\n";
      $readGamestate .= "        \$line = fgets(\$handler);\r\n";
      $readGamestate .= "        if (\$line !== false) {\r\n";
      $readGamestate .= "          \$obj = new " . $zone->Name . "(trim(\$line));\r\n";
      $readGamestate .= "          \$g" . $zone->Name . " = \$obj;\r\n";
      $readGamestate .= "        }\r\n";
      $readGamestate .= "      }\r\n";
      $readGamestate .= "    }\r\n";
      if($zone->DisplayMode == "Value" || $zone->DisplayMode == "Radio") $readGamestate .= "    if(\$g" . $zone->Name . " == null) \$g" . $zone->Name . " = new " . $zone->Name . "(0);\r\n";
    } else {
      $readGamestate .= AddReadZone($zone, 1);
      $readGamestate .= AddReadZone($zone, 2);
    }
  }
  $readGamestate .= "  }\r\n";
  $readGamestate .= "  fclose(\$handler);\r\n";
  return $readGamestate;
}

function AddReadZone($zone, $player) {
  $zoneName = $zone->Name;
  $rv = "";
  $rv .= "    \$line = fgets(\$handler);\r\n";
  $rv .= "    if (\$line !== false) {\r\n";
  $rv .= "      \$num = intval(\$line);\r\n";
  $rv .= "      for(\$i=0; \$i<\$num; ++\$i) {\r\n";
  $rv .= "        \$line = fgets(\$handler);\r\n";
  $rv .= "        if (\$line !== false) {\r\n";
  $rv .= "          \$obj = new " . $zoneName . "(trim(\$line));\r\n";
  $rv .= "          array_push(\$p" . $player . $zoneName . ", \$obj);\r\n";
  $rv .= "        }\r\n";
  $rv .= "      }\r\n";
  $rv .= "    }\r\n";
  if($zone->DisplayMode == "Value" || $zone->DisplayMode == "Radio") $rv .= "    if(count(\$p" . $player . $zoneName . ") == 0) array_push(\$p" . $player . $zoneName . ", new " . $zoneName . "(0));\r\n";
  return $rv;
}

function AddWriteGamestate() {
  global $zones;
  $writeGamestate = "";
  $writeGamestate .= "  global \$gameName;\r\n";
  $writeGamestate .= "  \$filename = \$filepath . \"Games/\$gameName/Gamestate.txt\";\r\n";
  $writeGamestate .= "  \$handler = fopen(\$filename, \"w\");\r\n";
  //First write global data
  $writeGamestate .= "  fwrite(\$handler, \$currentPlayer . \"\\r\\n\");\r\n";
  $writeGamestate .= "  fwrite(\$handler, \$updateNumber . \"\\r\\n\");\r\n";
  //Then write player zones
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $zoneName = $zone->Name;
    $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
    if (strtolower($scope) == 'global') {
      $writeGamestate .= "  \$zoneText = \"\";\r\n";
      $writeGamestate .= "  \$count = 0;\r\n";
      $writeGamestate .= "  if(\$g" . $zoneName . " !== null && !\$g" . $zoneName . "->Removed()) { \$count = 1; \$zoneText = trim(\$g" . $zoneName . "->Serialize()) . \"\\r\\n\"; }\r\n";
      $writeGamestate .= "  fwrite(\$handler, \$count . \"\\r\\n\");\r\n";
      $writeGamestate .= "  fwrite(\$handler, \$zoneText);\r\n";
    } else {
      $writeGamestate .= AddWriteZone($zoneName, 1);
      $writeGamestate .= AddWriteZone($zoneName, 2);
    }
  }
  return $writeGamestate;
}

function AddWriteZone($zoneName, $player) {
  $rv = "";
  $rv .= "  \$zoneText = \"\";\r\n";
  $rv .= "  \$count = 0;\r\n";
  $rv .= "  for(\$i=0; \$i<count(\$p" . $player . $zoneName . "); ++\$i) {\r\n";
  $rv .= "    if(\$p" . $player . $zoneName . "[\$i]->Removed()) continue;\r\n";
  $rv .= "    ++\$count;\r\n";
  $rv .= "    \$zoneText .= trim(\$p" . $player . $zoneName . "[\$i]->Serialize()) . \"\\r\\n\";\r\n";
  $rv .= "  }\r\n";
  $rv .= "  fwrite(\$handler, \$count . \"\\r\\n\");\r\n";
  $rv .= "  fwrite(\$handler, \$zoneText);\r\n";
  return $rv;
}

function AddGetNextTurnForPlayer($player) {
  global $zones;
  $getNextTurn = "";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $zoneName = "p" . $player . $zone->Name;
    echo($zoneName . "<BR>");
    if($i > 0 || $player > 1) $getNextTurn .= "echo(\"<~>\");\r\n";
    if($zone->DisplayMode == "Single") {
      if($zone->Visibility == "Public") {
        //$getNextTurn .= "echo \"Single Public\";\r\n";
        if (strtolower(isset($zone->Scope) ? $zone->Scope : 'Player') == 'global') {
          $getNextTurn .= "  \$arr = &Get" . $zone->Name . "();\r\n";
        } else {
          $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
        }
        $getNextTurn .= "  echo(count(\$arr) > 0 ? ClientRenderedCard(\$arr[0]->CardID, counters:count(\$" . $zoneName . "), cardJSON:json_encode(\$arr[0])) : \"\");\r\n";
      } else if($zone->Visibility == "Private") {
        //Single Private
        $getNextTurn .= "  echo(ClientRenderedCard(\"CardBack\", counters:count(\$" . $zoneName . ")));\r\n";

      } else if ($zone->Visibility == "Self") {
        //$getNextTurn .= "echo \"Single Self\";\r\n";
      }
    } else if($zone->DisplayMode == "All" || $zone->DisplayMode == "Pane" || $zone->DisplayMode == "Tile" || $zone->DisplayMode == "None") {
      if (strtolower(isset($zone->Scope) ? $zone->Scope : 'Player') == 'global') {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "();\r\n";
      } else {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
      }
      $getNextTurn .= "  for(\$i=0; \$i<count(\$arr); ++\$i) {\r\n";
      $getNextTurn .= "    if(\$i > 0) echo(\"<|>\");\r\n";
      $getNextTurn .= "    \$obj = \$arr[\$i];\r\n";
      if($zone->Visibility == "Public") {
        $getNextTurn .= "    \$displayID = isset(\$obj->CardID) ? \$obj->CardID : \"-\";\r\n";
        $getNextTurn .= "    echo(ClientRenderedCard(\$displayID, cardJSON:json_encode(\$obj)));\r\n";
      } else if($zone->Visibility == "Private") {
        $getNextTurn .= "    echo(ClientRenderedCard(\"CardBack\"));\r\n";
      } else if ($zone->Visibility == "Self") {
        $getNextTurn .= "    \$displayID = isset(\$obj->CardID) ? \$obj->CardID : \"-\";\r\n";
        $getNextTurn .= "    if(\$playerID == " . $player . ") echo(ClientRenderedCard(\$displayID, cardJSON:json_encode(\$obj)));\r\n";
        $getNextTurn .= "    else echo(ClientRenderedCard(\"CardBack\"));\r\n";
      }
      $getNextTurn .= "  }\r\n";
    } else if($zone->DisplayMode == "Count") {
      $zoneName = count($zone->DisplayParameters) == 0 ? $zone->Name : $zone->DisplayParameters[0];
      if (strtolower(isset($zone->Scope) ? $zone->Scope : 'Player') == 'global') {
        $getNextTurn .= "  \$arr = &Get" . $zoneName . "();\r\n";
      } else {
        $getNextTurn .= "  \$arr = &Get" . $zoneName . "(" . $player . ");\r\n";
      }
      $getNextTurn .= "  echo(count(\$arr));\r\n";
      if($zone->Visibility == "Public") {
        //$getNextTurn .= "echo \"Count Public\";\r\n";
      } else if($zone->Visibility == "Private") {
        //$getNextTurn .= "echo \"Count Private\";\r\n";
      } else if ($zone->Visibility == "Self") {
        //$getNextTurn .= "echo \"Count Self\";\r\n";
      }
    } else if($zone->DisplayMode == "Value") {
      if (strtolower(isset($zone->Scope) ? $zone->Scope : 'Player') == 'global') {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "();\r\n";
      } else {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
      }
      $getNextTurn .= "  echo(\$arr[0]->Value);\r\n";
    }
    else if($zone->DisplayMode == "Radio") {
      if (strtolower(isset($zone->Scope) ? $zone->Scope : 'Player') == 'global') {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "();\r\n";
      } else {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
      }
      $getNextTurn .= "  echo(\$arr[0]->Value);\r\n";
    } else if($zone->DisplayMode == "Calculate") {

    } else if($zone->DisplayMode == "Panel") {
      //$getNextTurn .= "  echo('<div id=" . $zone->Name . " class=\"panel-wrapper\">');\r\n";
      //$getNextTurn .= "  echo('</div>');\r\n";
    }
  }
  return $getNextTurn;
}
function AddNextTurn() {
  global $zones, $numRows, $rootPath;
  $startPiece = 1;
  $numPieces = count($zones);
  $setData = "";
  $myStuff = "";
  $theirStuff = "";
  $myStaticStuff = "";
  $theirStaticStuff = "";
  $header = "echo(\"var myStatic = '';\");\r\n";
  $header .= "echo(\"var theirStatic = '';\");\r\n";
  $header .= "echo(\"var myRows = [];\");\r\n";
  $header .= "echo(\"var theirRows = [];\");\r\n";
  $header .= "echo(\"window.rootPath = '" . $rootPath . "';\");\r\n";
  $header .= "echo(\"var currentPlayerIndex = playerID;\");\r\n";
  $header .= "echo(\"var otherPlayerIndex = playerID == 1 ? 2 : 1;\");\r\n";
  $footer = "echo(\"RenderRows(myRows, theirRows);\");\r\n";
  $footer .= "echo(\"AppendStaticZones(myStatic, theirStatic);\");\r\n";
  for ($i = 0; $i < count($zones); ++$i) {
    $zone = $zones[$i];
    if ($zone->DisplayMode == "Panel") {
      $header .= "echo(\"window.my" . $zone->Name . "Panes = [];\");\r\n";
      $header .= "echo(\"window.their" . $zone->Name . "Panes = [];\");\r\n";
      $footer .= "echo(\"RenderPanes('" . $zone->Name . "', window.my" . $zone->Name . "Panes, window.their" . $zone->Name . "Panes);\");\r\n";
    }
  }
  $header .= "echo(\"for(var i=0; i<" . ($numRows+1) . "; ++i) { myRows[i] = \\\"\\\"; theirRows[i] = \\\"\\\"; }\");\r\n";

  // Add data references first to ensure they're defined before using them
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $index = $i + $startPiece;
    $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
    if (strtolower($scope) == 'global') {
      // Server will emit the same value in the slot for both players; use the base index
      $setData .= "echo(\"window." . $zone->Name . "Data = responseArr[" . $index . "];\");\r\n";
    } else {
      $setData .= "echo(\"window.my" . $zone->Name . "Data = responseArr[" . $index . " + (currentPlayerIndex-1)*" . count($zones) . "];\");\r\n";
      $setData .= "echo(\"window.their" . $zone->Name . "Data = responseArr[" . $index . " + (otherPlayerIndex-1)*" . count($zones) . "];\");\r\n";
    }
  }

  // Then add row-based zones
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $index = $i + $startPiece;
    if($zone->Row >= 0) {//If it's -1, position is defined some other way
      $myStuff .= "echo(\"myRows[" . $zone->Row . "] += PopulateZone('my" . $zone->Name . "', responseArr[" . $index . " + (currentPlayerIndex-1)*" . count($zones) . "], cardSize, '" . $rootPath . "/concat', '" . $zone->Row . "', '". $zone->DisplayMode . "');\");\r\n";
      $theirStuff .= "echo(\"theirRows[" . $zone->Row . "] += PopulateZone('their" . $zone->Name . "', responseArr[" . $index . " + (otherPlayerIndex-1)*" . count($zones) . "], cardSize, '" . $rootPath . "/concat', '" . $zone->Row . "', '". $zone->DisplayMode . "');\");\r\n";
    }
  }

  // Finally add static positioned zones
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $index = $i + $startPiece;
    if($zone->Row < 0) {
      $myStaticStuff .= "echo(\"var dataIndex = " . $index . " + (currentPlayerIndex-1)*" . count($zones) . ";\");\r\n";
      $myStaticStuff .= GeneratedZoneElement($zone, "my", "dataIndex", $dummy);

      $theirStaticStuff .= "echo(\"var dataIndex = " . $index . " + (otherPlayerIndex-1)*" . count($zones) . ";\");\r\n";
      $theirStaticStuff .= GeneratedZoneElement($zone, "their", "dataIndex", $dummy);
    }
  }

  return $header . $setData . $myStuff . $theirStuff . $myStaticStuff . $theirStaticStuff . $footer;
}

function GeneratedZoneElement($zone, $prefix, $index, &$setData) {
  global $rootPath;
  $rv = "";
  $style = "position: absolute;";
  $onclick = "onclick=\\\"ZoneClickHandler(\\\'" . $prefix . $zone->Name . "\\\');\\\"";
  $onscroll = $zone->DisplayMode == "Panel" ? "onscroll=\\\"ZoneScrollHandler(\\\'" . $prefix . $zone->Name . "\\\');\\\"" : "";
  if($zone->Left > -1) $style .= " left:" . $zone->Left . ";";
  if($zone->Right > -1) $style .= " right:" . $zone->Right . ";";
  if($zone->Top > -1) $style .= ($prefix == "my" ? " top:" : " bottom:") . $zone->Top . ";";
  if($zone->Bottom > -1) $style .= ($prefix == "my" ? " bottom:" : " top:") . $zone->Bottom . ";";
  if($zone->Width > -1) $style .= " width:" . $zone->Width . ";";
  if($zone->DisplayMode != "Pane") $style .= " overflow-y:auto;";
  if($zone->DisplayMode == "Pane") $rv .= "echo(\"" . $prefix . "CardPanePanes.push(responseArr[" . $index . "]);\");\r\n";
  else {
    $rv .= "echo(\"" . $prefix . "Static += '<div id=\\\'" . $prefix . $zone->Name . "Wrapper\\\' " . $onclick . " " . $onscroll . " style=\\\"$style\\\">' + PopulateZone('" . $prefix . $zone->Name . "', responseArr[" . $index . "], cardSize, '" . $rootPath . "/concat', '0', '". $zone->DisplayMode . "') + '</div>';\");\r\n";
    $setData .= "echo(\"window." . $prefix . $zone->Name . "Data = responseArr[" . $index . "];\");\r\n";
  }
  if($zone->DisplayMode == "None") return "";
  return $rv;
}

function AddGeneratedUI() {
  global $zones, $assetReflection;
  $rv = "";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    if($zone->DisplayMode == "Panel") {
      $rv .= "var _my_" . $zone->Name . "_activePane = 0;\r\n";
      $rv .= "var _their_" . $zone->Name . "_activePane = 0;\r\n";
    }
  }
  $rv .= "function generatedDragStart() {\r\n";
  $rv .= "  var zone = null;\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $rv .= "  zone = document.getElementById(\"my" . $zone->Name . "\");\r\n";
    $rv .= "  if(!!zone) zone.classList.add(\"droppable\");\r\n";
    //TODO: Only allow their with a setting? Or holding ctrl?
    $rv .= "  zone = document.getElementById(\"their" . $zone->Name . "\");\r\n";
    $rv .= "  if(!!zone) zone.classList.add(\"droppable\");\r\n";
  }
  $rv .= "}\r\n";

  $rv .= "function generatedDragEnd() {\r\n";
  $rv .= "  var zone = null;\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $rv .= "  zone = document.getElementById(\"my" . $zone->Name . "\");\r\n";
    $rv .= "  if(!!zone) zone.classList.remove(\"droppable\");\r\n";
    //TODO: Only allow their with a setting? Or holding ctrl?
    $rv .= "  zone = document.getElementById(\"their" . $zone->Name . "\");\r\n";
    $rv .= "  if(!!zone) zone.classList.remove(\"droppable\");\r\n";
  }
  $rv .= "}\r\n";

  //Client dictionary of zone widgets and their actions
  $rv .= "function GetZoneWidgets(zoneName) {\r\n";
  $rv .= "  switch(zoneName) {\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $widgets = [];
    foreach ($zone->Widgets as $widget) {
      $widgets[$widget->LinkedProperty] = $widget->Actions;
    }
    $rv .= "    case '" . $zone->Name . "':\r\n";
    $rv .= "      return " . json_encode($widgets) . ";\r\n";
  }
  $rv .= "    default:\r\n";
  $rv .= "      return {};\r\n";
  $rv .= "  }\r\n";
  $rv .= "}\r\n";

  //Client dictionary of zone click actions
  $rv .= "function GetZoneClickActions(zoneName) {\r\n";
  $rv .= "  switch(zoneName) {\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $clickActions = [];
    foreach ($zone->ClickActions as $clickAction) {
      $clickActions[] = ['Action' => $clickAction->Action, 'Parameters' => $clickAction->Parameters];
    }
    $rv .= "    case '" . $zone->Name . "':\r\n";
    $rv .= "      return " . json_encode(array_values($clickActions)) . ";\r\n";
  }
  $rv .= "    default:\r\n";
  $rv .= "      return {};\r\n";
  $rv .= "  }\r\n";
  $rv .= "}\r\n";

  //Client dictionary of all zone data
  $rv .= "function GetZoneData(zoneName) {\r\n";
  $rv .= "  switch(zoneName) {\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $rv .= "    case '" . $zone->Name . "': case 'my" . $zone->Name . "': case 'their" . $zone->Name . "': \r\n";
    $rv .= "      return " . json_encode($zone) . ";\r\n";
  }
  $rv .= "    default:\r\n";
  $rv .= "      return {};\r\n";
  $rv .= "  }\r\n";
  $rv .= "}\r\n";

  //Client dictionary of panes for each panel
  $panes = [];
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    if($zone->DisplayMode == "Pane") {
      $panelName = $zone->DisplayParameters[0];
      if (!isset($panes[$panelName])) {
        $panes[$panelName] = "";
      }
      if($panes[$panelName] != "") $panes[$panelName] .= ",";
      $panes[$panelName] .= "'" . $zone->Name . "'";
    }
  }
  $rv .= "function GetPaneData(zoneName) {\r\n";
  $rv .= "  switch(zoneName) {\r\n";
  foreach ($panes as $panelName => $paneZones) {
    $rv .= "    case '" . $panelName . "':\r\n";
    $rv .= "      return [" . $paneZones . "];\r\n";
  }
  $rv .= "    default:\r\n";
  $rv .= "      return [];\r\n";
  $rv .= "  }\r\n";
  $rv .= "}\r\n";

  $rv .= "function AssetReflectionPath() {\r\n";
  $rv .= "  return " . ($assetReflection === null ? "''" : "'" . $assetReflection . "'") . ";\r\n";
  $rv .= "}\r\n";

  //Client function for if screen should be split or not
  return $rv;
}

function WriteInitialLayout() {
  global $zones, $rootPath, $headerElements, $initializeScript, $clientIncludes, $pageBackground;
  $shouldSplitScreen = true;
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    if($zone->Split == "None") $shouldSplitScreen = false;
  }
  $filename = $rootPath . "/InitialLayout.php";
  $handler = fopen($filename, "w");
  fwrite($handler, "<?php\r\n");
  if($initializeScript != "") {
    fwrite($handler, "if(!isset(\$skipInitialize) || !\$skipInitialize) include_once '" . $initializeScript . "';\r\n");
  }
  for ($i = 0; $i < count($clientIncludes); ++$i) {
    fwrite($handler, "echo(\"<script src='" . $clientIncludes[$i] . "'></script>\");\r\n");
  }
  fwrite($handler, "echo(\"<div class='flex-container' style='margin:0px; padding:0px; display: flex; flex-direction: column; width: 100%; height: 100%;'>\");\r\n");
  if(count($headerElements) > 0) {
    fwrite($handler, "echo(\"<div class='flex-item' style='flex-basis: 20px; background-color: #2a2a2a; color: #fff; padding: 0px; display: flex; align-items: left; justify-content: left;'>\");\r\n");
    for ($i = 0; $i < count($headerElements); ++$i) {
      $headerElement = $headerElements[$i];
      if($headerElement->Module != "") {
        fwrite($handler, "echo(\"<div style='padding: 3px; margin: 5px;' id='" . $headerElement->Module . "'>\");\r\n");
        switch($headerElement->Module) {
          case "AssetVisibility":
            fwrite($handler, "include_once \$_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/AccountFiles/AccountSessionAPI.php';\r\n");
            fwrite($handler, "include_once \$_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/AccountFiles/AccountDatabaseAPI.php';\r\n");
            fwrite($handler, "include_once \$_SERVER['DOCUMENT_ROOT'] . '/TCGEngine/Database/ConnectionManager.php';\r\n");
            fwrite($handler, "\$assetData = LoadAssetData($headerElement->AssetType, \$gameName);\r\n");
            fwrite($handler, "\$visibility = \$assetData['assetVisibility'];\r\n");
            fwrite($handler, "\$patreonId = GetUserPatreonID();\r\n");
            fwrite($handler, "\$userData = LoadUserDataFromId(LoggedInUser());\r\n");
            fwrite($handler, "echo(\"<select id='assetVisibilityDropdown' style='background-color: #333; color: #fff; border: none; border-radius: 5px; font-size: 14px;' onchange=\\\"UpdateAssetVisibility(this.value, \$gameName, $headerElement->AssetType)\\\">\");\r\n");
            fwrite($handler, "echo(\"  <option value='private'\" . (\$visibility == 0 ? \" selected\" : \"\") . \">Private</option>\");\r\n");
            fwrite($handler, "if(isset(\$userData['teamID'])) echo(\"  <option value='team'\" . (\$visibility == (1000 + \$userData['teamID']) ? \" selected\" : \"\") . \">Team</option>\");\r\n");
            fwrite($handler, "if(\$patreonId != \"\") echo(\"  <option value='\$patreonId'\" . (\$visibility == \$patreonId ? \" selected\" : \"\") . \">Patreon</option>\");\r\n");
            fwrite($handler, "echo(\"  <option value='link only'\" . (\$visibility == 1 ? \" selected\" : \"\") . \">Link Only</option>\");\r\n");
            fwrite($handler, "echo(\"  <option value='public'\" . (\$visibility == 2 ? \" selected\" : \"\") . \">Public</option>\");\r\n");
            fwrite($handler, "echo(\"</select>\");\r\n");
            break;
          case "Versions":
            fwrite($handler, "echo(\"<select id='versionDropdown' style='background-color: #333; color: #fff; border: none; border-radius: 5px; font-size: 14px;' onchange=\\\"OnVersionChanged(this.value)\\\">\");\r\n");
            fwrite($handler, "echo(\"  <option value='current' selected >Current Version</option>\");\r\n");
            //Print out all the other versions here
            fwrite($handler, "\$versions = &GetVersions(\$playerID);\r\n");
            fwrite($handler, "for(\$i=0; \$i<count(\$versions); ++\$i) {\r\n");
            fwrite($handler, "  echo(\"  <option value='\" . \$i . \"'>Version \" . \$i . \"</option>\");\r\n");
            fwrite($handler, "}\r\n");
            fwrite($handler, "echo(\"  <option value='new'>New Version</option>\");\r\n");
            fwrite($handler, "echo(\"</select>\");\r\n");
            break;
          default: break;
        }
        fwrite($handler, "echo(\"</div>\");\r\n");
      } else {
        $target = $headerElement->Target == "blank" ? " target='_blank'" : "";
        fwrite($handler, "echo(\"<button style='background-color: #333; color: #fff; border: none; padding: 3px; margin: 5px; border-radius: 5px; font-size: 14px; cursor: pointer;' onmouseover=\\\"this.style.backgroundColor='#444';\\\" onmouseout=\\\"this.style.backgroundColor='#333';\\\" onclick=\\\"window.open('" . $headerElement->Link . "', '" . ($headerElement->Target == "blank" ? "_blank" : "_self") . "')\\\"$target>\");\r\n");
        if ($headerElement->Icon != "") {
          fwrite($handler, "echo(\"<img src='" . $headerElement->Icon . "' style='vertical-align: middle; margin-right: 3px; height:16px; width:16px;'>\");\r\n");
        }
        fwrite($handler, "echo(\"<span style='vertical-align: middle;'>" . $headerElement->Title . "</span>\");\r\n");
        fwrite($handler, "echo(\"</button>\");\r\n");
      }
    }
    fwrite($handler, "echo(\"</div>\");\r\n");
  }
  fwrite($handler, "echo(\"<div class='flex-item' style='flex-grow: 1;'>\");\r\n");
  if($shouldSplitScreen) {
    fwrite($handler, "echo(\"<div class='theirStuffWrapper' style='position:relative; z-index:10; left:0; top:0; width:100%; height:50%;'><div class='stuffParent'><div id='theirStuff' class='stuff theirStuff' style='background-image: url(\\\"$pageBackground\\\"); background-size: cover;'></div></div></div>\r\n<div class='myStuffWrapper' style='position:absolute; z-index:10; left:0; top:50%; width:100%; height:50%;'><div style='position:relative; width:100%; height:100%'><div class='stuffParent'><div id='myStuff' class='stuff myStuff' style='background-image: url(\\\"$pageBackground\\\"); background-size: cover;'></div></div></div>\");\r\n");
  } else {
    fwrite($handler, "echo(\"<div class='myStuffWrapper' style='position:relative; z-index:10; left:0; top:0; width:100%; height:100%;'><div class='stuffParent'><div id='myStuff' class='stuff myStuff' style='background-image: url(\\\"$pageBackground\\\"); background-size: cover;'></div></div></div>\r\n<div id='theirStuff' style='display:none;' class='theirStuff'></div>\");\r\n");
  }
  fwrite($handler, "echo(\"</div>\");\r\n");
  fwrite($handler, "echo(\"</div>\");\r\n");
  fwrite($handler, "?>");
  fclose($handler);
}

function GetModuleOfType($type) {
  global $headerElements;
  for($i=0; $i<count($headerElements); ++$i) {
    $headerElement = $headerElements[$i];
    if($headerElement->Module == $type) return $headerElement;
  }
  return null;
}
?>
