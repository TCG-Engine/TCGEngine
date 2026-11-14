<?php

// Regenerates ./Output/SWUSimImplementation.php, which SWUDeck/Initialize.php includes on every deck view.
// The file is built in memory and swapped in only after the fetch succeeded and the result lints: writing it
// line-by-line as before meant a failed fetch (petranaki's endpoint 404ing → json_decode null → count() fatal)
// left a truncated, unparseable file behind, and every SWUStats decklist died at that include (2026-11-14).

$outPath = "./Output/SWUSimImplementation.php";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://petranaki.net/Arena/api/UnimplementedCards.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = ($response !== false && $httpCode == 200) ? json_decode($response, true) : null;
if (!is_array($data)) {
  die("Unimplemented-cards fetch failed (HTTP $httpCode); left $outPath unchanged.\n");
}

$out = "<?php\n";
$out .= "echo \"<script>\n";
$out .= "function KarabastImplemented(cardID) {\n";
$out .= "  switch(cardID) {\n";
// Karabast's get-unimplemented feed is no longer fetched; every card reports implemented.
$out .= "    default: return true;\n";
$out .= "  }\n";
$out .= "}\n";

$out .= "function PetranakiImplemented(cardID) {\n";
$out .= "  switch(cardID) {\n";
foreach ($data as $cardID) {
  // Ids land inside a PHP double-quoted string and a JS single-quoted one; anything else is dropped.
  if (!is_string($cardID) || !preg_match('/^[A-Za-z0-9_]+$/', $cardID)) continue;
  $out .= "    case '" . $cardID . "': return false;\n";
}
$out .= "    default: return true;\n";
$out .= "  }\n";
$out .= "}\n";
$out .= "window.KarabastImplemented = KarabastImplemented;\n";
$out .= "window.PetranakiImplemented = PetranakiImplemented;\n";
$out .= "</script>\";\n";

$tmpPath = $outPath . ".tmp";
if (file_put_contents($tmpPath, $out) === false) {
  die("Unable to open file for writing.");
}
exec("php -l " . escapeshellarg($tmpPath) . " 2>&1", $lintOutput, $lintCode);
if ($lintCode !== 0) {
  unlink($tmpPath);
  die("Generated file failed lint; left $outPath unchanged.\n" . implode("\n", $lintOutput) . "\n");
}
rename($tmpPath, $outPath);

?>
