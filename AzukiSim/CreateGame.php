<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/Custom/DeckImport.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../APIKeys/APIKeys.php';

include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';

function AzukiSetupGame($lobby, $opts = []) {
    global $gameName, $updateNumber;
    $ttl = 600;

    $gameName = GetGameCounter(__DIR__ . '/Games', createGameDirectory: !GamestateUsesMemoryStorage());
    InitializeGamestate();
    WriteGamestate(__DIR__ . "/");
    ParseGamestate(__DIR__ . "/");
    DecisionQueueController::StoreVariable('AzukiMatchGameName', strval($gameName));

    $azukiCreateMode = isset($lobby->format) ? strtolower(strval($lobby->format)) : '';
    if ($azukiCreateMode !== 'tutorial') {
        GameLogEnableForHumanSession($azukiCreateMode === 'rlbot' ? 'human_vs_bot' : 'human_pvp');
        GameLogBeginFrame();
    }
    if ($azukiCreateMode === 'rlbot') {
        DecisionQueueController::StoreVariable('GameMode', 'rlbot');
        $azukiRlBotProfile = NormalizeAzukiRlBotProfile($lobby->azukiRlBotProfile ?? 'raizan');
        DecisionQueueController::StoreVariable('AzukiRlBotProfile', $azukiRlBotProfile);
        if (function_exists('SetAzukiRlBotPlayers')) {
            SetAzukiRlBotPlayers(isset($lobby->azukiRlBotPlayers) && is_array($lobby->azukiRlBotPlayers) ? $lobby->azukiRlBotPlayers : [2]);
        } else {
            DecisionQueueController::StoreVariable('AzukiRlBotPlayers', [2]);
        }
    } else if ($azukiCreateMode === 'tutorial') {
        DecisionQueueController::StoreVariable('GameMode', 'tutorial');
    }

    $playerCounter = 1;
    foreach ($lobby->players as $player) {
        $player->setGamePlayerID($playerCounter);
        $injected = $opts['resolvedDecks'][$playerCounter] ?? null;
        $userID = method_exists($player, 'getUserId') ? $player->getUserId() : null;
        LoadPlayer($playerCounter, $player->getPreconstructedDeck(), $player->getDeckLink(), $userID, $injected);
        ++$playerCounter;
    }
    if ($azukiCreateMode === 'rlbot') {
        AzukiStoreBotRematchConfig($gameName, $lobby);
    }
    if ($azukiCreateMode !== 'tutorial') {
        GameLogEvent('shuffle', ['by' => 'p1', 'zone' => 'deck']);
        GameLogEvent('shuffle', ['by' => 'p2', 'zone' => 'deck']);
    }

    // The random-roll winner chooses whether to take the first or second turn.
    // Use the rolled player provisionally until that pregame decision resolves.
    $forcedFirstPlayer = intval($opts['forcedFirstPlayer'] ?? 0);
    $rollWinner = $azukiCreateMode === 'tutorial'
        ? 1
        : (in_array($forcedFirstPlayer, [1, 2], true) ? $forcedFirstPlayer : EngineRandomInt(1, 2));
    $firstPlayer = &GetFirstPlayer();
    $firstPlayer = $rollWinner;
    $turnPlayer = &GetTurnPlayer();
    $turnPlayer = $firstPlayer;
    $currentTurn = &GetTurnNumber();
    $currentTurn = 1;

    SetFlashMessage('');
    $currentPhase = &GetCurrentPhase();
    $currentPhase = 'SOT';
    SetPhaseParameters("-");

    if ($azukiCreateMode === 'tutorial') {
        AzukiTutorialSetupGame();
    } else {
        // Draw 7 cards for each player at game start
        for ($p = 1; $p <= 2; ++$p) {
            DrawOpeningHand($p);
        }
        GameLogEvent('random_roll', [
            'winner' => 'p' . $rollWinner,
        ]);
        QueueFirstPlayerChoice($rollWinner);

        // Advance to Main after the turn-order choice and both mulligans resolve.
        AdvanceAndExecute("PASS");
        AutoAdvanceAndExecute();
    }

    if ($azukiCreateMode !== 'tutorial') {
        GameLogCommitFrame($gameName, $updateNumber);
    }
    if (!empty($opts['matchId'])) {
        DecisionQueueController::StoreVariable('MatchId', strval($opts['matchId']));
        DecisionQueueController::StoreVariable('GameNumber', strval(intval($opts['gameNumber'] ?? 1)));
    }
    if (function_exists('SimHistoryInitialize')) SimHistoryInitialize('Game start');
    WriteGamestate(__DIR__ . "/");

    $lobby->gameName = $gameName;
    if (!SimGameWriteAuthKeysFromLobby('AzukiSim', $gameName, $lobby)) {
        throw new RuntimeException('Unable to store game authentication metadata in APCu.');
    }
    return $gameName;
}

