<?php

// Build a DETERMINISTIC HellbreakSim game and save it as an integration fixture's starting state.
//
//   php DevTools/Hellbreak/make-fixture.php --slug=play-from-hand [--deck=gama|fixture]
//
// Writes Tests/Integration/HellbreakSim/<slug>/{initial_gamestate.txt,actions.json,assertions.json,meta.json}
// and prints the phase and both hands, so the author knows what actions to record next. Then:
//
//   php DevTools/RunIntegrationTests.php --root=HellbreakSim --test=<slug> --update-snapshots
//
// Determinism is the whole point, so this deliberately does NOT do what CreateGame.php does:
// no EngineShuffle (deck order is the decklist order) and no random_int initiative (always P1).
//
// --auto lists the seats that answer their own prompts (default 2: the opponent). Seat 1 keeps its
// decisions so a test can drive them. Automating BOTH seats makes the game play itself: every
// prompt resolves, rounds roll forward, and the only thing left to answer is whatever trigger the
// monster raises each round — useless as a card fixture.

declare(strict_types=1);

$repoRoot = dirname(__DIR__, 2);
chdir($repoRoot);

$args = getopt('', ['slug::', 'deck::', 'auto::', 'help']);
if (isset($args['help']) || empty($args['slug'])) {
    echo "Usage: php DevTools/Hellbreak/make-fixture.php --slug=<name> [--deck=gama|fixture] [--auto=2|1,2|none]\n";
    exit(isset($args['help']) ? 0 : 1);
}
$autoArg = strtolower(trim((string)($args['auto'] ?? '2')));
$autoSeats = ($autoArg === 'none' || $autoArg === '')
    ? []
    : array_values(array_filter(array_map('intval', explode(',', $autoArg)), fn($seat) => in_array($seat, [1, 2], true)));
$slug = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$args['slug']);
if ($slug === '') { fwrite(STDERR, "A slug may only contain letters, numbers, dashes and underscores.\n"); exit(1); }
$deckKind = strtolower(trim((string)($args['deck'] ?? 'gama')));
if (!in_array($deckKind, ['gama', 'fixture'], true)) { fwrite(STDERR, "--deck must be gama or fixture.\n"); exit(1); }

require_once $repoRoot . '/HellbreakSim/GamestateParser.php';
require_once $repoRoot . '/HellbreakSim/ZoneAccessors.php';
require_once $repoRoot . '/HellbreakSim/ZoneClasses.php';
require_once $repoRoot . '/HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once $repoRoot . '/HellbreakSim/Fixtures/QuickStartFixtures.php';
require_once $repoRoot . '/HellbreakSim/Custom/DeckImport.php';
require_once $repoRoot . '/HellbreakSim/Custom/GameLogic.php';
require_once $repoRoot . '/HellbreakSim/TurnController.php';
require_once $repoRoot . '/Core/CoreZoneModifiers.php';

$gameName = 'fixture_' . $slug;
$gameDir = $repoRoot . '/HellbreakSim/Games/' . $gameName;
if (!is_dir($gameDir) && !mkdir($gameDir, 0777, true) && !is_dir($gameDir)) {
    fwrite(STDERR, "Could not create {$gameDir}\n");
    exit(1);
}

InitializeGamestate();
foreach ([1 => 'DRACULA', 2 => 'JAWS'] as $player => $archetype) {
    if ($deckKind === 'gama') HellbreakLoadGamaDemoPlayer($player, $archetype);
    else HellbreakLoadFixturePlayer($player, $archetype);
    HellbreakReindexZone($deck = &GetDeck($player));
    unset($deck);
}

SetTurnNumber(0);
SetFirstPlayer(1);
SetInitiativePlayer(1);
SetTurnPlayer(1);
SetCurrentPhase('SETUP_LOCATION');
SetPhaseParameters('-');
SetPreviousActionPassLike(false);
SetSlumberPlayer(0);
SetSlumberUsed(false);
SetActionSequence(0);
SetWinner(0);
SetFixtureMode(true);
DecisionQueueController::StoreVariable('HellbreakAutoSetupPlayers', $autoSeats);
DecisionQueueController::StoreVariable('HellbreakDeckPreset', $deckKind === 'gama' ? 'gama-demo' : 'engine-fixture');
HellbreakBeginSetup();
WriteGamestate($repoRoot . '/HellbreakSim/');

$fixtureDir = $repoRoot . '/Tests/Integration/HellbreakSim/' . $slug;
if (!is_dir($fixtureDir) && !mkdir($fixtureDir, 0777, true) && !is_dir($fixtureDir)) {
    fwrite(STDERR, "Could not create {$fixtureDir}\n");
    exit(1);
}
$gamestate = (string)file_get_contents($gameDir . '/Gamestate.txt');
file_put_contents($fixtureDir . '/initial_gamestate.txt', $gamestate);
if (!is_file($fixtureDir . '/actions.json')) file_put_contents($fixtureDir . '/actions.json', "[]\n");
if (!is_file($fixtureDir . '/assertions.json')) file_put_contents($fixtureDir . '/assertions.json', "[]\n");
file_put_contents($fixtureDir . '/meta.json', json_encode([
    'name' => $slug,
    'rootName' => 'HellbreakSim',
    'createdAt' => gmdate('c'),
    'createdBy' => 'DevTools/Hellbreak/make-fixture.php',
    'deck' => $deckKind === 'gama' ? 'GAMA demo decks (Dracula vs Jaws)' : 'engine fixture decks',
    'autoSeats' => $autoSeats,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "Fixture written: Tests/Integration/HellbreakSim/{$slug}\n";
echo "Phase: " . GetCurrentPhase() . " | initiative P" . GetInitiativePlayer() . " | turn P" . GetTurnPlayer() . "\n";
foreach ([1, 2] as $player) {
    $hand = [];
    foreach (HellbreakLiveZoneObjects(GetHand($player)) as $index => $card) {
        $cardID = (string)($card->CardID ?? '');
        $hand[] = 'myHand-' . $index . ' ' . $cardID . ' (' . CardName($cardID) . ')';
    }
    echo "P{$player} hand: " . (count($hand) ? implode(', ', $hand) : '(empty)') . "\n";
    echo "P{$player} blood/malice: " . BloodValue($player) . '/' . MaliceValue($player) . "\n";
}
