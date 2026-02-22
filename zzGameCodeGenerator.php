<?php

include './zzImageConverter.php';
include './Core/Trie.php';
include "./Core/HTTPLibraries.php";
include_once "./AccountFiles/AccountSessionAPI.php";
include_once "./Database/ConnectionManager.php";
include_once "./CardEditor/Database/CardAbilityDB.php";

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
$hasDecisionQueue = false;
$modules = [];
$macros = [];
$hasFlashMessage = false;
$hasAnyIndexedProperties = false;

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
      case "Overlay":
        // Overlay: Status=1:exhausted
        if (!isset($zoneObj->Overlays)) $zoneObj->Overlays = [];
        $overlayParts = explode(":", $lineValue);
        if (count($overlayParts) == 2) {
          $cond = trim($overlayParts[0]); // e.g. Status=1
          $overlayName = trim($overlayParts[1]); // e.g. exhausted
          $condParts = explode("=", $cond);
          if (count($condParts) == 2) {
            $field = trim($condParts[0]);
            $value = trim($condParts[1]);
            $zoneObj->Overlays[] = ["field" => $field, "value" => $value, "overlay" => $overlayName];
          }
        }
        break;
      case "Counters":
        // Counters: Damage=Badge(Color=red,Position=TopRight,ShowZero=false)
        if (!isset($zoneObj->Counters)) $zoneObj->Counters = [];
        $parts = explode("=", $lineValue, 2);
        if (count($parts) == 2) {
          $field = trim($parts[0]);
          $spec = trim($parts[1]);
          $counterType = $spec;
          $params = [];
          // If spec has parentheses, parse type and params
          $pos = strpos($spec, '(');
          if ($pos !== false) {
            $counterType = trim(substr($spec, 0, $pos));
            $paramString = trim(substr($spec, $pos + 1));
            // Remove trailing ) if present
            if (substr($paramString, -1) == ')') $paramString = substr($paramString, 0, -1);
            // Split on commas for simple key=value pairs
            $paramParts = $paramString === '' ? [] : explode(',', $paramString);
            for ($i = 0; $i < count($paramParts); ++$i) {
              $p = trim($paramParts[$i]);
              if ($p == '') continue;
              $kv = explode('=', $p, 2);
              if (count($kv) == 2) {
                $params[trim($kv[0])] = trim($kv[1]);
              } else {
                // positional param (e.g. IconName)
                $params[] = $kv[0];
              }
            }
          }
          $zoneObj->Counters[] = ["field" => $field, "type" => $counterType, "params" => $params];
        }
        break;
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
      case "Module":
        $module = new StdClass();
        $module->Name = "";
        $module->Parameters = "";
        $moduleArr = explode("=", $lineValue, 2);
        $module->Name = trim($moduleArr[0]);
        $module->Parameters = isset($moduleArr[1]) ? trim($moduleArr[1]) : "";
        array_push($modules, $module);
        break;
      case "Macro":
        $macro = new StdClass();
        $macroArr = explode(";", $lineValue);
        for($i=0; $i<count($macroArr); ++$i) {
          $macroArr[$i] = trim($macroArr[$i]);
          if($macroArr[$i] == "") continue;
          $parameterArr = explode("=", $macroArr[$i]);
          $varName = ucwords($parameterArr[0]);
          $macro->$varName = $parameterArr[1];
          if ($varName == 'Name') {
            if (preg_match('/^(\w+)\((.*)\)$/', $parameterArr[1], $matches)) {
              $macro->FunctionName = $matches[1];
              $macro->Parameters = $matches[2] ? array_map('trim', explode(',', $matches[2])) : [];
            } else {
              $macro->FunctionName = $parameterArr[1];
              $macro->Parameters = [];
            }
          }
        }
        array_push($macros, $macro);
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
            case "Display":
              $zoneObj->Display = $propertyArr[1];
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
            case "Reverse":
              $zoneObj->Sort->Reverse = strtolower($propertyArr[1]) === "true";
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
        // Split by comma, but respect parentheses (don't split on commas inside parens)
        $widgetsArr = [];
        $current = "";
        $parenDepth = 0;
        for ($c = 0; $c < strlen($lineValue); $c++) {
          $char = $lineValue[$c];
          if ($char === '(') {
            $parenDepth++;
            $current .= $char;
          } else if ($char === ')') {
            $parenDepth--;
            $current .= $char;
          } else if ($char === ',' && $parenDepth === 0) {
            if ($current !== "") {
              $widgetsArr[] = trim($current);
            }
            $current = "";
          } else {
            $current .= $char;
          }
        }
        if ($current !== "") {
          $widgetsArr[] = trim($current);
        }
        
        for($i=0; $i<count($widgetsArr); ++$i) {
          if($widgetsArr[$i] == "None") continue;
          $widgetArr = explode("=", $widgetsArr[$i], 2);
          $widgetObj = new StdClass();
          $widgetObj->LinkedProperty = $widgetArr[0];
          $widgetObj->Position = "Center"; // Default position
          $widgetObj->Condition = null; // No condition by default
          
          $fullActionSpec = $widgetArr[1];
          // Parse parameters: Position:BottomLeft, Condition:Status=2, etc.
          if (preg_match('/\(([^)]+)\)/', $fullActionSpec, $matches)) {
            $paramString = $matches[1];
            $params = explode(',', $paramString);
            foreach($params as $param) {
              $param = trim($param);
              $paramParts = explode(':', $param, 2);
              if (count($paramParts) == 2) {
                $paramName = trim($paramParts[0]);
                $paramValue = trim($paramParts[1]);
                if ($paramName === 'Position') {
                  $widgetObj->Position = $paramValue;
                } else if ($paramName === 'Condition') {
                  $widgetObj->Condition = $paramValue;
                }
              }
            }
            $fullActionSpec = preg_replace('/\([^)]*\)/', '', $fullActionSpec); // Remove params from spec
          }
          
          $actionArr = explode("&", $fullActionSpec);
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
      case "AddReplacement":
        $zoneObj->AddReplacement = trim($lineValue);
        break;
      case "AfterAdd":
        $zoneObj->AfterAdd = trim($lineValue);
        break;
      case "Virtual":
        // Parse virtual properties: PropertyName=FunctionName()
        $virtualArr = explode(",", $lineValue);
        for($i=0; $i<count($virtualArr); ++$i) {
          $virtualArr[$i] = trim($virtualArr[$i]);
          $virtualParts = explode("=", $virtualArr[$i]);
          if(count($virtualParts) == 2) {
            $virtualObj = new StdClass();
            $virtualObj->Name = trim($virtualParts[0]);
            $virtualObj->Function = trim($virtualParts[1]);
            array_push($zoneObj->VirtualProperties, $virtualObj);
          }
        }
        break;
      case "Highlight":
        // Parse highlight property: PropertyName (should be a virtual property)
        // e.g., Highlight: SelectionMetadata
        $zoneObj->HighlightProperty = trim($lineValue);
        break;
      case "Index":
        // Parse indexed properties: Index: PropertyName1, PropertyName2
        // These properties will be tracked in $objectDataIndices global
        $indexArr = explode(",", $lineValue);
        for($i=0; $i<count($indexArr); ++$i) {
          $propName = trim($indexArr[$i]);
          if($propName != "") {
            array_push($zoneObj->IndexedProperties, $propName);
          }
        }
        break;
      default://This is a new zone
        if($zoneObj != null) array_push($zones, $zoneObj);
        $zone = str_replace(' ', '', $line);
        $zoneArr = explode("-", $zone);
        $zoneName = $zoneArr[0];
        if ($zoneName === 'DecisionQueue') {
          $hasDecisionQueue = true;
        }
        if ($zoneName === 'FlashMessage') {
          $hasFlashMessage = true;
        }
        $zoneObj = new StdClass();
        $zoneObj->Name = $zoneName;
        $zoneObj->Properties = [];
        $propertyArr = explode(",", $zoneArr[1]);
        for($i=0; $i<count($propertyArr); ++$i) {
          $thisProperty = explode(":", $propertyArr[$i]);
          $propertyObj = new StdClass();
          $propertyObj->Name = trim($thisProperty[0]);
          $thisProperty = explode("=", $thisProperty[1]);
          $rawType = trim($thisProperty[0]);
          // Check for array type: array[innerType]
          if(preg_match('/^array\[(\w+)\]$/', $rawType, $matches)) {
            $propertyObj->Type = "array";
            $propertyObj->InnerType = $matches[1];
          } else {
            $propertyObj->Type = $rawType;
            $propertyObj->InnerType = null;
          }
          $propertyObj->DefaultValue = count($thisProperty)>1 ? trim($thisProperty[1]) : "\"-\"";
          array_push($zoneObj->Properties, $propertyObj);
        }
        //Assign default value for all zoneObj display properties
        $zoneObj->Visibility = "Public";
        $zoneObj->DisplayMode = "Single";
        $zoneObj->Display = "Normal";
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
        $zoneObj->AddReplacement = null;
        $zoneObj->AfterAdd = null;
        $zoneObj->VirtualProperties = [];
        $zoneObj->IndexedProperties = [];
        $zoneObj->HighlightProperty = null;
        break;
    }
  }
}

if($zoneObj != null) array_push($zones, $zoneObj);//The previous ones are added when a new one is found, need to add the last one

fclose($handler);

