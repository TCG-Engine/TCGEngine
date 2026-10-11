<?php

// Record a HellbreakSim integration fixture's actions WITHOUT the browser.
//
//   php DevTools/Hellbreak/record-fixture.php --slug=play-from-hand --choices="YES,PASS,PASS"
//   php DevTools/Hellbreak/record-fixture.php --slug=play-from-hand            # just show the options
//
// Replays the fixture's initial_gamestate.txt, then for each choice: asks the engine which actions
// are legal (TestAutomationBridge enumerate-legal-actions), picks the one the choice names, and
// applies it. The applied actions are written to the fixture's actions.json, so the pair
// make-fixture.php + record-fixture.php replaces recording a game by hand in the UI.
//
// A choice matches an enumerated action by cardID or buttonInput (case-insensitive), or by position
// as "#0", "#1". When a choice matches nothing, recording stops and the legal options are printed,
// which is also what happens after the last choice: it shows what could come next.
//
// Afterwards, snapshot the expected end state:
//   php DevTools/RunIntegrationTests.php --root=HellbreakSim --test=<slug> --update-snapshots

declare(strict_types=1);

$repoRoot = dirname(__DIR__, 2);
chdir($repoRoot);

$args = getopt('', ['slug::', 'choices::', 'help']);
if (isset($args['help']) || empty($args['slug'])) {
    echo "Usage: php DevTools/Hellbreak/record-fixture.php --slug=<name> [--choices=\"YES,PASS\"]\n";
    exit(isset($args['help']) ? 0 : 1);
}
$slug = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$args['slug']);
$fixtureDir = $repoRoot . '/Tests/Integration/HellbreakSim/' . $slug;
$initialPath = $fixtureDir . '/initial_gamestate.txt';
if (!is_file($initialPath)) {
    fwrite(STDERR, "No fixture at Tests/Integration/HellbreakSim/{$slug} — run make-fixture.php first.\n");
    exit(1);
}

$gameName = 'fixture_' . $slug;
$gameDir = $repoRoot . '/HellbreakSim/Games/' . $gameName;
if (!is_dir($gameDir) && !mkdir($gameDir, 0777, true) && !is_dir($gameDir)) {
    fwrite(STDERR, "Could not create {$gameDir}\n");
    exit(1);
}
copy($initialPath, $gameDir . '/Gamestate.txt');

function BridgeCall(string $command, array $extra = []): array
{
    global $repoRoot, $gameName;
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($repoRoot . '/DevTools/TestAutomationBridge.php')
        . ' --command=' . escapeshellarg($command)
        . ' --root=HellbreakSim --gameName=' . escapeshellarg($gameName);
    foreach ($extra as $key => $value) $cmd .= ' --' . $key . '=' . escapeshellarg($value);
    $output = shell_exec($cmd . ' 2>/dev/null');
    $decoded = json_decode(trim((string)$output), true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "Bridge call {$command} returned no JSON:\n" . $output . "\n");
        exit(1);
    }
    return $decoded;
}

// A modal is answered with an index ("0"), so pair each answer with the label it selects — both to
// print and to accept as a choice. Param format: min|max|Label_A&Label_B.
function ModalLabels(array $state): array
{
    if (strtoupper((string)($state['decisionType'] ?? '')) !== 'MZMODAL') return [];
    $parts = explode('|', (string)($state['decisionParam'] ?? ''), 3);
    if (!isset($parts[2])) return [];
    return array_values(array_filter(array_map('trim', explode('&', $parts[2])), fn($label) => $label !== ''));
}

function DescribeOptions(array $state): string
{
    $labels = ModalLabels($state);
    $lines = [];
    foreach (($state['actions'] ?? []) as $index => $action) {
        $answer = trim((string)($action['cardID'] ?? ''));
        if ($answer === '') $answer = trim((string)($action['buttonInput'] ?? ''));
        $named = ($labels && preg_match('/^\d+$/', $answer) && isset($labels[(int)$answer]))
            ? $labels[(int)$answer] . ' (answer ' . $answer . ')'
            : $answer;
        $lines[] = '  #' . $index . ' ' . ($named === '' ? '(no label)' : $named) . ' [mode ' . ($action['mode'] ?? '?') . ', P' . ($action['playerID'] ?? '?') . ']';
    }
    return $lines ? implode("\n", $lines) : '  (no legal actions)';
}

