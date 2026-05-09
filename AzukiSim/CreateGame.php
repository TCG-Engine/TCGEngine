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

function LoadPlayer($playerID, $preconstructedDeck = 'Raizan') {
    $deck = &GetDeck($playerID);
    $garden = &GetGarden($playerID);
    $gate = &GetGate($playerID);

    // For AzukiSim, only Raizan starter deck is currently supported
    $deckName = 'Raizan';

    // Raizan (Leader) starts in the Garden.
    $leaderCard = new Garden('S1-STT01-001_Raizan_L_L_die');
    array_push($garden, $leaderCard);

    // Surge Gate
    $gateCard = new Gate('S1-STT01-002_Surge-Gate_G_G_die');
    array_push($gate, $gateCard);

    // Main deck - Raizan starter deck (50 cards)
    $deckList = [
        // 4x Alley Guy
        'S1-STT01-007_Alley-Guy_E_C_die',
        'S1-STT01-007_Alley-Guy_E_C_die',
        'S1-STT01-007_Alley-Guy_E_C_die',
        'S1-STT01-007_Alley-Guy_E_C_die',
        // 4x Alpine Prowler
        'S1-STT01-005_Alpine-Prowler_E_C_die',
        'S1-STT01-005_Alpine-Prowler_E_C_die',
        'S1-STT01-005_Alpine-Prowler_E_C_die',
        'S1-STT01-005_Alpine-Prowler_E_C_die',
        // 4x Black Jade Crewleader
        'S1-STT01-008_Black-Jade-Crewleader_E_UC_die',
        'S1-STT01-008_Black-Jade-Crewleader_E_UC_die',
        'S1-STT01-008_Black-Jade-Crewleader_E_UC_die',
        'S1-STT01-008_Black-Jade-Crewleader_E_UC_die',
        // 4x Black Jade Dagger
        'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        'S1-STT01-013_Black-Jade-Dagger_W_C_die',
        // 4x Black Jade Recruit
        'S1-STT01-004_Black-Jade-Recruit_E_C_die',
        'S1-STT01-004_Black-Jade-Recruit_E_C_die',
        'S1-STT01-004_Black-Jade-Recruit_E_C_die',
        'S1-STT01-004_Black-Jade-Recruit_E_C_die',
        // 4x Crate Rat Kurobo
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        'S1-STT01-003_Crate-Rat-Kurobo_E_C_die',
        // 3x Ikazuchi
        'S1-STT01-016_Ikazuchi_W_SR_die',
        'S1-STT01-016_Ikazuchi_W_SR_die',
        'S1-STT01-016_Ikazuchi_W_SR_die',
        // 3x Indra
        'S1-STT01-010_Indra_E_R_die',
        'S1-STT01-010_Indra_E_R_die',
        'S1-STT01-010_Indra_E_R_die',
        // 3x Lightning Orb
        'S1-STT01-017_Lightning-Orb_S_UC_die',
        'S1-STT01-017_Lightning-Orb_S_UC_die',
        'S1-STT01-017_Lightning-Orb_S_UC_die',
        // 3x Lightning Shuriken
        'S1-STT01-012_Lightning-Shuriken_W_C_die',
        'S1-STT01-012_Lightning-Shuriken_W_C_die',
        'S1-STT01-012_Lightning-Shuriken_W_C_die',
        // 3x Mastersmith Yamada
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
        'S1-STT01-009_Mastersmith-Yamada_E_UC_die',
        // 2x Silver Current Haruhi
        'S1-STT01-006_Silver-Current-Haruhi_E_R_die',
        'S1-STT01-006_Silver-Current-Haruhi_E_R_die',
        // 3x Tenraku
        'S1-STT01-015_Tenraku_W_UC_die',
        'S1-STT01-015_Tenraku_W_UC_die',
        'S1-STT01-015_Tenraku_W_UC_die',
        // 2x Tenshin
        'S1-STT01-014_Tenshin_W_C_die',
        'S1-STT01-014_Tenshin_W_C_die',
    ];

    for($i = 0; $i < count($deckList); ++$i) {
        $cardID = $deckList[$i];
        array_push($deck, new Deck($cardID));
    }
    EngineShuffle($deck, true);

    // Set leader health to 20
    $leaderHealth = &GetLeaderHealth($playerID);
    $leaderHealth = 20;
}

?>