// Check if any zone has indexed properties
for($i=0; $i<count($zones); ++$i) {
  if(count($zones[$i]->IndexedProperties) > 0) {
    $hasAnyIndexedProperties = true;
    break;
  }
}

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
  $isValueType = ($zone->DisplayMode == 'Value' || $zone->DisplayMode == 'Radio');
  $isValueOnly = ($zone->DisplayMode == 'Value');
  if (strtolower($scope) == 'global') {
    // Global-scoped zones don't take a player parameter
    if ($isValueOnly) {
      fwrite($handler, "function &Get" . $zoneName . "() {\r\n");
      fwrite($handler, "  global \$g" . $zoneName . ";\r\n");
      fwrite($handler, "  return \$g" . $zoneName . ";\r\n");
      fwrite($handler, "}\r\n\r\n");
      fwrite($handler, "function Set" . $zoneName . "(\$value) {\r\n");
      fwrite($handler, "  global \$g" . $zoneName . ";\r\n");
      fwrite($handler, "  \$g" . $zoneName . " = \$value;\r\n");
      fwrite($handler, "}\r\n\r\n");
    } else {
      fwrite($handler, "function &Get" . $zoneName . "() {\r\n");
      fwrite($handler, "  global \$g" . $zoneName . ";\r\n");
      fwrite($handler, "  return \$g" . $zoneName . ";\r\n");
      fwrite($handler, "}\r\n\r\n");
      if ($isValueType) {
        fwrite($handler, "function &" . $zoneName . "Value() {\r\n");
        fwrite($handler, "  \$arr = &Get" . $zoneName . "();\r\n");
        fwrite($handler, "  return \$arr[0]->Value;\r\n");
        fwrite($handler, "}\r\n\r\n");
      }
    }
  } else {
    if ($isValueOnly) {
      fwrite($handler, "function &Get" . $zoneName . "(\$player) {\r\n");
      fwrite($handler, "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n");
      fwrite($handler, "  if (\$player == 1) return \$p1" . $zoneName . ";\r\n");
      fwrite($handler, "  else return \$p2" . $zoneName . ";\r\n");
      fwrite($handler, "}\r\n\r\n");
      fwrite($handler, "function &" . $zoneName . "Value(\$player) {\r\n");
      fwrite($handler, "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n");
      fwrite($handler, "  if (\$player == 1) return \$p1" . $zoneName . ";\r\n");
      fwrite($handler, "  else return \$p2" . $zoneName . ";\r\n");
      fwrite($handler, "}\r\n\r\n");
    } else {
      fwrite($handler, "function &Get" . $zoneName . "(\$player) {\r\n");
      fwrite($handler, "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n");
      fwrite($handler, "  if (\$player == 1) return \$p1" . $zoneName . ";\r\n");
      fwrite($handler, "  else return \$p2" . $zoneName . ";\r\n");
      fwrite($handler, "}\r\n\r\n");
      if ($isValueType) {
        fwrite($handler, "function &" . $zoneName . "Value(\$player) {\r\n");
        fwrite($handler, "  \$arr = &Get" . $zoneName . "(\$player);\r\n");
        fwrite($handler, "  return \$arr[0]->Value;\r\n");
        fwrite($handler, "}\r\n\r\n");
      }
    }
  }
  //Setter
  $parameters = "";
  $parametersNoDefaults = "";
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    $parameters .= ", \$" . $property->Name . "=" . $property->DefaultValue;
    $parametersNoDefaults .= ", \$" . $property->Name;
  }
  // For global zones, don't include $player parameter
  if (strtolower($scope) == 'global') {
    // Remove leading comma from parameters if present
    $cleanParams = ltrim($parameters, ',');
    fwrite($handler, "function Add" . $zoneName . "(" . $cleanParams);
    if ($cleanParams != "") fwrite($handler, ", ");
    fwrite($handler, "\$sourceObject=null");
  } else {
    fwrite($handler, "function Add" . $zoneName . "(\$player" . $parameters . ", \$sourceObject=null");
  }
  fwrite($handler, ") {\r\n");
  if($zone->AddValidation != "") {
    fwrite($handler, "  if(!" . $zone->AddValidation . "(\$CardID)) return;\r\n");
  }
  if ($zone->AddReplacement != null) {
    if (strtolower($scope) == 'global') {
      fwrite($handler, "  \$replaceResult = " . $zone->AddReplacement . "(" . ltrim($parametersNoDefaults, ',') . ", \$sourceObject);\r\n");
    } else {
      fwrite($handler, "  \$replaceResult = " . $zone->AddReplacement . "(\$player" . $parametersNoDefaults . ", \$sourceObject);\r\n");
    }
    fwrite($handler, "  if(\$replaceResult) return \$replaceResult;\r\n");
  }
  if ($isValueOnly) {
    // For Value zones, set the global variable directly
    if (strtolower($scope) == 'global') {
      fwrite($handler, "  global \$g" . $zoneName . ";\r\n");
      fwrite($handler, "  \$g" . $zoneName . " = \$Value;\r\n");
      fwrite($handler, "  return null;\r\n");
    } else {
      fwrite($handler, "  global \$p1" . $zoneName . ", \$p2" . $zoneName . ";\r\n");
      fwrite($handler, "  if (\$player == 1) \$p1" . $zoneName . " = \$Value;\r\n");
      fwrite($handler, "  else \$p2" . $zoneName . " = \$Value;\r\n");
      fwrite($handler, "  return null;\r\n");
    }
  } else {
    fwrite($handler, "  \$zoneObj = new " . $zoneName . "(");
    for($j=0; $j<count($zone->Properties); ++$j) {
      $property = $zone->Properties[$j];
      fwrite($handler, "\$" . $property->Name);
      if($j < count($zone->Properties) - 1) fwrite($handler, " . ' ' . ");
    }
    // Add location and playerID to constructor for Player or Global scopes
    fwrite($handler, ", ");
    fwrite($handler, "'" . $zoneName . "', " . (strtolower($scope) == 'global' ? "0" : "\$player"));
    fwrite($handler, ");\r\n");
    if (strtolower($scope) == 'global') {
      fwrite($handler, "  \$zone = &Get" . $zoneName . "();\r\n");
      fwrite($handler, "  \$zoneObj->mzIndex = count(\$zone);\r\n");
      fwrite($handler, "  array_push(\$zone, \$zoneObj);\r\n");
    } else {
      fwrite($handler, "  \$zone = &Get" . $zoneName . "(\$player);\r\n");
      fwrite($handler, "  \$zoneObj->mzIndex = count(\$zone);\r\n");
      fwrite($handler, "  array_push(\$zone, \$zoneObj);\r\n");
    }
  }
  // Copy properties from sourceObject before calling AfterAdd
  if (!$isValueOnly) {
    fwrite($handler, "  // Deep copy properties from sourceObject if provided\r\n");
    fwrite($handler, "  if(\$sourceObject !== null) {\r\n");
    fwrite($handler, "    \$properties = get_object_vars(\$sourceObject);\r\n");
    fwrite($handler, "    foreach(\$properties as \$prop => \$value) {\r\n");
    fwrite($handler, "      if(\$prop !== 'removed' && \$prop !== 'Location' && \$prop !== 'mzIndex') {\r\n");
    fwrite($handler, "        \$zoneObj->\$prop = \$value;\r\n");
    fwrite($handler, "      }\r\n");
    fwrite($handler, "    }\r\n");
    fwrite($handler, "  }\r\n");
    // Build index for zones with indexed properties
    $hasIndexedProperties = count($zone->IndexedProperties) > 0;
    if($hasIndexedProperties) {
      fwrite($handler, "  \$zoneObj->BuildIndex();\r\n");
    }
  }
  if ($zone->AfterAdd != null) {
    if (strtolower($scope) == 'global') {
      fwrite($handler, "  " . $zone->AfterAdd . "(" . ltrim($parametersNoDefaults, ',') . ");\r\n");
    } else {
      fwrite($handler, "  " . $zone->AfterAdd . "(\$player" . $parametersNoDefaults . ");\r\n");
    }
  }
  if (!$isValueOnly) {
    fwrite($handler, "  return \$zoneObj;\r\n");
  }
  fwrite($handler, "}\r\n\r\n");

  //Add to the master zone object getter
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    if ($zone->DisplayMode == 'Value') {
      $mzGetObject .= "    case \"" . $zoneName . "\": return Get" . $zoneName . "();\r\n";
      $mzGetZone .= "    case \"" . $zoneName . "\": return Get" . $zoneName . "();\r\n";
    } else {
      $mzGetObject .= "    case \"" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(); return \$zoneArr[\$mzArr[1]];\r\n";
      $mzGetZone .= "    case \"" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(); return \$zoneArr;\r\n";
    }
  } else {
    if ($zone->DisplayMode == 'Value') {
      $mzGetObject .= "    case \"my" . $zoneName . "\": return Get" . $zoneName . "(\$playerID);\r\n";
      $mzGetObject .= "    case \"their" . $zoneName . "\": return Get" . $zoneName . "(\$playerID == 1 ? 2 : 1);\r\n";
      $mzGetZone .= "    case \"my" . $zoneName . "\": return Get" . $zoneName . "(\$playerID);\r\n";
      $mzGetZone .= "    case \"their" . $zoneName . "\": return Get" . $zoneName . "(\$playerID == 1 ? 2 : 1);\r\n";
    } else {
      $mzGetObject .= "    case \"my" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID); return \$zoneArr[\$mzArr[1]];\r\n";
      $mzGetObject .= "    case \"their" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID == 1 ? 2 : 1); return \$zoneArr[\$mzArr[1]];\r\n";
      $mzGetZone .= "    case \"my" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID); return \$zoneArr;\r\n";
      $mzGetZone .= "    case \"their" . $zoneName . "\": \$zoneArr = &Get" . $zoneName . "(\$playerID == 1 ? 2 : 1); return \$zoneArr;\r\n";
    }
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

// Generate GetAllZones helper for CleanupRemovedCards
fwrite($handler, "// Get all zone names for iteration (used by CleanupRemovedCards)\r\n");
fwrite($handler, "function GetAllZones() {\r\n");
fwrite($handler, "  return [\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  // Skip Value display mode zones (they're not arrays)
  if ($zone->DisplayMode == 'Value') continue;
  if (strtolower($scope) == 'global') {
    fwrite($handler, "    \"" . $zoneName . "\",\r\n");
  } else {
    fwrite($handler, "    \"my" . $zoneName . "\", \"their" . $zoneName . "\",\r\n");
  }
}
fwrite($handler, "  ];\r\n");
fwrite($handler, "}\r\n\r\n");

fwrite($handler, "function MZMove(\$player, \$mzIndex, \$toZone) {\r\n");
fwrite($handler, "  \$removed = GetZoneObject(\$mzIndex);\r\n");
fwrite($handler, "  if(\$removed->removed) return null;\r\n");
fwrite($handler, "  \$removed->Remove();\r\n");
fwrite($handler, "  // Pass the removed object to MZAddZone so properties are copied before AfterAdd hooks fire\r\n");
fwrite($handler, "  \$newObj = MZAddZone(\$player, \$toZone, \$removed->CardID, \$removed);\r\n");
fwrite($handler, "  return \$newObj;\r\n");
fwrite($handler, "}\r\n\r\n");

fwrite($handler, "function MZZoneCount(\$zoneName) {\r\n");
fwrite($handler, "  \$zone = GetZone(\$zoneName);\r\n");
fwrite($handler, "  for(\$i=0, \$count=0; \$i<count(\$zone); ++\$i) {\r\n");
fwrite($handler, "    if(!\$zone[\$i]->removed) ++\$count;\r\n");
fwrite($handler, "  }\r\n");
fwrite($handler, "  return \$count;\r\n");
fwrite($handler, "}\r\n\r\n");

//MZAddZone
fwrite($handler, "function MZAddZone(\$player, \$zoneName, \$cardID, \$sourceObject=null) {\r\n");
fwrite($handler, "  switch(\$zoneName) {\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    fwrite($handler, "    case \"" . $zoneName . "\": return Add" . $zoneName . "(CardID:\$cardID, sourceObject:\$sourceObject);\r\n");
  } else {
    fwrite($handler, "    case \"my" . $zoneName . "\": return Add" . $zoneName . "(\$player, CardID:\$cardID, sourceObject:\$sourceObject);\r\n");
    fwrite($handler, "    case \"their" . $zoneName . "\": return Add" . $zoneName . "(\$player == 1 ? 2 : 1, CardID:\$cardID, sourceObject:\$sourceObject);\r\n");
  }
}
fwrite($handler, "    default: return null;\r\n");
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
    if ($zone->DisplayMode == 'Value') {
      fwrite($handler, "    case \"" . $zoneName . "\": \$g" . $zoneName . " = 0; break;\r\n");
    } else {
      fwrite($handler, "    case \"" . $zoneName . "\": \$zone = &Get" . $zoneName . "(); for(\$i=0; \$i<count(\$zone); ++\$i) \$zone[\$i]->Remove(); break;\r\n");
    }
  } else {
    if ($zone->DisplayMode == 'Value') {
      // Emit code like: case "myZone": ${'p' . $player . 'Zone'} = 0; break;
      fwrite($handler, "    case \"my" . $zoneName . "\": \${'p' . \$player . '" . $zoneName . "'} = 0; break;\r\n");
      // Emit code like: case "theirZone": ${'p' . ($player == 1 ? 2 : 1) . 'Zone'} = 0; break;
      fwrite($handler, "    case \"their" . $zoneName . "\": \${'p' . (\$player == 1 ? 2 : 1) . '" . $zoneName . "'} = 0; break;\r\n");
    } else {
      fwrite($handler, "    case \"my" . $zoneName . "\": \$zone = &Get" . $zoneName . "(\$player); for(\$i=0; \$i<count(\$zone); ++\$i) \$zone[\$i]->Remove(); break;\r\n");
      fwrite($handler, "    case \"their" . $zoneName . "\": \$zone = &Get" . $zoneName . "(\$player == 1 ? 2 : 1); for(\$i=0; \$i<count(\$zone); ++\$i) \$zone[\$i]->Remove(); break;\r\n");
    }
  }
}
fwrite($handler, "    default: break;\r\n");
fwrite($handler, "  }\r\n");
fwrite($handler, "}\r\n\r\n");

