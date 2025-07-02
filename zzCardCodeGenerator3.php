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
  $count = 0;
  $currentPage = 1;
  $hasMoreData = true;

  while($hasMoreData) {
    $curl = curl_init();
    $headers = array(
      "Content-Type: application/json",
    );
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $jsonUrl . ($paginationUrlParameter != "" ? "&" . $paginationUrlParameter . "=" . $currentPage : ""));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $apiData = curl_exec($curl);
    curl_close($curl);


    $response = json_decode($apiData);

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
      } else if($rootName == "SoulMasters" || $rootName == "SoulMastersDB") {
        $cardID = $card->Number;
      } else if($rootName == "SWUDeck") {
        $card = $card->attributes;
        $cardID = $card->cardUid;
        switch($card->cardUid) {
          case "3463348370"://Battle droid
            $cardID = "TWI_T01";
            break;
          case "3941784506"://Clone Trooper
            $cardID = "TWI_T02";
            break;
        }
      }
      $card->id = $cardID;
      $cardArray[] = $card;

      $thisImageUrl = $imageUrl . $cardID . "." . $imageFormat;
      if($rootName == "SWUDeck") {
        $thisImageUrl = $card->artFront->data->attributes->formats->card->url;
      } else if($rootName == "SoulMastersDB" || $rootName == "SoulMasters") {
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

  $tries = [];
  foreach ($properties as $property) {
    $tries[$property] = [];
  }
  if($rootName == "SWUDeck") {
    $tries["uuidLookup"] = [];
    $tries["cardIdLookup"] = [];
  }
  $allCardIds = [];
  for ($i = 0; $i < count($cardArray); ++$i) {
    $card = $cardArray[$i];
    foreach ($properties as $property) {
      $value = GetPropertyValue($card, $property);
      AddToTrie($tries[$property], $card->id, 0, $value);
    }
    if($rootName == "SWUDeck") {
      $cardNumber = $card->cardNumber;
      if($cardNumber < 10) $cardNumber = "00" . $cardNumber;
      else if($cardNumber < 100) $cardNumber = "0" . $cardNumber;
      $set = $card->expansion->data->attributes->code;
      $cardID= $set . "_" . $cardNumber;
      AddToTrie($tries["uuidLookup"], $cardID, 0, $card->id);
      AddToTrie($tries["cardIdLookup"], $card->id, 0, $cardID);
    }
    $allCardIds[] = $card->id;
  }

  $directory = "./" . $rootName . "/GeneratedCode";
  if(!is_dir($directory)) mkdir($directory, 777, true);

  $generateFilename = $directory . "/GeneratedCardDictionaries.php";
  $handler = fopen($generateFilename, "w");
  fwrite($handler, "<?php\r\n");
  for($i=0; $i<count($properties); ++$i) {
    $property = $properties[$i];
    $propertyType = $propertyTypes[$i];
    $defaultValue = "";
    $dataType = 0;
    GetTypeData($propertyType, $defaultValue, $dataType);
    GenerateFunction($tries[$property], $handler, "Card" . ucwords($property), $propertyType == "string", $defaultValue, $dataType, "PHP");
  }
  if($rootName == "SWUDeck") {
    GenerateFunction($tries["uuidLookup"], $handler, "UUIDLookup", true, "", 0, "PHP");
    GenerateFunction($tries["cardIdLookup"], $handler, "CardIDLookup", true, "", 0, "PHP");
  }
  fwrite($handler, "function GetAllCardIds() {\r\n");
  fwrite($handler, "  return " . var_export($allCardIds, true) . ";\r\n");
  fwrite($handler, "}\r\n\r\n");
  fwrite($handler, "?>");
  fclose($handler);

  $generateFilename = $directory . "/GeneratedCardDictionaries.js";
  $handler = fopen($generateFilename, "w");
  for($i=0; $i<count($properties); ++$i) {
    $property = $properties[$i];
    $propertyType = $propertyTypes[$i];
    $defaultValue = "";
    $dataType = 0;
    GetTypeData($propertyType, $defaultValue, $dataType);
    GenerateFunction($tries[$property], $handler, "Card" . $property, $propertyType == "string", $defaultValue, $dataType, "js");
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
  for($i=0; $i<count($properties); ++$i) {
    $property = $properties[$i];
    fwrite($handler, "      case \"" . strtolower($property) . "\":\r\n");
    if($propertyTypes[$i] == "string") {
      fwrite($handler, "        if(!Card" . $property . "(cardID).toLowerCase().includes(thisValue.toLowerCase())) return true;\r\n");
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
  for($i=0; $i<count($properties); ++$i) {
    fwrite($handler, "  { \"Name\": \"" . $properties[$i] . "\", \"Type\": \"" . $propertyTypes[$i] . "\" }");
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
          default: return $card->$property;
        }
      case "SoulMastersDB": case "SoulMasters":
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

  function GenerateFunction($cardArray, $handler, $functionName, $isString, $defaultValue, $dataType = 0, $language = "PHP")
  {
    if($language == "PHP") fwrite($handler, "function " . $functionName . "(\$cardID) {\r\n");
    else if($language = "js") fwrite($handler, "function " . $functionName . "(cardID) {\r\n");
    TraverseTrie($cardArray, "", $handler, $isString, $defaultValue, $dataType, $language);
    fwrite($handler, "}\r\n\r\n");
  }

  function GetTypeData($propertyType, &$defaultValue, &$dataType)
  {
    switch($propertyType) {
      case "number":
        $defaultValue = 0;
        $dataType = 0;
        break;
      case "boolean":
        $defaultValue = false;
        $dataType = 1;
        break;
      default:
        $defaultValue = "";
        $dataType = 0;
        break;
    }
  }



?>
