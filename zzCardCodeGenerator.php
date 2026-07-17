<?php

set_time_limit(10800);

include './zzImageConverter.php';
include './Core/Trie.php';
include "./Core/HTTPLibraries.php";
include_once "./AccountFiles/AccountSessionAPI.php";
include_once "./Database/ConnectionManager.php";
include_once "./CardEditor/Database/CardAbilityDB.php";

// zzImageConverter.php (included above) already enforces auth for HTTP requests.
// zzCardCodeGenerator is only reachable via HTTP, so enforce auth here too.
// CLI invocations (e.g. from MCP server) do not go through this file directly.
$response = new stdClass();
$error = CheckLoggedInUserMod();
if($error !== "") {
  $response->error = $error;
  echo json_encode($response);
  exit();
}

// Sets whose canonical card ID uses 2-digit zero-padding ("TS26_34") instead of the
// standard 3-digit padding ("SOR_034"), matching the convention other SWU deckbuilders
// use for this set. Keep in sync with SWUDeck/Custom/CardIdentifiers.php's $doubleDigitsSets.
const CardIDDoubleDigitSets = ['TS26'];

$startTime = microtime(true);
ob_implicit_flush(true);
function logLine($msg) {
  global $startTime;
  $elapsed = round(microtime(true) - $startTime, 2);
  $mem = round(memory_get_usage(true) / 1048576, 1);
  echo "[{$elapsed}s | {$mem}MB] " . $msg . "<BR>\n";
  if(ob_get_level() > 0) ob_flush();
  flush();
}

$rootName = TryGET("rootName", "");

// Optional override for card database path (useful when multiple roots share card data)
$cardDBOverride = TryGET("CardDBOverride", "");
// When withPreview=1, fetch fresh data from the external API (use when previewing new cards).
// Default (withPreview omitted) skips the API and rebuilds dictionaries from the saved cache.
$withPreview = (TryGET("withPreview", "") === "1" || TryGET("withPreview", "") === "true");
// When overwriteImages=1, delete and re-download all images (webp, concat, crop) even if they exist.
$overwriteImages = (TryGET("overwriteImages", "") === "1");
logLine("=== Generator starting: rootName=" . $rootName . " | PHP " . PHP_VERSION . " | memory_limit=" . ini_get('memory_limit') . " | max_exec_time=" . ini_get('max_execution_time') . "s ===");

$schemaFile = "./Schemas/" . $rootName . "/ImportSchema.txt";
$handler = fopen($schemaFile, "r");
$jsonUrl= trim(fgets($handler));
$imageUrl= trim(fgets($handler));
$imageFormat = trim(fgets($handler));
$cardArrayJson = trim(fgets($handler));
$paginationUrlParameter = trim(fgets($handler));
$paginationResponseMetadata = trim(fgets($handler));
$properties = explode(",", fgets($handler));
$keywordsFile = trim(fgets($handler));
$importOptions = [];
while(($line = fgets($handler)) !== false) {
  $line = trim($line);
  if($line == "" || substr($line, 0, 1) == "#") continue;
  $optionParts = explode("=", $line, 2);
  if(count($optionParts) == 2) {
    $optionKey = trim($optionParts[0]);
    $optionValue = trim($optionParts[1]);
    if(isset($importOptions[$optionKey])) {
      if(is_array($importOptions[$optionKey])) {
        $importOptions[$optionKey][] = $optionValue;
      } else {
        $importOptions[$optionKey] = [$importOptions[$optionKey], $optionValue];
      }
    } else {
      $importOptions[$optionKey] = $optionValue;
    }
  }
}
$propertyTypes = [];
fclose($handler);
$nestedCardPaths = isset($importOptions["nestedCardPaths"]) && !is_array($importOptions["nestedCardPaths"]) && $importOptions["nestedCardPaths"] != "" ? array_map("trim", explode(",", $importOptions["nestedCardPaths"])) : [];
$supplementalCardSources = ImportOptionList($importOptions, "cardEditorSupplement");

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

$cacheFile = "./$rootName/GeneratedCode/cardArrayCache.json";

if($rootName == "SWUDeck" || $rootName == "SWUSim") {
  $validSets = [
    "SOR", "SHD", "TWI", // blank rotation
    "JTL", "LOF", "IBH", "SEC", // rotation A
    "LAW", "ASH", // rotation B
    "TS26", // supplemental
  ];
}

$cardArray = [];
$duplicateMap = [];
$reprintMap = [];
$otherOrientationMap = [];
$count = 0;
$tokenCountersPhase1 = []; // SET → count, used to assign SET_T## IDs during Phase 1
$tokenTypesPhase1 = ['Token Unit', 'Token Upgrade', 'Force Token', 'Credit Token'];

