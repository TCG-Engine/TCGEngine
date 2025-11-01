<?php
// tcgcsv category id for Star Wars Unlimited - change here if needed
$priceCategory = 79;
// Path to SWUDeck directory as seen by the browser (used to call PriceCache.php)
$scriptNameDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$swuDeckBase = $scriptNameDir . "/SWUDeck";


  include_once './SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
  include_once './Database/ConnectionManager.php';

  //Set up the list of cards to choose from
  $p1Leaders = [];
  $p1Bases = [];
  $p1Cards = [];
  $allCards = GetAllCardIds();
  foreach ($allCards as $cardId) {
    $cardType = CardType($cardId);
    if($cardType == "Leader") {
      array_push($p1Leaders, new Leaders($cardId));
    } else if($cardType == "Base") {
      array_push($p1Bases, new Bases($cardId));
    } else {
      array_push($p1Cards, new Cards($cardId));
    }
  }

  WriteGamestate("./SWUDeck/");

  $conn = GetLocalMySQLConnection();
  
  // First check if there are any rows with source = 1 (deck owner stats)
  $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM carddeckstats WHERE deckID = ? AND source = 1");
  $checkStmt->bind_param("i", $gameName);
  $checkStmt->execute();
  $checkResult = $checkStmt->get_result();
  $row = $checkResult->fetch_assoc();
  $sourceToUse = ($row['count'] > 0) ? 1 : 0;
  $checkStmt->close();
  
  // Now query with the appropriate source filter
  $stmt = $conn->prepare("SELECT * FROM carddeckstats WHERE deckID = ? AND source = ?");
  $stmt->bind_param("ii", $gameName, $sourceToUse);
  $stmt->execute();
  $result = $stmt->get_result();
  $stmt->close();
  $conn->close();
  $playWinRate = "";
  $resourceRatio = "";
  
  while ($row = $result->fetch_assoc()) {
    $playWinRate .= "    case '" . $row["cardID"] . "': return " . ($row["timesPlayed"] > 0 ? round($row["timesPlayedInWins"] / $row["timesPlayed"], 4) : -1) . ";\r\n";
    $resourceRatio .= "    case '" . $row["cardID"] . "': return " . ($row["timesResourced"] + $row["timesPlayed"] > 0 ? round($row["timesResourced"] / ($row["timesResourced"] + $row["timesPlayed"]), 4) : -1) . ";\r\n";
  }

  echo("<script>\r\n");
  echo("function CardPlayWinRate(cardId) {\r\n");
  echo("  switch(cardId) {\r\n");
  echo($playWinRate);
  echo("    default: return -1;\r\n");
  echo("  }\r\n");
  echo("}\r\n");
  echo("function CardResourceRatio(cardId) {\r\n");
  echo("  switch(cardId) {\r\n");
  echo($resourceRatio);
  echo("    default: return -1;\r\n");
  echo("  }\r\n");
  echo("}\r\n");
  // Helper for safe JSON fetch that avoids unhandled JSON parse errors
  echo("function safeJsonFetch(url) {\r\n");
  echo("  return fetch(url).then(r => {\r\n");
  echo("    if(!r.ok) { console.warn('Fetch failed', url, r.status); return null; }\r\n");
  echo("    var ct = r.headers.get('content-type') || '';\r\n");
  echo("    if(ct.indexOf('application/json') === -1) {\r\n");
  echo("      // Not JSON - read text for debugging and return null\r\n");
  echo("      return r.text().then(t => { console.warn('Expected JSON from', url, 'got:', t && t.substr ? t.substr(0,200) : t); return null; });\r\n");
  echo("    }\r\n");
  echo("    return r.json().catch(e => { console.warn('JSON parse error for', url, e); return null; });\r\n");
  echo("  }).catch(e => { console.warn('Network error fetching', url, e); return null; });\r\n");
  echo("}\r\n");

  echo("function PriceHeatmap(cardId) {\r\n");
  echo("  // If data already loaded, return normalized price value (lower is better).\r\n");
  echo("  if(window.PriceHeatmapData && window.PriceHeatmapData[cardId]) {\r\n");
  echo("    var price = window.PriceHeatmapData[cardId];\r\n");
  echo("    // Normalize price into 0..1 range - adjust divisor as needed for scaling.\r\n");
  echo("    return Math.min(price / 100.0, 1.0);\r\n");
  echo("  }\r\n");
  echo("  // If not loaded, start a single lazy load and return -1 (No Data) for now.\r\n");
  echo("  // Use PriceHeatmapLoaded to avoid re-fetching during the same page session.\r\n");
  echo("  if(!window.PriceHeatmapLoading && !window.PriceHeatmapLoaded) {\r\n");
  echo("    window.PriceHeatmapLoading = true;\r\n");
  echo("    showFlashMessage('Loading price heatmap...');\r\n");
  echo("    // Fetch cached price data from our server-side proxy/cache endpoint\r\n");
  echo("    safeJsonFetch('" . $swuDeckBase . "/PriceCache.php')\r\n");
  echo("      .then(j => {\r\n");
  echo("        window.PriceHeatmapData = {};\r\n");
  echo("        if(j && j.results) {\r\n");
  echo("          Object.keys(j.results).forEach(k => { window.PriceHeatmapData[String(k)] = j.results[k]; });\r\n");
  echo("        }\r\n");
  echo("        window.PriceHeatmapLoading = false;\r\n");
  echo("        // Mark that we've attempted/loaded price data so we won't re-fetch repeatedly.\r\n");
  echo("        window.PriceHeatmapLoaded = true;\r\n");
  echo("        showFlashMessage('Price heatmap loaded', 3000);\r\n");
  echo("        // Update overlays now that data is available\r\n");
  echo("        setTimeout(function() { UpdatePriceOverlays(); }, 50);\r\n");
  echo("      }).catch(e => {\r\n");
  echo("        // Any unexpected error - mark loading false and surface a message\r\n");
  echo("        console.warn('Error loading price data', e);\r\n");
  echo("        window.PriceHeatmapLoading = false;\r\n");
  echo("        // Mark as attempted so we don't keep retrying while on this page.\r\n");
  echo("        window.PriceHeatmapLoaded = true;\r\n");
  echo("        showFlashMessage('Failed to load price data');\r\n");
  echo("      });\r\n");
  echo("  }\r\n");
  echo("  return -1;\r\n");
  echo("}\r\n");

  // Helper to update existing card overlays in-place after price data arrives
  echo("function UpdatePriceOverlays() {\r\n");
  echo("  try {\r\n");
  echo("    // Find all generated card image elements (they use the id suffix '-img')\r\n");
  echo("    var imgs = document.querySelectorAll('img[id$=\"-img\"]');\r\n");
  echo("    imgs.forEach(function(img) {\r\n");
  echo("      try {\r\n");
  echo("        // Extract card id from filename portion of src (strip extension)\r\n");
  echo("        var src = img.getAttribute('src') || '';\r\n");
  echo("        var parts = src.split('/');\r\n");
  echo("        var file = parts.length ? parts[parts.length - 1] : src;\r\n");
  echo("        var cardId = file.replace(/\.(png|webp|jpg|jpeg)$/i, '');\r\n");
  echo("        var overlay = document.getElementById(img.id.replace('-img', '-ovr'));\r\n");
  echo("        if(!overlay) return;\r\n");
  echo("        var val = (window.PriceHeatmapData && window.PriceHeatmapData[cardId]) ? Math.min(window.PriceHeatmapData[cardId] / 100.0, 1.0) : -1;\r\n");
  echo("        if(val == -1) {\r\n");
  echo("          overlay.style.background = 'rgba(0,0,0,0.5)';\r\n");
  echo("          overlay.innerText = 'No Data';\r\n");
  echo("        } else {\r\n");
  echo("          var color = getOverlayColorLowerIsBetter(val);\r\n");
  echo("          overlay.style.background = `linear-gradient(to top, \${color}, rgba(255,255,255,0))`;\r\n");
  echo("          overlay.innerText = (val * 100).toFixed(2) + '%';\r\n");
  echo("        }\r\n");
  echo("        overlay.style.visibility = 'visible';\r\n");
  echo("      } catch(e) { }\r\n");
  echo("    });\r\n");
  echo("  } catch(e) { }\r\n");
  echo("}\r\n");
  echo("</script>\r\n");

  include_once './Utils/Output/SWUSimImplementation.php';

      /*
    $cardStatsTable .= "<tr>";
    $cardStatsTable .= "<td>" . htmlspecialchars(CardTitle($row["cardID"]), ENT_QUOTES, 'UTF-8') . "</td>";
    $cardStatsTable .= "<td>" . $row["timesIncluded"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesIncludedInWins"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesPlayed"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesPlayedInWins"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesResourced"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesResourcedInWins"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesDiscarded"] . "</td>";
    $cardStatsTable .= "<td>" . $row["timesDiscardedInWins"] . "</td>";
    $cardStatsTable .= "</tr>";
    */
?>