fwrite($handler, "\$systemDQHandlers = [];\r\n");
//Generate macro functions
global $macros;
for($i=0; $i<count($macros); ++$i) {
  $macro = $macros[$i];
  // Generate handlers
  // ChoiceFunction is deterministic and will be called directly from the wrapper; no queued handler is emitted here.
  // Choice handler (if Choice is set)
  if(isset($macro->Choice) && substr($macro->Choice, 0, 1) == '{') {
    $choiceSpec = substr($macro->Choice, 1, -1);
    $parts = explode(':', $choiceSpec);
    $type = $parts[0];
    $param = $parts[1];
    if($type == 'MZCHOOSE') {
      $paramParts = explode('|', $param);
      $source = $paramParts[0];
      $tooltip = isset($paramParts[1]) ? $paramParts[1] : "Choose a card";
      
      fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_Choice\"] = function(\$player, \$param, \$lastResult) {\r\n");
      
      // If macro has parameters, parse them from $param which is pipe-delimited
      if (!empty($macro->Parameters)) {
        //fwrite($handler, "  \$params = explode('|', \$param);\r\n");
        for ($pIdx = 0; $pIdx < count($macro->Parameters); $pIdx++) {
          fwrite($handler, "  \$" . $macro->Parameters[$pIdx] . " = \$param[" . $pIdx . "] ?? '';\r\n");
        }
        // Now $source and $tooltip might reference parameter names, so we need to evaluate them
        // If the source/tooltip exactly match a parameter name, use the variable directly
        if (in_array($source, $macro->Parameters)) {
          fwrite($handler, "  \$source = \$" . $source . ";\r\n");
        } else {
          fwrite($handler, "  \$source = \"" . $source . "\";\r\n");
        }
        if (in_array($tooltip, $macro->Parameters)) {
          fwrite($handler, "  \$tooltip = \$" . $tooltip . ";\r\n");
        } else {
          fwrite($handler, "  \$tooltip = \"" . $tooltip . "\";\r\n");
        }
      } else {
        fwrite($handler, "  \$source = \"" . $source . "\";\r\n");
        fwrite($handler, "  \$tooltip = \"" . $tooltip . "\";\r\n");
      }
      
      fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"MZCHOOSE\", \$source, 1, \$tooltip);\r\n");
      fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"SYSTEM\", \"" . $macro->FunctionName . "_AfterChoice\", 99);\r\n");
      fwrite($handler, "};\r\n\r\n");
    }
  }
  // For ChoiceFunction macros: generate Action handler that executes directly without decision queue
  // For Choice-based macros: keep AfterChoice and traditional Action handlers
  if(isset($macro->ChoiceFunction)) {
    // ChoiceFunction variant: Action handler calls MZMove directly
    if(isset($macro->Action) && substr($macro->Action, 0, 1) == '{') {
      $actionSpec = substr($macro->Action, 1, -1);
      $parts = explode(':', $actionSpec);
      $type = $parts[0];
      if($type == 'MZMOVE') {
        $param = $parts[1];
        fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_Action\"] = function(\$player, \$result, \$lastDecision) {\r\n");
        // Resolve {<-} placeholder with the result from ChoiceFunction
        fwrite($handler, "  \$resolvedParam = str_replace(\"{<-}\", \$result, \"" . $param . "\");\r\n");
        fwrite($handler, "  \$parts = explode(\"->\", \$resolvedParam);\r\n");
        fwrite($handler, "  \$source = \$parts[0];\r\n");
        fwrite($handler, "  \$destination = explode(\"-\", \$parts[1])[0];\r\n");
        fwrite($handler, "  MZMove(\$player, \$source, \$destination);\r\n");
        fwrite($handler, "};\r\n\r\n");
      } else {
        fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_Action\"] = function(\$player, \$result, \$lastDecision) {\r\n");
        fwrite($handler, "};\r\n\r\n");
      }
    }
    // AfterAction handler for ChoiceFunction
    fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_AfterAction\"] = function(\$player, \$param, \$lastResult) {\r\n");
    if(isset($macro->AfterAction)) {
      fwrite($handler, "  " . $macro->AfterAction . "(\$player, \$lastResult);\r\n");
    }
    fwrite($handler, "};\r\n\r\n");
  } else {
    // Choice-based variant: traditional handlers
    // AfterChoice handler
    fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_AfterChoice\"] = function(\$player, \$param, \$lastResult) {\r\n");
    if(isset($macro->AfterChoice)) {
      fwrite($handler, "  " . $macro->AfterChoice . "(\$player, \$lastResult);\r\n");
    }
    fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"SYSTEM\", \"" . $macro->FunctionName . "_Action\", 99);\r\n");
    fwrite($handler, "};\r\n\r\n");
    // Action handler
    if(isset($macro->Action) && substr($macro->Action, 0, 1) == '{') {
      $actionSpec = substr($macro->Action, 1, -1);
      $parts = explode(':', $actionSpec);
      $type = $parts[0];
      if($type == 'MZMOVE') {
        $param = $parts[1];
        fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_Action\"] = function(\$player, \$param, \$lastResult) {\r\n");
        fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"MZMOVE\", \"" . $param . "\", 1);\r\n");
        fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"SYSTEM\", \"" . $macro->FunctionName . "_AfterAction\", 99);\r\n");
        fwrite($handler, "};\r\n\r\n");
      } else {
        fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_Action\"] = function(\$player, \$param, \$lastResult) {\r\n");
        fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"SYSTEM\", \"" . $macro->FunctionName . "_AfterAction\", 99);\r\n");
        fwrite($handler, "};\r\n\r\n");
      }
    }
    // AfterAction handler
    fwrite($handler, "\$systemDQHandlers[\"" . $macro->FunctionName . "_AfterAction\"] = function(\$player, \$param, \$lastResult) {\r\n");
    if(isset($macro->AfterAction)) {
      fwrite($handler, "  " . $macro->AfterAction . "(\$player, \$lastResult);\r\n");
    }
    fwrite($handler, "};\r\n\r\n");
  }
  // Main macro function
  $paramList = '$player';
  if (!empty($macro->Parameters)) {
    $paramList .= ', $' . implode(', $', $macro->Parameters);
  }
  fwrite($handler, "function " . $macro->FunctionName . "($paramList) {\r\n");
  if(isset($macro->ChoiceFunction)) {
    $choiceParts = explode('|', $macro->ChoiceFunction);
    $cfName = $choiceParts[0];
    if (!empty($macro->Parameters)) {
      $cfArgs = '$player, ' . implode(', ', array_map(fn($p) => '$' . $p, $macro->Parameters));
      // Store macro parameters in DecisionQueueVariables before calling ChoiceFunction
      fwrite($handler, "  // Store macro parameters for access in generated ability code\r\n");
      foreach ($macro->Parameters as $param) {
        fwrite($handler, "  DecisionQueueController::StoreVariable(\"" . $param . "\", \$" . $param . ");\r\n");
      }
    } else {
      $cfArgs = '$player';
    }
    fwrite($handler, "  \$result = " . $cfName . "(" . $cfArgs . ");\r\n");
    fwrite($handler, "  global \$systemDQHandlers;\r\n");
    fwrite($handler, "  \$systemDQHandlers[\"" . $macro->FunctionName . "_Action\"](\$player, \$result, null);\r\n");
    fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"PASSPARAMETER\", \"\$result\", 99);\r\n");
    fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"SYSTEM\", \"" . $macro->FunctionName . "_AfterAction\", 99);\r\n");
    fwrite($handler, "  \$dqController = new DecisionQueueController();\r\n");
    fwrite($handler, "  \$dqController->ExecuteStaticMethods(\$player, \"-\");\r\n");
  } else {
    // If macro has parameters and uses Choice (not ChoiceFunction), pass them to the Choice handler
    if (!empty($macro->Parameters)) {
      $paramString = implode(" . '|' . ", array_map(fn($p) => '$' . $p, $macro->Parameters));
      fwrite($handler, "  \$paramString = str_replace(' ', '_', " . $paramString . ");\r\n");
      fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"SYSTEM\", \"" . $macro->FunctionName . "_Choice|\$paramString\", 99);\r\n");
    } else {
      fwrite($handler, "  DecisionQueueController::AddDecision(\$player, \"SYSTEM\", \"" . $macro->FunctionName . "_Choice\", 99);\r\n");
    }
  }
  fwrite($handler, "}\r\n\r\n");
}

// Generate ComputeVirtualProperties function for zones with virtual properties
fwrite($handler, "// Compute virtual properties for objects before sending to client\r\n");
fwrite($handler, "function ComputeVirtualProperties(\$obj) {\r\n");
fwrite($handler, "  if(!isset(\$obj->Location)) return;\r\n");
fwrite($handler, "  switch(\$obj->Location) {\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  if(count($zone->VirtualProperties) > 0) {
    $zoneName = $zone->Name;
    fwrite($handler, "    case \"" . $zoneName . "\":\r\n");
    for($j=0; $j<count($zone->VirtualProperties); ++$j) {
      $virtual = $zone->VirtualProperties[$j];
      $functionName = rtrim($virtual->Function, '()');
      fwrite($handler, "      \$obj->" . $virtual->Name . " = " . $functionName . "(\$obj);\r\n");
    }
    fwrite($handler, "      break;\r\n");
  }
}
fwrite($handler, "    default: break;\r\n");
fwrite($handler, "  }\r\n");
fwrite($handler, "}\r\n\r\n");

// Generate global index helper functions if any zone has indexed properties
if($hasAnyIndexedProperties) {
  // HasIndexedValue - check if an mzID has a specific value in a specific property
  fwrite($handler, "// Check if an object has a specific value in an indexed property\r\n");
  fwrite($handler, "function HasIndexedValue(\$mzID, \$propertyName, \$value) {\r\n");
  fwrite($handler, "  global \$objectDataIndices;\r\n");
  fwrite($handler, "  return isset(\$objectDataIndices[\$mzID][\$propertyName][\$value]) && \$objectDataIndices[\$mzID][\$propertyName][\$value] > 0;\r\n");
  fwrite($handler, "}\r\n\r\n");
  
  // GetIndexedValueCount - get the count of a specific value in a property for an mzID
  fwrite($handler, "// Get the count of a specific value in an indexed property\r\n");
  fwrite($handler, "function GetIndexedValueCount(\$mzID, \$propertyName, \$value) {\r\n");
  fwrite($handler, "  global \$objectDataIndices;\r\n");
  fwrite($handler, "  return isset(\$objectDataIndices[\$mzID][\$propertyName][\$value]) ? \$objectDataIndices[\$mzID][\$propertyName][\$value] : 0;\r\n");
  fwrite($handler, "}\r\n\r\n");
  
  // GetAllIndexedValues - get all unique values for a property of an mzID
  fwrite($handler, "// Get all unique values in an indexed property for an object\r\n");
  fwrite($handler, "function GetAllIndexedValues(\$mzID, \$propertyName) {\r\n");
  fwrite($handler, "  global \$objectDataIndices;\r\n");
  fwrite($handler, "  if(!isset(\$objectDataIndices[\$mzID][\$propertyName])) return [];\r\n");
  fwrite($handler, "  return array_keys(\$objectDataIndices[\$mzID][\$propertyName]);\r\n");
  fwrite($handler, "}\r\n\r\n");
  
  // FindObjectsWithIndexedValue - find all mzIDs that have a specific value in a property
  fwrite($handler, "// Find all objects that have a specific value in an indexed property\r\n");
  fwrite($handler, "function FindObjectsWithIndexedValue(\$propertyName, \$value) {\r\n");
  fwrite($handler, "  global \$objectDataIndices;\r\n");
  fwrite($handler, "  \$results = [];\r\n");
  fwrite($handler, "  foreach(\$objectDataIndices as \$mzID => \$properties) {\r\n");
  fwrite($handler, "    if(isset(\$properties[\$propertyName][\$value]) && \$properties[\$propertyName][\$value] > 0) {\r\n");
  fwrite($handler, "      \$results[] = \$mzID;\r\n");
  fwrite($handler, "    }\r\n");
  fwrite($handler, "  }\r\n");
  fwrite($handler, "  return \$results;\r\n");
  fwrite($handler, "}\r\n\r\n");
}