if(!$withPreview && file_exists($cacheFile)) {
  logLine("=== Phase 1: Loading card array from cache (use withPreview=1 to fetch from API) ===");
  $cacheData = json_decode(file_get_contents($cacheFile), false);
  $cardArray = $cacheData->cardArray;
  $reprintMap = (array)($cacheData->reprintMap);
  $count = count($cardArray);
  logLine("=== Phase 1 complete: loaded " . $count . " cards from cache ===");
} else {
  if(!$withPreview) logLine("WARNING: No cache found at $cacheFile — falling back to live fetch. Run with withPreview=1 to populate the cache.");
  $currentPage = 1;
  $hasMoreData = true;
  $totalSkipped = 0;

  logLine("=== Phase 1: Fetching card data from API ===");
  while($hasMoreData) {
  $curl = curl_init();
  $headers = array(
    "Content-Type: application/json",
  );
  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
  $urlWithParams = $jsonUrl . ($paginationUrlParameter != "" ? "&" . $paginationUrlParameter . "=" . $currentPage : "");
  logLine("Fetching page " . $currentPage . ": " . $urlWithParams);
  curl_setopt($curl, CURLOPT_URL, $urlWithParams);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $curlStart = microtime(true);
  $apiData = curl_exec($curl);
  $curlMs = round((microtime(true) - $curlStart) * 1000);
  curl_close($curl);

  // Remove BOM if present
  if (substr($apiData, 0, 3) === "\xEF\xBB\xBF") {
      $apiData = substr($apiData, 3);
  }
  $responseBytes = strlen($apiData);
  $response = json_decode($apiData);
  unset($apiData); // free the raw page string; the decoded $response is all we need below
  if(!$response) {
    logLine("ERROR: Failed to decode JSON on page " . $currentPage . " (" . $curlMs . "ms, " . round($responseBytes/1024, 1) . "KB). Aborting.");
    break;
  }
  logLine("Page " . $currentPage . " fetched in " . $curlMs . "ms (" . round($responseBytes/1024, 1) . "KB)");

  if($cardArrayJson == "") {
    $cardArrayJson = "Data";
    $responseCopy = $response;
    $response = new stdClass();
    $response->$cardArrayJson = $responseCopy;
  }
  $pageCardCount = count($response->$cardArrayJson);
  $pageSkipped = 0;
  logLine("Parsing " . $pageCardCount . " raw records from page " . $currentPage);

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
      if(!in_array($setCode, $validSets)) {
        $pageSkipped++; $totalSkipped++;
        continue;
      }

      $nameHash = hash('sha256', $card->title . $card->subtitle);
      if(!isset($duplicateMap[$nameHash])) {
        $duplicateMap[$nameHash] = true;
        $reprintMap[$cardID] = true;
      }

      $definedType = $card->type->data->attributes->name;
      if($definedType == "Token Unit" || $definedType == "Token Upgrade" || $definedType == "Force Token") {
        $pageSkipped++; $totalSkipped++;
        continue;
      }
    } else if($rootName == "RBDeck") {
      $cardID = $card->id;
      $cardID = explode("/", $cardID)[0];
      if (substr($cardID, -2) === '-P' || substr($cardID, -5) === '-STAR' || substr($cardID, -1) === 'A') continue;
    } else if($rootName == "GudnakSim") {
      $cardID = $card->number;
    } else if($rootName == "GrandArchiveSim") {
      $cardID = $card->uuid;
    } else if($rootName == "AzukiSim") {
      $cardID = $card->id;
    } else if($rootName == "SWUSim") {
      // Official SWU API (admin.starwarsunlimited.com) — Strapi format.
      // Unwrap .attributes if present (Strapi v4 compat), otherwise use card directly (v5).
      if(isset($card->attributes) && !isset($card->title)) {
        $card = $card->attributes;
      }
      $setObj = $card->set ?? null;
      if(is_object($setObj)) {
        $setCode = $setObj->abbreviation
          ?? $setObj->code
          ?? $setObj->data->attributes->abbreviation
          ?? $setObj->data->attributes->code
          ?? '';
      } else {
        $setCode = (string)($setObj ?? '');
      }
      // Official SWU API uses expansion.data.attributes.code (same shape as SWUDeck)
      if($setCode === '') {
        $setCode = $card->expansion->data->attributes->abbreviation
          ?? $card->expansion->data->attributes->code
          ?? '';
      }
      $cardNum = intval($card->cardNumber ?? 0);
      $numPadWidth = in_array($setCode, CardIDDoubleDigitSets, true) ? 2 : 3;
      $cardID = $setCode . "_" . str_pad($cardNum, $numPadWidth, '0', STR_PAD_LEFT);
      if(!in_array($setCode, $validSets)) {
        $pageSkipped++; $totalSkipped++;
        continue;
      }
      // Re-ID token cards to SET_T## before CheckImage so image files get the right name.
      $typeName = SWURelAttr($card->type ?? null, 'name') ?? '';
      if(in_array($typeName, $tokenTypesPhase1)) {
        $serialCode = $card->serialCode ?? '';
        if(preg_match('/[Tt]0*(\d+)$/', $serialCode, $m)) {
          $cardID = $setCode . "_T" . str_pad(intval($m[1]), 2, '0', STR_PAD_LEFT);
        } else {
          $tokenCountersPhase1[$setCode] = ($tokenCountersPhase1[$setCode] ?? 0) + 1;
          $cardID = $setCode . "_T" . str_pad($tokenCountersPhase1[$setCode], 2, '0', STR_PAD_LEFT);
        }
      }
    }
    $card->id = $cardID;
    $cardArray[] = $card;

    $thisImageUrl = $imageUrl . $cardID . "." . $imageFormat;
    $thisBackImageUrl = null;
    $squareCards = false;
    if($rootName == "SWUDeck") {
      $thisImageUrl = $card->artFront->data->attributes->formats->card->url;
      $artBack = $card->artBack ?? null;
      if($artBack && isset($artBack->data->attributes->formats->card->url)) {
        $thisBackImageUrl = $artBack->data->attributes->formats->card->url;
      }
    } else if($rootName == "SWUSim") {
      // Fetch URL uses UUID (artFront CDN URL or documentId); saved filename uses SET_NNN ($cardID).
      $artFront = $card->artFront ?? null;
      if($artFront && isset($artFront->formats->card->url)) {
        // Strapi v5 flat shape
        $thisImageUrl = $artFront->formats->card->url;
      } else if($artFront && isset($artFront->data->attributes->formats->card->url)) {
        // Strapi v4 nested shape
        $thisImageUrl = $artFront->data->attributes->formats->card->url;
      } else {
        $docId = $card->documentId ?? null;
        $thisImageUrl = $docId ? ($imageUrl . $docId . "." . $imageFormat) : null;
      }
      // Leaders have a back side (unit side) — download it as SET_NNN_back.
      $artBack = $card->artBack ?? null;
      if($artBack && isset($artBack->formats->card->url)) {
        $thisBackImageUrl = $artBack->formats->card->url;
      } else if($artBack && isset($artBack->data->attributes->formats->card->url)) {
        $thisBackImageUrl = $artBack->data->attributes->formats->card->url;
      }
    } else if($rootName == "AzukiSim") {
      $thisImageUrl = $card->image;
    } else if($rootName == "SoulMastersDB" || $rootName == "SoulMastersSim") {
      $thisImageUrl = $imageUrl . $cardID . "-CYMK.jpg";
    } else if($rootName == "GudnakSim") {
      $thisImageUrl = $imageUrl . $card->number . ".jpg";
      $squareCards = true;
    } else if($rootName == "GrandArchiveSim") {
      $imageID = GetGrandArchiveImageId($card);
      $thisImageUrl = $imageUrl . $imageID . "." . $imageFormat;
      if($cardID == "bEXmm4rKOs") { // Grand Archive promo card with unique image
        $thisImageUrl = $imageUrl . "hJb7hcK4Fd" . "." . $imageFormat;
      }
    }
    $cardType = "";
    if($rootName == "SWUDeck") {
      $cardType = SWURelAttr($card->type ?? null, 'name') ?? "";
    } else if($rootName == "SWUSim") {
      $cardType = GetPropertyValue($card, 'type');
    }
    if($thisImageUrl !== null) {
      CheckImage($cardID, $thisImageUrl, $cardType, "", rootPath:"./" . $rootName . "/", squareCards:$squareCards, overwriteImages:$overwriteImages);
    } else {
      logLine("WARNING: No image URL for $cardID — skipping download.");
    }
    if($thisBackImageUrl !== null) {
      // A leader's back is normally its deployed UNIT side (landscape source → CheckImage rotates it to
      // portrait as "LeaderUnit"). EXCEPTION: a double-leader-face FLIP card (e.g. TWI_017 "Flipatine")
      // has no unit side — its back is another LEADER face that renders horizontally in the leader slot,
      // so treat it as "Leader" (kept horizontal). Detected generically: a Leader with no unit-side stats
      // (empty cost/power/hp).
      $power = GetPropertyValue($card, 'power');
      $hp    = GetPropertyValue($card, 'hp');
      $isFlipLeader = ($cardType === 'Leader')
          && ($power === null || $power === '') && ($hp === null || $hp === '');
      $backType = $isFlipLeader ? "Leader" : "LeaderUnit";
      CheckImage($cardID . "_back", $thisBackImageUrl, $backType, "", rootPath:"./" . $rootName . "/", squareCards:$squareCards, overwriteImages:$overwriteImages);
    }

    // Drop heavy API fields that NO later phase reads (verified: 0 references), so the
    // retained $cardArray — and the cache written from it — stay small. Without this the
    // decoded array peaks ~700MB and blows the box's PHP memory_limit. `variants` (alternate
    // printings) alone is ~70% of card size; `localizations` (other languages), reprint,
    // and art/thumbnail metadata make up most of the rest. The art URLs were the only thing
    // we needed from the media fields, and they were already consumed by CheckImage above.
    unset(
      $card->variants, $card->localizations, $card->reprints, $card->reprintOf,
      $card->artFront, $card->artBack, $card->artThumbnail,
      $card->variantTypes, $card->linkHtml
    );

    ++$count;
  }
  if($paginationUrlParameter != "") {
    if($rootName == "SWUDeck" || $rootName == "SWUSim") {
      $pageCount = $response->meta->pagination->pageCount;
    }
    $pageLabel = isset($pageCount) ? $currentPage . "/" . $pageCount : (string)$currentPage;
    logLine("Page " . $pageLabel . " done: " . ($pageCardCount - $pageSkipped) . " accepted, " . $pageSkipped . " skipped (running totals: " . $count . " accepted, " . $totalSkipped . " skipped)");
    $currentPage++;

    // Check for more data based on response metadata
    if($rootName == "SWUDeck" || $rootName == "SWUSim") {
      $hasMoreData = $currentPage <= $pageCount;
    } else if($paginationResponseMetadata != "") {
      // Navigate to the metadata field in the response
      $hasMoreData = GetResponseMetadata($response, $paginationResponseMetadata);
    } else {
      $hasMoreData = false;
    }
  } else {
    $hasMoreData = false;
  }
}

  $cacheJson = json_encode(['cardArray' => $cardArray, 'reprintMap' => $reprintMap]);
  file_put_contents($cacheFile, $cacheJson);
  logLine("=== Phase 1 complete: " . $count . " cards accepted, " . $totalSkipped . " skipped across " . ($currentPage - 1) . " pages — cache saved (" . round(strlen($cacheJson)/1024, 1) . "KB) ===");
}
$nestedCardCount = ExpandNestedCards($cardArray, $nestedCardPaths, $otherOrientationMap, $imageUrl, $imageFormat);
if($nestedCardCount > 0) {
  $count += $nestedCardCount;
  logLine("Expanded " . $nestedCardCount . " nested cards from ImportSchema nestedCardPaths.");
}

$supplementalCardCount = AppendSupplementalImportCards($cardArray, $supplementalCardSources);
if($supplementalCardCount > 0) {
  $count += $supplementalCardCount;
  logLine("Appended " . $supplementalCardCount . " supplemental cards from ImportSchema cardEditorSupplement sources.");
}

// Phase 1b (SWUSim only): migrate stale token image files that were downloaded under the
// wrong SET_NNN name (before Phase 1 got the early re-ID logic). For each token in the
// card array, if WebpImages/SET_NNN.webp exists but WebpImages/SET_T##.webp does not,
// rename it so the token art lands at the correct path and the real card can be
// downloaded fresh by the next generator run.
if($rootName == "SWUSim") {
  $tokenCountersPhase1b = [];
  $tokenMigrateCount = 0;
  $tokenTypes1b = ['Token Unit', 'Token Upgrade', 'Force Token', 'Credit Token'];
  for($i = 0; $i < count($cardArray); ++$i) {
    $typeName = SWURelAttr($cardArray[$i]->type ?? null, 'name') ?? '';
    if(!in_array($typeName, $tokenTypes1b)) continue;
    $correctId = $cardArray[$i]->id ?? ''; // already SET_T## from Phase 1
    if(!preg_match('/^[A-Z0-9]+_T\d+$/', $correctId)) continue;
    $setCode = strstr($correctId, '_', true); // "SOR"
    if(!$setCode) continue;
    // Reconstruct what the old wrong ID would have been.
    $serialCode = $cardArray[$i]->serialCode ?? '';
    if(preg_match('/[Tt]0*(\d+)$/', $serialCode, $m)) {
      $oldId = $setCode . "_" . str_pad(intval($m[1]), 3, '0', STR_PAD_LEFT);
    } else {
      $tokenCountersPhase1b[$setCode] = ($tokenCountersPhase1b[$setCode] ?? 0) + 1;
      $oldId = $setCode . "_" . str_pad($tokenCountersPhase1b[$setCode], 3, '0', STR_PAD_LEFT);
    }
    $oldPath  = "./" . $rootName . "/WebpImages/" . $oldId . ".webp";
    $newPath  = "./" . $rootName . "/WebpImages/" . $correctId . ".webp";
    if($oldId !== $correctId && file_exists($oldPath) && !file_exists($newPath)) {
      rename($oldPath, $newPath);
      logLine("Phase 1b: renamed stale token image $oldId → $correctId");
      ++$tokenMigrateCount;
    }
  }
  if($tokenMigrateCount > 0) {
    logLine("Phase 1b: migrated " . $tokenMigrateCount . " stale token image file(s). Run generator again to re-download real card images for the freed-up IDs.");
  } else {
    logLine("Phase 1b: no stale token images to migrate.");
  }
}

