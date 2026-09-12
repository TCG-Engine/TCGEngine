<?php

include_once __DIR__ . '/GamestateParser.php';
include_once __DIR__ . '/ZoneAccessors.php';
include_once __DIR__ . '/ZoneClasses.php';
include_once __DIR__ . '/GeneratedCode/GeneratedCardDictionaries.php';
include_once __DIR__ . '/Custom/DeckImport.php';
include_once __DIR__ . '/BotDeck.php';
include_once __DIR__ . '/Custom/GameLogic.php';
include_once __DIR__ . '/TurnController.php';
include_once __DIR__ . '/../Core/CoreZoneModifiers.php';
include_once __DIR__ . '/../Core/GameAuth.php';
include_once __DIR__ . '/../FaBDeck/DeckService.php';

$gameName = GetGameCounter(__DIR__ . '/Games');
InitializeGamestate();
if (($lobby->format ?? '') === 'upf' && count($lobby->players) !== 4) throw new RuntimeException('UPF requires four players.');
if (count($lobby->players) < 2 || count($lobby->players) > 4) throw new RuntimeException('FaBSim requires two to four players.');
// Identity lookup during opening draws must already know every seat.
SetSeatOrder(implode('', range(1, count($lobby->players))));
SetLiveSeats(GetSeatOrder());
$playerNumber = 1;
$passiveSeats = [];
$botProfiles = [];
foreach ($lobby->players as $player) {
    if ($playerNumber > 4) throw new RuntimeException('FaBSim supports a maximum of four seats.');
    $player->setGamePlayerID($playerNumber);
    $isPassiveGoldfishSeat = isset($lobby->goldfishPlayers)
        && is_array($lobby->goldfishPlayers)
        && in_array($playerNumber, $lobby->goldfishPlayers, true)
        && trim((string)$player->getDeckLink()) === ''
        && trim((string)$player->getPreconstructedDeck()) === '';
    if (!in_array($player->getBotProfile(), ['', 'goldfish', 'fai', 'professor', 'ira'], true)) throw new RuntimeException('Unsupported FaB bot profile.');
    if ($isPassiveGoldfishSeat || $player->getBotProfile() === 'goldfish') {
        $passiveSeats[] = $playerNumber;
        FaBEnsureGoldfishOpponent($playerNumber);
        ++$playerNumber;
        continue;
    }
    $isDeckBot=in_array($player->getBotProfile(),['fai','professor','ira'],true);
    if($isDeckBot)$botProfiles[$playerNumber]=$player->getBotProfile();
    $resolved = $isDeckBot ? FaBBotDeck($player->getBotProfile()) : FaBResolveDeckInput($player->getDeckLink(), method_exists($player, 'getUserId') ? $player->getUserId() : null);
    if (empty($resolved['success'])) throw new RuntimeException($resolved['message'] ?? 'Unable to load FaB deck.');
    if (($lobby->format ?? '') === 'upf' && ($errors = FaBUPFDeckErrors($resolved))) throw new RuntimeException(implode(' ', $errors));
    FaBLoadPlayer($playerNumber, $resolved, $isDeckBot);
    ++$playerNumber;
}
if ($playerNumber <= 2) throw new RuntimeException('FaBSim requires at least two seats.');
$seatCount = min(4, $playerNumber - 1);
$seatList = implode('', range(1, $seatCount));
SetSeatOrder($seatList);
SetLiveSeats($seatList);

SetTurnPlayer(1);
SetTurnNumber(1);
SetCurrentPhase('SOT');
SetPhaseParameters('');
$initialState = FaBGetState();
$initialState['passiveSeats'] = $passiveSeats;
$initialState['botProfiles'] = $botProfiles;
$initialState['gameMode'] = ($lobby->format ?? '') === 'upf' ? 'UPF'
    : (empty($passiveSeats) ? strtoupper((string)($lobby->format ?? '')) : 'GOLDFISH');
FaBSetState($initialState);
// Run setup continuations only after every seat exists, before normal turn play.
DecisionQueueController::SuspendAutoAdvance();
try { foreach(FaBSeatOrder() as $setupSeat) (new DecisionQueueController())->ExecuteStaticMethods($setupSeat); }
finally { DecisionQueueController::ResumeAutoAdvance(); }
if (FaBIsPassiveSeat(1)) SetTurnPlayer(FaBNextInteractiveSeat(1));
StartOfTurnPhase();
SetCurrentPhase('MAIN');
SetWinner(0);
SaveUndoVersion(1, 'Start of game');
WriteGamestate(__DIR__ . '/');

$lobby->gameName = $gameName;
if (!SimGameWriteAuthKeysFromLobby('FaBSim', $gameName, $lobby)) throw new RuntimeException('Unable to store FaBSim authentication metadata.');

function FaBLoadPlayer($playerID, $resolved, bool $bot = false) {
    $heroObj = AddHero($playerID, CardID:$resolved['hero'], Owner:$playerID, Controller:$playerID, Status:2);
    foreach ($resolved['weapons'] as $cardID) {
        AddWeapons($playerID, CardID:$cardID, Owner:$playerID, Controller:$playerID, Status:2);
    }
    foreach ($resolved['equipment'] as $cardID) {
        AddEquipment($playerID, CardID:$cardID, Owner:$playerID, Controller:$playerID, Status:2);
    }
    foreach ($resolved['mainDeck'] as $cardID) AddDeck($playerID, CardID:$cardID);
    $deck = &GetDeck($playerID);
    EngineShuffle($deck, true);
    $health = &GetHealth($playerID);
    $resources = &GetResources($playerID);
    $actionPoints = &GetActionPoints($playerID);
    $health = max(1, intval(CardHealth($resolved['hero'])) ?: 20);
    $resources = 0;
    $actionPoints = 1;
    if($resolved['hero']==='fai'&&in_array('phoenix_flame_red',$resolved['mainDeck'],true)){
        if($bot)FaBFaiSetup($playerID,true);
        else {
            DecisionQueueController::AddDecision($playerID,'MZMODAL','1|1|Start_with_Phoenix_Flame_in_graveyard&Keep_it_in_deck',1,'Fai_setup');
            DecisionQueueController::AddDecision($playerID,'CUSTOM','FAB_FAI_SETUP',1);
        }
    }elseif(in_array($resolved['hero'],['dash','dash_inventor_extraordinaire'],true)){
        DecisionQueueController::AddDecision($playerID,'CUSTOM','FAB_ARC_SETUP',1);
    }else DoDrawCard($playerID, max(1, intval(CardIntelligence($resolved['hero'])) ?: 4));
}

?>
