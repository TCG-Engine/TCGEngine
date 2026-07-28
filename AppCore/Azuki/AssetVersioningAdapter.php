<?php

require_once __DIR__ . '/../../Core/Versioning/AssetVersioningCapability.php';
require_once __DIR__ . '/../../AzukiDeck/DeckService.php';

function AzukiAssetVersioningEnsureCardNames() {
    if(function_exists('CardName')) return;
    // Generated dictionaries assign their lookup arrays in the scope where they
    // are included. Promote the one used by CardName() to global scope when the
    // version API lazily loads the dictionary.
    global $nameData;
    require_once __DIR__ . '/../../AzukiSim/GeneratedCode/GeneratedCardDictionaries.php';
}

function CreateAzukiAssetVersioningAdapter() {
    return [
        'appKey' => 'AzukiDeck',
        'assetType' => 1,
        'enabled' => true,

        'snapshot' => function($assetID) {
            $deckState = AzukiDeckReadDeckState($assetID);
            if(!is_array($deckState) || empty($deckState['success'])) return null;
            $leader = trim((string)($deckState['leader'] ?? ''));
            $gate = trim((string)($deckState['gate'] ?? ''));
            $mainDeck = (array)($deckState['mainDeck'] ?? []);
            if($leader === '' || $gate === '' || empty($mainDeck)) return null;

            $counts = [];
            foreach($mainDeck as $cardID) {
                $cardID = trim((string)$cardID);
                if($cardID === '') continue;
                $counts[$cardID] = intval($counts[$cardID] ?? 0) + 1;
            }
            if(empty($counts)) return null;

            return [
                'identities' => ['leader' => $leader, 'gate' => $gate],
                'zones' => ['mainDeck' => $counts]
            ];
        },

        'applySnapshot' => function($assetID, $playerID, $snapshot) {
            $leaderID = trim((string)($snapshot['identities']['leader'] ?? ''));
            $gateID = trim((string)($snapshot['identities']['gate'] ?? ''));
            $mainCounts = (array)($snapshot['zones']['mainDeck'] ?? []);
            if($leaderID === '' || $gateID === '' || empty($mainCounts)) return false;

            $leader = &GetLeader($playerID);
            $gate = &GetGate($playerID);
            $mainDeck = &GetMainDeck($playerID);
            $leader = [new Leader($leaderID, 'Leader', $playerID, 0)];
            $gate = [new Gate($gateID, 'Gate', $playerID, 0)];
            $mainDeck = [];
            foreach($mainCounts as $cardID => $quantity) {
                for($i = 0; $i < intval($quantity); ++$i) {
                    $mainDeck[] = new MainDeck(
                        $cardID,
                        'MainDeck',
                        $playerID,
                        count($mainDeck)
                    );
                }
            }
            return true;
        },

        'authorize' => function($assetID, $userID, $action) {
            return AzukiDeckLoadOwnedDeck($assetID, $userID) !== null;
        },

        'describeItem' => function($itemID) {
            AzukiAssetVersioningEnsureCardNames();
            return function_exists('CardName') ? CardName($itemID) : $itemID;
        }
    ];
}

?>