logLine("=== Phase 2: Building property arrays for " . count($properties) . " properties ===");

$associativeArrays = [];
foreach ($properties as $property) {
  $associativeArrays[$property] = [];
}
if($rootName == "SWUDeck") {
  $associativeArrays["uuidLookup"] = [];
  $associativeArrays["cardIdLookup"] = [];
  $associativeArrays["setNumOffsets"] = [];
  foreach($validSets as $set) { $associativeArrays["setNumOffsets"][$set] = 1000 * (1 + array_search($set, $validSets)); }
}
if($rootName == "SWUSim") {
  // cardUUIDData: SET_NNN → documentId, used for SWUDeck/SWUStats stat reporting.
  $associativeArrays["cardUUIDData"] = [];
}
$allCardIds = [];
for ($i = 0; $i < count($cardArray); ++$i) {
  $card = $cardArray[$i];
  foreach ($properties as $property) {
    $value = GetPropertyValue($card, $property);
    // SWUSim card logic and the test suite assume ASCII punctuation (straight quotes for
    // grant-style ability detection, plain hyphens for "non-leader"/"non-Sentinel" matches,
    // "-N/-N" stat modifiers). The upstream card API now returns typographic Unicode (curly
    // quotes, en/em dashes, non-breaking hyphens). Normalise it back to ASCII so regenerating
    // does not silently break text-matching card logic. (Accented letters are left intact.)
    if ($rootName == "SWUSim") $value = NormalizeCardPunctuation($value);
    $associativeArrays[$property][$card->id] = $value;
  }
  if($rootName == "SWUDeck") {
    $cardNumber = $card->cardNumber;
    $set = $card->expansion->data->attributes->code;
    if(in_array($set, CardIDDoubleDigitSets, true)) {
      if($cardNumber < 10) $cardNumber = "0" . $cardNumber;
    } else {
      if($cardNumber < 10) $cardNumber = "00" . $cardNumber;
      else if($cardNumber < 100) $cardNumber = "0" . $cardNumber;
    }
    $cardID= $set . "_" . $cardNumber;
    $associativeArrays["uuidLookup"][$cardID] = $card->id;
    $associativeArrays["cardIdLookup"][$card->id] = $cardID;
    if(isset($reprintMap[$card->id])) $allCardIds[] = $card->id;
  } else if($rootName == "SWUSim") {
    // card->id is already SET_NNN (built during Phase 1).
    // Store documentId → SET_NNN mapping for stat reporting.
    $documentId = $card->documentId ?? $card->cardUid ?? $card->cardId ?? '';
    $associativeArrays["cardUUIDData"][$card->id] = $documentId;
    $allCardIds[] = $card->id;
  } else {
    $allCardIds[] = $card->id;
  }
}

// Process keywords file if it exists
$keywordData = [];
$keywordTypes = []; // 'boolean' or 'value'
if(!empty($keywordsFile) && file_exists($keywordsFile)) {
  logLine("=== Phase 3: Processing keywords from: " . $keywordsFile . " ===");
  $keywordsJson = file_get_contents($keywordsFile);
  $keywordsData = json_decode($keywordsJson, true);

  // First pass: identify all unique keywords with applicability="self" and determine their types
  $keywordValueCheck = []; // Track if any card has a value for each keyword
  foreach($keywordsData as $cardId => $cardData) {
    if(!isset($cardData['keywords'])) continue;
    foreach($cardData['keywords'] as $kw) {
      if($kw['applicability'] !== 'self') continue;
      $keywordName = $kw['keyword'];

      if(!isset($keywordValueCheck[$keywordName])) {
        $keywordValueCheck[$keywordName] = false;
      }
      if($kw['value'] !== null) {
        $keywordValueCheck[$keywordName] = true;
      }
    }
  }

  // Determine keyword types
  foreach($keywordValueCheck as $keywordName => $hasValue) {
    $keywordTypes[$keywordName] = $hasValue ? 'value' : 'boolean';
    $keywordData[$keywordName] = [];
  }

  // Second pass: populate keyword data arrays
  foreach($keywordsData as $cardId => $cardData) {
    if(!isset($cardData['keywords'])) continue;
    foreach($cardData['keywords'] as $kw) {
      if($kw['applicability'] !== 'self') continue;
      $keywordName = $kw['keyword'];

      if($keywordTypes[$keywordName] === 'boolean') {
        $keywordData[$keywordName][$cardId] = true;
      } else {
        // For value keywords, store the numeric value (default to 1 if null)
        $keywordData[$keywordName][$cardId] = $kw['value'] !== null ? intval($kw['value']) : 1;
      }
    }
  }

  logLine("Processed " . count($keywordTypes) . " unique keywords");
}

logLine("=== Phase 4: Initializing card abilities database ===");
// Populate card abilities database with card IDs from this root
// Only inserts cards that don't already have entries (preserves existing custom code)
// Uses CardDBOverride if provided, otherwise uses rootName for the database root
try {
  $conn = GetLocalMySQLConnection();
  $cardAbilityDB = new CardAbilityDB($conn);

  // Determine which root to use for the card abilities database
  $databaseRoot = !empty($cardDBOverride) ? $cardDBOverride : $rootName;

  $existingCount = 0;

  foreach($allCardIds as $cardId) {
    // Check if this card already has abilities in the database (using the appropriate database root)
    if(!$cardAbilityDB->cardHasAbilities($databaseRoot, $cardId)) {
      // Create placeholder entry for this card so it shows up in the editor
      // Placeholder has empty macro_name and ability_code, ready to be filled in via CardEditor
      $cardAbilityDB->saveAbility(null, $databaseRoot, $cardId, "", "", null);
    }
    $existingCount++;
  }

  mysqli_close($conn);
  logLine("Card abilities database initialized for $databaseRoot. " . count($allCardIds) . " total cards available for editing.");

} catch (Exception $e) {
  logLine("WARNING: Could not initialize card abilities database: " . $e->getMessage());
}

$directory = "./" . $rootName . "/GeneratedCode";
if(!is_dir($directory)) mkdir($directory, 777, true);
logLine("=== Phase 5: Writing GeneratedCardDictionaries.php ===");$generateFilename = $directory . "/GeneratedCardDictionaries.php";
$handler = fopen($generateFilename, "w");
fwrite($handler, "<?php\r\n");
foreach ($properties as $property) {
  $arr = $associativeArrays[$property];
  // For SWUSim: strip null and empty-string entries from every property except 'text'
  // so lookups return null (absent) rather than a -1 sentinel or empty string.
  // 'text' is always written in full so CardText() always returns a string.
  if($rootName === "SWUSim" && $property !== 'text') {
    $arr = array_filter($arr, fn($v) => $v !== null && $v !== '');
  }
  fwrite($handler, "  \$" . $property . "Data = " . var_export($arr, true) . ";\r\n");
  fwrite($handler, "function Card" . ucwords($property) . "(\$cardID) {\r\n");
  fwrite($handler, "  global \$" . $property . "Data;\r\n");
  fwrite($handler, "  return isset(\$" . $property . "Data[\$cardID]) ? \$" . $property . "Data[\$cardID] : null;\r\n");
  fwrite($handler, "}\r\n\r\n");
}

