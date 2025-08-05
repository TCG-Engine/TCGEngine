<?php

include './zzImageConverter.php';
include './Core/Trie.php';
include "./Core/HTTPLibraries.php";

$rootName = TryGET("rootName", "");

$schemaFile = "./Schemas/" . $rootName . "/ImportSchema.txt";
$handler = fopen($schemaFile, "r");
$jsonUrl= trim(fgets($handler));
$imageUrl= trim(fgets($handler));
$imageFormat = trim(fgets($handler));
$cardArrayJson = trim(fgets($handler));
$paginationUrlParameter = trim(fgets($handler));
$paginationResponseMetadata = trim(fgets($handler));
$properties = explode(",", fgets($handler));
$propertyTypes = [];
fclose($handler);

$rootPath = "./" . $rootName;
if(!is_dir($rootPath)) mkdir($rootPath, 0755, true);
if(!is_dir($rootPath . "/TempImages")) mkdir($rootPath . "/TempImages", 0755, true);
if(!is_dir($rootPath . "/WebpImages")) mkdir($rootPath . "/WebpImages", 0755, true);
if(!is_dir($rootPath . "/concat")) mkdir($rootPath . "/concat", 0755, true);
if(!is_dir($rootPath . "/crops")) mkdir($rootPath . "/crops", 0755, true);

for($i=0; $i<count($properties); ++$i) {
  $property = trim($properties[$i]);
  $propertyArr = explode(":", $property);
  $properties[$i] = trim($propertyArr[0]);
  $propertyTypes[$i] = count($propertyArr) > 1 ? trim($propertyArr[1]) : "string";//Default to string
}

$cardArray = [];
$duplicateMap = [];
$reprintMap = [];
$count = 0;
$currentPage = 1;
$hasMoreData = true;

while($hasMoreData) {
  $curl = curl_init();
  $headers = array(
    "Content-Type: application/json",
  );
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  $urlWithParams = $jsonUrl . ($paginationUrlParameter != "" ? "?" . $paginationUrlParameter . "=" . $currentPage : "");
  echo("Fetching data from: " . $urlWithParams . "<BR>");
  curl_setopt($curl, CURLOPT_URL, $urlWithParams);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $apiData = curl_exec($curl);
  curl_close($curl);

  // Remove BOM if present
  if (substr($apiData, 0, 3) === "\xEF\xBB\xBF") {
      $apiData = substr($apiData, 3);
  }
  $response = json_decode($apiData);
  echo($response ? "Response received successfully.<BR>" : "Failed to decode JSON response.<BR>");

  echo(count($response->$cardArrayJson) . " cards on page " . $currentPage . "<BR>");

  for ($i = 0; $i < count($response->$cardArrayJson); ++$i)
  {
    $card = $response->$cardArrayJson[$i];
    
    if($rootName == "Lorcana") {
      $setNumber = $card->setId;
      if($setNumber < 10) $setNumber = "00" . $setNumber;
      else if($setNumber < 100) $setNumber = "0" . $setNumber;
      $cardId = $card->setCardId;
      if($cardId < 10) $cardId = "00" . $cardId;
      else if($cardId < 100) $cardId = "0" . $cardId;
      $cardID = $setNumber . "-" . $cardId;
    } else if($rootName == "SoulMastersSim" || $rootName == "SoulMastersDB") {
      $cardID = $card->Number;
    } else if($rootName == "SWUDeck") {
      $card = $card->attributes;
      $cardID = $card->cardUid;
      $setCode = $card->expansion->data->attributes->code ?? "Unknown";
      if($setCode != "SOR" && $setCode != "SHD" && $setCode != "TWI" && $setCode != "JTL" && $setCode != "LOF") {
        continue;
      }

      $nameHash = hash('sha256', $card->title . $card->subtitle);
      if(!isset($duplicateMap[$nameHash])) {
        $duplicateMap[$nameHash] = true;
        $reprintMap[$cardID] = true;
      }
      
      $definedType = $card->type->data->attributes->name;
      if($definedType == "Token Unit" || $definedType == "Token Upgrade" || $definedType == "Force Token") {
        continue;
      }
    }
    $card->id = $cardID;
    $cardArray[] = $card;

    $thisImageUrl = $imageUrl . $cardID . "." . $imageFormat;
    if($rootName == "SWUDeck") {
      $thisImageUrl = $card->artFront->data->attributes->formats->card->url;
    } else if($rootName == "SoulMastersDB" || $rootName == "SoulMastersSim") {
      $thisImageUrl = $imageUrl . $cardID . "-CYMK.jpg";
    }
    CheckImage($cardID, $thisImageUrl, "", "", rootPath:"./" . $rootName . "/");

    ++$count;
  }
  if($paginationUrlParameter != "") {
    echo("Parsed " . $count . " cards on page " . $currentPage . "<BR>");
    $currentPage++;
    $pageCount = 1;
    if($rootName == "SWUDeck") {
      $pageCount = $response->meta->pagination->pageCount;
    }
    $hasMoreData = $currentPage <= $pageCount;
  } else {
    $hasMoreData = false;
  }
}