// Backward-compatible lobby entrypoint. MatchHooks includes this file as a library and calls
// AzukiSetupGame directly when it needs to create a rematch child game.
if (!defined('AZUKISIM_CREATEGAME_LIBRARY_ONLY') && isset($lobby) && is_object($lobby)) {
    AzukiSetupGame($lobby);
}

function GetPreconstructedDeckConfig($deckName) {
    $normalized = is_string($deckName) ? trim(strtolower($deckName)) : '';
    if($normalized === 'bobu') {
        return [
            'name' => 'Bobu',
            'leader' => 'S1-STT03-001_Bobu_L_L_die',
            'gate' => 'S1-STT03-002_Stonehaven-Gate_G_G_die',
            'deckList' => [
                'S1-STT03-003_Koyama-Farm-Potter_E_C_die',
                'S1-STT03-004_Sloth-Scarecrow_E_C_die',
                'S1-STT03-005_Wobbly-Cabbage-Cart_E_C_die',
                'S1-STT03-006_Cactus-Farmer_E_UC_die',
                'S1-STT03-007_Koyama-Farm-Caretaker_E_R_die',
                'S1-STT03-008_Midnight-Courier_E_C_die',
                'S1-STT03-009_Warding-Totem_E_UC_die',
                'S1-STT03-010_Shroommancer_E_C_die',
                'S1-STT03-011_Koyama-Farm-Plowman_E_C_die',
                'S1-STT03-012_Miharu-of-the-White-Bloom_E_SR_die',
                'S1-STT03-013_Stone-Masked-Ancient_E_SR_die',
                'S1-STT03-014_Sandcoil-Python_E_UC_die',
                'S1-STT03-015_Jar-of-Beans_S_UC_die',
                'S1-STT03-016_Quicksand_S_R_die',
                'S1-STT03-017_Sprout-of-Fortune_S_C_die',
            ],
        ];
    }

    if($normalized === 'shao') {
        return [
            'name' => 'Shao',
            'leader' => 'S1-STT02-001_Shao_L_L_die',
            'gate' => 'S1-STT02-002_Hydromancy-Gate_G_G_die',
            'deckList' => [
                'S1-STT02-003_Hayabusa-Itto_E_C_die','S1-STT02-003_Hayabusa-Itto_E_C_die','S1-STT02-003_Hayabusa-Itto_E_C_die','S1-STT02-003_Hayabusa-Itto_E_C_die',
                'S1-STT02-004_Rei_E_C_die','S1-STT02-004_Rei_E_C_die','S1-STT02-004_Rei_E_C_die','S1-STT02-004_Rei_E_C_die',
                'S1-STT02-005_Hayabusa-Saburo_E_UC_die','S1-STT02-005_Hayabusa-Saburo_E_UC_die','S1-STT02-005_Hayabusa-Saburo_E_UC_die',
                'S1-STT02-006_Foamback-Crab_E_C_die','S1-STT02-006_Foamback-Crab_E_C_die','S1-STT02-006_Foamback-Crab_E_C_die','S1-STT02-006_Foamback-Crab_E_C_die',
                'S1-STT02-007_Benzai-the-Merchant_E_C_die','S1-STT02-007_Benzai-the-Merchant_E_C_die','S1-STT02-007_Benzai-the-Merchant_E_C_die','S1-STT02-007_Benzai-the-Merchant_E_C_die',
                'S1-STT02-008_Serene-Fist-Misaki_E_UC_die','S1-STT02-008_Serene-Fist-Misaki_E_UC_die','S1-STT02-008_Serene-Fist-Misaki_E_UC_die',
                'S1-STT02-009_Aya_E_C_die','S1-STT02-009_Aya_E_C_die','S1-STT02-009_Aya_E_C_die','S1-STT02-009_Aya_E_C_die',
                'S1-STT02-010_Selis-of-the-Shore_E_R_die','S1-STT02-010_Selis-of-the-Shore_E_R_die',
                'S1-STT02-011_Bubblemancer_E_C_die','S1-STT02-011_Bubblemancer_E_C_die','S1-STT02-011_Bubblemancer_E_C_die','S1-STT02-011_Bubblemancer_E_C_die',
                'S1-STT02-012_Young-Shao_E_UC_die','S1-STT02-012_Young-Shao_E_UC_die','S1-STT02-012_Young-Shao_E_UC_die',
                'S1-STT02-013_Mizuki_E_SR_die','S1-STT02-013_Mizuki_E_SR_die','S1-STT02-013_Mizuki_E_SR_die',
                'S1-STT02-014_Chilling-Water_S_C_die','S1-STT02-014_Chilling-Water_S_C_die','S1-STT02-014_Chilling-Water_S_C_die','S1-STT02-014_Chilling-Water_S_C_die',
                'S1-STT02-015_Commune-with-Water_S_UC_die','S1-STT02-015_Commune-with-Water_S_UC_die','S1-STT02-015_Commune-with-Water_S_UC_die',
                'S1-STT02-016_Water-Orb_S_R_die','S1-STT02-016_Water-Orb_S_R_die',
                'S1-STT02-017_Shaos-Perseverance_S_SR_die','S1-STT02-017_Shaos-Perseverance_S_SR_die','S1-STT02-017_Shaos-Perseverance_S_SR_die',
            ],
        ];
    }

    if($normalized === 'zero') {
        return [
            'name' => 'Zero',
            'leader' => 'S1-STT04-001_Zero_L_L_die',
            'gate' => 'S1-STT04-002_Ragefire-Gate_G_G_die',
            'deckList' => [
                'S1-STT04-003_Cinderwake-Seer_E_UC_die',
                'S1-STT04-004_Fanatic-Kindler_E_C_die',
                'S1-STT04-005_Ruby_E_C_die',
                'S1-STT04-006_Wolf-Cub_E_C_die',
                'S1-STT04-007_Enraged-Howler_E_C_die',
                'S1-STT04-008_Lady-Emberheart_E_UC_die',
                'S1-STT04-009_Cinderwake-Ritualist_E_R_die',
                'S1-STT04-010_Reckless-Tinkerer_E_C_die',
                'S1-STT04-011_Scorchland-Raven_E_C_die',
                'S1-STT04-012_Spiteful-Raider_E_UC_die',
                'S1-STT04-013_Kurai-the-Volcano_E_SR_die',
                'S1-STT04-014_Scorchveil-Shinobi-Suzuka_E_SR_die',
                'S1-STT04-015_Detonation-Pact_S_C_die',
                'S1-STT04-016_Collateral-Burst_S_UC_die',
                'S1-STT04-017_Wrath-of-Sinder_S_R_die',
            ],
        ];
    }

    // The custom Zero list used for the Deck 51 self-play policy. Keep this
    // local so playable bot games do not depend on the training deck endpoint.
    if($normalized === 'zerorl') {
        return [
            'name' => 'Zero (Deck 51)',
            'leader' => 'S1-STT04-001_Zero_L_L_die',
            'gate' => 'S1-AZK01-122_Rushfire-Gate_G_G_die',
            'deckList' => [
                'S1-AZK01-004_Alley-Thug_E_C_die','S1-AZK01-004_Alley-Thug_E_C_die','S1-AZK01-004_Alley-Thug_E_C_die','S1-AZK01-004_Alley-Thug_E_C_die',
                'S1-AZK01-059_Spice_E_UC_die','S1-AZK01-059_Spice_E_UC_die','S1-AZK01-059_Spice_E_UC_die','S1-AZK01-059_Spice_E_UC_die',
                'S1-STT04-015_Detonation-Pact_S_C_die','S1-STT04-015_Detonation-Pact_S_C_die','S1-STT04-015_Detonation-Pact_S_C_die','S1-STT04-015_Detonation-Pact_S_C_die',
                'S1-STT04-005_Ruby_E_C_die','S1-STT04-005_Ruby_E_C_die','S1-STT04-005_Ruby_E_C_die','S1-STT04-005_Ruby_E_C_die',
                'S1-AZK01-058_Black-Jade-Warlord_E_C_die','S1-AZK01-058_Black-Jade-Warlord_E_C_die','S1-AZK01-058_Black-Jade-Warlord_E_C_die','S1-AZK01-058_Black-Jade-Warlord_E_C_die',
                'S1-STT04-003_Cinderwake-Seer_E_UC_die','S1-STT04-003_Cinderwake-Seer_E_UC_die','S1-STT04-003_Cinderwake-Seer_E_UC_die','S1-STT04-003_Cinderwake-Seer_E_UC_die',
                'S1-AZK01-116_Tenmoku-Daiki_E_R_die','S1-AZK01-116_Tenmoku-Daiki_E_R_die','S1-AZK01-116_Tenmoku-Daiki_E_R_die',
                'S1-STT04-016_Collateral-Burst_S_UC_die','S1-STT04-016_Collateral-Burst_S_UC_die','S1-STT04-016_Collateral-Burst_S_UC_die','S1-STT04-016_Collateral-Burst_S_UC_die',
                'S1-AZK01-060_Scarlett_E_R_die','S1-AZK01-060_Scarlett_E_R_die','S1-AZK01-060_Scarlett_E_R_die',
                'S1-AZK01-062_Pekiro_E_R_die','S1-AZK01-062_Pekiro_E_R_die','S1-AZK01-062_Pekiro_E_R_die','S1-AZK01-062_Pekiro_E_R_die',
                'S1-AZK01-056_Glass-Blower-Hokuto_E_C_die','S1-AZK01-056_Glass-Blower-Hokuto_E_C_die','S1-AZK01-056_Glass-Blower-Hokuto_E_C_die','S1-AZK01-056_Glass-Blower-Hokuto_E_C_die',
                'S1-STT04-004_Fanatic-Kindler_E_C_die','S1-STT04-004_Fanatic-Kindler_E_C_die','S1-STT04-004_Fanatic-Kindler_E_C_die','S1-STT04-004_Fanatic-Kindler_E_C_die',
                'S1-AZK01-065_Fire-Orb_S_C_die','S1-AZK01-065_Fire-Orb_S_C_die','S1-AZK01-065_Fire-Orb_S_C_die','S1-AZK01-065_Fire-Orb_S_C_die',
            ],
        ];
    }

    // The custom Bobu list from AzukiDeck 241. Keep the published bot deck
    // local so live games do not depend on the editor API or deck ownership.
    if($normalized === 'boburl') {
        return [
            'name' => 'Bobu (Deck 241)',
            'leader' => 'S1-STT03-001_Bobu_L_L_die',
            'gate' => 'S1-STT03-002_Stonehaven-Gate_G_G_die',
            'deckList' => [
                'S1-AZK01-047_Shiko-the-Priestess_E_UC_die','S1-AZK01-047_Shiko-the-Priestess_E_UC_die','S1-AZK01-047_Shiko-the-Priestess_E_UC_die','S1-AZK01-047_Shiko-the-Priestess_E_UC_die',
                'S1-AZK01-045_Treetop-Scout_E_C_die',
                'S1-AZK01-050_Shroom-Tender_E_R_die','S1-AZK01-050_Shroom-Tender_E_R_die','S1-AZK01-050_Shroom-Tender_E_R_die','S1-AZK01-050_Shroom-Tender_E_R_die',
                'S1-STT03-009_Warding-Totem_E_UC_die','S1-STT03-009_Warding-Totem_E_UC_die','S1-STT03-009_Warding-Totem_E_UC_die','S1-STT03-009_Warding-Totem_E_UC_die',
                'S1-STT03-003_Koyama-Farm-Potter_E_C_die','S1-STT03-003_Koyama-Farm-Potter_E_C_die','S1-STT03-003_Koyama-Farm-Potter_E_C_die','S1-STT03-003_Koyama-Farm-Potter_E_C_die',
                'S1-STT03-016_Quicksand_S_R_die','S1-STT03-016_Quicksand_S_R_die','S1-STT03-016_Quicksand_S_R_die',
                'S1-STT03-013_Stone-Masked-Ancient_E_SR_die','S1-STT03-013_Stone-Masked-Ancient_E_SR_die','S1-STT03-013_Stone-Masked-Ancient_E_SR_die','S1-STT03-013_Stone-Masked-Ancient_E_SR_die',
                'S1-STT03-014_Sandcoil-Python_E_UC_die','S1-STT03-014_Sandcoil-Python_E_UC_die','S1-STT03-014_Sandcoil-Python_E_UC_die',
                'S1-STT03-011_Koyama-Farm-Plowman_E_C_die','S1-STT03-011_Koyama-Farm-Plowman_E_C_die','S1-STT03-011_Koyama-Farm-Plowman_E_C_die','S1-STT03-011_Koyama-Farm-Plowman_E_C_die',
                'S1-AZK01-069_Link_E_C_die','S1-AZK01-069_Link_E_C_die','S1-AZK01-069_Link_E_C_die','S1-AZK01-069_Link_E_C_die',
                'S1-AZK01-002_Healing-Flutter_S_UC_die','S1-AZK01-002_Healing-Flutter_S_UC_die','S1-AZK01-002_Healing-Flutter_S_UC_die','S1-AZK01-002_Healing-Flutter_S_UC_die',
                'S1-AZK01-067_Frida_E_C_die','S1-AZK01-067_Frida_E_C_die','S1-AZK01-067_Frida_E_C_die',
                'S1-AZK01-048_Kale_E_C_die','S1-AZK01-048_Kale_E_C_die','S1-AZK01-048_Kale_E_C_die','S1-AZK01-048_Kale_E_C_die',
                'S1-STT03-012_Miharu-of-the-White-Bloom_E_SR_die','S1-STT03-012_Miharu-of-the-White-Bloom_E_SR_die','S1-STT03-012_Miharu-of-the-White-Bloom_E_SR_die','S1-STT03-012_Miharu-of-the-White-Bloom_E_SR_die',
            ],
        ];
    }

    // The custom Raizan list from AzukiDeck 373. Keep the published bot deck
    // local so live games do not depend on the editor API or deck ownership.
    if($normalized === 'raizanrl') {
        return [
            'name' => 'Raizan (Deck 373)',
            'leader' => 'S1-STT01-001_Raizan_L_L_die',
            'gate' => 'S1-STT01-002_Surge-Gate_G_G_die',
            'deckList' => [
                'S1-STT01-013_Black-Jade-Dagger_W_C_die','S1-STT01-013_Black-Jade-Dagger_W_C_die','S1-STT01-013_Black-Jade-Dagger_W_C_die','S1-STT01-013_Black-Jade-Dagger_W_C_die',
                'S1-STT01-004_Black-Jade-Recruit_E_C_die','S1-STT01-004_Black-Jade-Recruit_E_C_die','S1-STT01-004_Black-Jade-Recruit_E_C_die','S1-STT01-004_Black-Jade-Recruit_E_C_die',
                'S1-STT01-003_Crate-Rat-Kurobo_E_C_die','S1-STT01-003_Crate-Rat-Kurobo_E_C_die','S1-STT01-003_Crate-Rat-Kurobo_E_C_die','S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
                'S1-AZK01-094_Hidden-Dagger_W_C_die','S1-AZK01-094_Hidden-Dagger_W_C_die','S1-AZK01-094_Hidden-Dagger_W_C_die','S1-AZK01-094_Hidden-Dagger_W_C_die',
                'S1-STT01-017_Lightning-Orb_S_UC_die','S1-STT01-017_Lightning-Orb_S_UC_die','S1-STT01-017_Lightning-Orb_S_UC_die','S1-STT01-017_Lightning-Orb_S_UC_die',
                'S1-STT01-006_Silver-Current-Haruhi_E_R_die','S1-STT01-006_Silver-Current-Haruhi_E_R_die','S1-STT01-006_Silver-Current-Haruhi_E_R_die','S1-STT01-006_Silver-Current-Haruhi_E_R_die',
                'S1-STT01-014_Tenshin_W_C_die','S1-STT01-014_Tenshin_W_C_die','S1-STT01-014_Tenshin_W_C_die','S1-STT01-014_Tenshin_W_C_die',
                'S1-STT01-008_Black-Jade-Crewleader_E_UC_die','S1-STT01-008_Black-Jade-Crewleader_E_UC_die','S1-STT01-008_Black-Jade-Crewleader_E_UC_die','S1-STT01-008_Black-Jade-Crewleader_E_UC_die',
                'S1-STT01-005_Alpine-Prowler_E_C_die','S1-STT01-005_Alpine-Prowler_E_C_die',
                'S1-AZK01-011_Rooftop-Hunter_E_C_die','S1-AZK01-011_Rooftop-Hunter_E_C_die',
                'S1-AZK01-077_Stalking-Assassin_E_C_die','S1-AZK01-077_Stalking-Assassin_E_C_die','S1-AZK01-077_Stalking-Assassin_E_C_die',
                'S1-AZK01-015_Mo_E_SR_die','S1-AZK01-015_Mo_E_SR_die','S1-AZK01-015_Mo_E_SR_die','S1-AZK01-015_Mo_E_SR_die',
                'S1-AZK01-042_Thunderclap_S_SR_die','S1-AZK01-042_Thunderclap_S_SR_die',
                'S1-STT01-012_Lightning-Shuriken_W_C_die','S1-STT01-012_Lightning-Shuriken_W_C_die','S1-STT01-012_Lightning-Shuriken_W_C_die',
                'S1-AZK01-127_Sundering-Strike_S_UC_die','S1-AZK01-127_Sundering-Strike_S_UC_die',
            ],
        ];
    }

    return [
        'name' => 'Raizan',
        'leader' => 'S1-STT01-001_Raizan_L_L_die',
        'gate' => 'S1-STT01-002_Surge-Gate_G_G_die',
        'deckList' => [
            'S1-STT01-007_Alley-Guy_E_C_die','S1-STT01-007_Alley-Guy_E_C_die','S1-STT01-007_Alley-Guy_E_C_die','S1-STT01-007_Alley-Guy_E_C_die',
            'S1-STT01-005_Alpine-Prowler_E_C_die','S1-STT01-005_Alpine-Prowler_E_C_die','S1-STT01-005_Alpine-Prowler_E_C_die','S1-STT01-005_Alpine-Prowler_E_C_die',
            'S1-STT01-008_Black-Jade-Crewleader_E_UC_die','S1-STT01-008_Black-Jade-Crewleader_E_UC_die','S1-STT01-008_Black-Jade-Crewleader_E_UC_die','S1-STT01-008_Black-Jade-Crewleader_E_UC_die',
            'S1-STT01-013_Black-Jade-Dagger_W_C_die','S1-STT01-013_Black-Jade-Dagger_W_C_die','S1-STT01-013_Black-Jade-Dagger_W_C_die','S1-STT01-013_Black-Jade-Dagger_W_C_die',
            'S1-STT01-004_Black-Jade-Recruit_E_C_die','S1-STT01-004_Black-Jade-Recruit_E_C_die','S1-STT01-004_Black-Jade-Recruit_E_C_die','S1-STT01-004_Black-Jade-Recruit_E_C_die',
            'S1-STT01-003_Crate-Rat-Kurobo_E_C_die','S1-STT01-003_Crate-Rat-Kurobo_E_C_die','S1-STT01-003_Crate-Rat-Kurobo_E_C_die','S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
            'S1-STT01-016_Ikazuchi_W_SR_die','S1-STT01-016_Ikazuchi_W_SR_die','S1-STT01-016_Ikazuchi_W_SR_die',
            'S1-STT01-010_Indra_E_R_die','S1-STT01-010_Indra_E_R_die','S1-STT01-010_Indra_E_R_die',
            'S1-STT01-017_Lightning-Orb_S_UC_die','S1-STT01-017_Lightning-Orb_S_UC_die','S1-STT01-017_Lightning-Orb_S_UC_die',
            'S1-STT01-012_Lightning-Shuriken_W_C_die','S1-STT01-012_Lightning-Shuriken_W_C_die','S1-STT01-012_Lightning-Shuriken_W_C_die',
            'S1-STT01-009_Mastersmith-Yamada_E_UC_die','S1-STT01-009_Mastersmith-Yamada_E_UC_die','S1-STT01-009_Mastersmith-Yamada_E_UC_die',
            'S1-STT01-006_Silver-Current-Haruhi_E_R_die','S1-STT01-006_Silver-Current-Haruhi_E_R_die',
            'S1-STT01-015_Tenraku_W_UC_die','S1-STT01-015_Tenraku_W_UC_die','S1-STT01-015_Tenraku_W_UC_die',
            'S1-STT01-014_Tenshin_W_C_die','S1-STT01-014_Tenshin_W_C_die',
        ],
    ];
}