// Generate keyword functions
foreach($keywordTypes as $keywordName => $type) {
  $functionName = $type === 'boolean' ? "CardHas" . $keywordName : "Card" . $keywordName . "Amount";
  fwrite($handler, "  \$" . $keywordName . "Data = " . var_export($keywordData[$keywordName], true) . ";\r\n");
  fwrite($handler, "function " . $functionName . "(\$cardID) {\r\n");
  fwrite($handler, "  global \$" . $keywordName . "Data;\r\n");
  if($type === 'boolean') {
    fwrite($handler, "  return isset(\$" . $keywordName . "Data[\$cardID]) ? \$" . $keywordName . "Data[\$cardID] : false;\r\n");
  } else {
    fwrite($handler, "  return isset(\$" . $keywordName . "Data[\$cardID]) ? \$" . $keywordName . "Data[\$cardID] : 0;\r\n");
  }
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
if($rootName == "SWUSim") {
  // cardUUIDData: SET_NNN → documentId, for SWUDeck/SWUStats stat reporting.
  $uuidArr = array_filter($associativeArrays["cardUUIDData"], fn($v) => $v !== '' && $v !== null);
  fwrite($handler, "  \$cardUUIDData = " . var_export($uuidArr, true) . ";\r\n");
  fwrite($handler, "function GetCardUUID(\$cardID) {\r\n");
  fwrite($handler, "  global \$cardUUIDData;\r\n");
  fwrite($handler, "  return \$cardUUIDData[\$cardID] ?? null;\r\n");
  fwrite($handler, "}\r\n\r\n");
  // IsSWUCardID uses $nameData (title array) as the validity set.
  fwrite($handler, "function IsSWUCardID(\$cardID) {\r\n");
  fwrite($handler, "  global \$titleData;\r\n");
  fwrite($handler, "  return is_array(\$titleData) && array_key_exists(\$cardID, \$titleData);\r\n");
  fwrite($handler, "}\r\n\r\n");
  // BuildCardID / DecomposeCardID helpers for constructing/destructuring SET_NNN keys.
  fwrite($handler, "function BuildCardID(\$setAbbreviation, \$cardNumber) {\r\n");
  fwrite($handler, "  \$setAbbreviation = strtoupper(trim(\$setAbbreviation));\r\n");
  fwrite($handler, "  \$padWidth = in_array(\$setAbbreviation, " . var_export(CardIDDoubleDigitSets, true) . ", true) ? 2 : 3;\r\n");
  fwrite($handler, "  return \$setAbbreviation . '_' . str_pad(intval(\$cardNumber), \$padWidth, '0', STR_PAD_LEFT);\r\n");
  fwrite($handler, "}\r\n\r\n");
  fwrite($handler, "function DecomposeCardID(\$cardID) {\r\n");
  fwrite($handler, "  if(!preg_match('/^([A-Z0-9]{2,5})_(T?\\\\d{1,4})\$/', \$cardID, \$m)) return null;\r\n");
  fwrite($handler, "  return ['set' => \$m[1], 'number' => \$m[2], 'isToken' => (strpos(\$m[2], 'T') === 0)];\r\n");
  fwrite($handler, "}\r\n\r\n");
  // leaderCanDeployAsUpgradeData: leaders whose Epic Action offers the Pilot-leader deploy mode
  // (e.g. JTL_001). Derived by exact-substring match on card text.
  $deployUpgradePhrase = "Deploy this leader as an upgrade on a friendly Vehicle unit without a Pilot on it.";
  $leaderCanDeployAsUpgradeData = [];
  foreach($allCardIds as $cardId) {
    $text = $associativeArrays["text"][$cardId] ?? "";
    if(strpos($text, $deployUpgradePhrase) !== false) {
      $leaderCanDeployAsUpgradeData[$cardId] = true;
    }
  }
  fwrite($handler, "  \$leaderCanDeployAsUpgradeData = " . var_export($leaderCanDeployAsUpgradeData, true) . ";\r\n");
  fwrite($handler, "function CardLeaderCanDeployAsUpgrade(\$cardID) {\r\n");
  fwrite($handler, "  global \$leaderCanDeployAsUpgradeData;\r\n");
  fwrite($handler, "  return isset(\$leaderCanDeployAsUpgradeData[\$cardID]) ? \$leaderCanDeployAsUpgradeData[\$cardID] : false;\r\n");
  fwrite($handler, "}\r\n\r\n");
  // ── Piloting data, parsed from the epicAction text ("Piloting [N resources <aspects>] …").
  // Pilots store their upgrade-side text in epicAction; this is the only place the
  // Piloting cost Y and its aspect set live.
  // epicAction is not in the $properties list (we don't need it in the JS bundle), so build
  // the lookup directly from $cardArray here.
  $epicActionLookup = [];
  foreach($cardArray as $card) {
    $epicActionLookup[$card->id] = NormalizeCardPunctuation($card->epicAction ?? '');
  }
  $pilotingCostData         = [];
  $pilotingAspectData       = [];
  $pilotAddsCapacityData    = [];
  $pilotIgnoresOccupiedData = [];
  $aspectWords = ['Vigilance','Command','Aggression','Cunning','Heroism','Villainy'];
  foreach($allCardIds as $cardId) {
    $epic = $epicActionLookup[$cardId] ?? '';
    if($epic === '' || stripos($epic, 'Piloting') === false) continue;
    // "Piloting [3 resources Vigilance Villainy] …" — capture the bracket body.
    if(preg_match('/Piloting\s*\[([^\]]*)\]/i', $epic, $m)) {
      $body = $m[1];
      if(preg_match('/^\s*(\d+)/', $body, $cm)) {
        $pilotingCostData[$cardId] = intval($cm[1]);
      }
      $aspects = [];
      foreach($aspectWords as $aw) {
        if(stripos($body, $aw) !== false) $aspects[] = $aw;
      }
      if(!empty($aspects)) $pilotingAspectData[$cardId] = $aspects;
    }
    // Capacity grant: "play or deploy 1 additional Pilot" (R2-D2, Millennium Falcon).
    if(stripos($epic, 'additional Pilot') !== false) {
      $pilotAddsCapacityData[$cardId] = true;
    }
    // Occupied override: "Vehicle unit with a Pilot on it" (R2-D2).
    if(stripos($epic, 'with a Pilot on it') !== false) {
      $pilotIgnoresOccupiedData[$cardId] = true;
    }
  }
  // Capacity grant may also appear in unit text (e.g. Millennium Falcon JTL_249, a Vehicle
  // whose "additional Pilot" ability is in the text field, not epicAction).
  foreach($allCardIds as $cardId) {
    $cardText = $associativeArrays["text"][$cardId] ?? '';
    if(stripos($cardText, 'additional Pilot') !== false) {
      $pilotAddsCapacityData[$cardId] = true;
    }
  }
  foreach([
    ['pilotingCostData',         $pilotingCostData,         'CardPilotingCost',         'null'],
    ['pilotingAspectData',       $pilotingAspectData,       'CardPilotingAspects',      'null'],
    ['pilotAddsCapacityData',    $pilotAddsCapacityData,    'CardPilotAddsCapacity',    'false'],
    ['pilotIgnoresOccupiedData', $pilotIgnoresOccupiedData, 'CardPilotIgnoresOccupied', 'false'],
  ] as [$varName, $arr, $fnName, $default]) {
    fwrite($handler, "  \$" . $varName . " = " . var_export($arr, true) . ";\r\n");
    fwrite($handler, "function " . $fnName . "(\$cardID) {\r\n");
    fwrite($handler, "  global \$" . $varName . ";\r\n");
    fwrite($handler, "  return isset(\$" . $varName . "[\$cardID]) ? \$" . $varName . "[\$cardID] : " . $default . ";\r\n");
    fwrite($handler, "}\r\n\r\n");
  }
  // ── OnAttached: manual, hand-curated (tiny pool). Fires on the attachment event,
  // distinct from WhenPlayedAsUpgrade (a play trigger).
  $onAttachedManual = ['SOR_122', 'JTL_036'];
  $onAttachedData = [];
  foreach($onAttachedManual as $oid) $onAttachedData[$oid] = true;
  fwrite($handler, "  \$onAttachedData = " . var_export($onAttachedData, true) . ";\r\n");
  fwrite($handler, "function HasOnAttachedAbility(\$cardID) {\r\n");
  fwrite($handler, "  global \$onAttachedData;\r\n");
  fwrite($handler, "  return isset(\$onAttachedData[\$cardID]) ? \$onAttachedData[\$cardID] : false;\r\n");
  fwrite($handler, "}\r\n\r\n");
}
fwrite($handler, "  \$otherOrientationData = " . var_export($otherOrientationMap, true) . ";\r\n");
fwrite($handler, "function CardOtherOrientation(\$cardID) {\r\n");
fwrite($handler, "  global \$otherOrientationData;\r\n");
fwrite($handler, "  return isset(\$otherOrientationData[\$cardID]) ? \$otherOrientationData[\$cardID] : null;\r\n");
fwrite($handler, "}\r\n\r\n");
fwrite($handler, "function GetAllCardIds() {\r\n");
fwrite($handler, "  return " . var_export($allCardIds, true) . ";\r\n");
fwrite($handler, "}\r\n\r\n");
fwrite($handler, "?>");
fclose($handler);
logLine("PHP file written: " . basename($generateFilename) . " (" . round(filesize($generateFilename)/1024, 1) . "KB)");

$fileSuffix = date("YmdHis");
$generateFilename = $directory . "/GeneratedCardDictionaries_$fileSuffix.js";
$oldFiles = glob($directory . "/GeneratedCardDictionaries*.js");
foreach ($oldFiles as $oldFile) {
  if (preg_match('/GeneratedCardDictionaries_\d+\.js$/', basename($oldFile))) {
    unlink($oldFile);
  }
}
$handler = fopen($generateFilename, "w");
logLine("=== Phase 6: Writing " . basename($generateFilename) . " ===");
foreach ($properties as $property) {
  $arr = $associativeArrays[$property];
  if($rootName === "SWUSim" && $property !== 'text') {
    $arr = array_filter($arr, fn($v) => $v !== null && $v !== '');
  }
  fwrite($handler, "var " . $property . "Data = " . json_encode($arr) . ";\r\n");
  fwrite($handler, "function Card" . $property . "(cardID) {\r\n");
  fwrite($handler, "  return " . $property . "Data[cardID] !== undefined ? " . $property . "Data[cardID] : null;\r\n");
  fwrite($handler, "}\r\n\r\n");
}
fwrite($handler, "var otherOrientationData = " . json_encode($otherOrientationMap) . ";\r\n");
fwrite($handler, "function CardOtherOrientation(cardID) {\r\n");
fwrite($handler, "  return otherOrientationData[cardID] !== undefined ? otherOrientationData[cardID] : null;\r\n");
fwrite($handler, "}\r\n\r\n");
if($rootName == "SWUDeck") {
  fwrite($handler, "var setNumOffsets = " . json_encode($associativeArrays["setNumOffsets"]) . ";\r\n");
  fwrite($handler, "function Cardsetnum(cardID) {\r\n");
  fwrite($handler, "  var set = Cardset(cardID);\r\n");
  fwrite($handler, "  if(set == null) return null;\r\n");
  fwrite($handler, "  var offset = setNumOffsets[set];\r\n");
  fwrite($handler, "  if(offset == null) return null;\r\n");
  fwrite($handler, "  return offset + CardcardNumber(cardID);\r\n");
  fwrite($handler, "}\r\n\r\n");

  fwrite($handler, "function Cardswudb(cardID) {\r\n");
  fwrite($handler, "  var _cost = String(Cardcost(cardID) ?? 0).padStart(3, '0');\r\n");
  fwrite($handler, "  switch (Cardtype(cardID)) {\r\n");
  fwrite($handler, "    case \"Unit\":\r\n");
  fwrite($handler, "      if(Cardarena(cardID) === \"Ground\") {\r\n");
  fwrite($handler, "        return \"a\" + _cost + Cardtitle(cardID);\r\n");
  fwrite($handler, "      } else if (Cardarena(cardID) === \"Space\") {\r\n");
  fwrite($handler, "        return \"b\" + _cost + Cardtitle(cardID);\r\n");
  fwrite($handler, "      }\r\n");
  fwrite($handler, "      break;\r\n");
  fwrite($handler, "    case \"Event\":\r\n");
  fwrite($handler, "      return \"c\" + _cost + Cardtitle(cardID);\r\n");
  fwrite($handler, "    case \"Upgrade\":\r\n");
  fwrite($handler, "      return \"d\" + _cost + Cardtitle(cardID);\r\n");
  fwrite($handler, "    default: return \"zzz\";\r\n");
  fwrite($handler, "  };\r\n");
  fwrite($handler, "}\r\n\r\n");
}
// Load AllSets data for ordered set filtering
$allSetsOrdered = [];
// SWU's set list lives in the shared AppCore/SWU/ dir; SoulMastersDB keeps its own per-product file.
$allSetsPath = ($rootName == "SWUDeck") ? "./AppCore/SWU/AllSets.php" : ("./" . $rootName . "/AllSets.php");
if(($rootName == "SWUDeck" || $rootName == "SoulMastersDB") && file_exists($allSetsPath)) {
  $allSetsOrdered = include($allSetsPath);
  if(!is_array($allSetsOrdered)) $allSetsOrdered = [];
}
$allSetsJson = json_encode($allSetsOrdered, JSON_FORCE_OBJECT);
fwrite($handler, "var allSetsData = " . $allSetsJson . ";\r\n");
// Build reprint set map: canonicalUUID => [reprintSetCode, ...] for ordered set filtering
$reprintSetsMap = [];
if($rootName == "SWUDeck" && file_exists("./AppCore/SWU/Overrides.php")) { // Overrides.php lives in the shared AppCore/SWU/ dir
  $overridesContent = file_get_contents("./AppCore/SWU/Overrides.php");
  $overrideMatches = [];
  preg_match_all('/case "([A-Z0-9]+_[0-9]+)":\s*return "([A-Z0-9]+_[0-9]+)"/', $overridesContent, $overrideMatches);
  for($oi = 0; $oi < count($overrideMatches[1]); $oi++) {
    $reprintSetNNN = $overrideMatches[1][$oi];
    $canonicalSetNNN = $overrideMatches[2][$oi];
    $reprintSetCode = explode("_", $reprintSetNNN)[0];
    if(!isset($allSetsOrdered[$reprintSetCode])) continue; // Skip promos/non-premier sets
    $canonicalUUID = $associativeArrays["uuidLookup"][$canonicalSetNNN] ?? null;
    if($canonicalUUID === null) continue;
    if(!isset($reprintSetsMap[$canonicalUUID])) $reprintSetsMap[$canonicalUUID] = [];
    if(!in_array($reprintSetCode, $reprintSetsMap[$canonicalUUID])) {
      $reprintSetsMap[$canonicalUUID][] = $reprintSetCode;
    }
  }
}
fwrite($handler, "var cardReprintSets = " . json_encode($reprintSetsMap) . ";\r\n");
$defaultTextFilterProperty = "title";
$lowercaseProperties = array_map('strtolower', $properties);
if(!in_array("title", $lowercaseProperties, true)) {
  if(in_array("name", $lowercaseProperties, true)) {
    $defaultTextFilterProperty = "name";
  } else {
    for($i=0; $i<count($properties); ++$i) {
      if($propertyTypes[$i] == "string") {
        $defaultTextFilterProperty = strtolower($properties[$i]);
        break;
      }
    }
  }
}
logLine("Reprint map: " . count($reprintSetsMap) . " canonical cards have reprints");
fwrite($handler, "function ShouldFilter(cardID,filter) {\r\n");
fwrite($handler, "  var filterArr = filter.match(/(?:[^\\s\"]+|\"[^\"]*\")+/g) || [];\r\n");
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
fwrite($handler, "      thisFilter = \"" . $defaultTextFilterProperty . "\";\r\n");
fwrite($handler, "      thisValue = filterArr[i];\r\n");
fwrite($handler, "    } else {\r\n");
fwrite($handler, "      var thisFilterArr = filterArr[i].split(operand);\r\n");
fwrite($handler, "      var thisFilter = thisFilterArr[0].toLowerCase();\r\n");
fwrite($handler, "      var thisValue = thisFilterArr[1];\r\n");
fwrite($handler, "    }\r\n");
fwrite($handler, "    if(thisValue && thisValue.length >= 2 && thisValue[0] === '\"' && thisValue[thisValue.length-1] === '\"') {\r\n");
fwrite($handler, "      thisValue = thisValue.slice(1, -1);\r\n");
fwrite($handler, "    }\r\n");
fwrite($handler, "    if(thisValue == \"\") continue;\r\n");
if($rootName == "SWUDeck") {
  fwrite($handler, "    var _filterAliases = {t:\"text\",p:\"power\",tr:\"trait\",up:\"upgradepower\",uhp:\"upgradehp\",r:\"rarity\",a:\"arena\",is:\"type\",unq:\"unique\"};\r\n");
  fwrite($handler, "    if(_filterAliases[thisFilter]) thisFilter = _filterAliases[thisFilter];\r\n");
}
fwrite($handler, "    switch(thisFilter) {\r\n");
for ($i = 0; $i < count($properties); ++$i) {
  $property = $properties[$i];
  fwrite($handler, "      case \"" . strtolower($property) . "\":\r\n");
  if($propertyTypes[$i] == "string") {
    if(strtolower($property) == "set" && ($rootName == "SWUDeck" || $rootName == "SoulMastersDB")) {
      fwrite($handler, "        var propertyValue = Card" . $property . "(cardID);\r\n");
      fwrite($handler, "        if(propertyValue == null) return true;\r\n");
      fwrite($handler, "        if(Object.keys(allSetsData).length === 0 || operand === '=' || operand === ':') {\r\n");
      fwrite($handler, "          if(!propertyValue.toLowerCase().includes(thisValue.toLowerCase())) return true;\r\n");
      fwrite($handler, "        } else {\r\n");
      fwrite($handler, "          var targetOrder = allSetsData[thisValue.toUpperCase()];\r\n");
      fwrite($handler, "          var cardOrder = allSetsData[propertyValue.toUpperCase()];\r\n");
      fwrite($handler, "          if(targetOrder === undefined || cardOrder === undefined) {\r\n");
      fwrite($handler, "            if(!propertyValue.toLowerCase().includes(thisValue.toLowerCase())) return true;\r\n");
      fwrite($handler, "          } else {\r\n");
      fwrite($handler, "            var _reprints = cardReprintSets[cardID];\r\n");
      fwrite($handler, "            if(_reprints) { for(var _ri=0; _ri<_reprints.length; _ri++) { var _ro=allSetsData[_reprints[_ri]]; if(_ro!==undefined) { if((operand=='>' || operand=='>=') && _ro>cardOrder) cardOrder=_ro; if((operand=='<' || operand=='<=') && _ro<cardOrder) cardOrder=_ro; } } }\r\n");
      fwrite($handler, "            if(operand == '>' && cardOrder <= targetOrder) return true;\r\n");
      fwrite($handler, "            else if(operand == '<' && cardOrder >= targetOrder) return true;\r\n");
      fwrite($handler, "            else if(operand == '>=' && cardOrder < targetOrder) return true;\r\n");
      fwrite($handler, "            else if(operand == '<=' && cardOrder > targetOrder) return true;\r\n");
      fwrite($handler, "          }\r\n");
      fwrite($handler, "        }\r\n");
    } else {
      fwrite($handler, "        var propertyValue = Card" . $property . "(cardID);\r\n");
      fwrite($handler, "        if(propertyValue == null || !propertyValue.toLowerCase().includes(thisValue.toLowerCase())) return true;\r\n");
    }
  } else if($propertyTypes[$i] == "number") {
    fwrite($handler, "        if(operand == '=' && Card" . $property . "(cardID) != parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == ':' && Card" . $property . "(cardID) != parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '>' && Card" . $property . "(cardID) <= parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '<' && Card" . $property . "(cardID) >= parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '>=' && Card" . $property . "(cardID) < parseInt(thisValue)) return true;\r\n");
    fwrite($handler, "        else if(operand == '<=' && Card" . $property . "(cardID) > parseInt(thisValue)) return true;\r\n");
  } else if($propertyTypes[$i] == "boolean") {
    fwrite($handler, "        var _bv = Card" . $property . "(cardID);\r\n");
    fwrite($handler, "        var _want = (thisValue === '1' || thisValue.toLowerCase() === 'true');\r\n");
    fwrite($handler, "        if(_want ? !_bv : !!_bv) return true;\r\n");
  }
  fwrite($handler, "        break;\r\n");
}
fwrite($handler, "      case \"specificcards\":\r\n");
fwrite($handler, "        var cardArr = thisValue.split(',');\r\n");
fwrite($handler, "        if(cardArr.indexOf(cardID) === -1) return true;\r\n");
fwrite($handler, "        break;\r\n");
if($rootName == "SWUDeck") {
  fwrite($handler, "      case \"c\":\r\n");
  fwrite($handler, "        var _aspectColorMap = {b:\"Vigilance\",g:\"Command\",r:\"Aggression\",y:\"Cunning\",w:\"Heroism\",k:\"Villainy\"};\r\n");
  fwrite($handler, "        var _pv = Cardaspect(cardID);\r\n");
  fwrite($handler, "        if(_pv == null) return true;\r\n");
  fwrite($handler, "        for(var _ci = 0; _ci < thisValue.length; ++_ci) {\r\n");
  fwrite($handler, "          var _fa = _aspectColorMap[thisValue[_ci].toLowerCase()];\r\n");
  fwrite($handler, "          if(_fa && !_pv.toLowerCase().includes(_fa.toLowerCase())) return true;\r\n");
  fwrite($handler, "        }\r\n");
  fwrite($handler, "        break;\r\n");
}
fwrite($handler, "      default: break;\r\n");
fwrite($handler, "    }\r\n");
fwrite($handler, "  }\r\n");
fwrite($handler, "  return false;\r\n");
fwrite($handler, "}\r\n\r\n");
//Add a JSON client lookup dictionary for properties
// Build reverse alias map: property name (lowercase) => shortest alias
$reverseAliasMap = [];
if($rootName == "SWUDeck") {
  $aliasMap = ["t" => "text", "p" => "power", "tr" => "trait", "up" => "upgradepower", "uhp" => "upgradehp", "r" => "rarity", "a" => "arena", "is" => "type", "unq" => "unique"];
  foreach($aliasMap as $alias => $prop) {
    if(!isset($reverseAliasMap[$prop])) $reverseAliasMap[$prop] = $alias;
    else if(strlen($alias) < strlen($reverseAliasMap[$prop])) $reverseAliasMap[$prop] = $alias;
  }
  // c is a special-case handler (character map), not a simple string alias — document for display only
  $reverseAliasMap["aspect"] = "c";
}
fwrite($handler, "var propertyLookup = [\r\n");
for ($i = 0; $i < count($properties); ++$i) {
  $property = $properties[$i];
  $alias = isset($reverseAliasMap[strtolower($property)]) ? $reverseAliasMap[strtolower($property)] : "";
  fwrite($handler, "  { \"Name\": \"" . $property . "\", \"Type\": \"" . $propertyTypes[$i] . "\", \"Alias\": \"" . $alias . "\" }");
  if($i < count($properties) - 1) fwrite($handler, ",");
  fwrite($handler, "\r\n");
}
fwrite($handler, "];\r\n\r\n");
fclose($handler);
logLine("JS file written: " . basename($generateFilename) . " (" . round(filesize($generateFilename)/1024, 1) . "KB)");

if($rootName == "SWUSim") {
  logLine("=== Phase 7: Writing GeneratedAbilityStubs.php ===");

  $stubs = [
    "whenPlayedUsingSmuggle" => [],
    "whenPlayedAsUpgrade"    => [],
    "whenPlayed"             => [],
    "whenDefeated"           => [],
    "onAttack"               => [],
    "onDefense"              => [],
    "onAttackEnd"            => [],
  ];

  $unitTypes = ["Unit", "Token Unit", "Leader Unit", "Leader"];

  foreach($allCardIds as $cardId) {
    $text       = $associativeArrays["text"][$cardId] ?? "";
    $deployText = $associativeArrays["deployText"][$cardId] ?? "";
    $combined   = $text . " " . $deployText;
    $cardType   = $associativeArrays["type"][$cardId] ?? "";

    // Check most-specific When Played variants first, then general
    if(strpos($combined, "When played using Smuggle:") !== false) {
      $stubs["whenPlayedUsingSmuggle"][] = $cardId;
    }
    // whenPlayedAsUpgrade: "When played as an upgrade:" cards, plus leader-as-Pilot deploy.
    // "When deployed as an upgrade:" is the leader-as-Pilot deploy variant (e.g. JTL_001),
    // which also resolves through the WhenPlayedAsUpgrade window.
    if(strpos($combined, "When played as an upgrade:") !== false
      || strpos($combined, "When deployed as an upgrade:") !== false
      // Suppressor: a Pilot with a unit-only WhenPlayed and NO explicit upgrade ability needs a
      // WhenPlayedAsUpgrade stub (dispatched to a no-op handler in Custom code) so the Pilot-attach
      // path fires the no-op instead of falling back to the unit's WhenPlayed (CollectWhenPlayedAs-
      // UpgradeTriggers' HasWhenPlayedAbility fallback). Matches only JTL_100 Poe — JTL_098/210 have
      // a real "When played as an upgrade:" ability, and JTL_213 is not a Pilot (can't attach).
      || (strpos($combined, "Piloting") !== false
          && (strpos($combined, "When played as a unit:") !== false || strpos($combined, "When played as a unit/") !== false)
          && strpos($combined, "When played as an upgrade:") === false)) {
      $stubs["whenPlayedAsUpgrade"][] = $cardId;
    }
    // whenPlayed also covers a deployed leader's "When Deployed:" window — the engine collects it
    // through the WhenPlayed trigger (CollectEntryTriggers on deploy). The upstream dataset uses
    // "When Deployed:" instead of "When Played:" for leaders like SOR_006 (Palpatine).
    if(strpos($combined, "When Played:") !== false || strpos($combined, "When Played/") !== false
      || strpos($combined, "When Deployed:") !== false
      // Dual-mode Pilot cards trigger their unit-play ability through the WhenPlayed window when
      // played as a unit. The colon form is unit-only (JTL_100/210/213); the slash form is a compound
      // window, e.g. "When played as a unit/On Attack:" (JTL_098).
      || strpos($combined, "When played as a unit:") !== false
      || strpos($combined, "When played as a unit/") !== false
      // TS26_34 Fives — "enter play with the 'When Played' abilities of another unit"; the copy fires
      // through the WhenPlayed window but the text has no literal "When Played:" trigger to auto-detect.
      || $cardId === 'TS26_34') {
      $stubs["whenPlayed"][] = $cardId;
    }
    // whenDefeated: units only, innate ability (not grant-style).
    // Grant-style text always has a double-quote before "When Defeated:" e.g. gains "When Defeated:...".
    if(in_array($cardType, $unitTypes)
      && strpos($combined, "When Defeated:") !== false
      && strpos($combined, '"When Defeated:') === false) {
      $stubs["whenDefeated"][] = $cardId;
    }
    // Include if there's an UNQUOTED "On Attack:" (the unit's own ability) even when a granted
    // (quoted) "On Attack:" also appears in the text — e.g. JTL_018 Kazuda's deploy side has both.
    if(in_array($cardType, $unitTypes)
      && (preg_match('/(?<!")On Attack:/', $combined) === 1 || strpos($combined, "On Attack/") !== false)) {
      $stubs["onAttack"][] = $cardId;
    }
    // "On Defense:" and the legacy/modern phrasing "When this unit is attacked:" are the same
    // timing window (CR 15.c). Detect both. (Reminder text is parenthesised, so the bare ability
    // line — not the "On Defense:" quoted reminder — is what matches.)
    if(in_array($cardType, $unitTypes)
      && ((strpos($combined, "On Defense:") !== false && strpos($combined, '"On Defense:') === false)
          || strpos($combined, "When this unit is attacked:") !== false
          || strpos($combined, "When this unit is attacked (before damage is dealt):") !== false)) {
      $stubs["onDefense"][] = $cardId;
    }
    // On Attack End: newer templates use "On Attack End:"; older cards (e.g. SOR_192 Ezra Bridger)
    // use the phrasing "When this unit completes an attack:". Both map to the onAttackEnd stub.
    // Grant-style text (preceded by a double-quote) is excluded for either phrasing.
    if(in_array($cardType, $unitTypes)
      && ((strpos($combined, "On Attack End:") !== false
            && strpos($combined, '"On Attack End:') === false)
        || (strpos($combined, "When this unit completes an attack:") !== false
            && strpos($combined, '"When this unit completes an attack:') === false)
        // "(and survives)" parenthetical variant (JTL_070, JTL_089, SEC_096): the survival gate is
        // enforced by CollectAfterAttackTriggers, so it maps to the same onAttackEnd stub.
        || (strpos($combined, "When this unit completes an attack (and survives):") !== false
            && strpos($combined, '"When this unit completes an attack (and survives):') === false)
        // "When Attack Ends:" phrasing (LAW_074 Maz Kanata) — same onAttackEnd window.
        || (strpos($combined, "When Attack Ends:") !== false
            && strpos($combined, '"When Attack Ends:') === false))) {
      $stubs["onAttackEnd"][] = $cardId;
    }
  }

  // (No manual stub list — every trigger window is now derived from card text above. The former
  // manual entries are all covered: dual-mode pilots' "When played as a unit[:/]" → whenPlayed +
  // the Pilot WhenPlayedAsUpgrade suppressor; ASH_001/LOF_016 → onAttackEnd via "When Attack Ends:"
  // / "completes an attack (and survives):". If a future card's trigger isn't caught, EXTEND the
  // detection above rather than reintroducing a manual list.)

  $stubFilename = $directory . "/GeneratedAbilityStubs.php";
  $stubHandler  = fopen($stubFilename, "w");
  fwrite($stubHandler, "<?php\r\n");
  fwrite($stubHandler, "// AUTO-GENERATED FILE: Ability stubs derived from card text patterns\r\n");
  fwrite($stubHandler, "// DO NOT EDIT MANUALLY - overwritten on each generator run\r\n");
  fwrite($stubHandler, "// Last generated: " . date("Y-m-d H:i:s") . "\r\n\r\n");

  $stubGroups = [
    "whenPlayedUsingSmuggle" => ["HasWhenPlayedUsingSmuggleAbility", "When Played Using Smuggle"],
    "whenPlayedAsUpgrade"    => ["HasWhenPlayedAsUpgradeAbility",    "When Played As Upgrade"],
    "whenPlayed"             => ["HasWhenPlayedAbility",             "When Played"],
    "whenDefeated"           => ["HasWhenDefeatedAbility",           "When Defeated"],
    "onAttack"               => ["HasOnAttackAbility",               "On Attack"],
    "onDefense"              => ["HasOnDefenseAbility",              "On Defense"],
    "onAttackEnd"            => ["HasOnAttackEndAbility",            "On Attack End"],
  ];

  foreach($stubGroups as $key => [$fnName, $label]) {
    $cards = $stubs[$key] ?? [];
    fwrite($stubHandler, "// $label Has-check (" . count($cards) . " cards)\r\n");
    fwrite($stubHandler, "function $fnName(string \$cardID): bool {\r\n");
    if(!empty($cards)) {
      fwrite($stubHandler, "    switch (\$cardID) {\r\n");
      foreach($cards as $cardId) {
        fwrite($stubHandler, "        case '$cardId':\r\n");
      }
      fwrite($stubHandler, "            return true;\r\n");
      fwrite($stubHandler, "        default: return false;\r\n");
      fwrite($stubHandler, "    }\r\n");
    } else {
      fwrite($stubHandler, "    return false;\r\n");
    }
    fwrite($stubHandler, "}\r\n\r\n");
  }

  fwrite($stubHandler, "?>");
  fclose($stubHandler);
  logLine("PHP file written: " . basename($stubFilename) . " (" . round(filesize($stubFilename)/1024, 1) . "KB)"
    . " — whenPlayedUsingSmuggle:" . count($stubs["whenPlayedUsingSmuggle"])
    . " whenPlayedAsUpgrade:" . count($stubs["whenPlayedAsUpgrade"])
    . " whenPlayed:" . count($stubs["whenPlayed"])
    . " whenDefeated:" . count($stubs["whenDefeated"])
    . " onAttack:" . count($stubs["onAttack"])
    . " onDefense:" . count($stubs["onDefense"])
    . " onAttackEnd:" . count($stubs["onAttackEnd"]));
}

logLine("=== Generator complete! Total time: " . round(microtime(true) - $startTime, 2) . "s ===");

function GetResponseMetadata($response, $metadataPath)
{
  // Navigate nested properties using dot notation
  // e.g., "meta.pagination.pageCount" or simple "has_more"
  $path = explode(".", $metadataPath);
  $current = $response;

  foreach($path as $key) {
    if(is_object($current) && isset($current->$key)) {
      $current = $current->$key;
    } else if(is_array($current) && isset($current[$key])) {
      $current = $current[$key];
    } else {
      return false;
    }
  }

  return $current;
}

function ExpandNestedCards(&$cardArray, $nestedCardPaths, &$otherOrientationMap, $imageUrl, $imageFormat)
{
  global $rootName;
  if(empty($nestedCardPaths)) return 0;

  $seenCardIds = [];
  for($i = 0; $i < count($cardArray); ++$i) {
    if(isset($cardArray[$i]->id)) $seenCardIds[$cardArray[$i]->id] = true;
  }

  $originalCount = count($cardArray);
  $added = 0;
  for($i = 0; $i < $originalCount; ++$i) {
    $parentCard = $cardArray[$i];
    if(!isset($parentCard->id)) continue;
    foreach($nestedCardPaths as $path) {
      if($path == "") continue;
      $nestedCards = GetNestedImportCards($parentCard, explode(".", $path));
      foreach($nestedCards as $nestedCard) {
        if(!is_object($nestedCard) || !isset($nestedCard->uuid)) continue;
        $nestedCardId = ($rootName == "GrandArchiveSim") ? GetGrandArchiveImageId($nestedCard) : $nestedCard->uuid;
        if(!isset($otherOrientationMap[$parentCard->id])) $otherOrientationMap[$parentCard->id] = $nestedCardId;
        if(!isset($otherOrientationMap[$nestedCardId])) $otherOrientationMap[$nestedCardId] = $parentCard->id;
        if(isset($seenCardIds[$nestedCardId])) continue;

        $nestedCard->id = $nestedCardId;
        NormalizeNestedImportCard($nestedCard, $parentCard);
        $cardArray[] = $nestedCard;
        $seenCardIds[$nestedCardId] = true;
        ++$added;

        $thisImageUrl = $imageUrl . $nestedCardId . "." . $imageFormat;
        $squareCards = false;
        if($rootName == "GudnakSim") {
          $squareCards = true;
        }
        CheckImage($nestedCardId, $thisImageUrl, "", "", rootPath:"./" . $rootName . "/", squareCards:$squareCards);
      }
    }
  }
  return $added;
}

function ImportOptionList($importOptions, $key)
{
  if(!isset($importOptions[$key])) return [];
  $value = $importOptions[$key];
  $values = is_array($value) ? $value : [$value];
  $results = [];
  foreach($values as $item) {
    foreach(explode(",", $item) as $part) {
      $part = trim($part);
      if($part !== "") $results[] = $part;
    }
  }
  return $results;
}

function AppendSupplementalImportCards(&$cardArray, $sources)
{
  global $rootName, $overwriteImages;
  if(empty($sources)) return 0;

  $seenCardIds = [];
  for($i = 0; $i < count($cardArray); ++$i) {
    if(isset($cardArray[$i]->id)) $seenCardIds[(string)$cardArray[$i]->id] = true;
  }

  $added = 0;
  foreach($sources as $sourceUrl) {
    $rows = FetchSupplementalImportRows($sourceUrl);
    if($rows === null) {
      logLine("WARNING: Failed to load supplemental card source: " . $sourceUrl);
      continue;
    }

    $sourceAdded = 0;
    $sourceDuplicates = 0;
    $sourceInvalid = 0;
    foreach($rows as $row) {
      if(is_array($row)) $row = (object)$row;
      if(!is_object($row)) {
        ++$sourceInvalid;
        continue;
      }

      $cardID = SupplementalCardId($row);
      if($cardID === "") {
        ++$sourceInvalid;
        continue;
      }
      if(isset($seenCardIds[$cardID])) {
        ++$sourceDuplicates;
        continue;
      }

      $row->id = $cardID;
      $cardArray[] = $row;
      $seenCardIds[$cardID] = true;
      ++$added;
      ++$sourceAdded;

      if(isset($row->image_url) && trim((string)$row->image_url) !== "") {
        CheckImage($cardID, trim((string)$row->image_url), "", "", rootPath:"./" . $rootName . "/", overwriteImages:$overwriteImages);
      }
    }

    logLine("Supplemental source " . $sourceUrl . ": " . count($rows) . " rows, " . $sourceAdded . " appended, " . $sourceDuplicates . " duplicates skipped, " . $sourceInvalid . " invalid skipped.");
  }

  return $added;
}

function FetchSupplementalImportRows($sourceUrl)
{
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $sourceUrl);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_FAILONERROR, true);
  $response = curl_exec($curl);
  $curlError = curl_error($curl);
  curl_close($curl);
  if($response === false || $curlError) return null;

  if(substr($response, 0, 3) === "\xEF\xBB\xBF") {
    $response = substr($response, 3);
  }
  $decoded = json_decode($response);
  if($decoded === null) return null;

  if(is_object($decoded) && isset($decoded->success) && !$decoded->success) return null;
  if(is_object($decoded) && isset($decoded->data) && is_array($decoded->data)) return $decoded->data;
  if(is_array($decoded)) return $decoded;
  return null;
}