function StateHeading(array $state): string
{
    $kind = (string)($state['kind'] ?? 'unknown');
    if ($kind === 'decision') {
        return 'P' . ($state['playerID'] ?? '?') . ' ' . ($state['decisionType'] ?? '?')
            . ' — "' . ($state['decisionTooltip'] ?? $state['decisionParam'] ?? '') . '"';
    }
    return $kind . ' — P' . ($state['playerID'] ?? '?') . ' to act';
}

$choices = array_values(array_filter(array_map('trim', explode(',', (string)($args['choices'] ?? '')))));
$recorded = [];
foreach ($choices as $step => $choice) {
    $state = BridgeCall('enumerate-legal-actions');
    if (empty($state['success'])) {
        fwrite(STDERR, "Step {$step}: " . ($state['message'] ?? 'enumeration failed') . "\n");
        exit(1);
    }
    $actions = $state['actions'] ?? [];
    $picked = null;
    if (preg_match('/^#(\d+)$/', $choice, $match)) {
        $picked = $actions[(int)$match[1]] ?? null;
    } else {
        $labels = ModalLabels($state);
        foreach ($actions as $action) {
            $cardID = (string)($action['cardID'] ?? '');
            $button = (string)($action['buttonInput'] ?? '');
            $label = ($labels && preg_match('/^\d+$/', $cardID)) ? ($labels[(int)$cardID] ?? '') : '';
            if (strcasecmp($cardID, $choice) === 0 || strcasecmp($button, $choice) === 0
                || ($label !== '' && strcasecmp($label, $choice) === 0)) { $picked = $action; break; }
        }
    }
    if ($picked === null) {
        echo "Step {$step}: no legal action matches \"{$choice}\".\n";
        echo StateHeading($state) . "\n" . DescribeOptions($state) . "\n";
        exit(1);
    }
    $applied = BridgeCall('apply-engine-action', ['action' => base64_encode(json_encode($picked))]);
    if (empty($applied['success'])) {
        fwrite(STDERR, "Step {$step}: applying \"{$choice}\" failed: " . ($applied['message'] ?? '?') . "\n");
        exit(1);
    }
    $recorded[] = $picked;
    echo "Step {$step}: " . $choice . " — " . StateHeading($state) . "\n";
}

file_put_contents($fixtureDir . '/actions.json', json_encode($recorded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "\nRecorded " . count($recorded) . " action(s) to Tests/Integration/HellbreakSim/{$slug}/actions.json\n";

$state = BridgeCall('enumerate-legal-actions');
echo "\nNext: " . StateHeading($state) . "\n" . DescribeOptions($state) . "\n";

// Board summary, so "why is that action not offered?" is answerable without opening the gamestate.
require_once $repoRoot . '/HellbreakSim/GamestateParser.php';
require_once $repoRoot . '/HellbreakSim/ZoneAccessors.php';
require_once $repoRoot . '/HellbreakSim/ZoneClasses.php';
require_once $repoRoot . '/HellbreakSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once $repoRoot . '/HellbreakSim/Custom/GameLogic.php';
$GLOBALS['gameName'] = $gameName;
ParseGamestate($repoRoot . '/HellbreakSim/');
echo "\nBoard: round " . GetTurnNumber() . ' | phase ' . GetCurrentPhase()
    . ' | turn P' . GetTurnPlayer() . ' | initiative P' . GetInitiativePlayer() . "\n";
foreach ([1, 2] as $player) {
    $hand = [];
    foreach (HellbreakLiveZoneObjects(GetHand($player)) as $index => $card) {
        $cardID = (string)($card->CardID ?? '');
        $hand[] = 'myHand-' . $index . ' ' . $cardID . ' (' . CardName($cardID) . ', ' . CardCost($cardID) . ' blood)';
    }
    echo "P{$player}: blood " . BloodValue($player) . ', malice ' . MaliceValue($player)
        . ', health ' . HealthValue($player)
        . ', characters ' . count(HellbreakLiveZoneObjects(GetCharacters($player)))
        . "\n  hand: " . (count($hand) ? implode(', ', $hand) : '(empty)') . "\n";
}