fwrite($handler, "?>");
fclose($handler);
//Write the class file
$filename = $rootPath . "/ZoneClasses.php";
$handler = fopen($filename, "w");
fwrite($handler, "<?php\r\n");
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  $hasIndexedProperties = count($zone->IndexedProperties) > 0;
  fwrite($handler, "class " . $zoneName . " {\r\n");
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    fwrite($handler, "  public \$" . $property->Name . ";\r\n");
  }
  fwrite($handler, "  public \$removed;\r\n");
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  fwrite($handler, "  public \$Location;\r\n");
  fwrite($handler, "  public \$PlayerID;\r\n");
  fwrite($handler, "  public \$mzIndex;\r\n"); // Add mzIndex property for all classes
  fwrite($handler, "  function __construct(\$line, \$location = \"\", \$playerID = 0, \$mzIndex = -1) {\r\n");
  fwrite($handler, "    \$arr = explode(\" \", \$line);\r\n");
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    $propertyName = $property->Name;
    $propertyType = $property->Type;
    fwrite($handler, "    \$this->" . $propertyName . " = ");
    if($propertyType == "int" || $propertyType == "number") {
      fwrite($handler, "(count(\$arr) > " . $j . " ? intval(\$arr[" . $j . "]) : -1);\r\n");
    } else if($propertyType == "float") {
      fwrite($handler, "(count(\$arr) > " . $j . " ? floatval(\$arr[" . $j . "]) : -1);\r\n");
    } else if($propertyType == "array") {
      // Arrays are serialized with ~ delimiter, e.g. "item1~item2~item3" or "-" for empty
      fwrite($handler, "(count(\$arr) > " . $j . " && \$arr[" . $j . "] != \"-\" ? explode(\"~\", \$arr[" . $j . "]) : []);\r\n");
    } else {
      fwrite($handler, "(count(\$arr) > " . $j . " ? \$arr[" . $j . "] : \"\");\r\n");
    }
  }
  fwrite($handler, "    \$this->Location = \$location;\r\n");
  fwrite($handler, "    \$this->PlayerID = \$playerID;\r\n");
  fwrite($handler, "    \$this->mzIndex = \$mzIndex;\r\n");
  fwrite($handler, "  }\r\n");
  //Serialize function
  fwrite($handler, "  function Serialize(\$delimiter = \" \") {\r\n");
  fwrite($handler, "    \$rv = \"\";\r\n");
  for($j=0; $j<count($zone->Properties); ++$j) {
    $property = $zone->Properties[$j];
    $propertyName = $property->Name;
    if($j > 0) fwrite($handler, "    \$rv .= \$delimiter;\r\n");
    if($property->Type == "array") {
      // Serialize arrays with ~ delimiter, or "-" if empty
      fwrite($handler, "    \$rv .= (count(\$this->" . $propertyName . ") > 0 ? implode(\"~\", \$this->" . $propertyName . ") : \"-\");\r\n");
    } else {
      fwrite($handler, "    \$rv .= \$this->" . $propertyName . ";\r\n");
    }
  }
  fwrite($handler, "    return \$rv;\r\n");
  fwrite($handler, "  }\r\n");
  
  // Generate indexed property methods if this zone has any
  if($hasIndexedProperties) {
    // GetMzID function
    fwrite($handler, "  function GetMzID() {\r\n");
    fwrite($handler, "    return \$this->Location . \"-\" . \$this->mzIndex;\r\n");
    fwrite($handler, "  }\r\n");
    
    // For each indexed property that is an array, generate Add/Remove methods
    for($j=0; $j<count($zone->Properties); ++$j) {
      $property = $zone->Properties[$j];
      if(in_array($property->Name, $zone->IndexedProperties) && $property->Type == "array") {
        $propName = $property->Name;
        
        // Add method
        fwrite($handler, "  function Add" . $propName . "(\$value) {\r\n");
        fwrite($handler, "    global \$objectDataIndices;\r\n");
        fwrite($handler, "    \$this->" . $propName . "[] = \$value;\r\n");
        fwrite($handler, "    \$mzID = \$this->GetMzID();\r\n");
        fwrite($handler, "    if(!isset(\$objectDataIndices[\$mzID])) \$objectDataIndices[\$mzID] = [];\r\n");
        fwrite($handler, "    if(!isset(\$objectDataIndices[\$mzID][\"" . $propName . "\"])) \$objectDataIndices[\$mzID][\"" . $propName . "\"] = [];\r\n");
        fwrite($handler, "    if(!isset(\$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value])) \$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value] = 0;\r\n");
        fwrite($handler, "    \$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value]++;\r\n");
        fwrite($handler, "  }\r\n");
        
        // Remove method
        fwrite($handler, "  function Remove" . $propName . "(\$value) {\r\n");
        fwrite($handler, "    global \$objectDataIndices;\r\n");
        fwrite($handler, "    \$key = array_search(\$value, \$this->" . $propName . ");\r\n");
        fwrite($handler, "    if(\$key !== false) {\r\n");
        fwrite($handler, "      array_splice(\$this->" . $propName . ", \$key, 1);\r\n");
        fwrite($handler, "      \$mzID = \$this->GetMzID();\r\n");
        fwrite($handler, "      if(isset(\$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value])) {\r\n");
        fwrite($handler, "        \$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value]--;\r\n");
        fwrite($handler, "        if(\$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value] <= 0) {\r\n");
        fwrite($handler, "          unset(\$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value]);\r\n");
        fwrite($handler, "        }\r\n");
        fwrite($handler, "      }\r\n");
        fwrite($handler, "    }\r\n");
        fwrite($handler, "  }\r\n");
        
        // Has method
        fwrite($handler, "  function Has" . $propName . "(\$value) {\r\n");
        fwrite($handler, "    global \$objectDataIndices;\r\n");
        fwrite($handler, "    \$mzID = \$this->GetMzID();\r\n");
        fwrite($handler, "    return isset(\$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value]) && \$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value] > 0;\r\n");
        fwrite($handler, "  }\r\n");
        
        // Get count method
        fwrite($handler, "  function Get" . $propName . "Count(\$value) {\r\n");
        fwrite($handler, "    global \$objectDataIndices;\r\n");
        fwrite($handler, "    \$mzID = \$this->GetMzID();\r\n");
        fwrite($handler, "    return isset(\$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value]) ? \$objectDataIndices[\$mzID][\"" . $propName . "\"][\$value] : 0;\r\n");
        fwrite($handler, "  }\r\n");
        
        // Clear method
        fwrite($handler, "  function Clear" . $propName . "() {\r\n");
        fwrite($handler, "    global \$objectDataIndices;\r\n");
        fwrite($handler, "    \$mzID = \$this->GetMzID();\r\n");
        fwrite($handler, "    \$this->" . $propName . " = [];\r\n");
        fwrite($handler, "    if(isset(\$objectDataIndices[\$mzID][\"" . $propName . "\"])) {\r\n");
        fwrite($handler, "      \$objectDataIndices[\$mzID][\"" . $propName . "\"] = [];\r\n");
        fwrite($handler, "    }\r\n");
        fwrite($handler, "  }\r\n");
      }
    }
    
    // BuildIndex method to rebuild indices from current array state
    fwrite($handler, "  function BuildIndex() {\r\n");
    fwrite($handler, "    global \$objectDataIndices;\r\n");
    fwrite($handler, "    \$mzID = \$this->GetMzID();\r\n");
    fwrite($handler, "    if(!isset(\$objectDataIndices[\$mzID])) \$objectDataIndices[\$mzID] = [];\r\n");
    for($j=0; $j<count($zone->Properties); ++$j) {
      $property = $zone->Properties[$j];
      if(in_array($property->Name, $zone->IndexedProperties) && $property->Type == "array") {
        $propName = $property->Name;
        fwrite($handler, "    \$objectDataIndices[\$mzID][\"" . $propName . "\"] = [];\r\n");
        fwrite($handler, "    foreach(\$this->" . $propName . " as \$val) {\r\n");
        fwrite($handler, "      if(!isset(\$objectDataIndices[\$mzID][\"" . $propName . "\"][\$val])) \$objectDataIndices[\$mzID][\"" . $propName . "\"][\$val] = 0;\r\n");
        fwrite($handler, "      \$objectDataIndices[\$mzID][\"" . $propName . "\"][\$val]++;\r\n");
        fwrite($handler, "    }\r\n");
      }
    }
    fwrite($handler, "  }\r\n");
    
    // ClearIndex method to clear this object's entries from the index
    fwrite($handler, "  function ClearIndex() {\r\n");
    fwrite($handler, "    global \$objectDataIndices;\r\n");
    fwrite($handler, "    \$mzID = \$this->GetMzID();\r\n");
    fwrite($handler, "    if(isset(\$objectDataIndices[\$mzID])) {\r\n");
    fwrite($handler, "      unset(\$objectDataIndices[\$mzID]);\r\n");
    fwrite($handler, "    }\r\n");
    fwrite($handler, "  }\r\n");
  } else {
    // If no indexed properties, just add empty BuildIndex and ClearIndex methods for consistency
    fwrite($handler, "  function BuildIndex() {\r\n");
    fwrite($handler, "    // No indexed properties\r\n");
    fwrite($handler, "  }\r\n");
    fwrite($handler, "  function ClearIndex() {\r\n");
    fwrite($handler, "    // No indexed properties\r\n");
    fwrite($handler, "  }\r\n");
  }
  
  //Remove function
  fwrite($handler, "  function Remove(\$trigger=\"\") {\r\n");
  fwrite($handler, "    \$this->removed = true;\r\n");
  if($hasIndexedProperties) {
    fwrite($handler, "    \$this->ClearIndex();\r\n");
  }
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
    $versionsModule = GetModule("Versions");
    $isNewModule = true;
    if($versionsModule == null) {
      $versionsModule = GetModuleOfType("Versions");
      $isNewModule = false;
    }
    if($versionsModule != null) {
      $separator = $isNewModule ? "," : ";";
      $versionZones = explode($separator, $versionsModule->Parameters ?? $versionsModule->Zones ?? "");
      
      // Build lookup map for Value mode zones
      $valueZones = [];
      $globalZones = [];
      for($k=0; $k<count($zones); ++$k) {
        if($zones[$k]->DisplayMode == 'Value') {
          $valueZones[$zones[$k]->Name] = true;
        }
        if($zones[$k]->Scope == 'Global') {
          $globalZones[$zones[$k]->Name] = true;
        }
      }
      
      fwrite($handler, "  static function GetSerializedZones() {\r\n");
      fwrite($handler, "    \$rv = \"\";\r\n");
      for($j=0; $j<count($versionZones); ++$j) {
        $baseZoneName = $versionZones[$j];
        // Strip my/their prefix to check zone DisplayMode
        $checkZoneName = $baseZoneName;
        if(str_starts_with($checkZoneName, "my")) $checkZoneName = substr($checkZoneName, 2);
        elseif(str_starts_with($checkZoneName, "their")) $checkZoneName = substr($checkZoneName, 5);
        
        $isValueZone = isset($valueZones[$checkZoneName]);
        $isGlobalZone = isset($globalZones[$checkZoneName]);
        
        if($isValueZone) {
          $globalName = $isGlobalZone ? "g" . $checkZoneName : $baseZoneName;
          // Value zones are single objects, not arrays
          if($isGlobalZone) {
            fwrite($handler, "    global \$g" . $checkZoneName . ";\r\n");
            fwrite($handler, "    \$rv .= \$g" . $checkZoneName . ";\r\n");
          } else {
            fwrite($handler, "    \$rv .= GetZone(\"" . $baseZoneName . "\");\r\n");
          }
        } else {
          // Array zones
          fwrite($handler, "    \$zone = &GetZone(\"" . $baseZoneName . "\");\r\n");
          fwrite($handler, "    for(\$i=0; \$i<count(\$zone); ++\$i) {\r\n");
          fwrite($handler, "      if(\$i > 0) \$rv .= \"<v1>\";\r\n");
          fwrite($handler, "      \$rv .= \$zone[\$i]->Serialize(\"<v2>\");\r\n");
          fwrite($handler, "    }\r\n");
        }
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
if($hasAnyIndexedProperties) {
  fwrite($handler, "  \$objectDataIndices = [];\r\n");
}
for($i=0; $i<count($zones); ++$i) {
  $zone = $zones[$i];
  $zoneName = $zone->Name;
  $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
  if (strtolower($scope) == 'global') {
    if ($zone->DisplayMode == 'Value') {
      fwrite($handler, "  \$g" . $zoneName . " = 0;\r\n");
    } else {
      fwrite($handler, "  \$g" . $zoneName . " = [];\r\n");
    }
  } else {
    if ($zone->DisplayMode == 'Value') {
      fwrite($handler, "  \$p1" . $zoneName . " = 0;\r\n");
      fwrite($handler, "  \$p2" . $zoneName . " = 0;\r\n");
    } else {
      fwrite($handler, "  \$p1" . $zoneName . " = [];\r\n");
      fwrite($handler, "  \$p2" . $zoneName . " = [];\r\n");
    }
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

//Load version function
$versionsModule = GetModule("Versions");
if($versionsModule != null) {
  // Build lookup map for Value mode zones
  $valueZones = [];
  $globalZones = [];
  for($k=0; $k<count($zones); ++$k) {
    if($zones[$k]->DisplayMode == 'Value') {
      $valueZones[$zones[$k]->Name] = true;
    }
    if($zones[$k]->Scope == 'Global') {
      $globalZones[$zones[$k]->Name] = true;
    }
  }
  
  fwrite($handler, "function LoadVersion(\$playerID, \$versionNum = -1) {\r\n");
  fwrite($handler, "  \$versions = &GetVersions(\$playerID);\r\n");
  fwrite($handler, "  if(\$versionNum == -1) \$versionNum = count(\$versions) - 1;\r\n");
  fwrite($handler, "  if(\$versionNum == -1) return;\r\n//No versions to load\r\n");
  fwrite($handler, "  \$versionNum = intval(\$versionNum);\r\n");
  fwrite($handler, "  \$copyFrom = \$versions[\$versionNum];\r\n");
  fwrite($handler, "  \$zones = explode(\"<v0>\", \$copyFrom->Version);\r\n");
  $versionZones = explode(",", $versionsModule->Parameters);
  for($i=0; $i<count($versionZones); ++$i) {
    $baseZoneName = $versionZones[$i];
    $className = $baseZoneName;
    if(str_starts_with($className, "my")) $className = substr($className, 2);
    elseif(str_starts_with($className, "their")) $className = substr($className, 5);
    
    $isValueZone = isset($valueZones[$className]);
    $isGlobalZone = isset($globalZones[$className]);
    
    fwrite($handler, "  if(count(\$zones) > " . $i . ") {\r\n");
    if($isValueZone) {
      // Value zones are single objects, not arrays
      fwrite($handler, "    \$data = str_replace(\"<v2>\", \" \", \$zones[" . $i . "]);\r\n");
      
      if($isGlobalZone) {
        fwrite($handler, "  global \$g" . $className . ";\r\n");
        fwrite($handler, "  \$g" . $className . " = \$data;\r\n");
      } else {
        fwrite($handler, "    if(trim(\$data) != \"\") {\r\n");
        fwrite($handler, "      \$zone = &GetZone(\"" . $baseZoneName . "\");\r\n");
        fwrite($handler, "      \$zone = \$data;\r\n");
        fwrite($handler, "    }\r\n");
      }
    } else {
      // Array zones
      fwrite($handler, "    \$data = explode(\"<v1>\", \$zones[" . $i . "]);\r\n");
      fwrite($handler, "    if(count(\$data) > 0) {\r\n");
      fwrite($handler, "      \$zone = &GetZone(\"" . $baseZoneName . "\");\r\n");
      fwrite($handler, "      \$zone = [];\r\n");
      fwrite($handler, "      for(\$j=0; \$j<count(\$data); ++\$j) {\r\n");
      fwrite($handler, "        if(trim(\$data[\$j]) == \"\") continue;\r\n");
      fwrite($handler, "        \$data[\$j] = str_replace(\"<v2>\", \" \", \$data[\$j]);\r\n");
      fwrite($handler, "        \$location = '" . $baseZoneName . "';\r\n");
      fwrite($handler, "        \$controller = ");
      if(str_starts_with('" . $baseZoneName . "', 'my')) {
        fwrite($handler, "\$playerID;\r\n");
      } elseif(str_starts_with('" . $baseZoneName . "', 'their')) {
        fwrite($handler, "(\$playerID == 1 ? 2 : 1);\r\n");
      } else {
        fwrite($handler, "0;\r\n");
      }
      fwrite($handler, "        array_push(\$zone, new " . $className . "(\$data[\$j], \$location, \$controller));\r\n");
      fwrite($handler, "      }\r\n");
      fwrite($handler, "    }\r\n");
    }
    fwrite($handler, "  }\r\n");
  }
  fwrite($handler, "}\r\n\r\n");

  //Save version function
  fwrite($handler, "function SaveVersion(\$playerID) {\r\n");
  fwrite($handler, "  \$zones = Versions::GetSerializedZones();\r\n");
  fwrite($handler, "  AddVersions(\$playerID, \$zones);\r\n");
  fwrite($handler, "}\r\n\r\n");
}

fwrite($handler, "?>");
fclose($handler);

//Write the Gamestate network file
$filename = $rootPath . "/GetNextTurn.php";
$handler = fopen($filename, "w");
fwrite($handler, "<?php\r\n");
fwrite($handler, "include '../Core/UILibraries.php';\r\n");
fwrite($handler, "include '../Core/NetworkingLibraries.php';\r\n");
fwrite($handler, "include '../Core/HTTPLibraries.php';\r\n");
fwrite($handler, "include '../Core/CoreZoneModifiers.php';\r\n");
fwrite($handler, "include '../Assets/patreon-php-master/src/PatreonLibraries.php';\r\n");
fwrite($handler, "include './GamestateParser.php';\r\n");
fwrite($handler, "include './ZoneAccessors.php';\r\n");
fwrite($handler, "include './ZoneClasses.php';\r\n");
fwrite($handler, "include './GeneratedCode/GeneratedCardDictionaries.php';\r\n");
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

// Generate macro code from database card abilities
GenerateMacroCode();

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
  global $hasAnyIndexedProperties;
  $coreGlobals = "";
  $coreGlobals .= "  global \$currentPlayer, \$updateNumber;\r\n";
  if($hasAnyIndexedProperties) {
    $coreGlobals .= "  global \$objectDataIndices;\r\n";
  }
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
    $hasIndexedProperties = count($zone->IndexedProperties) > 0;
    if (strtolower($scope) == 'global') {
      if ($zone->DisplayMode == 'Value') {
        $readGamestate .= "    \$line = fgets(\$handler);\r\n";
        $readGamestate .= "    if (\$line !== false) {\r\n";
        if($zone->Properties[0]->Type == "number") {
          $readGamestate .= "      \$g" . $zone->Name . " = intval(trim(\$line));\r\n";
        } else {
          $readGamestate .= "      \$g" . $zone->Name . " = trim(\$line);\r\n";
        }
        $readGamestate .= "    }\r\n";
      } else {
        $readGamestate .= "    \$line = fgets(\$handler);\r\n";
        $readGamestate .= "    if (\$line !== false) {\r\n";
        $readGamestate .= "      \$num = intval(\$line);\r\n";
        $readGamestate .= "      for(\$i=0; \$i<\$num; ++\$i) {\r\n";
        $readGamestate .= "        \$line = fgets(\$handler);\r\n";
        $readGamestate .= "        if (\$line !== false) {\r\n";
        $readGamestate .= "          \$obj = new " . $zone->Name . "(trim(\$line), '" . $zone->Name . "', 0, \$i);\r\n";
        $readGamestate .= "          array_push(\$g" . $zone->Name . ", \$obj);\r\n";
        if($hasIndexedProperties) {
          $readGamestate .= "          \$obj->BuildIndex();\r\n";
        }
        $readGamestate .= "        }\r\n";
        $readGamestate .= "      }\r\n";
        $readGamestate .= "    }\r\n";
        if($zone->DisplayMode == "Value" || $zone->DisplayMode == "Radio") $readGamestate .= "    if(count(\$g" . $zone->Name . ") == 0) array_push(\$g" . $zone->Name . ", new " . $zone->Name . "(0));\r\n";
      }
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
  $hasIndexedProperties = count($zone->IndexedProperties) > 0;
  if ($zone->DisplayMode == 'Value') {
    $rv = "";
    $rv .= "    \$line = fgets(\$handler);\r\n";
    $rv .= "    if (\$line !== false) {\r\n";
    $rv .= "      \$p" . $player . $zoneName . " = intval(trim(\$line));\r\n";
    $rv .= "    }\r\n";
    return $rv;
  } else {
    $rv = "";
    $rv .= "    \$line = fgets(\$handler);\r\n";
    $rv .= "    if (\$line !== false) {\r\n";
    $rv .= "      \$num = intval(\$line);\r\n";
    $rv .= "      for(\$i=0; \$i<\$num; ++\$i) {\r\n";
    $rv .= "        \$line = fgets(\$handler);\r\n";
    $rv .= "        if (\$line !== false) {\r\n";
    $rv .= "          \$obj = new " . $zoneName . "(trim(\$line), '" . $zoneName . "', " . $player . ", \$i);\r\n";
    $rv .= "          array_push(\$p" . $player . $zoneName . ", \$obj);\r\n";
    if($hasIndexedProperties) {
      $rv .= "          \$obj->BuildIndex();\r\n";
    }
    $rv .= "        }\r\n";
    $rv .= "      }\r\n";
    $rv .= "    }\r\n";
    if($zone->DisplayMode == "Value" || $zone->DisplayMode == "Radio") $rv .= "    if(count(\$p" . $player . $zoneName . ") == 0) array_push(\$p" . $player . $zoneName . ", new " . $zoneName . "(0));\r\n";
    return $rv;
  }
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
      if ($zone->DisplayMode == 'Value') {
        $writeGamestate .= "  fwrite(\$handler, \$g" . $zoneName . " . \"\\r\\n\");\r\n";
      } else {
        $writeGamestate .= "  \$zoneText = \"\";\r\n";
        $writeGamestate .= "  \$count = 0;\r\n";
        $writeGamestate .= "  for(\$i=0; \$i<count(\$g" . $zoneName . "); ++\$i) {\r\n";
        $writeGamestate .= "    if(\$g" . $zoneName . "[\$i]->Removed()) continue;\r\n";
        $writeGamestate .= "    ++\$count;\r\n";
        $writeGamestate .= "    \$zoneText .= trim(\$g" . $zoneName . "[\$i]->Serialize()) . \"\\r\\n\";\r\n";
        $writeGamestate .= "  }\r\n";
        $writeGamestate .= "  fwrite(\$handler, \$count . \"\\r\\n\");\r\n";
        $writeGamestate .= "  fwrite(\$handler, \$zoneText);\r\n";
      }
    } else {
      if ($zone->DisplayMode == 'Value') {
        $writeGamestate .= "  fwrite(\$handler, \$p1" . $zoneName . " . \"\\r\\n\");\r\n";
        $writeGamestate .= "  fwrite(\$handler, \$p2" . $zoneName . " . \"\\r\\n\");\r\n";
      } else {
        $writeGamestate .= AddWriteZone($zoneName, 1);
        $writeGamestate .= AddWriteZone($zoneName, 2);
      }
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
    $scope = strtolower(isset($zone->Scope) ? $zone->Scope : 'Player');
    // Skip global zones for player 2, they were already output for player 1
    if ($scope == 'global' && $player > 1) continue;
    
    $zoneName = ($scope == 'global') ? "g" . $zone->Name : "p" . $player . $zone->Name;
    echo($zoneName . "<BR>");
    if($i > 0 || $player > 1) $getNextTurn .= "echo(\"<~>\");\r\n";
    if($zone->DisplayMode == "Single") {
      if($zone->Visibility == "Public") {
        //$getNextTurn .= "echo \"Single Public\";\r\n";
        if ($scope == 'global') {
          $getNextTurn .= "  \$arr = &Get" . $zone->Name . "();\r\n";
        } else {
          $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
        }
        // For Single Public zones, return ALL cards so they can be accessed from window data
        // (needed for MZCHOOSE decision queue and other client-side operations)
        $getNextTurn .= "  for(\$i=0; \$i<count(\$arr); ++\$i) {\r\n";
        $getNextTurn .= "    if(\$i > 0) echo(\"<|>\");\r\n";
        $getNextTurn .= "    \$obj = \$arr[\$i];\r\n";
        if(count($zone->VirtualProperties) > 0) {
          $getNextTurn .= "    ComputeVirtualProperties(\$obj);\r\n";
        }
        $getNextTurn .= "    \$displayID = isset(\$obj->CardID) ? \$obj->CardID : \"-\";\r\n";
        $getNextTurn .= "    echo(ClientRenderedCard(\$displayID, cardJSON:json_encode(\$obj)));\r\n";
        $getNextTurn .= "  }\r\n";
      } else if($zone->Visibility == "Private") {
        //Single Private
        $getNextTurn .= "  echo(ClientRenderedCard(\"CardBack\", counters:count(\$" . $zoneName . ")));\r\n";

      } else if ($zone->Visibility == "Self") {
        //$getNextTurn .= "echo \"Single Self\";\r\n";
      }
    } else if($zone->DisplayMode == "All" || $zone->DisplayMode == "Pane" || $zone->DisplayMode == "Tile" || $zone->DisplayMode == "None") {
      if ($scope == 'global') {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "();\r\n";
      } else {
        $getNextTurn .= "  \$arr = &Get" . $zone->Name . "(" . $player . ");\r\n";
      }
      $getNextTurn .= "  for(\$i=0; \$i<count(\$arr); ++\$i) {\r\n";
      $getNextTurn .= "    if(\$i > 0) echo(\"<|>\");\r\n";
      $getNextTurn .= "    \$obj = \$arr[\$i];\r\n";
      if(count($zone->VirtualProperties) > 0) {
        $getNextTurn .= "    ComputeVirtualProperties(\$obj);\r\n";
      }
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
      $countZoneName = count($zone->DisplayParameters) == 0 ? $zone->Name : $zone->DisplayParameters[0];
      if ($scope == 'global') {
        $getNextTurn .= "  \$arr = &Get" . $countZoneName . "();\r\n";
      } else {
        $getNextTurn .= "  \$arr = &Get" . $countZoneName . "(" . $player . ");\r\n";
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
      if ($scope == 'global') {
        $getNextTurn .= "  echo(\$g" . $zone->Name . ");\r\n";
      } else {
        $getNextTurn .= "  echo(\$p" . $player . $zone->Name . ");\r\n";
      }
    }
    else if($zone->DisplayMode == "Radio") {
      if ($scope == 'global') {
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
  global $zones, $numRows, $rootPath, $hasDecisionQueue, $hasFlashMessage;
  $startPiece = 1;
  $numPieces = count($zones);
  $setData = "";
  $myStuff = "";
  $theirStuff = "";
  $myStaticStuff = "";
  $theirStaticStuff = "";
  $header = "echo(\"var myStatic = '';\");\r\n";
  $header .= "echo(\"var theirStatic = '';\");\r\n";
  $header .= "echo(\"var globalStatic = '';\");\r\n";
  $header .= "echo(\"var myRows = [];\");\r\n";
  $header .= "echo(\"var theirRows = [];\");\r\n";
  $header .= "echo(\"window.rootPath = '" . $rootPath . "';\");\r\n";
  $header .= "echo(\"var currentPlayerIndex = playerID;\");\r\n";
  $header .= "echo(\"var otherPlayerIndex = playerID == 1 ? 2 : 1;\");\r\n";
  $footer = "echo(\"RenderRows(myRows, theirRows);\");\r\n";
  $footer .= "echo(\"AppendStaticZones(myStatic, theirStatic, globalStatic);\");\r\n";
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
  $globalStaticStuff = "";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $index = $i + $startPiece;
    $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
    if($zone->Row < 0) {
      if (strtolower($scope) == 'global') {
        // Global zones: single zone, no my/their prefix, use absolute positioning
        $globalStaticStuff .= GeneratedGlobalZoneElement($zone, $index, $dummy);
      } else {
        // Player zones: create my/their versions with mirrored positioning
        $myStaticStuff .= "echo(\"var dataIndex = " . $index . " + (currentPlayerIndex-1)*" . count($zones) . ";\");\r\n";
        $myStaticStuff .= GeneratedZoneElement($zone, "my", "dataIndex", $dummy);

        $theirStaticStuff .= "echo(\"var dataIndex = " . $index . " + (otherPlayerIndex-1)*" . count($zones) . ";\");\r\n";
        $theirStaticStuff .= GeneratedZoneElement($zone, "their", "dataIndex", $dummy);
      }
    }
  }

  if ($hasDecisionQueue) {
    $setData .= "echo(\"CheckAndShowDecisionQueue(window.myDecisionQueueData);\");\r\n";
  }
  if($hasFlashMessage) {
    $setData .= "echo(\"if(window.FlashMessageData && window.FlashMessageData != '') { showFlashMessage(window.FlashMessageData); window.FlashMessageData = ''; }\");\r\n";
  }
  return $header . $setData . $myStuff . $theirStuff . $myStaticStuff . $theirStaticStuff . $globalStaticStuff . $footer;
}

function GeneratedGlobalZoneElement($zone, $index, &$setData) {
  global $rootPath;
  $rv = "";
  
  // Build style dynamically based on playerID to support mirroring for player 2
  $baseStyle = "position: fixed; z-index:30;";
  $staticStyles = "";
  
  $onclick = "onclick=\\\"ZoneClickHandler(\\\'" . $zone->Name . "\\\');\\\"";
  $onscroll = $zone->DisplayMode == "Panel" ? "onscroll=\\\"ZoneScrollHandler(\\\'" . $zone->Name . "\\\');\\\"" : "";
  
  // Static properties (not mirrored)
  if($zone->Width > -1) $staticStyles .= " width:" . $zone->Width . ";";
  if($zone->DisplayMode != "Pane") $staticStyles .= " overflow-y:auto;";
  
  // Check what positioning properties are defined
  $hasLeft = $zone->Left > -1;
  $hasRight = $zone->Right > -1;
  $hasTop = $zone->Top > -1;
  $hasBottom = $zone->Bottom > -1;
  
  if($zone->DisplayMode == "Pane") {
    $rv .= "echo(\"globalCardPanePanes.push(responseArr[" . $index . "]);\");\r\n";
  } else {
    // Build dynamic style with conditional mirroring for player 2
    $rv .= "echo(\"var globalStyle_" . $zone->Name . " = '" . $baseStyle . $staticStyles . "' + (playerID == 1 ? '";
    
    // Player 1 styles
    if($hasLeft) $rv .= " left:" . $zone->Left . ";";
    if($hasRight) $rv .= " right:" . $zone->Right . ";";
    if($hasTop) $rv .= " top:" . $zone->Top . ";";
    if($hasBottom) $rv .= " bottom:" . $zone->Bottom . ";";
    
    $rv .= "' : '";
    
    // Player 2 styles (mirrored - left↔right, top↔bottom)
    if($hasLeft) $rv .= " right:" . $zone->Left . ";";
    if($hasRight) $rv .= " left:" . $zone->Right . ";";
    if($hasTop) $rv .= " bottom:" . $zone->Top . ";";
    if($hasBottom) $rv .= " top:" . $zone->Bottom . ";";
    
    $rv .= "');\");\r\n";
    $rv .= "echo(\"globalStatic += '<div id=\\\'" . $zone->Name . "Wrapper\\\' " . $onclick . " " . $onscroll . " style=\\\"' + globalStyle_" . $zone->Name . " + '\\\">' + PopulateZone('" . $zone->Name . "', responseArr[" . $index . "], cardSize, '" . $rootPath . "/concat', '0', '". $zone->DisplayMode . "') + '</div>';\");\r\n";
  }
  if($zone->Visibility == "Private") return "";
  return $rv;
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
  global $zones, $assetReflection, $hasFlashMessage;
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
    $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
    if (strtolower($scope) == 'global') {
      $rv .= "  zone = document.getElementById(\"" . $zone->Name . "\");\r\n";
      $rv .= "  if(!!zone) zone.classList.add(\"droppable\");\r\n";
    } else {
      $rv .= "  zone = document.getElementById(\"my" . $zone->Name . "\");\r\n";
      $rv .= "  if(!!zone) zone.classList.add(\"droppable\");\r\n";
      //TODO: Only allow their with a setting? Or holding ctrl?
      $rv .= "  zone = document.getElementById(\"their" . $zone->Name . "\");\r\n";
      $rv .= "  if(!!zone) zone.classList.add(\"droppable\");\r\n";
    }
  }
  $rv .= "}\r\n";

  $rv .= "function generatedDragEnd() {\r\n";
  $rv .= "  var zone = null;\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $scope = isset($zone->Scope) ? $zone->Scope : 'Player';
    if (strtolower($scope) == 'global') {
      $rv .= "  zone = document.getElementById(\"" . $zone->Name . "\");\r\n";
      $rv .= "  if(!!zone) zone.classList.remove(\"droppable\");\r\n";
    } else {
      $rv .= "  zone = document.getElementById(\"my" . $zone->Name . "\");\r\n";
      $rv .= "  if(!!zone) zone.classList.remove(\"droppable\");\r\n";
      //TODO: Only allow their with a setting? Or holding ctrl?
      $rv .= "  zone = document.getElementById(\"their" . $zone->Name . "\");\r\n";
      $rv .= "  if(!!zone) zone.classList.remove(\"droppable\");\r\n";
    }
  }
  $rv .= "}\r\n";

  //Client dictionary of zone widgets and their actions
  $rv .= "function GetZoneWidgets(zoneName) {\r\n";
  $rv .= "  switch(zoneName) {\r\n";
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    $widgets = [];
    foreach ($zone->Widgets as $widget) {
      $widgetEntry = ['actions' => $widget->Actions];
      if (isset($widget->Position)) {
        $widgetEntry['position'] = $widget->Position;
      }
      if (isset($widget->Condition) && $widget->Condition !== null) {
        $condParts = explode('=', $widget->Condition, 2);
        if (count($condParts) == 2) {
          $widgetEntry['condition'] = ['field' => trim($condParts[0]), 'value' => trim($condParts[1])];
        }
      }
      $widgets[$widget->LinkedProperty] = $widgetEntry;
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


  // Emit overlay rules as a JS object
  $overlayRules = [];
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    if (isset($zone->Overlays) && is_array($zone->Overlays) && count($zone->Overlays) > 0) {
      $overlayRules[$zone->Name] = $zone->Overlays;
    }
  }
  $rv .= "const OverlayRules = " . json_encode($overlayRules) . ";\r\n";

  // Emit counter rules as a JS object
  $counterRules = [];
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    if (isset($zone->Counters) && is_array($zone->Counters) && count($zone->Counters) > 0) {
      $counterRules[$zone->Name] = $zone->Counters;
    }
  }
  $rv .= "const CounterRules = " . json_encode($counterRules) . ";\r\n";

  // Emit highlight rules as a JS object
  $highlightRules = [];
  for($i=0; $i<count($zones); ++$i) {
    $zone = $zones[$i];
    if (isset($zone->HighlightProperty) && $zone->HighlightProperty !== null) {
      $highlightRules[$zone->Name] = $zone->HighlightProperty;
    }
  }
  $rv .= "const HighlightRules = " . json_encode($highlightRules) . ";\r\n";

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

  if($hasFlashMessage) {
    $rv .= "function showFlashMessage(message) {\r\n";
    $rv .= "  var overlay = document.createElement('div');\r\n";
    $rv .= "  overlay.style.position = 'fixed';\r\n";
    $rv .= "  overlay.style.top = '0';\r\n";
    $rv .= "  overlay.style.left = '0';\r\n";
    $rv .= "  overlay.style.width = '100%';\r\n";
    $rv .= "  overlay.style.height = '100%';\r\n";
    $rv .= "  overlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';\r\n";
    $rv .= "  overlay.style.zIndex = '10000';\r\n";
    $rv .= "  overlay.style.display = 'flex';\r\n";
    $rv .= "  overlay.style.alignItems = 'center';\r\n";
    $rv .= "  overlay.style.justifyContent = 'center';\r\n";
    $rv .= "  overlay.style.opacity = '0';\r\n";
    $rv .= "  overlay.style.transition = 'opacity 0.10s ease-in';\r\n";
    $rv .= "  var messageBox = document.createElement('div');\r\n";
    $rv .= "  messageBox.innerHTML = message;\r\n";
    $rv .= "  messageBox.style.backgroundColor = 'rgba(50, 50, 50, 0.9)';\r\n";
    $rv .= "  messageBox.style.padding = '20px';\r\n";
    $rv .= "  messageBox.style.borderRadius = '10px';\r\n";
    $rv .= "  messageBox.style.boxShadow = '0 4px 8px rgba(0,0,0,0.3)';\r\n";
    $rv .= "  messageBox.style.maxWidth = '80%';\r\n";
    $rv .= "  messageBox.style.textAlign = 'center';\r\n";
    $rv .= "  messageBox.style.fontSize = '18px';\r\n";
    $rv .= "  messageBox.style.fontWeight = 'bold';\r\n";
    $rv .= "  messageBox.style.color = 'white';\r\n";
    $rv .= "  overlay.appendChild(messageBox);\r\n";
    $rv .= "  document.body.appendChild(overlay);\r\n";
    $rv .= "  setTimeout(function() { overlay.style.opacity = '1'; }, 10);\r\n";
    $rv .= "  setTimeout(function() {\r\n";
    $rv .= "    overlay.style.transition = 'opacity .250s ease-out';\r\n";
    $rv .= "    overlay.style.opacity = '0';\r\n";
    $rv .= "    setTimeout(function() { if(overlay.parentNode) overlay.parentNode.removeChild(overlay); }, 250);\r\n";
    $rv .= "  }, 500);\r\n";
    $rv .= "}\r\n";
  }

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
  fwrite($handler, "echo(\"<div id='globalStuff' style='position:fixed; top:0; left:0; width:100%; height:100%; z-index:30; pointer-events:none;'></div>\");\r\n");
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

function GetModule($type) {
  global $modules;
  for($i=0; $i<count($modules); ++$i) {
    if($modules[$i]->Name == $type) return $modules[$i];
  }
  return null;
}

/**
 * Generate macro code from database card abilities
 * Creates a GeneratedMacroCode.php file in the GeneratedCode folder
 * This file contains implementations of macros for specific cards and macro count functions
 */
/**
 * Generate code to retrieve macro parameters from DecisionQueueVariables
 * This allows ability code to access the parameters that were passed when invoking the macro
 * 
 * @param array $macroParams Array of parameter names from the macro definition
 * @return string PHP code to retrieve all macro parameters
 */
function GenerateMacroParamRetrievalCode($macroParams) {
  if (empty($macroParams)) {
    return "";
  }
  $code = "// Retrieve macro parameters\n";
  foreach ($macroParams as $param) {
    $code .= "\$" . $param . " = DecisionQueueController::GetVariable(\"" . $param . "\");\n";
  }
  return $code;
}

/**
 * Generate code to retrieve macro parameters with indentation (for continuation handlers)
 * 
 * @param array $macroParams Array of parameter names from the macro definition
 * @param string $indent Indentation to prepend to each line
 * @return string PHP code to retrieve all macro parameters
 */
function GenerateMacroParamRetrievalCodeIndented($macroParams, $indent = "  ") {
  if (empty($macroParams)) {
    return "";
  }
  $code = $indent . "// Retrieve macro parameters\n";
  foreach ($macroParams as $param) {
    $code .= $indent . "\$" . $param . " = DecisionQueueController::GetVariable(\"" . $param . "\");\n";
  }
  return $code;
}

/**
 * Transform await syntax in ability code to DecisionQueue calls with variable storage
 * 
 * Supported patterns:
 *   Pattern 1: $var = await $player.ChoiceType(params)
 *     Becomes: AddDecision + continuation handler with variable storage
 *   
 *   Pattern 2: await FunctionName($player, args)
 *     Becomes: FunctionName call + continuation handler (for functions that queue decisions)
 * 
 * Supported choice types (Pattern 1):
 *   - MZChoose: Mandatory card choice from zones
 *   - MZMayChoose: Optional card choice (client shows Pass button)
 *   - YesNo: Yes/No choice
 *   - Rearrange: Rearrange cards with zones and starting cards (semicolon-delimited)
 *   - Custom types: Any name is converted to uppercase (e.g., CustomChoice -> CUSTOMCHOICE)
 * 
 * Example transformation (Pattern 1 - choice):
 *   INPUT:
 *     $unit1 = await $player.MZChoose("BG1&BG2");
 *     $unit2 = await $player.MZMayChoose("BG1&BG2");  // Optional choice
 *     if ($unit2 !== "PASS") SwapPosition($unit1, $unit2);
 * 
 *   OUTPUT (main handler):
 *     DecisionQueueController::AddDecision($player, "MZCHOOSE", "BG1&BG2", 1);
 *     DecisionQueueController::AddDecision($player, "CUSTOM", "CARDID-1", 1);
 * 
 * Example transformation (Pattern 2 - void function):
 *   INPUT:
 *     $hunter = await $player.MZChoose($hunters);
 *     await DoPlayFighter($player, $hunter);
 *     echo("Fighter deployed!");
 * 
 *   OUTPUT (main handler):
 *     DecisionQueueController::AddDecision($player, "MZCHOOSE", $hunters, 1);
 *     DecisionQueueController::AddDecision($player, "CUSTOM", "CARDID-1", 1);
 * 
 *   OUTPUT (continuation handler):
 *     $customDQHandlers["CARDID-1"] = function($player, $parts, $lastDecision) {
 *       DecisionQueueController::StoreVariable("hunter", $lastDecision);
 *       $hunter = $lastDecision;
 *       DoPlayFighter($player, $hunter);  // Calls function which queues decisions
 *       DecisionQueueController::AddDecision($player, "CUSTOM", "CARDID-2", 1);  // Continue after
 *     };
 *     $customDQHandlers["CARDID-2"] = function($player, $parts, $lastDecision) {
 *       $hunter = DecisionQueueController::GetVariable("hunter");
 *       echo("Fighter deployed!");
 *     };
 */
function TransformAwaitCode($code, $cardId, $macroName, &$continuationHandlers, $macroParams = []) {
  $lines = explode("\n", $code);
  $awaits = [];
  
  // First pass: find all await statements
  for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    // Pattern 1: $var = await $player.ChoiceType(params) - method call on player variable
    if (preg_match('/(\$\w+)\s*=\s*await\s+(\$\w+)\.(\w+)\((.*)\)\s*;?\s*$/', $line, $matches)) {
      $rawParams = trim($matches[4]);
      $methodName = $matches[3];
      
      // For Rearrange, keep the parameter exactly as-is (with string concatenation)
      // For other methods, trim quotes
      if (strtolower($methodName) === 'rearrange') {
        $params = $rawParams;
      } else {
        $params = trim($rawParams, '"\'');
      }
      
      $awaits[] = [
        'lineIndex' => $i,
        'returnVar' => $matches[1],  // e.g., $cardToDeploy
        'playerVar' => $matches[2],  // e.g., $player
        'choiceType' => strtolower($methodName) === 'rearrange' ? 'MZREARRANGE' : strtoupper($methodName), // e.g., MZCHOOSE or MZREARRANGE
        'params' => $params, // e.g., myHand or "Battlefield=" . $cards
        'isRearrange' => strtolower($methodName) === 'rearrange',
        'isVoidFunction' => false
      ];
    }
    // Pattern 2: await FunctionName(args) - void function call that queues decisions
    // Also matches: await FunctionName($player, $arg) etc.
    else if (preg_match('/^\s*await\s+(\w+)\s*\((.+)\)\s*;?\s*$/', $line, $matches)) {
      $functionName = $matches[1];
      $functionArgs = trim($matches[2]);
      
      // Try to extract $player from the arguments (first argument is typically player)
      // This handles cases like DoPlayFighter($player, $card)
      $playerVar = '$player'; // Default
      if (preg_match('/^\s*(\$\w+)/', $functionArgs, $playerMatch)) {
        $playerVar = $playerMatch[1];
      }
      
      $awaits[] = [
        'lineIndex' => $i,
        'returnVar' => null,  // No return variable for void await
        'playerVar' => $playerVar,
        'functionName' => $functionName,
        'functionArgs' => $functionArgs,
        'isVoidFunction' => true
      ];
    }
  }
  
  // If no awaits found, return original code with macro param retrieval prepended
  if (count($awaits) == 0) {
    $paramRetrievalCode = GenerateMacroParamRetrievalCode($macroParams);
    return $paramRetrievalCode . $code;
  }
  
  // Generate main handler code (up to first await)
  $transformedCode = "";
  // Add macro parameter retrieval at the start
  $transformedCode .= GenerateMacroParamRetrievalCode($macroParams);
  for ($i = 0; $i < $awaits[0]['lineIndex']; $i++) {
    $transformedCode .= $lines[$i] . "\n";
  }
  
  // Add first decision (or call function for void await)
  $firstAwait = $awaits[0];
  if (isset($firstAwait['isVoidFunction']) && $firstAwait['isVoidFunction']) {
    // For void function await: call the function, then queue continuation
    $transformedCode .= $firstAwait['functionName'] . "(" . $firstAwait['functionArgs'] . ");\n";
    $transformedCode .= "DecisionQueueController::AddDecision(" . $firstAwait['playerVar'] . ", \"CUSTOM\", \"" . $cardId . "-1\", 1);\n";
  } else {
    // For Rearrange and other complex params, don't add extra quotes if they contain concatenation
    if (isset($firstAwait['isRearrange']) && $firstAwait['isRearrange']) {
      $transformedCode .= "DecisionQueueController::AddDecision(" . $firstAwait['playerVar'] . ", \"" . $firstAwait['choiceType'] . "\", " . $firstAwait['params'] . ", 1);\n";
    } else {
      $transformedCode .= "DecisionQueueController::AddDecision(" . $firstAwait['playerVar'] . ", \"" . $firstAwait['choiceType'] . "\", \"" . $firstAwait['params'] . "\", 1);\n";
    }
    $transformedCode .= "DecisionQueueController::AddDecision(" . $firstAwait['playerVar'] . ", \"CUSTOM\", \"" . $cardId . "-1\", 1);\n";
  }
  
  // Generate continuation handlers for each await
  for ($awaitIndex = 0; $awaitIndex < count($awaits); $awaitIndex++) {
    $currentAwait = $awaits[$awaitIndex];
    $handlerName = $cardId . "-" . ($awaitIndex + 1);
    
    $handlerCode = "";
    
    // Retrieve macro parameters first (they persist across continuations)
    $handlerCode .= GenerateMacroParamRetrievalCodeIndented($macroParams);
    
    // For non-void awaits: store the current variable (from $lastDecision)
    if (!isset($currentAwait['isVoidFunction']) || !$currentAwait['isVoidFunction']) {
      $varName = substr($currentAwait['returnVar'], 1); // Remove $ prefix
      $handlerCode .= "  DecisionQueueController::StoreVariable(\"" . $varName . "\", \$lastDecision);\n";
    }
    
    // Retrieve all previously stored variables that might be referenced in the code block
    for ($j = 0; $j < $awaitIndex; $j++) {
      $prevAwait = $awaits[$j];
      // Only retrieve variables from non-void awaits
      if (!isset($prevAwait['isVoidFunction']) || !$prevAwait['isVoidFunction']) {
        $prevVarName = substr($prevAwait['returnVar'], 1);
        $handlerCode .= "  " . $prevAwait['returnVar'] . " = DecisionQueueController::GetVariable(\"" . $prevVarName . "\");\n";
      }
    }
    
    // For non-void awaits: assign the current result from $lastDecision to the awaited variable
    if (!isset($currentAwait['isVoidFunction']) || !$currentAwait['isVoidFunction']) {
      $handlerCode .= "  " . $currentAwait['returnVar'] . " = \$lastDecision;\n";
    }
    
    // Get the code between this await and the next await (or end of code)
    $startLine = $currentAwait['lineIndex'] + 1;
    $endLine = ($awaitIndex + 1 < count($awaits)) ? $awaits[$awaitIndex + 1]['lineIndex'] : count($lines);
    
    // Add the code between awaits
    for ($i = $startLine; $i < $endLine; $i++) {
      // Skip the next await line itself if we're not at the last await
      if ($awaitIndex + 1 < count($awaits) && $i == $awaits[$awaitIndex + 1]['lineIndex']) {
        break;
      }
      $handlerCode .= "  " . $lines[$i] . "\n";
    }
    
    // If there's another await after this one, queue it
    if ($awaitIndex + 1 < count($awaits)) {
      $nextAwait = $awaits[$awaitIndex + 1];
      if (isset($nextAwait['isVoidFunction']) && $nextAwait['isVoidFunction']) {
        // For void function await: call the function, then queue continuation
        $handlerCode .= "  " . $nextAwait['functionName'] . "(" . $nextAwait['functionArgs'] . ");\n";
        $handlerCode .= "  DecisionQueueController::AddDecision(" . $nextAwait['playerVar'] . ", \"CUSTOM\", \"" . $cardId . "-" . ($awaitIndex + 2) . "\", 1);\n";
      } else {
        // For Rearrange and other complex params, don't add extra quotes if they contain concatenation
        if (isset($nextAwait['isRearrange']) && $nextAwait['isRearrange']) {
          $handlerCode .= "  DecisionQueueController::AddDecision(" . $nextAwait['playerVar'] . ", \"" . $nextAwait['choiceType'] . "\", " . $nextAwait['params'] . ", 1);\n";
        } else {
          $handlerCode .= "  DecisionQueueController::AddDecision(" . $nextAwait['playerVar'] . ", \"" . $nextAwait['choiceType'] . "\", \"" . $nextAwait['params'] . "\", 1);\n";
        }
        $handlerCode .= "  DecisionQueueController::AddDecision(" . $nextAwait['playerVar'] . ", \"CUSTOM\", \"" . $cardId . "-" . ($awaitIndex + 2) . "\", 1);\n";
      }
    }
    
    // Store handler for generation
    $continuationHandlers[$handlerName] = [
      'code' => $handlerCode,
      'comment' => $macroName
    ];
  }
  
  return rtrim($transformedCode);
}

function GenerateMacroCode() {
  global $rootName;
  
  try {
    $conn = GetLocalMySQLConnection();
    $cardAbilityDB = new CardAbilityDB($conn);
    
    $rootPath = "./" . $rootName;
    $directory = $rootPath . "/GeneratedCode";
    
    if (!is_dir($directory)) {
      mkdir($directory, 0755, true);
    }
    
    $filename = $directory . "/GeneratedMacroCode.php";
    $handler = fopen($filename, "w");
    
    fwrite($handler, "<?php\r\n");
    fwrite($handler, "// AUTO-GENERATED FILE: Macro implementations from CardEditor database\r\n");
    fwrite($handler, "// DO NOT EDIT MANUALLY - changes will be overwritten when generator runs\r\n");
    fwrite($handler, "// Last generated: " . date("Y-m-d H:i:s") . "\r\n\r\n");
    
    // Get all macros defined in the schema
    $schemaFile = "./Schemas/" . $rootName . "/GameSchema.txt";
    $macrosByName = [];
    
    if (file_exists($schemaFile)) {
      $lines = file($schemaFile, FILE_IGNORE_NEW_LINES);
      foreach ($lines as $line) {
        if (strpos($line, 'Macro:') === 0) {
          // Extract macro name and parameters from: Macro: Name=MacroName(param1,param2);
          if (preg_match('/Name=(\w+)(?:\(([^)]*)\))?/', $line, $matches)) {
            $macroName = trim($matches[1]);
            // Parse parameter names if present
            $params = [];
            if (isset($matches[2]) && $matches[2] !== '') {
              $paramList = explode(',', $matches[2]);
              foreach ($paramList as $param) {
                $params[] = trim($param);
              }
            }
            $macrosByName[$macroName] = $params;
          }
        }
      }
    }
    
    // Group abilities by macro name
    $abilitiesByMacro = [];
    foreach ($macrosByName as $macroName => $params) {
      $abilities = $cardAbilityDB->getAbilitiesByMacro($rootName, $macroName);
      if (count($abilities) > 0) {
        $abilitiesByMacro[$macroName] = $abilities;
      }
    }
    
    // Generate card-specific macro implementations
    if (count($abilitiesByMacro) > 0) {
      fwrite($handler, "// Card-specific macro implementations\r\n");
      fwrite($handler, "// Each macro has an array where card IDs are keys (or CardID:Index for multiple abilities)\r\n\r\n");
      
      $allContinuationHandlers = []; // Store all continuation handlers for output later
      
      // Track ability index per card for macros that support multiple abilities
      $abilityIndexByCard = [];
      
      foreach ($abilitiesByMacro as $macroName => $abilities) {
        // Convert macro name to valid variable name (e.g., "card-play" -> "cardPlayAbilities")
        $varName = lcfirst(str_replace("-", "", ucwords($macroName, "-"))) . "Abilities";
        
        // Get the macro parameters from the schema
        $macroParams = isset($macrosByName[$macroName]) ? $macrosByName[$macroName] : [];
        
        // Reset ability index tracking for this macro type
        $abilityIndexByCard = [];
        
        foreach ($abilities as $ability) {
          $cardId = $ability['card_id'];
          $code = $ability['ability_code'];
          $name = $ability['ability_name'] ?? $cardId;
          
          // Track ability index for this card
          if (!isset($abilityIndexByCard[$cardId])) {
            $abilityIndexByCard[$cardId] = 0;
          }
          $abilityIndex = $abilityIndexByCard[$cardId];
          $abilityIndexByCard[$cardId]++;
          
          // Use CardID:Index as the key for abilities (supports multiple per card)
          $abilityKey = $cardId . ":" . $abilityIndex;
          
          // Transform code to handle await syntax (pass macro params for variable retrieval)
          // Use the ability key for continuation handlers instead of just cardId
          $continuationHandlers = [];
          $transformedCode = TransformAwaitCode($code, $abilityKey, $name, $continuationHandlers, $macroParams);
          
          // Merge continuation handlers into global collection
          $allContinuationHandlers = array_merge($allContinuationHandlers, $continuationHandlers);
          
          fwrite($handler, "\$" . $varName . "[\"" . $abilityKey . "\"] = function(\$player) { //" . $name . "\r\n");
          fwrite($handler, "  " . str_replace("\n", "\n  ", trim($transformedCode)) . "\r\n");
          fwrite($handler, "};\r\n");
        }
        
        fwrite($handler, "\r\n");
      }
      
      // Generate continuation handlers (from await transformations)
      if (count($allContinuationHandlers) > 0) {
        fwrite($handler, "// Continuation handlers for await syntax\r\n");
        fwrite($handler, "// These handlers are called after player makes a choice in an ability\r\n\r\n");
        
        foreach ($allContinuationHandlers as $handlerName => $handlerData) {
          fwrite($handler, "\$customDQHandlers[\"" . $handlerName . "\"] = function(\$player, \$parts, \$lastDecision) { //" . $handlerData['comment'] . "\r\n");
          fwrite($handler, $handlerData['code']);
          fwrite($handler, "};\r\n\r\n");
        }
      }
      
      // Generate macro count data and functions
      fwrite($handler, "// Global macro count arrays - stores how many of each macro each card has\r\n");
      fwrite($handler, "// These are built once at file load time for optimal performance\r\n\r\n");
      
      foreach ($abilitiesByMacro as $macroName => $abilities) {
        // Generate PHP function
        $functionName = "Card" . str_replace(" ", "", ucwords(str_replace("-", " ", $macroName))) . "Count";
        $varName = "\$" . $functionName . "Data";
        
        // Build associative array of card ID => count
        $countArray = [];
        foreach ($abilities as $ability) {
          $cardId = $ability['card_id'];
          if (!isset($countArray[$cardId])) {
            $countArray[$cardId] = 0;
          }
          $countArray[$cardId]++;
        }
        
        // Emit global array
        fwrite($handler, $varName . " = [\r\n");
        foreach ($countArray as $cardId => $count) {
          fwrite($handler, "  \"" . addslashes($cardId) . "\" => " . intval($count) . ",\r\n");
        }
        fwrite($handler, "];\r\n\r\n");
        
        // Emit lookup function
        fwrite($handler, "function " . $functionName . "(\$cardId) {\r\n");
        fwrite($handler, "  global \$" . $functionName . "Data;\r\n");
        fwrite($handler, "  return isset(\$" . $functionName . "Data[\$cardId]) ? \$" . $functionName . "Data[\$cardId] : 0;\r\n");
        fwrite($handler, "}\r\n\r\n");
        
        // Generate ability names array for this macro (e.g., CardActivateAbilityNames)
        $namesVarName = "\$" . $functionName . "NamesData";
        $abilityIndexByCard = [];
        
        fwrite($handler, $namesVarName . " = [\r\n");
        foreach ($abilities as $ability) {
          $cardId = $ability['card_id'];
          $name = $ability['ability_name'] ?? $cardId;
          
          if (!isset($abilityIndexByCard[$cardId])) {
            $abilityIndexByCard[$cardId] = 0;
          }
          $abilityIndex = $abilityIndexByCard[$cardId];
          $abilityIndexByCard[$cardId]++;
          
          $abilityKey = $cardId . ":" . $abilityIndex;
          fwrite($handler, "  \"" . addslashes($abilityKey) . "\" => \"" . addslashes($name) . "\",\r\n");
        }
        fwrite($handler, "];\r\n\r\n");
        
        // Emit ability names lookup function
        $namesFunction = $functionName . "Names";
        fwrite($handler, "function " . $namesFunction . "(\$cardId, \$abilityIndex = null) {\r\n");
        fwrite($handler, "  global \$" . $functionName . "NamesData;\r\n");
        fwrite($handler, "  if (\$abilityIndex !== null) {\r\n");
        fwrite($handler, "    \$key = \$cardId . \":\" . \$abilityIndex;\r\n");
        fwrite($handler, "    return isset(\$" . $functionName . "NamesData[\$key]) ? \$" . $functionName . "NamesData[\$key] : \"\";\r\n");
        fwrite($handler, "  }\r\n");
        fwrite($handler, "  // Return all ability names for the card as an array\r\n");
        fwrite($handler, "  \$names = [];\r\n");
        fwrite($handler, "  for (\$i = 0; \$i < CardActivateAbilityCount(\$cardId); \$i++) {\r\n");
        fwrite($handler, "    \$key = \$cardId . \":\" . \$i;\r\n");
        fwrite($handler, "    if (isset(\$" . $functionName . "NamesData[\$key])) {\r\n");
        fwrite($handler, "      \$names[] = \$" . $functionName . "NamesData[\$key];\r\n");
        fwrite($handler, "    }\r\n");
        fwrite($handler, "  }\r\n");
        fwrite($handler, "  return \$names;\r\n");
        fwrite($handler, "}\r\n\r\n");
      }
      
    } else {
      fwrite($handler, "// No custom macro implementations found in database\r\n");
      fwrite($handler, "// This file will be populated as abilities are added through CardEditor\r\n\r\n");
    }
    
    fwrite($handler, "?>");
    fclose($handler);
    
    // Generate JavaScript macro count functions
    GenerateMacroCountJS($rootName, $abilitiesByMacro);
    
    mysqli_close($conn);
    
    echo("Generated macro code file: $filename<BR>");
    
  } catch (Exception $e) {
    echo("Note: Could not generate macro code file: " . $e->getMessage() . "<BR>");
  }
}

/**
 * Generate JavaScript macro count functions
 * Creates count functions that can be called client-side to query macro counts by card ID
 */
function GenerateMacroCountJS($rootName, $abilitiesByMacro) {
  try {
    $rootPath = "./" . $rootName;
    $directory = $rootPath . "/GeneratedCode";
    
    if (!is_dir($directory)) {
      mkdir($directory, 0755, true);
    }
    
    $filename = $directory . "/GeneratedMacroCount.js";
    $handler = fopen($filename, "w");
    
    fwrite($handler, "// AUTO-GENERATED FILE: Macro count functions\r\n");
    fwrite($handler, "// DO NOT EDIT MANUALLY - changes will be overwritten when generator runs\r\n");
    fwrite($handler, "// Last generated: " . date("Y-m-d H:i:s") . "\r\n\r\n");
    
    if (count($abilitiesByMacro) > 0) {
      fwrite($handler, "// Global macro count objects - stores how many of each macro each card has\r\n");
      fwrite($handler, "// These are built once at file load time for optimal performance\r\n\r\n");
      
      foreach ($abilitiesByMacro as $macroName => $abilities) {
        // Generate JavaScript function
        $functionName = "Card" . str_replace(" ", "", ucwords(str_replace("-", " ", $macroName))) . "Count";
        $varName = $functionName . "Data";
        
        // Build associative array of card ID => count
        $countArray = [];
        foreach ($abilities as $ability) {
          $cardId = $ability['card_id'];
          if (!isset($countArray[$cardId])) {
            $countArray[$cardId] = 0;
          }
          $countArray[$cardId]++;
        }
        
        // Emit global object
        fwrite($handler, "const " . $varName . " = {\r\n");
        foreach ($countArray as $cardId => $count) {
          fwrite($handler, "  \"" . addslashes($cardId) . "\": " . intval($count) . ",\r\n");
        }
        fwrite($handler, "};\r\n\r\n");
        
        // Emit lookup function
        fwrite($handler, "function " . $functionName . "(cardId) {\r\n");
        fwrite($handler, "  return " . $varName . "[cardId] !== undefined ? " . $varName . "[cardId] : 0;\r\n");
        fwrite($handler, "}\r\n\r\n");
        
        // Generate ability names data for this macro
        $namesVarName = $functionName . "NamesData";
        $abilityIndexByCard = [];
        
        fwrite($handler, "const " . $namesVarName . " = {\r\n");
        foreach ($abilities as $ability) {
          $abCardId = $ability['card_id'];
          $name = $ability['ability_name'] ?? $abCardId;
          
          if (!isset($abilityIndexByCard[$abCardId])) {
            $abilityIndexByCard[$abCardId] = 0;
          }
          $abilityIndex = $abilityIndexByCard[$abCardId];
          $abilityIndexByCard[$abCardId]++;
          
          $abilityKey = $abCardId . ":" . $abilityIndex;
          fwrite($handler, "  \"" . addslashes($abilityKey) . "\": \"" . addslashes($name) . "\",\r\n");
        }
        fwrite($handler, "};\r\n\r\n");
        
        // Emit ability names lookup function
        $namesFunction = $functionName . "Names";
        fwrite($handler, "function " . $namesFunction . "(cardId, abilityIndex) {\r\n");
        fwrite($handler, "  if (abilityIndex !== undefined && abilityIndex !== null) {\r\n");
        fwrite($handler, "    const key = cardId + \":\" + abilityIndex;\r\n");
        fwrite($handler, "    return " . $namesVarName . "[key] !== undefined ? " . $namesVarName . "[key] : \"\";\r\n");
        fwrite($handler, "  }\r\n");
        fwrite($handler, "  // Return all ability names for the card as an array\r\n");
        fwrite($handler, "  const names = [];\r\n");
        fwrite($handler, "  const count = " . $functionName . "(cardId);\r\n");
        fwrite($handler, "  for (let i = 0; i < count; i++) {\r\n");
        fwrite($handler, "    const key = cardId + \":\" + i;\r\n");
        fwrite($handler, "    if (" . $namesVarName . "[key] !== undefined) {\r\n");
        fwrite($handler, "      names.push(" . $namesVarName . "[key]);\r\n");
        fwrite($handler, "    }\r\n");
        fwrite($handler, "  }\r\n");
        fwrite($handler, "  return names;\r\n");
        fwrite($handler, "}\r\n\r\n");
      }
      
    } else {
      fwrite($handler, "// No custom macro implementations found in database\r\n");
      fwrite($handler, "// This file will be populated as abilities are added through CardEditor\r\n\r\n");
    }
    
    fclose($handler);
    echo("Generated JavaScript macro count file: $filename<BR>");
    
  } catch (Exception $e) {
    echo("Note: Could not generate JavaScript macro count file: " . $e->getMessage() . "<BR>");
  }
}

?>