function SupplementalCardId($card)
{
  foreach(["uuid", "id", "card_id", "cardId"] as $property) {
    if(isset($card->$property) && trim((string)$card->$property) !== "") return trim((string)$card->$property);
  }
  return "";
}

function GetNestedImportCards($node, $pathParts)
{
  if(empty($pathParts)) {
    if(is_array($node)) return $node;
    return [$node];
  }

  $part = array_shift($pathParts);
  $results = [];

  if(is_array($node)) {
    foreach($node as $item) {
      $results = array_merge($results, GetNestedImportCards($item, array_merge([$part], $pathParts)));
    }
    return $results;
  }

  if($part == "*") {
    if(is_object($node)) {
      foreach(get_object_vars($node) as $value) {
        $results = array_merge($results, GetNestedImportCards($value, $pathParts));
      }
    }
    return $results;
  }

  if(is_object($node) && isset($node->$part)) {
    return GetNestedImportCards($node->$part, $pathParts);
  }

  return [];
}

function NormalizeNestedImportCard($card, $parentCard)
{
  global $rootName;
  if($rootName == "GrandArchiveSim") {
    if(!isset($card->editions)) {
      if(isset($card->edition)) {
        $card->editions = [$card->edition];
      } else if(isset($parentCard->editions)) {
        $card->editions = $parentCard->editions;
      }
    }
    if(!isset($card->result_editions) && isset($card->editions)) {
      $card->result_editions = $card->editions;
    }
  }
}

