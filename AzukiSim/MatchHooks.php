<?php
// AzukiSim adapter for the shared Core/Match rematch framework. Azuki currently exposes Bo1
// quick rematches only: both seats keep the exact deck they entered with and must opt in.
require_once __DIR__ . '/../Core/Match/Hooks.php';
require_once __DIR__ . '/CreateGame.php';

function AzukiResolveLobbyDecksForMatch($lobby) {
    $out = [];
    $seat = 1;
    foreach (($lobby->players ?? []) as $player) {
        $deckLink = trim(strval($player->getDeckLink()));
        $starter = strval($player->getPreconstructedDeck());
        $userID = method_exists($player, 'getUserId') ? $player->getUserId() : null;

        if ($deckLink !== '') {
            $resolved = AzukiResolveDeckInput($deckLink, $userID);
            if (empty($resolved['success'])) {
                SetFlashMessage('Deck error for player ' . $seat . ': ' . strval($resolved['message'] ?? 'Unable to resolve deck.'));
                return null;
            }
            $historyName = trim((string)CardName($resolved['leader'] ?? '')) . ' deck';
            if (preg_match('/^azukideck:(\d+)$/i', $deckLink, $deckMatch)) {
                $ownedDeck = AzukiDeckLoadOwnedDeck($deckMatch[1], $userID);
                $savedName = trim(strval($ownedDeck['assetName'] ?? ''));
                if ($savedName !== '') $historyName = $savedName;
            }
            if ($historyName === ' deck') $historyName = 'Imported deck';
        } else {
            $config = GetPreconstructedDeckConfig($starter);
            $resolved = [
                'success' => true,
                'leader' => strval($config['leader'] ?? ''),
                'gate' => strval($config['gate'] ?? ''),
                'mainDeck' => array_values($config['deckList'] ?? []),
            ];
            $historyName = strval($config['name'] ?? ($starter !== '' ? $starter : 'Starter deck')) . ' starter';
        }

        // These fields travel with originalDeck so rematches do not need to re-fetch remote/owned decks
        // and still attribute history/stats to the same user and deck entry.
        $resolved['_deckLink'] = $deckLink;
        $resolved['_userId'] = $userID;
        $resolved['_historyDeckName'] = $historyName;
        $resolved['_casterMode'] = !empty($lobby->casterMode);
        $out[$seat] = [
            'originalDeck' => $resolved,
            'authKey' => strval($player->getAuthKey()),
            'userId' => $userID,
            'deckLink' => $deckLink,
        ];
        ++$seat;
    }

    if (count($out) !== 2) {
        SetFlashMessage('Rematch games require two players.');
        return null;
    }
    return $out;
}

function AzukiValidateResolvedMatchDeck($deck, $format) {
    return is_array($deck)
        && !empty($deck['leader'])
        && !empty($deck['gate'])
        && isset($deck['mainDeck'])
        && is_array($deck['mainDeck']);
}

function AzukiSetupMatchGame($lobby, $opts = []) {
    $resolved = $opts['resolvedDecks'] ?? [];
    if (!empty($resolved[1]['_casterMode'])) $lobby->casterMode = true;
    return AzukiSetupGame($lobby, $opts);
}

MatchRegisterHooks('AzukiSim', [
    'resolveLobbyDecks' => 'AzukiResolveLobbyDecksForMatch',
    'validateDeck' => 'AzukiValidateResolvedMatchDeck',
    'setupGame' => 'AzukiSetupMatchGame',
    'queueTypes' => ['bo1'],
]);
