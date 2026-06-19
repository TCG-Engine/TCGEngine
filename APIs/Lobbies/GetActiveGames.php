<?php

require_once "../../Core/NetworkingLibraries.php";

$response = new stdClass();
$response->success = true;
$response->message = "Successfully fetched active games.";

$rootName = isset($_GET['rootName']) ? strval($_GET['rootName']) : '';
$includePrivate = isset($_GET['includePrivate']) && ($_GET['includePrivate'] === '1' || strtolower($_GET['includePrivate']) === 'true');
$activeWithinSeconds = isset($_GET['activeWithinSeconds']) ? intval($_GET['activeWithinSeconds']) : 1800;
if ($activeWithinSeconds <= 0) $activeWithinSeconds = 1800;

$response->data = [];
$response->totalCount = 0;
$response->publicCount = 0;
$response->privateCount = 0;

$now = time();
$index = ReadActiveGameIndex();

foreach ($index as $key => $game) {
  if (!is_array($game)) continue;
  $gRoot = isset($game['rootName']) ? strval($game['rootName']) : '';
  $gName = isset($game['gameName']) ? strval($game['gameName']) : '';
  $isPrivate = isset($game['isPrivate']) ? boolval($game['isPrivate']) : false;
  $lastUpdatedAt = isset($game['lastUpdatedAt']) ? intval($game['lastUpdatedAt']) : 0;
  if ($gRoot === '' || $gName === '' || $lastUpdatedAt <= 0) continue;
  if (SimGameAuthKeysPath($gRoot, $gName) !== '' && file_exists(SimGameAuthKeysPath($gRoot, $gName))) {
    $isPrivate = SimGameIsPrivateGame($gRoot, $gName);
  }
  if ($rootName !== '' && $gRoot !== $rootName) continue;
  if (($now - $lastUpdatedAt) > $activeWithinSeconds) continue;

  ++$response->totalCount;
  if ($isPrivate) ++$response->privateCount;
  else ++$response->publicCount;

  if ($isPrivate && !$includePrivate) continue;

  $response->data[] = [
    'rootName' => $gRoot,
    'gameName' => $gName,
    'isPrivate' => $isPrivate,
    'lastUpdatedAt' => $lastUpdatedAt,
    'createdAt' => isset($game['createdAt']) ? intval($game['createdAt']) : $lastUpdatedAt,
  ];
}

usort($response->data, function($a, $b) {
  $aTime = isset($a['lastUpdatedAt']) ? intval($a['lastUpdatedAt']) : 0;
  $bTime = isset($b['lastUpdatedAt']) ? intval($b['lastUpdatedAt']) : 0;
  return $bTime <=> $aTime;
});

header('Content-Type: application/json');
echo json_encode($response);

?>