function LoadPlayer($playerID, $preconstructedDeck = 'Raizan', $deckLink = '', $userID = null, $injectedDeck = null) {
    $deck = &GetDeck($playerID);
    $garden = &GetGarden($playerID);
    $gate = &GetGate($playerID);

    $resolvedDeck = is_array($injectedDeck) && !empty($injectedDeck['leader'])
        && !empty($injectedDeck['gate']) && isset($injectedDeck['mainDeck'])
        ? $injectedDeck
        : null;
    if ($resolvedDeck !== null) {
        $deckLink = trim(strval($resolvedDeck['_deckLink'] ?? $deckLink));
        $userID = $resolvedDeck['_userId'] ?? $userID;
    }
    $deckLink = trim((string)$deckLink);
    if ($resolvedDeck === null && $deckLink !== '' && function_exists('AzukiResolveDeckInput')) {
        try {
            $candidateDeck = AzukiResolveDeckInput($deckLink, $userID);
            if (!empty($candidateDeck['success'])) {
                $resolvedDeck = $candidateDeck;
            } else {
                error_log('AzukiSim deck import fallback to starter deck: ' . ($candidateDeck['message'] ?? 'unknown error'));
            }
        } catch (Throwable $e) {
            error_log('AzukiSim deck import failed during game creation: ' . $e->getMessage());
        }
    }

    if ($resolvedDeck !== null) {
        $leaderCard = new Garden($resolvedDeck['leader']);
        NormalizeStartingGardenCard($leaderCard, $playerID);
        array_push($garden, $leaderCard);

        $gateCard = new Gate($resolvedDeck['gate']);
        NormalizeStartingGateCard($gateCard, $playerID);
        array_push($gate, $gateCard);

        $deckList = $resolvedDeck['mainDeck'];
        AzukiStatsCaptureDeck($playerID, $deckLink, $deckList);
        $historyDeckName = trim(strval($resolvedDeck['_historyDeckName'] ?? ''));
        if($historyDeckName === '') $historyDeckName = trim((string)CardName($resolvedDeck['leader'])) . ' deck';
        if(!isset($resolvedDeck['_historyDeckName']) && preg_match('/^azukideck:(\d+)$/i', $deckLink, $historyDeckMatch)) {
            $historyDeck = AzukiDeckLoadOwnedDeck($historyDeckMatch[1], $userID);
            $savedDeckName = trim((string)($historyDeck['assetName'] ?? ''));
            if($savedDeckName !== '') $historyDeckName = $savedDeckName;
        }
        if($historyDeckName === ' deck') $historyDeckName = 'Imported deck';
    } else {
        $deckConfig = GetPreconstructedDeckConfig($preconstructedDeck);

        // Leader starts in the Garden.
        $leaderCard = new Garden($deckConfig['leader']);
        NormalizeStartingGardenCard($leaderCard, $playerID);
        array_push($garden, $leaderCard);

        $gateCard = new Gate($deckConfig['gate']);
        NormalizeStartingGateCard($gateCard, $playerID);
        array_push($gate, $gateCard);

        $deckList = $deckConfig['deckList'];
        $historyDeckName = strval($deckConfig['name'] ?? $preconstructedDeck ?: 'Starter deck') . ' starter';
    }

    AzukiMatchHistoryCaptureSeat(
        $playerID,
        $userID,
        $deckLink,
        $historyDeckName,
        strval($garden[0]->CardID ?? ''),
        strval($gate[0]->CardID ?? '')
    );

    for($i = 0; $i < count($deckList); ++$i) {
        $cardID = $deckList[$i];
        array_push($deck, new Deck($cardID));
    }
    if(!empty($GLOBALS['bridgeDeterministicDeckShuffle'])) {
        AzukiDeterministicStartingDeckShuffle($deck, $playerID);
    } else {
        EngineShuffle($deck, true);
    }

    // Leader health is tracked via the leader card's Damage in Garden.
    // Keep LeaderHealth zone as a pass-button display value.
    $leaderHealth = &GetLeaderHealth($playerID);
    $leaderHealth = 'PASS';
}

