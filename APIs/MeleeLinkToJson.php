<?php
// Public API to convert a melee.gg decklist link to the standard deck JSON format.
// Parameters (GET):
//   rootName  - game folder name, e.g. "SWUDeck"
//   meleeLink - full melee.gg decklist URL, e.g. https://melee.gg/Decklist/View/45acba58-495f-4d8d-bee6-b428010e8eb7
// Response JSON format:
//   {"metadata":{"name":"..."},"leader":{"id":"LOF_016","count":1},"base":{"id":"LOF_024","count":1},
//    "deck":[{"id":"JTL_201","count":3},...], "sideboard":[...]}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once '../Core/HTTPLibraries.php';

$rootName  = TryGet('rootName', '');
$meleeLink = TryGet('meleeLink', '');

// Validate rootName to a simple alphanumeric folder name (prevent path traversal)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $rootName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid rootName parameter.']);
    exit;
}

if (empty($meleeLink) || !filter_var($meleeLink, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or invalid meleeLink parameter.']);
    exit;
}

if (!preg_match('~^https://melee\.gg/Decklist/View/[a-zA-Z0-9\-]+$~i', $meleeLink)) {
    http_response_code(400);
    echo json_encode(['error' => 'meleeLink must be a melee.gg Decklist/View URL.']);
    exit;
}

$gameFolder = '../' . $rootName . '/';

if (!file_exists($gameFolder . 'GeneratedCode/GeneratedCardDictionaries.php')) {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown rootName: card dictionaries not found.']);
    exit;
}

require_once $gameFolder . 'GeneratedCode/GeneratedCardDictionaries.php';
require_once $gameFolder . 'Custom/CardIdentifiers.php';

// Fetch the melee.gg decklist HTML
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $meleeLink);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($curl, CURLOPT_TIMEOUT, 15);
$htmlContent = curl_exec($curl);
$curlError   = curl_error($curl);
$httpCode    = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($htmlContent === false || !empty($curlError)) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to fetch melee.gg page: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    http_response_code(502);
    echo json_encode(['error' => 'melee.gg returned HTTP ' . $httpCode]);
    exit;
}

// Parse the HTML DOM
$dom = new DOMDocument();
@$dom->loadHTML($htmlContent);
$xpath = new DOMXPath($dom);

$deckObj = new stdClass();
$deckObj->deck      = [];
$deckObj->sideboard = [];

// Extract deck title
$deckTitleNodes = $xpath->query("//div[@class='decklist-title']");
if ($deckTitleNodes->length > 0) {
    $deckTitle = trim($deckTitleNodes->item(0)->nodeValue);
    $deckObj->metadata       = new stdClass();
    $deckObj->metadata->name = $deckTitle;

    $deckNameParts = explode(' - ', $deckTitle);
    if (count($deckNameParts) >= 2) {
        $leaderSetCode = FindCardSetCode($deckNameParts[0]);
        if ($leaderSetCode !== null) {
            $deckObj->leader        = new stdClass();
            $deckObj->leader->id    = $leaderSetCode;
            $deckObj->leader->count = 1;
        }

        $baseSetCode = FindCardSetCode($deckNameParts[1]);
        if ($baseSetCode !== null) {
            $deckObj->base        = new stdClass();
            $deckObj->base->id    = $baseSetCode;
            $deckObj->base->count = 1;
        }
    }
}

// Walk every card category in the decklist
$categoryNodes = $xpath->query("//div[@class='decklist-category']");
foreach ($categoryNodes as $categoryNode) {
    $categoryTitleNodes = $xpath->query(".//div[@class='decklist-category-title']", $categoryNode);
    $categoryTitle = $categoryTitleNodes->length > 0 ? trim($categoryTitleNodes->item(0)->nodeValue) : '';

    // Leader / Base: only fill in if not already resolved from the title
    if ($categoryTitle === 'Leader (1)' || $categoryTitle === 'Base (1)') {
        $isLeader = ($categoryTitle === 'Leader (1)');
        $alreadySet = $isLeader
            ? (isset($deckObj->leader) && isset($deckObj->leader->id))
            : (isset($deckObj->base)   && isset($deckObj->base->id));

        if (!$alreadySet) {
            $cardNodes = $xpath->query(".//div[@class='decklist-record']", $categoryNode);
            if ($cardNodes->length > 0) {
                $nameNodes = $xpath->query(".//a[@class='decklist-record-name']", $cardNodes->item(0));
                if ($nameNodes->length > 0) {
                    $setCode = FindCardSetCode(trim($nameNodes->item(0)->nodeValue));
                    if ($setCode !== null) {
                        if ($isLeader) {
                            $deckObj->leader        = new stdClass();
                            $deckObj->leader->id    = $setCode;
                            $deckObj->leader->count = 1;
                        } else {
                            $deckObj->base        = new stdClass();
                            $deckObj->base->id    = $setCode;
                            $deckObj->base->count = 1;
                        }
                    }
                }
            }
        }
        continue;
    }

    // Main deck / sideboard cards
    $cardNodes = $xpath->query(".//div[@class='decklist-record']", $categoryNode);
    foreach ($cardNodes as $cardNode) {
        $quantityNodes = $xpath->query(".//span[@class='decklist-record-quantity']", $cardNode);
        $nameNodes     = $xpath->query(".//a[@class='decklist-record-name']", $cardNode);

        if ($quantityNodes->length === 0 || $nameNodes->length === 0) continue;

        $quantity = intval(trim($quantityNodes->item(0)->nodeValue));
        $cardName = trim($nameNodes->item(0)->nodeValue);
        $setCode  = FindCardSetCode($cardName);

        if ($setCode !== null) {
            $cardObj        = new stdClass();
            $cardObj->id    = $setCode;
            $cardObj->count = $quantity;

            if (stripos($categoryTitle, 'Sideboard') !== false) {
                $deckObj->sideboard[] = $cardObj;
            } else {
                $deckObj->deck[] = $cardObj;
            }
        }
    }
}

echo json_encode($deckObj);