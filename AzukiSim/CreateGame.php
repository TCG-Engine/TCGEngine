<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/HTTPLibraries.php';
include_once __DIR__ . '/../APIKeys/APIKeys.php';

include_once __DIR__ . '/../Database/ConnectionManager.php';
include_once __DIR__ . '/../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../AccountFiles/AccountSessionAPI.php';

$ttl = 600;

// ASSUMES: $lobby
$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();
WriteGamestate(__DIR__ . "/");
ParseGamestate(__DIR__ . "/");

$playerCounter = 1;
foreach ($lobby->players as $player) {
    $player->setGamePlayerID($playerCounter);
    LoadPlayer($playerCounter, $player->getPreconstructedDeck());
    ++$playerCounter;
}

$firstPlayer = &GetFirstPlayer();
$firstPlayer = 1;
$turnPlayer = &GetTurnPlayer();
$turnPlayer = $firstPlayer;
$currentTurn = &GetTurnNumber();
$currentTurn = 1;

$currentPhase = &GetCurrentPhase();
$currentPhase = 'SOT';
SetPhaseParameters("-");

// Draw 7 cards for each player at game start
for ($p = 1; $p <= 2; ++$p) {
    $hand = &GetHand($p);
    $deck = &GetDeck($p);
    for ($i = 0; $i < 7; ++$i) {
        if (!empty($deck)) {
            $card = array_shift($deck);
            array_push($hand, $card);
        }
    }
}

// Set up starting resources
// Player 1 gets 1 IKZ (player 2 draws on their first turn)
GainIKZ(1, 1);

// Player 2 gets 1 IKZ + 1 one-use token
GainIKZ(2, 1);
$player2Token = &GetIKZToken(2);
$player2Token = 1;

// Advance to Main phase to start the game
AdvanceAndExecute("PASS");
AutoAdvanceAndExecute();

WriteGamestate(__DIR__ . "/");

$lobby->gameName = $gameName;

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

function LoadPlayer($playerID, $preconstructedDeck = 'Raizan') {
    $deck = &GetDeck($playerID);
    $garden = &GetGarden($playerID);
    $gate = &GetGate($playerID);

    $deckConfig = GetPreconstructedDeckConfig($preconstructedDeck);
    $deckName = $deckConfig['name'];

    // Raizan (Leader) starts in the Garden.
    $leaderCard = new Garden($deckConfig['leader']);
    array_push($garden, $leaderCard);

    // Surge Gate
    $gateCard = new Gate($deckConfig['gate']);
    array_push($gate, $gateCard);

    $deckList = $deckConfig['deckList'];

    for($i = 0; $i < count($deckList); ++$i) {
        $cardID = $deckList[$i];
        array_push($deck, new Deck($cardID));
    }
    EngineShuffle($deck, true);

    // Leader health is tracked via the leader card's Damage in Garden.
    // Keep LeaderHealth zone as a pass-button display value.
    $leaderHealth = &GetLeaderHealth($playerID);
    $leaderHealth = 'PASS';
}

?>