function AzukiLoadedDeckSnapshot($playerID, $player = null) {
    $deck = &GetDeck($playerID);
    $garden = &GetGarden($playerID);
    $gate = &GetGate($playerID);
    $deckLink = is_object($player) && method_exists($player, 'getDeckLink') ? trim(strval($player->getDeckLink())) : '';
    $userID = is_object($player) && method_exists($player, 'getUserId') ? $player->getUserId() : null;
    $starter = is_object($player) && method_exists($player, 'getPreconstructedDeck') ? strval($player->getPreconstructedDeck()) : '';
    $mainDeck = [];
    foreach ($deck as $card) {
        $cardID = is_object($card) ? strval($card->CardID ?? '') : strval($card);
        if ($cardID !== '') $mainDeck[] = $cardID;
    }

    $historyName = '';
    if ($deckLink === '') {
        $config = GetPreconstructedDeckConfig($starter);
        $historyName = strval($config['name'] ?? ($starter !== '' ? $starter : 'Starter deck')) . ' starter';
    } else if (preg_match('/^azukideck:(\d+)$/i', $deckLink, $deckMatch)) {
        $ownedDeck = AzukiDeckLoadOwnedDeck($deckMatch[1], $userID);
        $historyName = trim(strval($ownedDeck['assetName'] ?? ''));
    }

    return [
        'success' => true,
        'leader' => strval($garden[0]->CardID ?? ''),
        'gate' => strval($gate[0]->CardID ?? ''),
        'mainDeck' => $mainDeck,
        '_deckLink' => $deckLink,
        '_preconstructedDeck' => $starter,
        '_userId' => $userID,
        '_historyDeckName' => $historyName,
    ];
}

