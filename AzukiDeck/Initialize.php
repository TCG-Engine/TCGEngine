<?php

include_once './AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
include_once './AzukiSim/Custom/Stats.php';

$p1Leaders = [];
$p1Gates = [];
$p1Cards = [];

foreach (GetAllCardIds() as $cardID) {
  $category = strtolower((string)CardCategory($cardID));
  if ($category === 'leader') {
    $p1Leaders[] = new Leaders($cardID);
  } elseif ($category === 'gate') {
    $p1Gates[] = new Gates($cardID);
  } elseif ($category === 'ikz') {
    continue;
  } else {
    $p1Cards[] = new Cards($cardID);
  }
}

WriteGamestate('./AzukiDeck/');

$azukiDeckCardStats = [];
try {
  $azukiDeckCardStats = AzukiLoadDeckCardStats($gameName);
} catch(Throwable $e) {
  error_log('AzukiDeck card stats load failed: ' . $e->getMessage());
}
$azukiDeckCardStatsJSON = json_encode($azukiDeckCardStats, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if($azukiDeckCardStatsJSON === false) $azukiDeckCardStatsJSON = '{}';

echo "<script>\n";
echo 'window.AzukiDeckCardStats = ' . $azukiDeckCardStatsJSON . ";\n";
echo <<<'JS'
function AzukiDeckCardStat(cardID, property) {
  var row = window.AzukiDeckCardStats && window.AzukiDeckCardStats[cardID];
  return row && typeof row[property] === 'number' ? row[property] : -1;
}
function AzukiCardPlayWinRate(cardID) {
  return AzukiDeckCardStat(cardID, 'playWinRate');
}
function AzukiCardInclusionWinRate(cardID) {
  return AzukiDeckCardStat(cardID, 'inclusionWinRate');
}
function AzukiCardPlayFrequency(cardID) {
  return AzukiDeckCardStat(cardID, 'playFrequency');
}
function AzukiCardAttackFrequency(cardID) {
  return AzukiDeckCardStat(cardID, 'attackFrequency');
}
function AzukiCardAttackedFrequency(cardID) {
  return AzukiDeckCardStat(cardID, 'attackedFrequency');
}
window.AzukiCardPlayWinRate = AzukiCardPlayWinRate;
window.AzukiCardInclusionWinRate = AzukiCardInclusionWinRate;
window.AzukiCardPlayFrequency = AzukiCardPlayFrequency;
window.AzukiCardAttackFrequency = AzukiCardAttackFrequency;
window.AzukiCardAttackedFrequency = AzukiCardAttackedFrequency;
JS;
echo "\n</script>\n";

?>