function GetGrandArchiveImageId($card)
{
  if(isset($card->edition_id) && $card->edition_id != "") return $card->edition_id;
  if(isset($card->edition) && isset($card->edition->uuid) && $card->edition->uuid != "") return $card->edition->uuid;
  if(isset($card->editions) && is_array($card->editions) && count($card->editions) > 0 && isset($card->editions[0]->uuid) && $card->editions[0]->uuid != "") return $card->editions[0]->uuid;
  return isset($card->id) ? $card->id : $card->uuid;
}

// Extract a scalar field from a Strapi relation object (v4 or v5).
// v5 flat: {name:"Unit"}  →  SWURelAttr($obj, 'name') = "Unit"
// v4 nested: {data:{attributes:{name:"Unit"}}}  →  same result
function SWURelAttr($obj, $field) {
    if(!is_object($obj)) return null;
    if(isset($obj->$field)) return $obj->$field;
    if(isset($obj->data->attributes->$field)) return $obj->data->attributes->$field;
    return null;
}

// Extract a named field from a Strapi multi-relation (v4 or v5), returning a plain array.
// v5: [{name:"Heroism"}, ...]  →  ["Heroism", ...]
// v4: {data:[{attributes:{name:"Heroism"}}, ...]}  →  ["Heroism", ...]
function SWURelAttrList($val, $field) {
    if(is_array($val)) {
        return array_map(fn($item) => is_object($item) ? ($item->$field ?? '') : (string)$item, $val);
    }
    if(is_object($val) && isset($val->data) && is_array($val->data)) {
        return array_map(fn($item) => $item->attributes->$field ?? '', $val->data);
    }
    return [];
}