echo("Parsed " . $count . " cards<BR>");

$associativeArrays = [];
foreach ($properties as $property) {
  $associativeArrays[$property] = [];
}
if($rootName == "SWUDeck") {
  $associativeArrays["uuidLookup"] = [];
  $associativeArrays["cardIdLookup"] = [];
}
$allCardIds = [];
for ($i = 0; $i < count($cardArray); ++$i) {
  $card = $cardArray[$i];
  foreach ($properties as $property) {
    $value = GetPropertyValue($card, $property);
    $associativeArrays[$property][$card->id] = $value;
  }
  if($rootName == "SWUDeck") {
    $cardNumber = $card->cardNumber;
    if($cardNumber < 10) $cardNumber = "00" . $cardNumber;
    else if($cardNumber < 100) $cardNumber = "0" . $cardNumber;
    $set = $card->expansion->data->attributes->code;
    $cardID= $set . "_" . $cardNumber;
    $associativeArrays["uuidLookup"][$cardID] = $card->id;
    $associativeArrays["cardIdLookup"][$card->id] = $cardID;
    if(isset($reprintMap[$card->id])) $allCardIds[] = $card->id;
  } else {
    $allCardIds[] = $card->id;
  }
}

$directory = "./" . $rootName . "/GeneratedCode";
if(!is_dir($directory)) mkdir($directory, 777, true);

$generateFilename = $directory . "/GeneratedCardDictionaries.php";
$handler = fopen($generateFilename, "w");
fwrite($handler, "<?php\r\n");
foreach ($properties as $property) {
  fwrite($handler, "  \$" . $property . "Data = " . var_export($associativeArrays[$property], true) . ";\r\n");
  fwrite($handler, "function Card" . ucwords($property) . "(\$cardID) {\r\n");
  fwrite($handler, "  global \$" . $property . "Data;\r\n");
  fwrite($handler, "  return isset(\$" . $property . "Data[\$cardID]) ? \$" . $property . "Data[\$cardID] : null;\r\n");
  fwrite($handler, "}\r\n\r\n");
}
if($rootName == "SWUDeck") {
  fwrite($handler, "function UUIDLookup(\$cardID) {\r\n");
  fwrite($handler, "  \$data = " . var_export($associativeArrays["uuidLookup"], true) . ";\r\n");
  fwrite($handler, "  return isset(\$data[\$cardID]) ? \$data[\$cardID] : null;\r\n");
  fwrite($handler, "}\r\n\r\n");
  fwrite($handler, "function CardIDLookup(\$cardID) {\r\n");
  fwrite($handler, "  \$data = " . var_export($associativeArrays["cardIdLookup"], true) . ";\r\n");
  fwrite($handler, "  return isset(\$data[\$cardID]) ? \$data[\$cardID] : null;\r\n");
  fwrite($handler, "}\r\n\r\n");
}
fwrite($handler, "function GetAllCardIds() {\r\n");
fwrite($handler, "  return " . var_export($allCardIds, true) . ";\r\n");
fwrite($handler, "}\r\n\r\n");
fwrite($handler, "?>");
fclose($handler);

