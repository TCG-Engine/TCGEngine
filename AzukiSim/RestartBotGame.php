<?php
header('Content-Type: application/json');
include_once __DIR__ . '/../Core/ViewerIdentity.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/NetworkingLibraries.php';
include_once __DIR__ . '/../APIs/Lobbies/Classes/Player.php';
include_once __DIR__ . '/CreateGame.php';

$gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($_POST['gameName'] ?? ''));
$requestPlayerID = strval($_POST['playerID'] ?? '');
$authKey = strval($_POST['authKey'] ?? '');
$viewerInfo = NormalizeViewerIdentity($requestPlayerID);
if ($viewerInfo['viewerID'] === '' || !SimGameValidateViewerAuth('AzukiSim', $gameName, $viewerInfo, $authKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid game authentication.']);
    exit;
}
if (intval($requestPlayerID) !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only the human player can restart this bot game.']);
    exit;
}

global $gameName;
ParseGamestate(__DIR__ . '/');
if (AzukiGameMode() !== 'rlbot' || AzukiGameOverWinner() === 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Play Again is only available after a bot game ends.']);
    exit;
}

$configKey = AzukiBotRematchCacheKey($gameName);
$cacheHit = false;
$config = ($configKey !== '' && function_exists('apcu_fetch')) ? apcu_fetch($configKey, $cacheHit) : null;
if (!$cacheHit || !is_array($config) || !is_array($config['decks'] ?? null)) {
    // Compatibility for bot games created before Play Again shipped. Saved AzukiDeck entries and
    // starter decks can be recovered authoritatively from the match-history snapshot.
    if (AzukiGameMode() === 'rlbot') {
        $seat = MatchHistorySeatSnapshot('AzukiSim', 1);
        $humanDeck = null;
        $deckID = intval($seat['deckID'] ?? 0);
        $userID = intval($seat['userID'] ?? 0);
        if ($deckID > 0) {
            $deckLink = 'azukideck:' . $deckID;
            $resolved = AzukiResolveDeckInput($deckLink, $userID > 0 ? $userID : null);
            if (!empty($resolved['success'])) {
                $resolved['_deckLink'] = $deckLink;
                $resolved['_preconstructedDeck'] = '';
                $resolved['_userId'] = $userID > 0 ? $userID : null;
                $resolved['_historyDeckName'] = strval($seat['deckName'] ?? '');
                $humanDeck = $resolved;
            }
        } else {
            $leader = strval($seat['keyCards'][0] ?? '');
            $gate = strval($seat['keyCards'][1] ?? '');
            foreach (['raizan', 'bobu', 'shao', 'zero', 'zerorl', 'boburl'] as $starterName) {
                $starterConfig = GetPreconstructedDeckConfig($starterName);
                if (strval($starterConfig['leader'] ?? '') !== $leader || strval($starterConfig['gate'] ?? '') !== $gate) continue;
                $humanDeck = [
                    'success' => true,
                    'leader' => $leader,
                    'gate' => $gate,
                    'mainDeck' => array_values($starterConfig['deckList'] ?? []),
                    '_deckLink' => '',
                    '_preconstructedDeck' => $starterName,
                    '_userId' => $userID > 0 ? $userID : null,
                    '_historyDeckName' => strval($seat['deckName'] ?? ''),
                ];
                break;
            }
        }

        $profileName = NormalizeAzukiRlBotProfile(DecisionQueueController::GetVariable('AzukiRlBotProfile'));
        $profile = GetAzukiRlBotProfile($profileName);
        $botStarter = strval($profile['deck'] ?? 'Raizan');
        $botConfig = GetPreconstructedDeckConfig($botStarter);
        if (is_array($humanDeck)) {
            $config = [
                'profile' => $profileName,
                'casterMode' => SimGameIsCasterMode('AzukiSim', $gameName),
                'decks' => [
                    1 => $humanDeck,
                    2 => [
                        'success' => true,
                        'leader' => strval($botConfig['leader'] ?? ''),
                        'gate' => strval($botConfig['gate'] ?? ''),
                        'mainDeck' => array_values($botConfig['deckList'] ?? []),
                        '_deckLink' => '',
                        '_preconstructedDeck' => $botStarter,
                        '_userId' => null,
                        '_historyDeckName' => strval($botConfig['name'] ?? $botStarter) . ' starter',
                    ],
                ],
            ];
            $cacheHit = function_exists('apcu_store') && apcu_store($configKey, $config, SIM_GAME_RECORD_CACHE_TTL);
        }
    }
}
if (!$cacheHit || !is_array($config) || !is_array($config['decks'] ?? null)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'This bot game can no longer be restarted.']);
    exit;
}

$resultKey = $configKey . ':result';
$resultHit = false;
$priorResult = function_exists('apcu_fetch') ? apcu_fetch($resultKey, $resultHit) : null;
if ($resultHit && is_array($priorResult) && !empty($priorResult['gameName'])) {
    echo json_encode($priorResult);
    exit;
}

$claimKey = $configKey . ':claim';
if (function_exists('apcu_add') && !apcu_add($claimKey, 1, 30)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'The new bot game is already being created.']);
    exit;
}

try {
    $profileName = NormalizeAzukiRlBotProfile($config['profile'] ?? 'raizan');
    $profile = GetAzukiRlBotProfile($profileName);
    $humanDeck = $config['decks'][1] ?? $config['decks']['1'] ?? null;
    $botDeck = $config['decks'][2] ?? $config['decks']['2'] ?? null;
    if (!is_array($humanDeck) || !is_array($botDeck)) throw new RuntimeException('The stored decks are unavailable.');

    $human = new Player(1, strval($humanDeck['_deckLink'] ?? ''), strval($humanDeck['_preconstructedDeck'] ?? ''), $humanDeck['_userId'] ?? null);
    $bot = new Player(2, '', strval($botDeck['_preconstructedDeck'] ?? ($profile['deck'] ?? 'Raizan')));
    $lobby = (object)[
        'numPlayers' => 2,
        'maxPlayers' => 2,
        'ready' => true,
        'id' => uniqid('rlbot_rematch_', true),
        'rootName' => 'AzukiSim',
        'format' => 'rlbot',
        'queueType' => 'bo1',
        'isPrivate' => true,
        'casterMode' => !empty($config['casterMode']),
        'isGoldfish' => true,
        'goldfishPlayers' => [2],
        'azukiRlBotPlayers' => [2],
        'azukiRlBotProfile' => $profileName,
        'players' => [$human, $bot],
    ];

    $newGameName = AzukiSetupGame($lobby, ['resolvedDecks' => [1 => $humanDeck, 2 => $botDeck]]);
    RegisterActiveGame('AzukiSim', strval($newGameName), true);
    RemoveActiveGame('AzukiSim', $gameName);
    $response = [
        'success' => true,
        'playerID' => 1,
        'authKey' => $human->getAuthKey(),
        'gameName' => strval($newGameName),
    ];
    if (function_exists('apcu_store')) apcu_store($resultKey, $response, 600);
    echo json_encode($response);
} catch (Throwable $e) {
    if (function_exists('apcu_delete')) apcu_delete($claimKey);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to create the new bot game.']);
}
