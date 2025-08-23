<?php

include_once './GamestateParser.php';
include_once './ZoneAccessors.php';
include_once './ZoneClasses.php';
include_once './GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/HTTPLibraries.php';
include_once '../Database/ConnectionManager.php';
include_once '../AccountFiles/AccountDatabaseAPI.php';
include_once '../AccountFiles/AccountSessionAPI.php';
include_once '../Assets/patreon-php-master/src/PatreonLibraries.php';
include_once '../Assets/patreon-php-master/src/PatreonDictionary.php';


$gameName = TryGet("gameName", "");

if($gameName == "") {
  echo("You must provide a game name to generate this pdf.");
  exit;
}

if(!IsUserLoggedIn()) {
  echo("You must be logged in to generate this pdf.");
  exit;
}
$loggedInUser = LoggedInUser();
$assetData = LoadAssetData(1, $gameName);
if($assetData == null) {
  echo("This game asset does not exist.");
  exit;
}
$assetOwner = $assetData["assetOwner"];
if($loggedInUser != $assetOwner) {
  if($assetData["assetVisibility"] > 10000) {
    if(!IsPatron($assetData["assetVisibility"])){
      echo("You must be a patron to generate this pdf.");
      exit;
    }
  } else if($assetData["assetVisibility"] == 0) {
    echo("You must own this asset to generate this pdf.");
    exit;
  }
}

// Parse the current gamestate and get the leader/base for the loaded deck
try {
  ParseGamestate();
} catch (Exception $e) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(["error" => "Failed to parse gamestate: " . $e->getMessage()]);
  exit;
}

$arr = &GetLeader(1);
$leaderName = count($arr) > 0 ? CardTitle($arr[0]->CardID) . (CardSubtitle($arr[0]->CardID) ? ", " . CardSubtitle($arr[0]->CardID) : "") : null;
$arr = &GetBase(1);
$baseName = count($arr) > 0 ? CardTitle($arr[0]->CardID) : null;

// Default simulation parameters (can be passed via query params)
$numTournaments = intval(TryGet('numTournaments', 1000));
$numParticipants = intval(TryGet('numParticipants', 64));
$numRounds = intval(TryGet('numRounds', 6));

if ($leaderName === null || $baseName === null) {
  echo json_encode(["error" => "No leader or base found in the loaded deck."]);
  exit;
}

// Call the Node.js simulator with the target leader/base so it reports performance from that archetype's perspective.
// Ensure the path matches your workspace; use an absolute path for reliability.
$nodeScript = __DIR__ . DIRECTORY_SEPARATOR . 'tournamentSim.js';
$cmd = 'node ' . escapeshellarg($nodeScript) . ' ' . escapeshellarg(strval($numTournaments)) . ' ' . escapeshellarg(strval($numParticipants)) . ' ' . escapeshellarg(strval($numRounds)) . ' ' . escapeshellarg($leaderName) . ' ' . escapeshellarg($baseName);

$output = null;
// Use shell_exec as a simple approach. On Windows ensure node is in PATH.
$output = shell_exec($cmd . ' 2>&1');
if ($output === null) {
  echo "<pre>Failed to run node simulator. Command: " . htmlspecialchars($cmd) . "</pre>";
  exit;
}

// Attempt to extract JSON from output even if there is a timing prefix like "sim: 71.445ms\n{...}"
$firstBrace = strpos($output, '{');
$jsonPart = null;
$prefix = null;
if ($firstBrace !== false) {
  $prefix = trim(substr($output, 0, $firstBrace));
  $jsonPart = substr($output, $firstBrace);
} else {
  $jsonPart = $output;
}

$decoded = json_decode($jsonPart, true);

// Render HTML output
echo '<!doctype html><html><head><meta charset="utf-8"><title>Tournament Simulation Results</title>';
echo '<style>body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#222}table{border-collapse:collapse;width:100%;max-width:1100px}th,td{padding:8px;border:1px solid #ddd;text-align:left}th{background:#f4f4f6}h1{margin-top:0} .muted{color:#666;font-size:0.9em} .card{background:#fff;border:1px solid #eee;padding:16px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.04)}</style>';
echo '</head><body>';
echo '<h1>Tournament Simulation Results</h1>';
echo '<div class="card">';
echo '<p><strong>Deck:</strong> ' . htmlspecialchars($leaderName . ' / ' . $baseName) . '</p>';
echo '<p class="muted">Command: ' . htmlspecialchars($cmd) . '</p>';
if ($prefix) echo '<p class="muted">' . nl2br(htmlspecialchars($prefix)) . '</p>';

if ($decoded === null) {
  // JSON decode failed, show raw output
  echo '<h3>Raw output</h3>';
  echo '<pre>' . htmlspecialchars($output) . '</pre>';
  echo '</div></body></html>';
  exit;
}

// show summary
echo '<p><strong>Parameters:</strong> Tournaments=' . intval($numTournaments) . ', Participants=' . intval($numParticipants) . ', Rounds=' . intval($numRounds) . '</p>';
if (isset($decoded['target'])) {
  echo '<p><strong>Target archetype:</strong> ' . htmlspecialchars($decoded['target']['leader'] . ' / ' . $decoded['target']['base']) . '</p>';
  echo '<ul>';
  echo '<li>Top‑8 rate: ' . (isset($decoded['target']['top8Rate']) ? round($decoded['target']['top8Rate'] * 100, 2) . '%' : 'N/A') . '</li>';
  echo '<li>Average rank: ' . (isset($decoded['target']['avgRank']) ? round($decoded['target']['avgRank'], 2) : 'N/A') . '</li>';
  echo '<li>Match win rate (approx): ' . (isset($decoded['target']['matchWinRate']) && $decoded['target']['matchWinRate'] !== null ? round($decoded['target']['matchWinRate'] * 100, 2) . '%' : 'N/A') . '</li>';
  echo '</ul>';
}

// table of totals
if (isset($decoded['totals']) && is_array($decoded['totals'])) {
  echo '<h3>Archetype Top‑8 Appearances</h3>';
  echo '<table><thead><tr><th>Leader</th><th>Base</th><th>Top‑8 Appearances</th><th>Top‑8 Rate</th></tr></thead><tbody>';
  foreach ($decoded['totals'] as $row) {
    $parts = explode('||', $row['archetype']);
    $leader = isset($parts[0]) ? $parts[0] : '';
    $base = isset($parts[1]) ? $parts[1] : '';
    echo '<tr>';
    echo '<td>' . htmlspecialchars($leader) . '</td>';
    echo '<td>' . htmlspecialchars($base) . '</td>';
    echo '<td>' . intval($row['top8Appearances']) . '</td>';
    echo '<td>' . (isset($row['top8Rate']) ? round($row['top8Rate'] * 100, 3) . '%' : '') . '</td>';
    echo '</tr>';
  }
  echo '</tbody></table>';
}

echo '</div></body></html>';
?>