// Normalise typographic Unicode punctuation from the card API to ASCII (SWUSim only).
// Only punctuation is mapped — accented letters (é, Î, …) are preserved.
function NormalizeCardPunctuation($value)
{
  if (!is_string($value)) return $value;
  static $map = [
    "\xE2\x80\x9C" => '"',   // U+201C left double quotation mark
    "\xE2\x80\x9D" => '"',   // U+201D right double quotation mark
    "\xE2\x80\x98" => "'",   // U+2018 left single quotation mark
    "\xE2\x80\x99" => "'",   // U+2019 right single quotation mark (apostrophe)
    "\xE2\x80\x93" => '-',   // U+2013 en dash (used in "-3/-3" stat modifiers)
    "\xE2\x80\x94" => '-',   // U+2014 em dash
    "\xE2\x80\x91" => '-',   // U+2011 non-breaking hyphen ("non-leader")
    "\xE2\x80\xA6" => '...', // U+2026 horizontal ellipsis
    "\xC2\xA0"     => ' ',   // U+00A0 non-breaking space
  ];
  return strtr($value, $map);
}

function NormalizeGrandArchiveSpeed($value)
{
  if($value === null || $value === "") return -1;
  if(is_bool($value)) return $value;
  if(is_numeric($value)) {
    $number = (int)$value;
    if($number === 1) return true;
    if($number === 0) return false;
    if($number === -1) return -1;
  }

  $speed = strtoupper(trim((string)$value));
  if(in_array($speed, ["FAST", "TRUE", "YES", "1"], true)) return true;
  if(in_array($speed, ["SLOW", "FALSE", "NO", "0"], true)) return false;
  if(in_array($speed, ["UNDEFINED", "UNKNOWN", "NONE", "NULL", "-1"], true)) return -1;
  return -1;
}

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
    case "GudnakSim":
      switch($property) {
        case "traits":
          if(isset($card->$property) && is_array($card->$property)) {
            return implode(",", $card->$property);
          }
          return isset($card->$property) ? $card->$property : "";
        default: return isset($card->$property) ? $card->$property : "";
      }
    case "SWUSim":
      // Official SWU API — relation fields vary between Strapi v4 nested ({data:{attributes:{...}}})
      // and v5 flat ({name:...}). SWURelAttr/SWURelAttrList handle both shapes.
      switch($property) {
        case "type":
          return SWURelAttr($card->type ?? null, 'name');
        case "arena":
          return implode(",", array_filter(SWURelAttrList($card->arenas ?? null, 'name')));
        case "rarity":
          $r = $card->rarity ?? null;
          return SWURelAttr($r, 'name') ?: SWURelAttr($r, 'character') ?: '';
        case "set":
          $s = $card->set ?? null;
          $setResult = SWURelAttr($s, 'abbreviation') ?: SWURelAttr($s, 'code') ?: '';
          if($setResult === '') $setResult = $card->expansion->data->attributes->abbreviation ?? $card->expansion->data->attributes->code ?? '';
          return $setResult;
        case "aspect":
          $aspects = SWURelAttrList($card->aspects ?? null, 'name');
          $dupes    = SWURelAttrList($card->aspectDuplicates ?? null, 'name');
          return implode(",", array_filter(array_merge($aspects, $dupes)));
        case "trait":
          // API uses "traits" (plural); "trait" in the schema maps to this relation
          return implode(",", array_filter(SWURelAttrList($card->traits ?? null, 'name')));
        case "documentId":
          // Strapi v5 uses documentId; old API uses cardUid / cardId
          return (string)($card->documentId ?? $card->cardUid ?? $card->cardId ?? '');
        case "text":
          // Combine card text with epic action text (leader deploy ability)
          $text = (string)($card->text ?? '');
          $epic = (string)($card->epicAction ?? '');
          return $text . ($epic !== '' ? "\n" . $epic : '');
        case "deployText":
          // API uses "deployBox" for the leader unit-side text
          return (string)($card->deployBox ?? $card->deployText ?? '');
        case "unique":
          return (bool)($card->unique ?? false);
        case "cost":
        case "hp":
        case "power":
        case "cardNumber":
        case "upgradeHp":
        case "upgradePower":
          $val = $card->$property ?? null;
          return $val !== null ? intval($val) : null;
        default:
          $val = $card->$property ?? null;
          return $val !== null ? (string)$val : '';
      }
    case "GrandArchiveSim":
      switch($property) {
        case "element":
          if(isset($card->elements) && is_array($card->elements) && count($card->elements) > 0) {
            return implode(",", $card->elements);
          }
          return isset($card->element) ? $card->element : "";
        case "type":
          if(isset($card->types) && is_array($card->types)) return implode(",", $card->types);
          return isset($card->type) ? $card->type : "";
        case "classes":
          if(isset($card->classes) && is_array($card->classes)) return implode(",", $card->classes);
          return isset($card->classes) ? $card->classes : "";
        case "subtypes":
          if(isset($card->subtypes) && is_array($card->subtypes)) return implode(",", $card->subtypes);
          return isset($card->subtypes) ? $card->subtypes : "";
        case "cost_memory":
        case "cost_reserve":
        case "level":
        case "power":
        case "life":
        case "durability":
          return isset($card->$property) && $card->$property !== null ? $card->$property : -1;
        case "speed":
          return NormalizeGrandArchiveSpeed($card->$property ?? null);
        case "set":
          if(isset($card->editions) && isset($card->editions[0]->set) && isset($card->editions[0]->set->prefix)) return $card->editions[0]->set->prefix;
          return isset($card->set) ? $card->set : "";
        case "effect":
          return isset($card->$property) ? str_replace("\n", "<br>", $card->$property) : "";
        default: return isset($card->$property) ? $card->$property : "";
      }
    case "AzukiSim":
      switch($property) {
        case "ikzCost":
        case "attack":
        case "health":
        case "gatePower":
          return isset($card->$property) && $card->$property !== null ? intval($card->$property) : -1;
        case "abilities":
        case "subtypes":
        case "set":
          return isset($card->$property) && is_array($card->$property) ? $card->$property : [];
        default: return isset($card->$property) ? $card->$property : "";
      }
    default: return isset($card->$property) ? $card->$property : "";
  }
}

?>