function AzukiBotRematchCacheKey($gameName) {
    $gameName = preg_replace('/[^A-Za-z0-9_]/', '', strval($gameName));
    return $gameName === '' ? '' : 'tcgengine:azuki-bot-rematch:' . $gameName;
}

function AzukiStoreBotRematchConfig($gameName, $lobby) {
    if (!function_exists('apcu_store') || !is_object($lobby)) return false;
    $key = AzukiBotRematchCacheKey($gameName);
    if ($key === '') return false;
    $players = is_array($lobby->players ?? null) ? $lobby->players : [];
    if (count($players) < 2) return false;

    return apcu_store($key, [
        'profile' => NormalizeAzukiRlBotProfile($lobby->azukiRlBotProfile ?? 'raizan'),
        'casterMode' => !empty($lobby->casterMode),
        'decks' => [
            1 => AzukiLoadedDeckSnapshot(1, $players[0]),
            2 => AzukiLoadedDeckSnapshot(2, $players[1]),
        ],
    ], SIM_GAME_RECORD_CACHE_TTL);
}

function AzukiDeterministicStartingDeckShuffle(&$deck, $playerID) {
    if(!is_array($deck)) return;
    $seed = intval($GLOBALS['bridgeDeterministicDeckShuffleSeed'] ?? GetDeterministicRandomCounter());
    for($i = count($deck) - 1; $i > 0; --$i) {
        $ids = [];
        for($j = 0; $j <= $i; ++$j) {
            $ids[] = is_object($deck[$j] ?? null) ? strval($deck[$j]->CardID ?? '') : '';
        }
        $bytes = hash('sha256', $seed . '|' . intval($playerID) . '|' . $i . '|' . implode(',', $ids), true);
        $value = unpack('N', substr($bytes, 0, 4))[1];
        $swapIndex = $value % ($i + 1);
        if($swapIndex === $i) continue;

        $tmp = $deck[$i];
        $deck[$i] = $deck[$swapIndex];
        $deck[$swapIndex] = $tmp;
    }
}

function NormalizeStartingGardenCard(&$card, $playerID) {
    if(!is_object($card)) return;
    $card->Status = 2;
    $card->Owner = intval($playerID);
    $card->Damage = 0;
    $card->Controller = intval($playerID);
    if(!isset($card->TurnEffects) || !is_array($card->TurnEffects)) $card->TurnEffects = [];
    if(!isset($card->Counters) || !is_array($card->Counters)) $card->Counters = [];
    if(!isset($card->Subcards) || !is_array($card->Subcards)) $card->Subcards = [];
}

function NormalizeStartingGateCard(&$card, $playerID) {
    if(!is_object($card)) return;
    $card->Status = 2;
    $card->Owner = intval($playerID);
    $card->Controller = intval($playerID);
    if(!isset($card->TurnEffects) || !is_array($card->TurnEffects)) $card->TurnEffects = [];
    if(!isset($card->Counters) || !is_array($card->Counters)) $card->Counters = [];
}

?>