$generateFilename = $directory . "/GeneratedCardDictionaries.js";
$handler = fopen($generateFilename, "w");
foreach ($properties as $property) {
  fwrite($handler, "var " . $property . "Data = " . json_encode($associativeArrays[$property]) . ";\r\n");
  fwrite($handler, "function Card" . $property . "(cardID) {\r\n");
  fwrite($handler, "  return " . $property . "Data[cardID] !== undefined ? " . $property . "Data[cardID] : null;\r\n");
  fwrite($handler, "}\r\n\r\n");
}
//Add should filter function
fwrite($handler, "function ShouldFilter(cardID,filter) {\r\n");
fwrite($handler, "  var filterArr = filter.split(\" \");\r\n");
fwrite($handler, "  for(var i=0; i<filterArr.length; ++i) {\r\n");
fwrite($handler, "    var operand = '';\r\n");
fwrite($handler, "    var operandArr = [':', '=', '<', '>', '<=', '>='];\r\n");
fwrite($handler, "    for(var j=0; j<operandArr.length; ++j) {\r\n");
fwrite($handler, "      if(filterArr[i].includes(operandArr[j])) {\r\n");
fwrite($handler, "        operand = operandArr[j];\r\n");
fwrite($handler, "      }\r\n");
fwrite($handler, "    }\r\n");
fwrite($handler, "    var thisFilter = \"\";\r\n");
fwrite($handler, "    var thisValue = \"\";\r\n");
fwrite($handler, "    if(operand == '') {\r\n");
fwrite($handler, "      thisFilter = \"title\";\r\n");
fwrite($handler, "      thisValue = filterArr[i];\r\n");
fwrite($handler, "    } else {\r\n");
fwrite($handler, "      var thisFilterArr = filterArr[i].split(operand);\r\n");
fwrite($handler, "      var thisFilter = thisFilterArr[0].toLowerCase();\r\n");
fwrite($handler, "      var thisValue = thisFilterArr[1];\r\n");
fwrite($handler, "    }\r\n");
fwrite($handler, "    if(thisValue == \"\") continue;\r\n");
fwrite($handler, "    switch(thisFilter) {\r\n");
for ($i = 0; $i < count($properties); ++$i) {
  $property = $properties[$i];
  fwrite($handler, "      case \"" . strtolower($property) . "\":\r\n");
  if($propertyTypes[$i] == "string") {
    fwrite($handler, "        var propertyValue = Card" . $property . "(cardID);\r\n");
    fwrite($handler, "        if(propertyValue == null || !propertyValue.toLowerCase().includes(thisValue.toLowerCase())) return true;\r\n");
  } else if($propertyTypes[$i] == "number") {
    fwrite($handler, "        if(operand == '=' && Card" . $property . "(cardID) != parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == ':' && Card" . $property . "(cardID) != parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '>' && Card" . $property . "(cardID) <= parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '<' && Card" . $property . "(cardID) >= parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '>=' && Card" . $property . "(cardID) < parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '<=' && Card" . $property . "(cardID) > parseInt(thisValue)) return true;\r\n");
  }
  fwrite($handler, "        break;\r\n");
}
fwrite($handler, "      case \"specificcards\":\r\n");
fwrite($handler, "        var cardArr = thisValue.split(',');\r\n");
fwrite($handler, "        if(cardArr.indexOf(cardID) === -1) return true;\r\n");
fwrite($handler, "        break;\r\n");
fwrite($handler, "      default: break;\r\n");
fwrite($handler, "    }\r\n");
fwrite($handler, "  }\r\n");
fwrite($handler, "  return false;\r\n");
fwrite($handler, "}\r\n\r\n");
//Add a JSON client lookup dictionary for properties
fwrite($handler, "var propertyLookup = [\r\n");
for ($i = 0; $i < count($properties); ++$i) {
  $property = $properties[$i];
  fwrite($handler, "  { \"Name\": \"" . $property . "\", \"Type\": \"" . $propertyTypes[$i] . "\" }");
  if($i < count($properties) - 1) fwrite($handler, ",");
  fwrite($handler, "\r\n");
}
fwrite($handler, "];\r\n\r\n");
fclose($handler);

function GetPropertyValue($card, $property)
{
  global $rootName;
  switch($rootName) {
    case "SWUDeck":
      switch($property) {
        case "rarity":
          return $card->rarity->data->attributes->character;
        case "type":
          $definedType = $card->type->data->attributes->name;
          if($definedType == "Token Unit") $definedType = "Unit";
          else if($definedType == "Token Upgrade") $definedType = "Upgrade";
          return $definedType;
        case "arena":
          $arenas = "";
          for($j = 0; $j < count($card->arenas->data); ++$j)
          {
            if($arenas != "") $arenas .= ",";
            $arenas .= $card->arenas->data[$j]->attributes->name;
          }
          return $arenas;
        case "trait":
          $traits = "";
          for($j = 0; $j < count($card->traits->data); ++$j)
          {
            if($traits != "") $traits .= ",";
            $traits .= $card->traits->data[$j]->attributes->name;
          }
          return $traits;
        case "aspect":
          $aspects = "";
          for($j = 0; $j < count($card->aspects->data); ++$j)
          {
            if($aspects != "") $aspects .= ",";
            $aspects .= $card->aspects->data[$j]->attributes->name;
          }
          for($j = 0; $j < count($card->aspectDuplicates->data); ++$j)
          {
            if($aspects != "") $aspects .= ",";
            $aspects .= $card->aspectDuplicates->data[$j]->attributes->name;
          }
          return $aspects;
        case "set":
          return $card->expansion->data->attributes->code;
        default: return $card->$property;
      }
    case "SoulMastersDB": case "SoulMastersSim":
      switch($property) {
        case "Attack":
          return isset($card->$property) ? intval($card->$property) : -1;
        case "Health":
          return isset($card->$property) ? intval($card->$property) : -1;
        default: return isset($card->$property) ? $card->$property : "";
        }
    default: return isset($card->$property) ? $card->$property : "";
  }
}

?>
